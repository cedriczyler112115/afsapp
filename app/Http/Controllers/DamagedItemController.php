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

        // Start with Issuances that have damaged items (status = 2)
        $query = Issuance::select('issuances.*', 'users.name as receiver_name')
            ->leftJoin('users', 'issuances.user_id', '=', 'users.id')
            ->whereHas('itemUnits', function ($q) {
                $q->where('status', 2);
            })
            ->with(['itemUnits' => function ($q) {
                // Eager load only the damaged units
                $q->where('status', 2)->with(['item.unit', 'item.category']);
            }, 'issuanceGroup']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('users.name', 'like', "%{$search}%")
                    ->orWhereHas('itemUnits', function ($u) use ($search) {
                        $u->where('status', 2)
                            ->whereHas('item', function ($i) use ($search) {
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
            $query->whereHas('itemUnits', function ($q) use ($itemId) {
                $q->where('status', 2)->where('item_id', $itemId);
            });
        }

        if ($categoryId) {
            $query->whereHas('itemUnits.item', function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            });
        }

        if ($itemId) {
            $query->with(['itemUnits' => function ($q) use ($itemId) {
                $q->where('status', 2)
                    ->where('item_id', $itemId)
                    ->with(['item.unit', 'item.category']);
            }]);
        }

        // Calculate Overall Total Units based on filter (only status = 2)
        $countQuery = clone $query;
        $issuanceIds = $countQuery->pluck('issuances.id');
        $overallTotalUnits = ItemUnit::whereIn('issuance_id', $issuanceIds)
            ->where('status', 2)
            ->count();

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
        $issuance = Issuance::with(['user', 'itemUnits.item.category', 'itemUnits.item.unit'])->findOrFail($id);

        $groupedUnits = $issuance->itemUnits->groupBy(function ($unit) {
            return $unit->item->item_name;
        });

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
        $units = ItemUnit::where('item_id', $item_id)
            ->where('status', 1)
            ->select('id', 'serial', 'full_code', 'qr_code')
            ->get();

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
            }

            return response()->json([
                'success' => false,
                'message' => "Unit is not available for reporting. Status: $statusLabel",
            ]);
        }

        return response()->json(['success' => true, 'unit' => $unit]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'item_id' => 'required|exists:items,item_id',
            'units' => 'required|array|min:1',
            'units.*.unit_id' => 'required|exists:item_units,id',
            'remarks' => 'nullable|string',
            'damage_photos' => 'nullable|array',
            'damage_photos.*' => 'file|image|max:10240',
        ]);

        $storedPhotoPaths = [];

        try {
            $itemId = $request->item_id;
            $unitIds = [];
            foreach ($request->units as $unit) {
                if (isset($unit['unit_id']) && $unit['unit_id']) {
                    $unitIds[] = $unit['unit_id'];
                }
            }

            if (empty($unitIds)) {
                throw new \Exception('No valid units selected.');
            }

            $count = ItemUnit::whereIn('id', $unitIds)->where('status', 1)->count();
            if ($count !== count($unitIds)) {
                throw new \Exception('One or more selected units are no longer available.');
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

            $issuance = DB::transaction(function () use ($request, $itemId, $unitIds, $storedPhotoPaths) {
                $issuance = Issuance::create([
                    'user_id' => Auth::id(),
                    'receiver_name' => Auth::user()->name,
                    'remarks' => $request->remarks,
                    'damage_photos_path' => $storedPhotoPaths ?: null,
                    'date_issued' => now(),
                ]);

                ItemUnit::whereIn('id', $unitIds)->update([
                    'status' => 2,
                    'issuance_id' => $issuance->id,
                ]);

                foreach ($unitIds as $unitId) {
                    StockTransaction::create([
                        'item_id' => $itemId,
                        'unit_id' => $unitId,
                        'type' => 'DAMAGED',
                        'date_created' => now(),
                        'created_by' => Auth::id(),
                    ]);
                }

                Item::where('item_id', $itemId)->decrement('current_quantity', count($unitIds));

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
        $issuances = Issuance::with(['user', 'itemUnits.item.category', 'itemUnits.item.unit'])
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
        $group = IssuanceGroup::with(['issuances.user', 'issuances.itemUnits.item'])->findOrFail($id);

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
