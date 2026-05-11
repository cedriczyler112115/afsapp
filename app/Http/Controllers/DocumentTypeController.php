<?php

namespace App\Http\Controllers;

use App\Models\DocumentType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DocumentTypeController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $search = trim((string) $request->input('search', ''));

        $types = DocumentType::query()
            ->when($search !== '', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->paginate($perPage)
            ->appends(['per_page' => $perPage, 'search' => $search]);

        if ($request->ajax()) {
            return view('document_types.table', compact('types'))->render();
        }

        return view('document_types.index', compact('types'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('document_types', 'name'),
            ],
            'is_active' => ['required', 'in:0,1'],
        ]);

        $type = DocumentType::create($validated);

        return response()->json([
            'success' => 'Document Type created successfully.',
            'data' => [
                'id' => $type->id,
                'name' => $type->name,
                'is_active' => $type->is_active ? 1 : 0,
            ],
        ]);
    }

    public function edit(DocumentType $documentType)
    {
        return response()->json($documentType);
    }

    public function update(Request $request, DocumentType $documentType)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('document_types', 'name')->ignore($documentType->id),
            ],
            'is_active' => ['required', 'in:0,1'],
        ]);

        $documentType->update($validated);

        return response()->json(['success' => 'Document Type updated successfully.']);
    }

    public function destroy(DocumentType $documentType)
    {
        $documentType->delete();

        return response()->json(['success' => 'Document Type deleted successfully.']);
    }
}
