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

        $borrowings = Borrowing::with(['borrower', 'item', 'itemUnit', 'issuedBy', 'returns.receivedBy'])
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

            return view('borrowings.table', compact('borrowings'))->render();
        }

        return view('borrowings.index', compact('borrowings', 'users', 'categories', 'items'));
    }

    public function create()
    {
        $users = User::all();
        $items = Item::where('current_quantity', '>', 0)->get();

        return view('borrowings.create', compact('users', 'items'));
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
            'borrow_date' => 'required|date',
            'expected_return_date' => 'required|date|after:borrow_date',
            'item_unit_ids' => 'nullable|array',
            'item_unit_ids.*' => 'exists:item_units,id',
        ]);

        DB::beginTransaction();

        try {
            $item = Item::findOrFail($request->item_id);

            // If specific units are selected
            if ($request->has('item_unit_ids') && is_array($request->item_unit_ids) && count($request->item_unit_ids) > 0) {

                $count = count($request->item_unit_ids);

                // Check if enough stock (redundant if we trust units count, but safe)
                if ($item->current_quantity < $count) {
                    throw new \Exception('Insufficient stock available.');
                }

                foreach ($request->item_unit_ids as $unitId) {
                    $unit = ItemUnit::lockForUpdate()->find($unitId);

                    if (! $unit || $unit->status != 1) {
                        throw new \Exception("Unit with ID {$unitId} is not available.");
                    }

                    // Update unit status to Borrowed (2)
                    $unit->status = 2;
                    $unit->save();

                    // Create Borrowing Record for this unit
                    Borrowing::create([
                        'borrower_id' => $request->borrower_id,
                        'item_id' => $request->item_id,
                        'item_unit_id' => $unit->id,
                        'quantity' => 1,
                        'borrow_date' => $request->borrow_date,
                        'expected_return_date' => $request->expected_return_date,
                        'status' => 'BORROWED',
                        'purpose' => $request->purpose,
                        'issued_by' => Auth::id(),
                    ]);

                    // Log Transaction
                    StockTransaction::create([
                        'item_id' => $request->item_id,
                        'unit_id' => $unit->id,
                        'type' => 'BORROW',
                        'date_created' => now(),
                        'created_by' => Auth::id(),
                    ]);
                }

                // Deduct Stock
                $item->decrement('current_quantity', $count);

            } else {
                // Generic Borrowing (No units selected)
                if ($item->current_quantity < $request->quantity) {
                    throw new \Exception('Insufficient stock available.');
                }

                Borrowing::create([
                    'borrower_id' => $request->borrower_id,
                    'item_id' => $request->item_id,
                    'item_unit_id' => null,
                    'quantity' => $request->quantity,
                    'borrow_date' => $request->borrow_date,
                    'expected_return_date' => $request->expected_return_date,
                    'status' => 'BORROWED',
                    'purpose' => $request->purpose,
                    'issued_by' => Auth::id(),
                ]);

                // Deduct Stock
                $item->decrement('current_quantity', $request->quantity);

                // Log Transaction
                StockTransaction::create([
                    'item_id' => $request->item_id,
                    'unit_id' => 0,
                    'type' => 'BORROW',
                    'date_created' => now(),
                    'created_by' => Auth::id(),
                ]);
            }

            DB::commit();

            return redirect()->route('borrowings.index')->with('success', 'Item(s) borrowed successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'An error occurred: '.$e->getMessage()])->withInput();
        }
    }
}
