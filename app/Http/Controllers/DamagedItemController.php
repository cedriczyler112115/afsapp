<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Issuance;
use App\Models\IssuanceGroup;
use App\Models\Item;
use App\Models\ItemUnit;
use App\Models\StockTransaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DamagedItemController extends Controller
{
    /**
     * Display a listing of the damaged/unserviceable items.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search');
        $dateReleased = $request->input('date_released');
        $itemId = $request->input('item_id');
        $categoryId = $request->input('category_id');

        $query = Issuance::select('issuances.*', 'users.name as receiver_name')
            ->leftJoin('users', 'issuances.user_id', '=', 'users.id')
            ->whereHas('damageTransactions')
            ->with(['damageTransactions.item.unit', 'damageTransactions.item.category', 'damageTransactions.unit', 'issuanceGroup']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('users.name', 'like', "%{$search}%")
                    ->orWhereHas('damageTransactions', function ($u) use ($search) {
                        $u->whereHas('item', function ($i) use ($search) {
                                $i->where('item_name', 'like', "%{$search}%")
                                    ->orWhere('sku', 'like', "%{$search}%");
                            });
                    });
            });
        }

        if ($dateReleased) {
            $query->whereDate('issuances.date_issued', $dateReleased);
        }

        if ($itemId) {
            $query->whereHas('damageTransactions', fn ($q) => $q->where('item_id', $itemId));
        }

        if ($categoryId) {
            $query->whereHas('damageTransactions.item', function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            });
        }

        $countQuery = clone $query;
        $issuanceIds = $countQuery->pluck('issuances.id');
        $overallTotalUnits = StockTransaction::whereIn('issuance_id', $issuanceIds)->where('type', 'DAMAGED')->count();

        $issuances = $query->orderBy('issuances.date_issued', 'desc')
            ->paginate($perPage)
            ->appends([
                'per_page' => $perPage,
                'search' => $search,
                'date_released' => $dateReleased,
                'item_id' => $itemId,
                'category_id' => $categoryId,
            ]);

        $categories = Category::orderBy('category_name')->get();
        $items = Item::orderBy('item_name')->get();

        if ($request->ajax()) {
            if ($request->has('get_items_by_category')) {
                $itemsQuery = Item::orderBy('item_name');
                if ($categoryId) {
                    $itemsQuery->where('category_id', $categoryId);
                }

                return response()->json($itemsQuery->get());
            }

            return view('damaged_items.table', compact('issuances', 'overallTotalUnits'))->render();
        }

        return view('damaged_items.index', compact('issuances', 'items', 'categories', 'overallTotalUnits'));
    }

    public function show($id)
    {
        $issuance = Issuance::with(['user', 'damageTransactions.item.category', 'damageTransactions.item.unit', 'damageTransactions.unit'])->findOrFail($id);
        $groupedUnits = $issuance->damageTransactions->groupBy(fn ($transaction) => $transaction->item->item_name);

        $damagePhotos = collect($issuance->damage_photos_path ?? [])
            ->filter()
            ->map(function ($path) {
                return [
                    'path' => $path,
                    'url' => asset('storage/' . ltrim($path, '/')),
                ];
            })
            ->values();

        return response()->json([
            'issuance' => $issuance,
            'groupedUnits' => $groupedUnits,
            'html' => view('damaged_items.show_modal', compact('issuance', 'groupedUnits', 'damagePhotos'))->render(),
        ]);
    }

    public function create()
    {
        // Items that have units with status = 1 (Available)
        $items = Item::whereIn('item_id', function ($query) {
            $query->select('item_id')
                ->from('item_units')
                ->where('status', 1);
        })->get();

        return view('damaged_items.create', compact('items'));
    }

    public function getAvailableUnits($item_id)
    {
        $originalPcsPerBox = max(1, (int) (Item::where('item_id', $item_id)->value('pcs_per_unit') ?? 1));
        $units = ItemUnit::where('item_id', $item_id)
            ->where('status', 1)
            ->select('id', 'serial', 'full_code', 'qr_code', 'pcs_per_unit')
            ->get()
            ->map(function ($unit) use ($originalPcsPerBox) {
                $unit->original_pcs_per_box = $originalPcsPerBox;
                $unit->remaining_pcs_per_box = max(0, (int) ($unit->pcs_per_unit ?? $originalPcsPerBox));

                return $unit;
            });

        return response()->json($units);
    }

    public function findUnit(Request $request)
    {
        $request->validate([
            'item_id' => 'required|exists:items,item_id',
            'query' => 'required|string',
        ]);

        $itemId = $request->input('item_id');
        $search = $request->input('query');

        // First, try to find the unit regardless of status or item
        $unit = ItemUnit::with('item')
            ->where(function ($q) use ($search) {
                $q->where('serial', $search)
                    ->orWhere('full_code', $search)
                    ->orWhere('qr_code', $search);
            })
            ->first();

        if (! $unit) {
            return response()->json(['success' => false, 'message' => 'Unit not found.']);
        }

        // Check if unit belongs to the selected item
        if ($unit->item_id != $itemId) {
            return response()->json([
                'success' => false,
                'message' => 'Unit found but belongs to another item: '.($unit->item->item_name ?? 'Unknown Item'),
            ]);
        }

        // Check status (Must be 1 - Available, to be reported as damaged from stock)
        if ($unit->status != 1) {
            $statusLabel = 'Unknown';
            if ($unit->status == 0) {
                $statusLabel = 'Already Issued (Out)';
            } elseif ($unit->status == 2) {
                $statusLabel = 'Already Marked Damaged';
            } elseif ($unit->status == 3) {
                $statusLabel = 'Currently Borrowed';
            } elseif ($unit->status == 4) {
                $statusLabel = 'Reported Lost';
            }

            return response()->json([
                'success' => false,
                'message' => "Unit is not available for reporting. Status: $statusLabel",
            ]);
        }

        $unit->load('item');
        $unit->original_pcs_per_box = max(1, (int) ($unit->item->pcs_per_unit ?? 1));
        $unit->remaining_pcs_per_box = max(0, (int) ($unit->pcs_per_unit ?? $unit->original_pcs_per_box));

        return response()->json(['success' => true, 'unit' => $unit]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'item_id' => 'required|exists:items,item_id',
            'units' => 'required|array|min:1',
            'units.*.unit_id' => 'required|exists:item_units,id',
            'units.*.damage_mode' => 'required|in:BOX,PCS',
            'units.*.pcs_damaged' => 'required|integer|min:1',
            'remarks' => 'nullable|string',
            'damage_photos' => 'nullable|array',
            'damage_photos.*' => 'file|image|max:10240',
        ]);

        $storedPhotoPaths = [];

        try {
            $itemId = $request->item_id;
            $requestedUnits = collect($request->units)->keyBy(fn ($unit) => (int) $unit['unit_id']);
            $unitIds = $requestedUnits->keys()->all();

            if (empty($unitIds)) {
                throw new \Exception('No valid units selected.');
            }

            if ($request->hasFile('damage_photos')) {
                foreach ($request->file('damage_photos') as $photo) {
                    if (! $photo) {
                        continue;
                    }
                    $filename = now()->format('YmdHis') . '_' . uniqid() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $photo->getClientOriginalName());
                    $storedPhotoPaths[] = $photo->storeAs('damaged_items', $filename, 'public');
                }
            }

            $issuance = DB::transaction(function () use ($request, $itemId, $unitIds, $requestedUnits, $storedPhotoPaths) {
                $item = Item::where('item_id', $itemId)->lockForUpdate()->firstOrFail();
                $units = ItemUnit::whereIn('id', $unitIds)->where('item_id', $itemId)->lockForUpdate()->get()->keyBy('id');
                if ($units->count() !== count($unitIds) || $units->contains(fn ($unit) => (int) $unit->status !== 1)) {
                    throw new \Exception('One or more selected units are no longer available.');
                }
                $issuance = Issuance::create([
                    'user_id' => Auth::id(),
                    'receiver_name' => Auth::user()->name,
                    'remarks' => $request->remarks,
                    'damage_photos_path' => $storedPhotoPaths ?: null,
                    'date_issued' => now(),
                ]);

                $depletedBoxes = 0;
                foreach ($unitIds as $unitId) {
                    $unit = $units->get($unitId);
                    $selection = $requestedUnits->get($unitId);
                    $mode = $selection['damage_mode'];
                    $pcsBefore = max(1, (int) ($unit->pcs_per_unit ?? $item->pcs_per_unit ?? 1));
                    $pcsDamaged = $mode === 'BOX' ? $pcsBefore : (int) $selection['pcs_damaged'];
                    if ($pcsDamaged > $pcsBefore) {
                        throw new \Exception("Damaged PC/S for {$unit->full_code} cannot exceed {$pcsBefore} remaining PC/S.");
                    }
                    $pcsAfter = $pcsBefore - $pcsDamaged;
                    $unit->pcs_per_unit = $pcsAfter;
                    if ($pcsAfter === 0) {
                        $unit->status = 2;
                        $unit->issuance_id = $issuance->id;
                        $depletedBoxes++;
                    }
                    $unit->save();
                    StockTransaction::create([
                        'item_id' => $itemId,
                        'unit_id' => $unitId,
                        'issuance_id' => $issuance->id,
                        'type' => 'DAMAGED',
                        'quantity' => $pcsDamaged,
                        'issue_mode' => $mode,
                        'pcs_before' => $pcsBefore,
                        'pcs_after' => $pcsAfter,
                        'date_created' => now(),
                        'created_by' => Auth::id(),
                    ]);
                }
                if ($depletedBoxes > 0) $item->decrement('current_quantity', $depletedBoxes);

                return $issuance;
            });

            return response()->json([
                'success' => 'Damage report processed successfully.',
                'issuance_id' => $issuance->id,
            ]);
        } catch (\Exception $e) {
            foreach ($storedPhotoPaths as $storedPath) {
                Storage::disk('public')->delete($storedPath);
            }

            return response()->json(['errors' => ['error' => [$e->getMessage()]]], 422);
        }
    }

    public function preview(Request $request)
    {
        $ids = $request->input('ids', []);
        $issuances = Issuance::with(['user', 'damageTransactions.item.category', 'damageTransactions.unit'])
            ->whereIn('id', $ids)
            ->orderBy('date_issued')
            ->get();

        return view('damaged_items.preview_modal', compact('issuances'))->render();
    }

    public function storeGroup(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'purpose' => 'required|string',
        ]);

        $ids = $request->ids;
        $purpose = $request->purpose;

        DB::beginTransaction();
        try {
            $group = IssuanceGroup::create([
                'purpose' => $purpose,
                'date_printed' => now(),
                'printed_by' => Auth::id(),
            ]);

            Issuance::whereIn('id', $ids)->update(['issuance_group_id' => $group->id]);

            DB::commit();

            return response()->json([
                'success' => true,
                'print_url' => route('damaged-items.print', $group->id),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function print($id)
    {
        $group = IssuanceGroup::with(['issuances.user', 'issuances.damageTransactions.item.category', 'issuances.damageTransactions.unit'])->findOrFail($id);

        return view('damaged_items.print', compact('group'));
    }

    public function updateReceiver(Request $request, $id)
    {
        $request->validate([
            'received_conformed_by' => 'nullable|string|max:255',
        ]);

        $group = IssuanceGroup::findOrFail($id);
        $group->update([
            'received_conformed_by' => $request->received_conformed_by,
        ]);

        return response()->json(['success' => true]);
    }
}
