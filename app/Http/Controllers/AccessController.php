<?php

namespace App\Http\Controllers;

use App\Models\UserLevel;
use Illuminate\Http\Request;

class AccessController extends Controller
{
    // Define the modules mapping statically
    public static $modules = [
        'dashboard' => 'Dashboard Main',
        'tracking-dashboard.index' => 'Tracking Dashboard',
        'low-stock.index' => 'Low Stock Alert',
        'stock-in.index' => 'Stock In (Receiving)',
        'stock-out.index' => 'Stock Out (Issuance)',
        'borrowings.index' => 'Borrowings Tracking',
        'damaged-items.index' => 'Unserviceable Items',
        'inbox.index' => 'Document Inbox',
        'incoming-documents.index' => 'Document Tracking',
        'inbox.batch' => 'Route Slips',
        'items.index' => 'Libraries: Stock Items',
        'categories.index' => 'Libraries: Categories',
        'unit_of_measures.index' => 'Libraries: Units of Measure',
        'groups.index' => 'Libraries: Group Section',
        'document-sources.index' => 'Libraries: Document Sources',
        'document-types.index' => 'Libraries: Document Types',
        'users.index' => 'Account: User Management',
        'access.index' => 'Account: Access Management',
    ];

    public function index()
    {
        if ((int) auth()->user()->level_id !== 1) {
            abort(403, 'Unauthorized access.');
        }

        $levels = UserLevel::orderBy('id')->get();
        $users = \App\Models\User::orderBy('name')->get();
        return view('access.index', [
            'levels' => $levels,
            'users' => $users,
            'modules' => self::$modules
        ]);
    }

    public function getConfig(Request $request)
    {
        if ((int) auth()->user()->level_id !== 1) {
            abort(403, 'Unauthorized access.');
        }

        $type = $request->query('type');
        $id = $request->query('id');

        if ($type === 'level') {
            $level = UserLevel::find($id);
            return response()->json(['access_rights' => $level->access_rights ?? []]);
        } else if ($type === 'user') {
            $user = \App\Models\User::find($id);
            return response()->json(['access_rights' => $user->access_rights ?? []]);
        }

        return response()->json(['access_rights' => []]);
    }

    public function update(Request $request)
    {
        if ((int) auth()->user()->level_id !== 1) {
            abort(403, 'Unauthorized access.');
        }

        $type = $request->input('access_type'); // 'level' or 'user'
        $id = $request->input('access_target_id');
        $accessData = $request->input('access', []);

        if ($type === 'level') {
            $level = UserLevel::findOrFail($id);
            $level->access_rights = $accessData;
            $level->save();
            $msg = "Access rights updated for user level.";
        } elseif ($type === 'user') {
            $user = \App\Models\User::findOrFail($id);
            $user->access_rights = $accessData;
            $user->save();
            $msg = "Access rights updated for specific user.";
        } else {
            return redirect()->back()->with('error', 'Invalid access type.');
        }

        return redirect()->route('access.index')->with('status', $msg);
    }
}
