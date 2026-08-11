<?php

namespace App\Http\Controllers;

use App\Models\Borrowing;
use App\Models\Category;
use App\Models\Item;
use App\Models\ItemUnit;
use App\Models\StockTransaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BorrowingController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $status = $request->input('status');
        $borrowerId = $request->input('borrower_id');
        $search = $request->input('search');
        $categoryId = $request->input('category_id');
        $itemId = $request->input('item_id');

        $canProcess = $this->canProcess();
        $borrowings = Borrowing::with(['borrower', 'item', 'itemUnit', 'issuedBy', 'returns.receivedBy'])
            ->when(! $canProcess, fn ($q) => $q->where('borrower_id', Auth::id()))
            ->when($status, function ($q) use ($status) {
                return $q->where('status', $status);
            })
            ->when($borrowerId, function ($q) use ($borrowerId) {
                return $q->where('borrower_id', $borrowerId);
            })
            ->when($categoryId, function ($q) use ($categoryId) {
                return $q->whereHas('item', function ($sq) use ($categoryId) {
                    $sq->where('category_id', $categoryId);
                });
            })
            ->when($itemId, function ($q) use ($itemId) {
                return $q->where('item_id', $itemId);
            })
            ->when($search, function ($q) use ($search) {
                return $q->whereHas('item', function ($sq) use ($search) {
                    $sq->where('item_name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->appends([
                'status' => $status,
                'borrower_id' => $borrowerId,
                'search' => $search,
                'category_id' => $categoryId,
                'item_id' => $itemId,
                'per_page' => $perPage,
            ]);

        $users = User::all(); // For filter
        $categories = Category::orderBy('category_name')->get();
        $items = Item::orderBy('item_name')->get();

        if ($request->ajax()) {
            if ($request->has('get_items_by_category')) {
                $itemsQuery = Item::orderBy('item_name');
                if ($categoryId) {
                    $itemsQuery->where('category_id', $categoryId);
                }

                return response()->json($itemsQuery->get());
            }

            return view('borrowings.table', compact('borrowings', 'canProcess'))->render();
        }

        return view('borrowings.index', compact('borrowings', 'users', 'categories', 'items', 'canProcess'));
    }

    public function create()
    {
        $canProcess = $this->canProcess();
        $users = $canProcess ? User::orderBy('name')->get() : User::whereKey(Auth::id())->get();
        $items = Item::whereHas('itemUnits', fn ($q) => $q->where('status', 1))
            ->select('items.*')
            ->withCount(['itemUnits as available_units' => fn ($q) => $q->where('status', 1)])
            ->selectSub(function ($query) {
                $query->from('item_units')->whereColumn('item_units.item_id', 'items.item_id')
                    ->where('item_units.status', 1)
                    ->selectRaw('COALESCE(SUM(COALESCE(item_units.pcs_per_unit, items.pcs_per_unit, 1)), 0)');
            }, 'available_pieces')
            ->orderBy('item_name')->get();

        return view('borrowings.create', compact('users', 'items', 'canProcess'));
    }

    public function getAvailableUnits($item_id)
    {
        $units = ItemUnit::where('item_id', $item_id)
            ->where('status', 1) // 1 = Available
            ->select('id', 'serial', 'full_code', 'qr_code')
            ->get();

        return response()->json($units);
    }

    public function store(Request $request)
    {
        $request->validate([
            'borrower_id' => 'required|exists:users,id',
            'item_id' => 'required|exists:items,item_id',
            'quantity' => 'required|integer|min:1',
            'borrow_mode' => 'required|in:UNIT,PCS',
            'borrow_date' => 'required|date',
            'expected_return_date' => 'required|date|after:borrow_date',
            'purpose' => 'required|string|max:1000',
        ]);
        if (! $this->canProcess() && (int) $request->borrower_id !== (int) Auth::id()) {
            abort(403);
        }
        $requestItem = Item::findOrFail($request->item_id);
        $availableUnits = ItemUnit::where('item_id', $request->item_id)->where('status', 1)->get();
        $available = $request->borrow_mode === 'UNIT'
            ? $availableUnits->count()
            : (int) $availableUnits->sum(fn ($unit) => max(1, (int) ($unit->pcs_per_unit ?? $requestItem->pcs_per_unit ?? 1)));
        if ((int) $request->quantity > $available) {
            $label = $request->borrow_mode === 'UNIT' ? 'item unit(s)' : 'PC/S';
            return back()->withErrors(['quantity' => "Only {$available} {$label} are currently available."])->withInput();
        }

        Borrowing::create([
            'request_no' => 'BR-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
            'borrower_id' => $request->borrower_id,
            'item_id' => $request->item_id,
            'quantity' => $request->quantity,
            'borrow_mode' => $request->borrow_mode,
            'borrow_date' => $request->borrow_date,
            'expected_return_date' => $request->expected_return_date,
            'status' => 'REQUESTED',
            'purpose' => $request->purpose,
            'requested_at' => now(),
        ]);

        return redirect()->route('borrowings.index');
    }

    public function approve(Request $request, Borrowing $borrowing)
    {
        abort_unless($this->canProcess(), 403);
        $request->validate(['approval_notes' => 'nullable|string|max:1000']);
        DB::transaction(function () use ($borrowing, $request) {
            $record = Borrowing::whereKey($borrowing->id)->lockForUpdate()->firstOrFail();
            abort_unless($record->status === 'REQUESTED', 422, 'This request has already been processed.');
            $requestedQuantity = (int) $record->quantity;
            $originalPcsPerUnit = max(1, (int) (Item::where('item_id', $record->item_id)->value('pcs_per_unit') ?? 1));
            $units = ItemUnit::where('item_id', $record->item_id)->where('status', 1)->lockForUpdate()->get();
            $allocations = collect();
            if ($record->borrow_mode === 'UNIT') {
                abort_if($units->count() < $requestedQuantity, 422, 'There are not enough available item units to approve this request.');
                foreach ($units->take($requestedQuantity) as $unit) {
                    $pcs = max(1, (int) ($unit->pcs_per_unit ?? $originalPcsPerUnit));
                    $allocations->push(['unit' => $unit, 'quantity' => 1, 'pcs' => $pcs, 'before' => $pcs, 'after' => 0]);
                }
            } else {
                $remaining = $requestedQuantity;
                foreach ($units as $unit) {
                    $before = max(1, (int) ($unit->pcs_per_unit ?? $originalPcsPerUnit));
                    $take = min($remaining, $before);
                    if ($take > 0) $allocations->push(['unit' => $unit, 'quantity' => $take, 'pcs' => $take, 'before' => $before, 'after' => $before - $take]);
                    $remaining -= $take;
                    if ($remaining <= 0) break;
                }
                abort_if($remaining > 0, 422, 'There are not enough available PC/S to approve this request.');
            }

            $depletedUnits = 0;
            foreach ($allocations as $index => $allocation) {
                $unit = $allocation['unit'];
                $attributes = [
                    'request_no' => $record->request_no, 'borrower_id' => $record->borrower_id,
                    'item_id' => $record->item_id, 'item_unit_id' => $unit->id, 'quantity' => $allocation['quantity'],
                    'borrow_mode' => $record->borrow_mode, 'pcs_borrowed' => $allocation['pcs'],
                    'pcs_before' => $allocation['before'], 'pcs_after' => $allocation['after'],
                    'borrow_date' => $record->borrow_date, 'expected_return_date' => $record->expected_return_date,
                    'status' => 'RELEASED', 'purpose' => $record->purpose, 'issued_by' => Auth::id(),
                    'requested_at' => $record->requested_at, 'approved_at' => now(),
                    'approval_notes' => $request->approval_notes,
                ];
                if ($index === 0) $record->update($attributes); else Borrowing::create($attributes);
                $unit->pcs_per_unit = $allocation['after'];
                if ($allocation['after'] === 0) {
                    $unit->status = 3;
                    $depletedUnits++;
                }
                $unit->save();
                StockTransaction::create(['item_id' => $record->item_id, 'unit_id' => $unit->id, 'type' => 'BORROW', 'quantity' => $allocation['pcs'], 'pcs_before' => $allocation['before'], 'pcs_after' => $allocation['after'], 'date_created' => now(), 'created_by' => Auth::id()]);
            }
            if ($depletedUnits > 0) Item::where('item_id', $record->item_id)->decrement('current_quantity', $depletedUnits);
        });
        return back();
    }

    public function receive(Borrowing $borrowing)
    {
        abort_unless($borrowing->borrower_id === Auth::id(), 403);
        Borrowing::where('request_no', $borrowing->request_no)->where('status', 'RELEASED')
            ->update(['status' => 'BORROWED', 'received_at' => now()]);
        return back();
    }

    public function requestReturn(Borrowing $borrowing)
    {
        abort_unless($borrowing->borrower_id === Auth::id(), 403);
        Borrowing::where('request_no', $borrowing->request_no)->whereIn('status', ['BORROWED', 'OVERDUE'])
            ->update(['status' => 'RETURN_PENDING', 'return_requested_at' => now()]);
        return back();
    }

    public function confirmReturn(Request $request, Borrowing $borrowing)
    {
        abort_unless($this->canProcess(), 403);
        $request->validate(['return_category' => 'required|in:GOOD,DAMAGED,LOST', 'remarks' => 'nullable|string|max:1000']);
        DB::transaction(function () use ($borrowing, $request) {
            $records = Borrowing::where('request_no', $borrowing->request_no)->where('status', 'RETURN_PENDING')->lockForUpdate()->get();
            abort_if($records->isEmpty(), 422, 'No units are pending return.');
            $restoredUnits = 0;
            foreach ($records as $record) {
                $returnedQuantity = $record->borrow_mode === 'PCS' ? (int) $record->pcs_borrowed : 1;
                \App\Models\ItemReturn::create(['borrowing_id' => $record->id, 'item_id' => $record->item_id, 'item_unit_id' => $record->item_unit_id, 'quantity' => $returnedQuantity, 'return_date' => now(), 'return_category' => $request->return_category, 'remarks' => $request->remarks, 'received_by' => Auth::id()]);
                $unit = ItemUnit::whereKey($record->item_unit_id)->lockForUpdate()->firstOrFail();
                $piecesBeforeReturn = max(0, (int) ($unit->pcs_per_unit ?? 0));
                if ($request->return_category === 'GOOD') {
                    $wasUnavailable = (int) $unit->status !== 1;
                    $originalCapacity = max(1, (int) ($record->item()->value('pcs_per_unit') ?? $record->pcs_before ?? 1));
                    $unit->pcs_per_unit = min($originalCapacity, $piecesBeforeReturn + (int) ($record->pcs_borrowed ?? $record->pcs_before ?? 1));
                    $unit->status = 1;
                    if ($wasUnavailable) $restoredUnits++;
                } elseif ((int) $unit->status !== 1) {
                    $unit->status = $request->return_category === 'DAMAGED' ? 2 : 4;
                }
                $unit->save();
                $record->update(['status' => 'RETURNED', 'returned_at' => now()]);
                StockTransaction::create(['item_id' => $record->item_id, 'unit_id' => $record->item_unit_id, 'type' => 'RETURN', 'quantity' => (int) ($record->pcs_borrowed ?? 1), 'pcs_before' => $piecesBeforeReturn, 'pcs_after' => (int) $unit->pcs_per_unit, 'date_created' => now(), 'created_by' => Auth::id()]);
            }
            if ($restoredUnits > 0) Item::where('item_id', $records->first()->item_id)->increment('current_quantity', $restoredUnits);
        });
        return back();
    }

    public function cancel(Borrowing $borrowing)
    {
        abort_unless($borrowing->status === 'REQUESTED' && ($borrowing->borrower_id === Auth::id() || $this->canProcess()), 403);
        $borrowing->update(['status' => 'CANCELLED']);
        return back();
    }

    private function canProcess(): bool
    {
        return (int) Auth::user()->level_id === 1 || Auth::user()->hasSidebarAccess('stock-in.index');
    }
}
