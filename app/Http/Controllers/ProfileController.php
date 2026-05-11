<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit()
    {
        /** @var User $user */
        $user = Auth::user();

        $userLevels = DB::table('user_level')
            ->select([DB::raw('id as level_id'), 'level_name'])
            ->whereNotNull('id')
            ->orderBy('id')
            ->get();

        $divisions = DB::table('lib_division')
            ->select([DB::raw('id as division_id'), 'division_name'])
            ->whereNotNull('id')
            ->orderBy('division_name')
            ->get();

        $provinces = DB::table('lib_provinces')
            ->select(['prov_code', 'prov_name'])
            ->where('region_code', 16)
            ->orderBy('prov_name')
            ->get();

        $sections = [];
        if ($user->division_id) {
            $sections = DB::table('lib_section')
                ->select([DB::raw('id as section_id'), 'section_name'])
                ->where('division_id', $user->division_id)
                ->orderBy('section_name')
                ->get();
        }

        $cities = [];
        if ($user->province) {
            $cities = DB::table('lib_cities')
                ->select(['city_code', 'city_name'])
                ->where('prov_code', $user->province)
                ->orderBy('city_name')
                ->get();
        }

        return view('auth.profile', [
            'user' => $user,
            'userLevels' => $userLevels,
            'divisions' => $divisions,
            'provinces' => $provinces,
            'sections' => $sections,
            'cities' => $cities,
        ]);
    }

    public function updateProfile(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $request->merge([
            'name' => preg_replace('/\s+/', ' ', trim((string) $request->input('name', ''))),
        ]);

        $sectionId = (int) $request->input('section_id');

        $validated = $request->validate([
            'name' => [
                'required', 'string', 'min:5', 'max:150',
                'regex:/^[\pL][\pL\s.\'-]{1,74},\s*[\pL][\pL\s.\'-]{1,74}$/u',
            ],
            'level_id' => ['required', 'integer', Rule::exists('user_level', 'id')],
            'division_id' => ['required', 'integer', Rule::exists('lib_division', 'id')],
            'section_id' => [
                'required', 'integer',
                Rule::exists('lib_section', 'id')->where(function ($q) use ($request) {
                    $q->where('division_id', $request->input('division_id'));
                }),
            ],
            'province_code' => [
                Rule::requiredIf(in_array($sectionId, [59, 61], true)),
                'nullable', 'integer',
                Rule::exists('lib_provinces', 'prov_code')
            ],
            'cluster' => [
                Rule::requiredIf($sectionId === 60),
                'nullable', 'integer', Rule::in([1, 2]),
            ],
            'municipality_code' => [
                Rule::requiredIf($sectionId === 61),
                'nullable', 'integer',
                Rule::exists('lib_cities', 'city_code')->where(function ($q) use ($request) {
                    $q->where('prov_code', $request->input('province_code'));
                }),
            ],
        ], [
            'name.regex' => 'Name format must be: lastname, firstname middlename.'
        ]);

        $user->update([
            'name' => $validated['name'],
            'level_id' => $validated['level_id'],
            'division_id' => $validated['division_id'],
            'section_id' => $validated['section_id'],
            'province' => $validated['province_code'] ?? null,
            'cluster' => $validated['cluster'] ?? null,
            'municipality' => $validated['municipality_code'] ?? null,
        ]);

        return back()->with('status', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $rules = [
            'password' => [
                'required', 'string', 'confirmed', 'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).+$/',
            ],
        ];

        // Only require current password if they have a non-empty string as a password
        if (!empty($user->password)) {
            $rules['current_password'] = ['required', 'current_password'];
        }

        $request->validate($rules, [
            'password.regex' => 'Password must include uppercase, lowercase, number, and special character.'
        ]);

        $user->update([
            'password' => Hash::make($request->password)
        ]);

        return back()->with('password_status', 'Password updated successfully.');
    }
}
