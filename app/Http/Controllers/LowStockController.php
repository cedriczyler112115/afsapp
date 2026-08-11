<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;

class LowStockController extends Controller
{
    public function index(Request $request)
    {
        $sort = $request->get('sort', 'sku');
        $direction = $request->get('direction', 'asc');

        $query = Item::with(['category', 'unit'])
            ->select('items.*')
            ->selectSub(function ($subQuery) {
                $subQuery->from('item_units')
                    ->whereColumn('item_units.item_id', 'items.item_id')
                    ->where('item_units.status', 1)
                    ->selectRaw('COUNT(*)');
            }, 'available_units')
            ->selectSub(function ($subQuery) {
                $subQuery->from('item_units')
                    ->whereColumn('item_units.item_id', 'items.item_id')
                    ->where('item_units.status', 1)
                    ->selectRaw('COALESCE(SUM(COALESCE(item_units.pcs_per_unit, items.pcs_per_unit, 1)), 0)');
            }, 'available_pieces');

        // Sorting Logic
        if ($sort === 'status') {
            // Status is derived from the live available item-unit count minus reorder level.
            // Critical (<0), Low (0), OK (>0)
            // Ascending: Critical -> Low -> OK
            $query->orderByRaw('(available_units - reorder_level) '.$direction);
        } elseif ($sort === 'shortage') {
            $query->orderByRaw('(available_units - reorder_level) '.$direction);
        } else {
            // Default column sorting
            // Ensure the column exists to avoid errors
            $allowedColumns = ['sku', 'item_name', 'available_units', 'available_pieces', 'reorder_level'];
            if (in_array($sort, $allowedColumns)) {
                $query->orderBy($sort, $direction);
            } else {
                $query->orderBy('sku', 'asc');
            }
        }

        $items = $query->paginate(10);

        return view('low_stock.index', compact('items'));
    }
}
