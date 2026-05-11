<?php

namespace App\Http\Controllers;

use App\Models\UnitOfMeasure;
use Illuminate\Http\Request;

class UnitOfMeasureController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search');

        $units = UnitOfMeasure::when($search, function ($query, $search) {
            return $query->where('unit_name', 'like', "%{$search}%")
                ->orWhere('unit_code', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        })->paginate($perPage)->appends(['per_page' => $perPage, 'search' => $search]);

        if ($request->ajax()) {
            return view('unit_of_measures.table', compact('units'))->render();
        }

        return view('unit_of_measures.index', compact('units'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('unit_of_measures.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'unit_name' => 'required|string|max:50',
            'unit_code' => 'required|string|max:10',
            'unit_type' => 'required|string|max:30',
            'description' => 'nullable|string',
            'is_active' => 'required|in:0,1',
        ]);

        UnitOfMeasure::create($request->only(['unit_name', 'unit_code', 'unit_type', 'description', 'is_active']));

        return response()->json(['success' => 'Unit of Measure created successfully.']);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(UnitOfMeasure $unitOfMeasure)
    {
        return response()->json($unitOfMeasure);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, UnitOfMeasure $unitOfMeasure)
    {
        $request->validate([
            'unit_name' => 'required|string|max:50',
            'unit_code' => 'required|string|max:10',
            'unit_type' => 'required|string|max:30',
            'description' => 'nullable|string',
            'is_active' => 'required|in:0,1',
        ]);

        $unitOfMeasure->update($request->only(['unit_name', 'unit_code', 'unit_type', 'description', 'is_active']));

        return response()->json(['success' => 'Unit of Measure updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UnitOfMeasure $unitOfMeasure)
    {
        $unitOfMeasure->delete();

        return response()->json(['success' => 'Unit of Measure deleted successfully']);
    }
}
