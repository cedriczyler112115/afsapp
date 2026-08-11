<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Issuance;
use App\Models\SupplyRequest;
use App\Models\SupplyRequestItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SupplyRequestController extends Controller
{
    public function index(Request $request)
    {
        $canProcess = $this->canProcess();
        $query = SupplyRequest::with(['requester', 'items.item'])->latest();

        if (! $canProcess) {
            $query->where('requester_id', Auth::id());
        }
        if ($request->filled('status')) {
            if ($request->status === 'READY') {
                $query->whereIn('status', ['APPROVED', 'PARTIALLY_ISSUED']);
            } else {
                $query->where('status', $request->status);
            }
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('request_no', 'like', "%{$search}%")
                    ->orWhereHas('requester', fn ($u) => $u->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('items.item', fn ($i) => $i->where('item_name', 'like', "%{$search}%"));
            });
        }

        $perPageInput = strtoupper((string) $request->input('per_page', '10'));
        $allowedPerPage = ['10', '20', '50', '100', 'ALL'];
        if (! in_array($perPageInput, $allowedPerPage, true)) {
            $perPageInput = '10';
        }
        $perPage = $perPageInput === 'ALL' ? max(1, (clone $query)->count()) : (int) $perPageInput;
        $requests = $query->paginate($perPage)->withQueryString();

        return view('supply_requests.index', compact('requests', 'canProcess'));
    }

    public function create()
    {
        $items = $this->availableItems()->get();
        $itemOptions = $items->map(function ($item) {
            return [
                'id' => $item->item_id,
                'text' => $item->item_name.' ('.$item->sku.')',
                'pieces' => (int) $item->available_pieces,
                'boxes' => (int) $item->available_boxes,
            ];
        })->values()->all();

        return view('supply_requests.create', compact('items', 'itemOptions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'purpose' => 'required|string|max:2000',
            'needed_at' => 'nullable|date|after_or_equal:today',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|distinct|exists:items,item_id',
            'items.*.issue_mode' => 'required|in:BOX,PCS',
            'items.*.quantity' => 'required|integer|min:1|max:999999',
            'items.*.notes' => 'nullable|string|max:500',
        ]);

        $requestedLines = collect($validated['items']);
        $availableItems = $this->availableItems()
            ->whereIn('items.item_id', $requestedLines->pluck('item_id'))
            ->get()
            ->keyBy('item_id');
        $availabilityErrors = [];
        foreach ($requestedLines as $index => $line) {
            $available = $availableItems->get((int) $line['item_id']);
            if (! $available) {
                $availabilityErrors["items.{$index}.item_id"] = 'The selected item no longer has issuable stock.';
            } else {
                $limit = $line['issue_mode'] === 'BOX' ? (int) $available->available_boxes : (int) $available->available_pieces;
                if ((int) $line['quantity'] > $limit) {
                    $unitLabel = $line['issue_mode'] === 'BOX' ? 'box(es)' : 'PC/S';
                    $availabilityErrors["items.{$index}.quantity"] = "Requested quantity cannot exceed the available {$limit} {$unitLabel} for {$available->item_name}.";
                }
            }
        }
        if ($availabilityErrors) {
            throw ValidationException::withMessages($availabilityErrors);
        }

        $supplyRequest = DB::transaction(function () use ($validated) {
            $record = SupplyRequest::create([
                'request_no' => 'SR-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
                'requester_id' => Auth::id(),
                'purpose' => $validated['purpose'],
                'needed_at' => $validated['needed_at'] ?? null,
                'status' => 'PENDING',
            ]);

            foreach ($validated['items'] as $line) {
                $record->items()->create([
                    'item_id' => $line['item_id'],
                    'issue_mode' => $line['issue_mode'],
                    'requested_quantity' => $line['quantity'],
                    'notes' => $line['notes'] ?? null,
                ]);
            }

            return $record;
        });

        return redirect()->route('supply-requests.show', $supplyRequest)->with('status', 'Supply request submitted.');
    }

    public function show(SupplyRequest $supplyRequest)
    {
        $this->authorizeView($supplyRequest);
        $supplyRequest->load(['requester', 'reviewer', 'items.item.unit', 'items.issuances.stockTransactions', 'items.issuances.receivedByUser']);
        $canProcess = $this->canProcess();

        return view('supply_requests.show', compact('supplyRequest', 'canProcess'));
    }

    public function approve(Request $request, SupplyRequest $supplyRequest)
    {
        abort_unless($this->canProcess(), 403);
        abort_unless($supplyRequest->status === 'PENDING', 422, 'Only pending requests can be approved.');

        $validated = $request->validate([
            'approved' => 'required|array',
            'approved.*' => 'required|integer|min:0',
            'review_notes' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($supplyRequest, $validated) {
            $locked = SupplyRequest::whereKey($supplyRequest->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === 'PENDING', 422, 'This request has already been reviewed.');
            $hasApprovedItem = false;

            foreach ($locked->items()->lockForUpdate()->get() as $item) {
                $quantity = (int) ($validated['approved'][$item->id] ?? 0);
                if ($quantity > $item->requested_quantity) {
                    abort(422, 'Approved quantity cannot exceed the requested quantity.');
                }
                $item->update(['approved_quantity' => $quantity]);
                $hasApprovedItem = $hasApprovedItem || $quantity > 0;
            }

            abort_unless($hasApprovedItem, 422, 'Approve at least one item quantity.');
            $locked->update([
                'status' => 'APPROVED',
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
                'review_notes' => $validated['review_notes'] ?? null,
            ]);
        });

        return back()->with('status', 'Supply request approved and ready for issuance.');
    }

    public function reject(Request $request, SupplyRequest $supplyRequest)
    {
        abort_unless($this->canProcess(), 403);
        abort_unless($supplyRequest->status === 'PENDING', 422, 'Only pending requests can be rejected.');
        $validated = $request->validate(['review_notes' => 'required|string|max:1000']);
        $supplyRequest->update([
            'status' => 'REJECTED',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'review_notes' => $validated['review_notes'],
        ]);

        return back()->with('status', 'Supply request rejected.');
    }

    public function cancel(SupplyRequest $supplyRequest)
    {
        abort_unless($supplyRequest->requester_id === Auth::id(), 403);
        abort_unless($supplyRequest->status === 'PENDING', 422, 'Only pending requests can be cancelled.');
        $supplyRequest->update(['status' => 'CANCELLED']);

        return redirect()->route('supply-requests.index')->with('status', 'Supply request cancelled.');
    }

    public function receive(Request $request, SupplyRequest $supplyRequest, Issuance $issuance)
    {
        abort_unless($supplyRequest->requester_id === Auth::id(), 403, 'Only the requesting user can confirm receipt.');
        $validated = $request->validate([
            'receipt_notes' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($supplyRequest, $issuance, $validated) {
            $lockedIssuance = Issuance::with('stockTransactions')
                ->whereKey($issuance->id)
                ->lockForUpdate()
                ->firstOrFail();
            $requestItem = SupplyRequestItem::whereKey($lockedIssuance->supply_request_item_id)
                ->where('supply_request_id', $supplyRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_if($lockedIssuance->received_at, 422, 'This issuance has already been received.');

            $receivedQuantity = $requestItem->issue_mode === 'BOX'
                ? $lockedIssuance->stockTransactions->count()
                : (int) $lockedIssuance->stockTransactions->sum('quantity');

            abort_if($receivedQuantity < 1, 422, 'This issuance has no issued quantity to receive.');

            $lockedIssuance->update([
                'received_at' => now(),
                'received_by' => Auth::id(),
                'receipt_notes' => $validated['receipt_notes'] ?? null,
            ]);
            $requestItem->update([
                'received_quantity' => min((int) $requestItem->issued_quantity, (int) $requestItem->received_quantity + $receivedQuantity),
            ]);

            $requestRecord = SupplyRequest::with('items')->whereKey($supplyRequest->id)->lockForUpdate()->firstOrFail();
            $totalIssued = (int) $requestRecord->items->sum('issued_quantity');
            $totalReceived = (int) $requestRecord->items->sum('received_quantity');
            $fullyReceived = $requestRecord->status === 'COMPLETED'
                && $totalIssued > 0
                && $requestRecord->items->every(fn ($item) => (int) $item->received_quantity >= (int) $item->issued_quantity);

            $requestRecord->update([
                'receipt_status' => $fullyReceived ? 'RECEIVED' : ($totalReceived > 0 ? 'PARTIALLY_RECEIVED' : 'NOT_RECEIVED'),
            ]);
        });

        return redirect()->route('supply-requests.show', $supplyRequest);
    }

    private function canProcess(): bool
    {
        $user = Auth::user();

        return (int) $user->level_id === 1 || $user->hasSidebarAccess('stock-out.index');
    }

    private function authorizeView(SupplyRequest $supplyRequest): void
    {
        abort_unless($supplyRequest->requester_id === Auth::id() || $this->canProcess(), 403);
    }

    private function availableItems()
    {
        return Item::query()
            ->select('items.*')
            ->selectSub(function ($query) {
                $query->from('item_units')
                    ->whereColumn('item_units.item_id', 'items.item_id')
                    ->where('item_units.status', 1)
                    ->where('item_units.is_printed', 1)
                    ->where(function ($units) {
                        $units->whereNull('item_units.pcs_per_unit')->orWhere('item_units.pcs_per_unit', '>', 0);
                    })
                    ->selectRaw('COALESCE(SUM(COALESCE(item_units.pcs_per_unit, items.pcs_per_unit, 1)), 0)');
            }, 'available_pieces')
            ->selectSub(function ($query) {
                $query->from('item_units')
                    ->whereColumn('item_units.item_id', 'items.item_id')
                    ->where('item_units.status', 1)
                    ->where('item_units.is_printed', 1)
                    ->whereRaw('COALESCE(item_units.pcs_per_unit, items.pcs_per_unit, 1) = COALESCE(items.pcs_per_unit, 1)')
                    ->selectRaw('COUNT(*)');
            }, 'available_boxes')
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('item_units')
                    ->whereColumn('item_units.item_id', 'items.item_id')
                    ->where('item_units.status', 1)
                    ->where('item_units.is_printed', 1)
                    ->where(function ($units) {
                        $units->whereNull('item_units.pcs_per_unit')->orWhere('item_units.pcs_per_unit', '>', 0);
                    });
            })
            ->orderBy('items.item_name');
    }
}
