<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search');

        $categories = Category::when($search, function ($query, $search) {
            return $query->where('category_name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        })->paginate($perPage)->appends(['per_page' => $perPage, 'search' => $search]);

        if ($request->ajax()) {
            return view('categories.table', compact('categories'))->render();
        }

        return view('categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('categories.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'category_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:1,2',
        ]);

        Category::create($request->only(['category_name', 'description', 'status']));

        return response()->json(['success' => 'Category created successfully.']);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category)
    {
        return response()->json($category);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        $request->validate([
            'category_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:1,2',
        ]);

        $category->update($request->only(['category_name', 'description', 'status']));

        return response()->json(['success' => 'Category updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        $category->delete();

        return response()->json(['success' => 'Category deleted successfully']);
    }
}
