<?php

namespace App\Http\Controllers;

use App\Models\DocumentSource;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DocumentSourceController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $search = trim((string) $request->input('search', ''));

        $sources = DocumentSource::query()
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($w) use ($search) {
                    $w->where('name', 'like', "%{$search}%")
                        ->orWhere('source_type', 'like', "%{$search}%");
                });
            })
            ->orderBy('source_type')
            ->orderBy('name')
            ->paginate($perPage)
            ->appends(['per_page' => $perPage, 'search' => $search]);

        if ($request->ajax()) {
            return view('document_sources.table', compact('sources'))->render();
        }

        return view('document_sources.index', compact('sources'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'source_type' => ['required', 'in:section,staff'],
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('document_sources', 'name')->where(fn ($q) => $q->where('source_type', $request->input('source_type'))),
            ],
            'is_active' => ['required', 'in:0,1'],
        ]);

        $source = DocumentSource::create($validated);

        return response()->json([
            'success' => 'Document Source created successfully.',
            'data' => [
                'id' => $source->id,
                'source_type' => $source->source_type,
                'name' => $source->name,
                'is_active' => $source->is_active ? 1 : 0,
            ],
        ]);
    }

    public function edit(DocumentSource $documentSource)
    {
        return response()->json($documentSource);
    }

    public function update(Request $request, DocumentSource $documentSource)
    {
        $validated = $request->validate([
            'source_type' => ['required', 'in:section,staff'],
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('document_sources', 'name')
                    ->ignore($documentSource->id)
                    ->where(fn ($q) => $q->where('source_type', $request->input('source_type'))),
            ],
            'is_active' => ['required', 'in:0,1'],
        ]);

        $documentSource->update($validated);

        return response()->json(['success' => 'Document Source updated successfully.']);
    }

    public function destroy(DocumentSource $documentSource)
    {
        $documentSource->delete();

        return response()->json(['success' => 'Document Source deleted successfully.']);
    }
}
