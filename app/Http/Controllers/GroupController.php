<?php

namespace App\Http\Controllers;

use App\Models\Group;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GroupController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10);
        $search = trim((string) $request->input('search', ''));
        $status = $request->input('status');

        $groups = Group::query()
            ->when($search !== '', fn ($q) => $q->where('group_name', 'like', "%{$search}%"))
            ->when($status !== null && $status !== '', fn ($q) => $q->where('status', (int) $status))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->appends([
                'per_page' => $perPage,
                'search' => $search,
                'status' => $status,
            ]);

        return view('groups.index', compact('groups'));
    }

    public function create()
    {
        return view('groups.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'group_name' => ['required', 'string', 'max:50'],
            'status' => ['required', 'integer', 'in:0,1'],
        ]);

        $expectsJson = $request->ajax() || $request->expectsJson();

        try {
            $group = Group::create([
                'group_name' => $validated['group_name'],
                'status' => (int) $validated['status'],
                'created_by' => Auth::id(),
                'date_created' => now(),
            ]);
        } catch (QueryException|\PDOException $e) {
            if ($expectsJson) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to create group due to a database error. Please try again.',
                ], 500);
            }

            return back()
                ->withErrors(['database' => 'Unable to create group due to a database error. Please try again.'])
                ->withInput();
        }

        if ($expectsJson) {
            return response()->json([
                'success' => 'Group created successfully.',
                'data' => [
                    'id' => $group->id,
                    'group_name' => $group->group_name,
                    'status' => (int) $group->status,
                ],
            ]);
        }

        return redirect()
            ->route('groups.index')
            ->with('status', 'Group created successfully.');
    }

    public function show(Group $group)
    {
        return view('groups.show', compact('group'));
    }

    public function edit(Group $group)
    {
        return view('groups.edit', compact('group'));
    }

    public function update(Request $request, Group $group)
    {
        $validated = $request->validate([
            'group_name' => ['required', 'string', 'max:50'],
            'status' => ['required', 'integer', 'in:0,1'],
        ]);

        try {
            $group->update([
                'group_name' => $validated['group_name'],
                'status' => (int) $validated['status'],
            ]);
        } catch (QueryException|\PDOException $e) {
            return back()
                ->withErrors(['database' => 'Unable to update group due to a database error. Please try again.'])
                ->withInput();
        }

        return redirect()
            ->route('groups.index')
            ->with('status', 'Group updated successfully.');
    }

    public function destroy(Group $group)
    {
        try {
            $group->delete();
        } catch (QueryException|\PDOException $e) {
            return back()
                ->withErrors(['database' => 'Unable to delete group due to a database error. Please try again.']);
        }

        return redirect()
            ->route('groups.index')
            ->with('status', 'Group deleted successfully.');
    }
}
