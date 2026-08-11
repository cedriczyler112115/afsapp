<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Issuance;
use App\Models\IssuanceGroup;
use App\Models\Item;
use App\Models\ItemUnit;
use App\Models\StockTransaction;
use App\Models\SupplyRequestItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockOutController extends Controller
{
    /**
     * Display a listing of the resource.
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
            ->with(['stockTransactions.unit.item.unit', 'stockTransactions.unit.item.category', 'issuanceGroup', 'supplyRequestItem.supplyRequest']);
        $query->selectSub(function ($subQuery) {
            $subQuery->from('stock_transactions')
                ->join('item_units', 'item_units.id', '=', 'stock_transactions.unit_id')
                ->whereColumn('stock_transactions.issuance_id', 'issuances.id')
                ->orderBy('item_units.full_code')
                ->limit(1)
                ->select('item_units.full_code');
        }, 'primary_full_code');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('users.name', 'like', "%{$search}%")
                    ->orWhereHas('stockTransactions.unit.item', function ($i) use ($search) {
                        $i->where('item_name', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%");
                    });
            });
        }

        if ($dateReleased) {
            $query->whereDate('issuances.date_issued', $dateReleased);
        }

        if ($itemId) {
            $query->whereHas('stockTransactions', function ($q) use ($itemId) {
                $q->where('item_id', $itemId);
            });
        }

        if ($categoryId) {
            $query->whereHas('stockTransactions.unit.item', function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            });
        }

        // Calculate Overall Total Units based on filter
        // We count all units belonging to the filtered issuances
        // Clone query to avoid modifying the original for pagination
        $countQuery = clone $query;
        $overallTotalUnits = StockTransaction::whereIn('issuance_id', $countQuery->select('issuances.id'))
            ->where('type', 'OUT')->sum('quantity');

        $issuances = $query->orderBy('issuances.date_issued', 'desc')
            ->orderByRaw('primary_full_code IS NULL')
            ->orderBy('primary_full_code')
            ->paginate($perPage)
            ->appends([
                'per_page' => $perPage,
                'search' => $search,
                'date_released' => $dateReleased,
                'item_id' => $itemId,
                'category_id' => $categoryId,
            ]);
        // echo "<pre>"; echo print_r($issuances); echo "</pre>";
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

            return view('stock_out.table', compact('issuances', 'overallTotalUnits'))->render();
        }

        return view('stock_out.index', compact('issuances', 'items', 'categories', 'overallTotalUnits'));
    }

    public function show($id)
    {
        $issuance = Issuance::with(['user', 'stockTransactions.unit.item.unit', 'supplyRequestItem.supplyRequest', 'receivedByUser'])->findOrFail($id);

        // Group units by item for better display in modal
        $groupedUnits = $issuance->stockTransactions->groupBy(function ($transaction) {
            return $transaction->unit->item->item_name;
        });

        return response()->json([
            'issuance' => $issuance,
            'groupedUnits' => $groupedUnits,
            'html' => view('stock_out.show_modal', compact('issuance', 'groupedUnits'))->render(),
        ]);
    }

    public function create(Request $request)
    {
        $items = Item::query()
            ->join('item_units', 'item_units.item_id', '=', 'items.item_id')
            ->where('item_units.status', 1)
            ->where('item_units.is_printed', 1)
            ->select('items.item_id', 'items.item_name', 'items.sku')
            ->distinct()
            ->orderBy('items.item_name')
            ->get();

        $users = User::all();
        $approvedRequestItems = SupplyRequestItem::with(['supplyRequest.requester', 'item'])
            ->whereColumn('issued_quantity', '<', 'approved_quantity')
            ->whereHas('supplyRequest', function ($query) {
                $query->whereIn('status', ['APPROVED', 'PARTIALLY_ISSUED']);
            })
            ->orderBy('supply_request_id')
            ->get();
        $requestItem = null;
        if ($request->filled('request_item')) {
            abort_unless((int) Auth::user()->level_id === 1 || Auth::user()->hasSidebarAccess('stock-out.index'), 403);
            $requestItem = SupplyRequestItem::with(['supplyRequest.requester', 'item'])
                ->findOrFail($request->integer('request_item'));
            abort_unless(in_array($requestItem->supplyRequest->status, ['APPROVED', 'PARTIALLY_ISSUED'], true), 422, 'This request is not ready for issuance.');
            abort_unless($requestItem->remaining_quantity > 0, 422, 'This requested item has already been fully issued.');
        }

        return view('stock_out.create', compact('items', 'users', 'requestItem', 'approvedRequestItems'));
    }

    public function getAvailableUnits(Request $request, $item_id)
    {
        $requestItem = $request->filled('request_item')
            ? SupplyRequestItem::findOrFail($request->integer('request_item'))
            : null;
        $units = ItemUnit::query()
            ->join('items', 'items.item_id', '=', 'item_units.item_id')
            ->where('item_units.item_id', $item_id)
            ->where('item_units.status', 1)
            ->where('item_units.is_printed', 1)
            ->where(function ($query) {
                $query->whereNull('item_units.pcs_per_unit')->orWhere('item_units.pcs_per_unit', '>', 0);
            })
            ->when($requestItem?->issue_mode === 'BOX', function ($query) {
                $query->whereRaw('COALESCE(item_units.pcs_per_unit, items.pcs_per_unit, 1) = COALESCE(items.pcs_per_unit, 1)');
            })
            ->select(
                'item_units.id',
                'item_units.serial',
                'item_units.full_code',
                'item_units.qr_code',
                'item_units.pcs_per_unit',
                'items.pcs_per_unit as original_pcs_per_unit'
            )
            ->get();

        return response()->json($units);
    }

    public function findUnit(Request $request)
    {
        $request->validate([
            'item_id' => 'required|exists:items,item_id',
            'query' => 'required|string',
            'request_item_id' => 'nullable|exists:supply_request_items,id',
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

        // Check status
        if ($unit->status != 1) {
            $statusLabel = 'Unknown';
            if ($unit->status == 0) {
                $statusLabel = 'Already Issued (Out)';
            } elseif ($unit->status == 2) {
                $statusLabel = 'Damaged';
            } elseif ($unit->status == 3) {
                $statusLabel = 'Borrowed';
            } elseif ($unit->status == 4) {
                $statusLabel = 'Lost';
            }

            return response()->json([
                'success' => false,
                'message' => "Unit is not available. Status: $statusLabel",
            ]);
        }


        if ((int) $unit->is_printed !== 1 || ($unit->pcs_per_unit !== null && (int) $unit->pcs_per_unit < 1)) {
            return response()->json([
                'success' => false,
                'message' => 'This box is not printed or has no remaining PC/S available.',
            ]);
        }

        $unit->setAttribute('original_pcs_per_unit', $unit->item?->pcs_per_unit);

        if ($request->filled('request_item_id')) {
            $requestItem = SupplyRequestItem::findOrFail($request->request_item_id);
            if ($requestItem->issue_mode === 'BOX') {
                $original = max(1, (int) ($unit->item?->pcs_per_unit ?? 1));
                $remaining = max(1, (int) ($unit->pcs_per_unit ?? 1));
                if ($remaining !== $original) {
                    return response()->json(['success' => false, 'message' => 'This request is issued by Box. Partially issued boxes cannot be selected.']);
                }
            }
        }

        return response()->json(['success' => true, 'unit' => $unit]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'item_id' => 'required|exists:items,item_id',
            'units' => 'required|array|min:1',
            'units.*.unit_id' => 'required|exists:item_units,id',
            'units.*.pcs_to_issue' => 'required|integer|min:1',
            'units.*.issue_mode' => 'required|in:BOX,PCS',
            'user_id' => 'required|exists:users,id',
            'remarks' => 'nullable|string',
            'supply_request_item_id' => 'nullable|exists:supply_request_items,id',
        ]);

        DB::beginTransaction();
        try {
            $itemId = $request->item_id;
            $item = Item::where('item_id', $itemId)->lockForUpdate()->firstOrFail();
            $supplyRequestItem = null;
            if ($request->filled('supply_request_item_id')) {
                if ((int) Auth::user()->level_id !== 1 && ! Auth::user()->hasSidebarAccess('stock-out.index')) {
                    abort(403, 'You are not authorized to process supply requests.');
                }
                $supplyRequestItem = SupplyRequestItem::with('supplyRequest')
                    ->whereKey($request->supply_request_item_id)
                    ->lockForUpdate()
                    ->firstOrFail();
                if (! in_array($supplyRequestItem->supplyRequest->status, ['APPROVED', 'PARTIALLY_ISSUED'], true)) {
                    throw new \Exception('This supply request is no longer available for issuance.');
                }
                if ((int) $supplyRequestItem->item_id !== (int) $itemId || (int) $supplyRequestItem->supplyRequest->requester_id !== (int) $request->user_id) {
                    throw new \Exception('The receiver or item does not match the approved supply request.');
                }
            }
            $requestedUnits = collect($request->units)->keyBy(fn ($unit) => (int) $unit['unit_id']);
            $unitIds = $requestedUnits->keys()->all();

            if (empty($unitIds)) {
                throw new \Exception('No valid units selected.');
            }

            if (count($unitIds) !== count($request->units)) {
                throw new \Exception('A box may only be selected once per issuance.');
            }

            $availableUnits = ItemUnit::whereIn('id', $unitIds)
                ->where('item_id', $itemId)
                ->where('status', 1)
                ->where('is_printed', 1)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            if ($availableUnits->count() !== count($unitIds)) {
                throw new \Exception('One or more selected units are no longer available.');
            }

            if ($supplyRequestItem && $supplyRequestItem->issue_mode === 'BOX' && count($unitIds) > $supplyRequestItem->remaining_quantity) {
                throw new \Exception("Issuance cannot exceed the remaining approved request balance of {$supplyRequestItem->remaining_quantity} box(es).");
            }

            // Get Receiver Name
            $user = User::findOrFail($request->user_id);

            // Create Issuance Record
            $issuance = Issuance::create([
                'supply_request_item_id' => $supplyRequestItem?->id,
                'user_id' => $request->user_id,
                'receiver_name' => $user->name,
                'remarks' => $request->remarks,
                'date_issued' => now(),
            ]);

            $fullyIssuedBoxes = 0;
            $totalIssuedPieces = 0;
            foreach ($unitIds as $unitId) {
                $itemUnit = $availableUnits->get($unitId);
                $requestedQty = (int) $requestedUnits->get($unitId)['pcs_to_issue'];
                $requestedMode = $requestedUnits->get($unitId)['issue_mode'];
                if ($supplyRequestItem && $requestedMode !== $supplyRequestItem->issue_mode) {
                    $requiredMode = $supplyRequestItem->issue_mode === 'BOX' ? 'Box' : 'PC/S';
                    throw new \Exception("This supply request must be issued by {$requiredMode}.");
                }
                $pcsBefore = max(1, (int) ($itemUnit->pcs_per_unit ?? 1));
                $originalPcsPerBox = max(1, (int) ($item->pcs_per_unit ?? 1));

                if ($requestedMode === 'BOX') {
                    if ($pcsBefore !== $originalPcsPerBox) {
                        throw new \Exception("Box {$itemUnit->full_code} is already partially issued. It has {$pcsBefore} of {$originalPcsPerBox} PC/S remaining and must be issued by PC/S.");
                    }
                    $requestedQty = $pcsBefore;
                }

                if ($requestedQty > $pcsBefore) {
                    throw new \Exception("PC/S to issue for box {$itemUnit->full_code} cannot exceed {$pcsBefore}.");
                }

                $totalIssuedPieces += $requestedQty;
                if ($supplyRequestItem && $supplyRequestItem->issue_mode === 'PCS' && $totalIssuedPieces > $supplyRequestItem->remaining_quantity) {
                    throw new \Exception("Issuance cannot exceed the remaining approved request balance of {$supplyRequestItem->remaining_quantity} PC/S.");
                }

                $pcsAfter = $pcsBefore - $requestedQty;
                $isWholeBox = $pcsAfter === 0;
                StockTransaction::create([
                    'item_id' => $itemId,
                    'unit_id' => $unitId,
                    'issuance_id' => $issuance->id,
                    'type' => 'OUT',
                    'quantity' => $requestedQty,
                    'issue_mode' => $requestedMode === 'BOX' ? 'BOX' : 'PCS',
                    'pcs_before' => $pcsBefore,
                    'pcs_after' => $pcsAfter,
                    'date_created' => now(),
                    'created_by' => Auth::id() ?? 1,
                ]);

                $itemUnit->pcs_per_unit = $pcsAfter;
                $itemUnit->issuance_id = $issuance->id;
                if ($isWholeBox) {
                    $itemUnit->status = 0;
                    $fullyIssuedBoxes++;
                }
                $itemUnit->save();
            }

            if ($fullyIssuedBoxes > 0) {
                Item::where('item_id', $itemId)->decrement('current_quantity', $fullyIssuedBoxes);
            }

            if ($supplyRequestItem) {
                $requestQuantityIssued = $supplyRequestItem->issue_mode === 'BOX' ? count($unitIds) : $totalIssuedPieces;
                $supplyRequestItem->increment('issued_quantity', $requestQuantityIssued);
                $requestRecord = $supplyRequestItem->supplyRequest()->with('items')->lockForUpdate()->firstOrFail();
                $hasIssued = $requestRecord->items->sum('issued_quantity') > 0;
                $isComplete = $requestRecord->items->every(function ($line) {
                    return (int) ($line->approved_quantity ?? 0) <= (int) $line->issued_quantity;
                });
                $requestRecord->update(['status' => $isComplete ? 'COMPLETED' : ($hasIssued ? 'PARTIALLY_ISSUED' : 'APPROVED')]);
            }

            DB::commit();

            return response()->json([
                'success' => 'Stock Out processed successfully.',
                'redirect_url' => $supplyRequestItem
                    ? route('supply-requests.show', $supplyRequestItem->supply_request_id)
                    : route('stock-out.index'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['errors' => ['error' => [$e->getMessage()]]], 422);
        }
    }

    public function preview(Request $request)
    {
        $ids = $request->input('ids', []);
        $issuances = Issuance::with(['user', 'stockTransactions.unit.item.category', 'stockTransactions.unit.item.unit'])
            ->whereIn('id', $ids)
            ->orderBy('date_issued')
            ->get();

        return view('stock_out.preview_modal', compact('issuances'))->render();
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
                'print_url' => route('stock-out.print', $group->id),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function print($id)
    {
        $group = IssuanceGroup::with(['issuances.user', 'issuances.stockTransactions.unit.item.category'])->findOrFail($id);

        return view('stock_out.print', compact('group'));
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
