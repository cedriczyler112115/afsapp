<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Item;
use App\Models\UnitOfMeasure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search');

        $items = Item::with(['category', 'unit'])
            ->when($search, function ($query, $search) {
                return $query->where('item_name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })
            ->orderBy('item_id', 'desc')
            ->paginate($perPage)
            ->appends(['per_page' => $perPage, 'search' => $search]);

        if ($request->ajax()) {
            return view('items.table', compact('items'))->render();
        }

        $categories = Category::where('status', 1)->get(); // Active categories
        $units = UnitOfMeasure::where('is_active', 1)->get(); // Active units

        return view('items.index', compact('items', 'categories', 'units'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'item_name' => 'required|string|max:150',
            'sku' => 'required|string|max:50|unique:items,sku',
            'category_id' => 'required|integer',
            'unit_id' => 'required|integer',
            'reorder_level' => 'required|integer',
            'description' => 'nullable|string',
        ]);

        $data = $request->only([
            'item_name', 'sku', 'category_id', 'unit_id', 'reorder_level', 'description',
        ]);

        $data['create_by'] = Auth::id() ?? 1; // Default to 1 if no auth for now
        $data['date_created'] = now();
        $data['current_quantity'] = 0;
        $data['is_status'] = 0;

        Item::create($data);

        return response()->json(['success' => 'Item created successfully.']);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $item = Item::findOrFail($id);

        return response()->json($item);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'item_name' => 'required|string|max:150',
            'category_id' => 'required|integer',
            'sku' => 'required|string|max:50|unique:items,sku,'.$id.',item_id',
            'unit_id' => 'required|integer',
            'reorder_level' => 'required|integer',
            'description' => 'nullable|string',
        ]);

        $item = Item::findOrFail($id);

        $data = $request->only([
            'item_name', 'sku', 'category_id', 'unit_id', 'reorder_level', 'description',
        ]);

        $item->update($data);

        return response()->json(['success' => 'Item updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        Item::destroy($id);

        return response()->json(['success' => 'Item deleted successfully']);
    }
}
