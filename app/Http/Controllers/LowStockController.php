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

        $query = Item::with(['category', 'unit']);

        // Sorting Logic
        if ($sort === 'status') {
            // Status is derived from (current_quantity - reorder_level)
            // Critical (<0), Low (0), OK (>0)
            // Ascending: Critical -> Low -> OK
            $query->orderByRaw('(current_quantity - reorder_level) '.$direction);
        } elseif ($sort === 'shortage') {
            $query->orderByRaw('(current_quantity - reorder_level) '.$direction);
        } else {
            // Default column sorting
            // Ensure the column exists to avoid errors
            $allowedColumns = ['sku', 'item_name', 'current_quantity', 'reorder_level'];
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
