<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10);
        $search = trim((string) $request->input('search', ''));
        $status = $request->input('status');
        $levelId = $request->input('level_id');
        $divisionId = $request->input('division_id');
        $sectionId = $request->input('section_id');
        $sortBy = trim((string) $request->input('sort_by', 'id'));
        $sortDir = strtolower(trim((string) $request->input('sort_dir', 'desc'))) === 'asc' ? 'asc' : 'desc';
        $canEdit = $this->canManageUsers();

        $allowedSort = [
            'id' => 'users.id',
            'name' => 'users.name',
            'email' => 'users.email',
            'level' => 'user_level.level_name',
            'division' => 'lib_division.division_name',
            'section' => 'lib_section.section_name',
            'province' => 'lib_provinces.prov_name',
            'cluster' => 'users.cluster',
            'municipality' => 'lib_cities.city_name',
            'group' => 'group.group_name',
            'status' => Schema::hasColumn('users', 'is_status') ? 'users.is_status' : 'users.id',
        ];

        $orderColumn = $allowedSort[$sortBy] ?? $allowedSort['id'];

        $query = DB::table('users')
            ->leftJoin('user_level', 'users.level_id', '=', 'user_level.id')
            ->leftJoin('lib_division', 'users.division_id', '=', 'lib_division.id')
            ->leftJoin('lib_section', 'users.section_id', '=', 'lib_section.id')
            ->leftJoin('lib_provinces', 'users.province', '=', 'lib_provinces.prov_code')
            ->leftJoin('lib_cities', 'users.municipality', '=', 'lib_cities.city_code')
            ->leftJoin('group', 'users.group_id', '=', 'group.id')
            ->select([
                'users.*',
                'user_level.level_name as level_name',
                'lib_division.division_name as division_name',
                'lib_section.section_name as section_name',
                'lib_provinces.prov_name as province_name',
                'lib_cities.city_name as municipality_name',
                'group.group_name as group_name',
            ])
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('users.name', 'like', "%{$search}%")
                        ->orWhere('users.email', 'like', "%{$search}%");
                });
            })
            ->when($status !== null && $status !== '' && Schema::hasColumn('users', 'is_status'), function ($q) use ($status) {
                $q->where('users.is_status', (int) $status);
            })
            ->when($levelId !== null && $levelId !== '', fn ($q) => $q->where('users.level_id', (int) $levelId))
            ->when($divisionId !== null && $divisionId !== '', fn ($q) => $q->where('users.division_id', (int) $divisionId))
            ->when($sectionId !== null && $sectionId !== '', fn ($q) => $q->where('users.section_id', (int) $sectionId))
            ->orderBy($orderColumn, $sortDir);

        $users = $query->paginate($perPage)->appends([
            'per_page' => $perPage,
            'search' => $search,
            'status' => $status,
            'level_id' => $levelId,
            'division_id' => $divisionId,
            'section_id' => $sectionId,
            'sort_by' => $sortBy,
            'sort_dir' => $sortDir,
        ]);

        $userLevels = DB::table('user_level')->orderBy('level_name')->get();
        $divisions = DB::table('lib_division')->orderBy('division_name')->get();
        $sections = DB::table('lib_section')->orderBy('section_name')->get();

        if ($request->ajax()) {
            return view('users.table', compact('users', 'sortBy', 'sortDir', 'canEdit'))->render();
        }

        return view('users.index', compact('users', 'userLevels', 'divisions', 'sections', 'sortBy', 'sortDir', 'canEdit'));
    }

    public function edit(User $user)
    {
        $this->assertCanManageUsers();

        $userLevels = DB::table('user_level')->orderBy('level_name')->get();
        $divisions = DB::table('lib_division')->orderBy('division_name')->get();
        $sections = DB::table('lib_section')->orderBy('section_name')->get();
        $provinces = DB::table('lib_provinces')->where('region_code', 16)->orderBy('prov_name')->get();
        $cities = DB::table('lib_cities')->orderBy('city_name')->get();
        $groups = DB::table('group')->where('status', 1)->orderBy('group_name')->get();

        return view('users.edit', compact('user', 'userLevels', 'divisions', 'sections', 'provinces', 'cities', 'groups'));
    }

    public function update(Request $request, User $user)
    {
        $this->assertCanManageUsers();

        $request->merge([
            'name' => preg_replace('/\s+/', ' ', trim((string) $request->input('name', ''))),
            'email' => mb_strtolower(trim((string) $request->input('email', ''))),
        ]);

        $rules = [
            'name' => [
                'required',
                'string',
                'min:5',
                'max:150',
                'regex:/^[\pL][\pL\s.\'-]{1,74},\s*[\pL][\pL\s.\'-]{1,74}$/u',
            ],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'level_id' => ['required', 'integer', Rule::exists('user_level', 'id')],
            'division_id' => ['required', 'integer', Rule::exists('lib_division', 'id')],
            'section_id' => [
                'required',
                'integer',
                Rule::exists('lib_section', 'id')->where(function ($q) use ($request) {
                    $q->where('division_id', $request->input('division_id'));
                }),
            ],
        ];

        if (Schema::hasColumn('users', 'province')) {
            $rules['province'] = ['nullable', 'integer', Rule::exists('lib_provinces', 'prov_code')->where(fn ($q) => $q->where('region_code', 16))];
        }
        if (Schema::hasColumn('users', 'municipality')) {
            $rules['municipality'] = ['nullable', 'integer', Rule::exists('lib_cities', 'city_code')];
        }
        if (Schema::hasColumn('users', 'cluster')) {
            $rules['cluster'] = ['nullable', 'integer', Rule::in([1, 2])];
        }
        if (Schema::hasColumn('users', 'group_id')) {
            $rules['group_id'] = ['nullable', 'integer', Rule::exists('group', 'id')->where(fn ($q) => $q->where('status', 1))];
        }
        if (Schema::hasColumn('users', 'is_status')) {
            $rules['is_status'] = ['required', 'integer', Rule::in([0, 1])];
        }

        $validated = $request->validate($rules, [
            'name.regex' => 'Name format must be: lastname, firstname middlename.',
        ]);

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'level_id' => (int) $validated['level_id'],
            'division_id' => (int) $validated['division_id'],
            'section_id' => (int) $validated['section_id'],
        ];

        foreach (['province', 'cluster', 'municipality', 'group_id', 'is_status'] as $col) {
            if (Schema::hasColumn('users', $col) && array_key_exists($col, $validated)) {
                $payload[$col] = $validated[$col];
            }
        }

        $user->update($payload);

        return redirect()
            ->route('users.index')
            ->with('status', 'User updated successfully.');
    }

    private function assertCanManageUsers(): void
    {
        if (! $this->canManageUsers()) {
            abort(403);
        }
    }

    private function canManageUsers(): bool
    {
        $authUser = Auth::user();
        if (! $authUser) {
            return false;
        }

        if ((int) $authUser->id === 1) {
            return true;
        }

        if (! Schema::hasColumn('users', 'level_id')) {
            return true;
        }

        $levelId = (int) ($authUser->level_id ?? 0);
        if ($levelId === 0) {
            return false;
        }

        if ($levelId === 1) {
            return true;
        }

        $levelName = null;
        if (Schema::hasTable('user_level') && Schema::hasColumn('user_level', 'level_name')) {
            $levelName = DB::table('user_level')->where('id', $levelId)->value('level_name');
        }

        if (! is_string($levelName) || $levelName === '') {
            return false;
        }

        return (bool) preg_match('/ADMIN|SUPER|ROOT|SYSTEM/i', $levelName);
    }
}
