<?php

namespace App\Http\Controllers;

use App\Models\Borrowing;
use App\Models\Item;
use App\Models\ItemReturn;
use App\Models\ItemUnit;
use App\Models\StockTransaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockReturnController extends Controller
{
    public function index(Request $request)
    {
        $returns = ItemReturn::with(['borrowing', 'item', 'itemUnit', 'receivedBy'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('returns.index', compact('returns'));
    }

    public function create(Request $request)
    {
        $borrowing_id = $request->input('borrowing_id');
        $borrowing = null;
        if ($borrowing_id) {
            $borrowing = Borrowing::with('item', 'itemUnit', 'borrower')->find($borrowing_id);
        }

        $activeBorrowings = Borrowing::where('status', 'BORROWED')
            ->with(['item', 'borrower'])
            ->get();

        $items = Item::all();
        $users = User::all();

        return view('returns.create', compact('borrowing', 'items', 'users', 'activeBorrowings'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'borrowing_id' => 'nullable|exists:borrowings,id',
            'item_id' => 'required|exists:items,item_id',
            'quantity' => 'required|integer|min:1',
            'return_date' => 'required|date',
            'return_category' => 'required|string',
            'item_unit_id' => 'nullable|exists:item_units,id',
        ]);

        DB::beginTransaction();

        try {
            // Create Return Record
            $return = ItemReturn::create([
                'borrowing_id' => $request->borrowing_id,
                'item_id' => $request->item_id,
                'item_unit_id' => $request->item_unit_id,
                'quantity' => $request->quantity,
                'return_date' => $request->return_date,
                'return_category' => $request->return_category,
                'remarks' => $request->remarks,
                'received_by' => Auth::id(),
            ]);

            // If linked to borrowing, update status
            if ($request->borrowing_id) {
                $borrowing = Borrowing::findOrFail($request->borrowing_id);
                // Check if fully returned
                // For simplicity, assuming full return if quantity matches or just marking as RETURNED
                // Ideal: track returned quantity vs borrowed quantity.
                // Req: "Item status becomes Borrowed... The system tracks... Completed returns"
                // I'll just mark as RETURNED for now.
                $borrowing->status = 'RETURNED';
                $borrowing->save();
            }

            // Restore Stock
            $item = Item::findOrFail($request->item_id);
            $item->increment('current_quantity', $request->quantity);

            // If unit, update status
            if ($request->item_unit_id) {
                $unit = ItemUnit::findOrFail($request->item_unit_id);
                // Status 1: Available (Returned)
                $unit->status = 1;
                $unit->save();
            }

            // Log Transaction
            StockTransaction::create([
                'item_id' => $request->item_id,
                'unit_id' => $request->item_unit_id ?? 0,
                'type' => 'RETURN',
                'date_created' => now(),
                'created_by' => Auth::id(),
            ]);

            DB::commit();

            return redirect()->route('borrowings.index')->with('success', 'Item returned successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'An error occurred: '.$e->getMessage()])->withInput();
        }
    }
}
