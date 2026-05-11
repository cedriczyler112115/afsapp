<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Item;
use App\Models\ItemUnit;
use App\Models\StockTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class StockInController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search');
        $categoryId = $request->input('category_id');
        $itemId = $request->input('item_id');

        $query = DB::table('items')
            ->leftJoin('item_units', 'item_units.item_id', '=', 'items.item_id')
            ->leftJoin('categories', 'items.category_id', '=', 'categories.category_id')
            ->select(
                'items.item_id',
                'items.item_name',
                'categories.category_name',
                'items.sku',
                'items.category_id',
                'items.unit_id as uom_id', // Unit of measure
                DB::raw('SUM(CASE WHEN item_units.status = 1 THEN 1 ELSE 0 END) as current_quantity'),
                'items.reorder_level',
                'items.description as item_description',
                'items.create_by as item_created_by',
                'items.date_created as item_date_created'
            );

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('items.item_name', 'like', "%{$search}%")
                    ->orWhere('items.sku', 'like', "%{$search}%")
                    ->orWhere('items.description', 'like', "%{$search}%");
            });
        }

        if ($categoryId) {
            $query->where('items.category_id', $categoryId);
        }

        if ($itemId) {
            $query->where('items.item_id', $itemId);
        }

        // Calculate Overall Total Quantity
        $totalQuery = DB::table('item_units')
            ->join('items', 'item_units.item_id', '=', 'items.item_id')
            ->where('item_units.status', 1);

        if ($search) {
            $totalQuery->where(function ($q) use ($search) {
                $q->where('items.item_name', 'like', "%{$search}%")
                    ->orWhere('items.sku', 'like', "%{$search}%")
                    ->orWhere('items.description', 'like', "%{$search}%");
            });
        }

        if ($categoryId) {
            $totalQuery->where('items.category_id', $categoryId);
        }

        if ($itemId) {
            $totalQuery->where('items.item_id', $itemId);
        }

        $overallTotalQuantity = $totalQuery->count();

        $stockTransactions = $query->groupBy('items.item_id')
            ->orderBy('items.create_by', 'desc')
            ->paginate($perPage)
            ->appends([
                'per_page' => $perPage,
                'search' => $search,
                'category_id' => $categoryId,
                'item_id' => $itemId,
            ]);

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

            return view('stock_in.table', compact('stockTransactions', 'overallTotalQuantity'))->render();
        }

        return view('stock_in.index', compact('stockTransactions', 'items', 'categories', 'overallTotalQuantity'));
    }

    public function create()
    {
        // Get IDs of items that already exist in item_units
        $existingItemIds = ItemUnit::distinct()->pluck('item_id');

        // Filter items to exclude those IDs
        $items = Item::whereNotIn('item_id', $existingItemIds)->get();

        return view('stock_in.create', compact('items'));
    }

    public function edit(Request $request, $item_id)
    {
        $item = Item::findOrFail($item_id);

        $perPage = $request->input('per_page', 10);
        $dateFilter = $request->input('date_filter');

        $query = DB::table('stock_transactions')
            ->join('items', 'stock_transactions.item_id', '=', 'items.item_id')
            ->join('item_units', 'stock_transactions.unit_id', '=', 'item_units.id')
            ->select(
                'stock_transactions.id as transaction_id',
                'stock_transactions.date_created as transaction_date',
                'item_units.serial',
                'item_units.full_code',
                'item_units.qr_code',
                'item_units.is_printed',
                'item_units.id as unit_id'
            )
            ->where('item_units.status', 1)
            ->where('stock_transactions.item_id', $item_id);

        if ($dateFilter) {
            $query->whereDate('stock_transactions.date_created', $dateFilter);
        }

        $query->orderBy('stock_transactions.date_created', 'desc');

        if ($perPage === 'all') {
            $stockTransactions = $query->paginate(999999)->appends(['per_page' => 'all', 'date_filter' => $dateFilter]);
        } else {
            $stockTransactions = $query->paginate($perPage)->appends(['per_page' => $perPage, 'date_filter' => $dateFilter]);
        }

        return view('stock_in.edit', compact('item', 'stockTransactions'));
    }

    public function markAsPrinted(Request $request)
    {
        $request->validate([
            'unit_ids' => 'required|array',
            'unit_ids.*' => 'exists:item_units,id',
        ]);

        try {
            ItemUnit::whereIn('id', $request->unit_ids)->update(['is_printed' => 1]);

            return response()->json(['success' => true, 'message' => 'Units tagged as printed successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: '.$e->getMessage()], 500);
        }
    }

    public function destroy($item_id)
    {
        DB::beginTransaction();
        try {
            // Find all 'IN' transactions for this item
            $transactions = StockTransaction::where('item_id', $item_id)
                ->where('type', 'IN')
                ->get();

            if ($transactions->isEmpty()) {
                return redirect()->back()->with('error', 'No stock-in history found for this item.');
            }

            // Collect unit IDs to delete from item_units
            $unitIds = $transactions->pluck('unit_id')->toArray();

            // Delete transactions
            StockTransaction::whereIn('id', $transactions->pluck('id'))->delete();

            // Delete item_units
            ItemUnit::whereIn('id', $unitIds)->delete();

            // Update item quantity (decrement by count)
            $count = count($unitIds);
            $item = Item::find($item_id);
            if ($item) {
                $item->decrement('current_quantity', $count);
            }

            DB::commit();

            return redirect()->route('stock-in.index')->with('success', 'Stock-in history for this item has been deleted successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Failed to delete stock-in history: '.$e->getMessage());
        }
    }

    public function destroyTransaction($id)
    {
        DB::beginTransaction();
        try {
            $transaction = StockTransaction::findOrFail($id);

            // Ensure it's a Stock IN
            if ($transaction->type !== 'IN') {
                return redirect()->back()->with('error', 'Invalid transaction type.');
            }

            $unitId = $transaction->unit_id;
            $itemId = $transaction->item_id;

            // Delete transaction
            $transaction->delete();

            // Delete item_unit
            if ($unitId) {
                ItemUnit::destroy($unitId);
            }

            // Update item quantity
            $item = Item::find($itemId);
            if ($item) {
                $item->decrement('current_quantity', 1);
            }

            DB::commit();

            return redirect()->back()->with('success', 'Stock-in transaction deleted successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Failed to delete transaction: '.$e->getMessage());
        }
    }

    public function storeTransaction(Request $request, $item_id)
    {
        $request->validate([
            'serial' => 'nullable|string|unique:item_units,serial|required_without_all:qr_code',
            'qr_code' => 'nullable|string|unique:item_units,qr_code|required_without_all:serial',
        ], [
            'serial.required_without_all' => 'At least one of Serial or QR Code is required.',
            'qr_code.required_without_all' => 'At least one of Serial or QR Code is required.',
        ]);

        DB::beginTransaction();
        try {
            $item = Item::findOrFail($item_id);

            $itemUnit = ItemUnit::create([
                'item_id' => $item_id,
                'serial' => $request->input('serial'),
                'qr_code' => $request->input('qr_code'),
                'status' => 1,
                'date_created' => now(),
                'created_by' => Auth::id() ?? 1,
            ]);

            // Generate Full Code: sku-item_id-unit_id
            $fullCode = $item->sku.'-'.$item->item_id.'-'.$itemUnit->id;
            $itemUnit->update(['full_code' => $fullCode]);

            StockTransaction::create([
                'item_id' => $item_id,
                'unit_id' => $itemUnit->id,
                'type' => 'IN',
                'date_created' => now(),
                'created_by' => Auth::id() ?? 1,
            ]);

            $item->increment('current_quantity', 1);

            DB::commit();

            return redirect()->back()->with('success', 'Individual stock unit added successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Failed to add stock unit: '.$e->getMessage());
        }
    }

    public function bulkStore(Request $request, $item_id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1|max:1000',
        ]);

        DB::beginTransaction();
        try {
            $item = Item::findOrFail($item_id);
            $quantity = $request->input('quantity');
            $createdCount = 0;

            for ($i = 0; $i < $quantity; $i++) {
                // 1. Create ItemUnit (without full_code initially)
                $itemUnit = ItemUnit::create([
                    'item_id' => $item_id,
                    'status' => 1,
                    'date_created' => now(),
                    'created_by' => Auth::id() ?? 1,
                ]);

                // 2. Generate and Update full_code
                // Format: sku_code-item_id-unit_id
                $fullCode = $item->sku.'-'.$item->item_id.'-'.$itemUnit->id;
                $itemUnit->update(['full_code' => $fullCode]);

                // 3. Create Transaction
                StockTransaction::create([
                    'item_id' => $item_id,
                    'unit_id' => $itemUnit->id,
                    'type' => 'IN',
                    'date_created' => now(),
                    'created_by' => Auth::id() ?? 1,
                ]);

                $createdCount++;
            }

            // 4. Update Item Quantity
            $item->increment('current_quantity', $createdCount);

            DB::commit();

            return response()->json(['success' => true, 'message' => "$createdCount units added successfully."]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'message' => 'Failed to bulk add units: '.$e->getMessage()], 500);
        }
    }

    public function getItemDetails($id)
    {
        $item = Item::with(['category', 'unit'])->find($id);
        if ($item) {
            return response()->json([
                'item_name' => $item->item_name,
                'sku' => $item->sku,
                'category' => $item->category ? $item->category->category_name : '',
                'unit' => $item->unit ? $item->unit->unit_name : '',
                'current_quantity' => $item->current_quantity,
                'description' => $item->description,
            ]);
        }

        return response()->json(['error' => 'Item not found'], 404);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_id' => 'required|exists:items,item_id',
            'units' => 'required|array|min:1',
            'units.*.serial' => 'nullable|string|distinct|unique:item_units,serial',
            'units.*.full_code' => 'nullable|string|distinct|unique:item_units,full_code',
            'units.*.qr_code' => 'nullable|string|distinct|unique:item_units,qr_code',
        ]);

        $validator->after(function ($validator) use ($request) {
            if (is_array($request->units)) {
                foreach ($request->units as $idx => $unitData) {
                    $serial = trim($unitData['serial'] ?? '');
                    $full = trim($unitData['full_code'] ?? '');
                    $qr = trim($unitData['qr_code'] ?? '');
                    if ($serial === '' && $full === '' && $qr === '') {
                        $validator->errors()->add("units.$idx", 'Row '.($idx + 1).' must have at least one of Serial, Full Code, or QR Code.');
                    }
                }
            }
        });

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();

        try {
            $item = Item::find($request->item_id);
            $count = 0;

            foreach ($request->units as $unitData) {
                // 1. Create Item Unit
                $itemUnit = ItemUnit::create([
                    'item_id' => $request->item_id,
                    'serial' => $unitData['serial'] ?? null,
                    'full_code' => $unitData['full_code'] ?? null,
                    'qr_code' => $unitData['qr_code'] ?? null,
                    'status' => 1, // Active
                    'date_created' => now(),
                    'created_by' => Auth::id() ?? 1,
                ]);

                // 2. Create Stock Transaction
                StockTransaction::create([
                    'item_id' => $request->item_id,
                    'unit_id' => $itemUnit->id,
                    'type' => 'IN',
                    'date_created' => now(),
                    'created_by' => Auth::id() ?? 1,
                ]);

                $count++;
            }

            // 3. Update Item Quantity
            $item->increment('current_quantity', $count);

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Stock added successfully!']);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'message' => 'Error adding stock: '.$e->getMessage()], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        // We only allow updating Unit details, not the Item itself (complex logic change)
        $request->validate([
            'serial' => 'nullable|string', // Unique validation skipped for simplicity or need ignore
            'full_code' => 'nullable|string',
            'qr_code' => 'nullable|string',
        ]);

        $transaction = StockTransaction::findOrFail($id);
        $unit = ItemUnit::findOrFail($transaction->unit_id);

        $unit->update([
            'serial' => $request->serial,
            'full_code' => $request->full_code,
            'qr_code' => $request->qr_code,
        ]);

        return response()->json(['success' => 'Stock details updated successfully.']);
    }

    /**
     * Remove the specified resource from storage.
     */
}
