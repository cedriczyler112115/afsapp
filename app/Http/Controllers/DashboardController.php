<?php

namespace App\Http\Controllers;

use App\Models\Borrowing;
use App\Models\Category;
use App\Models\Issuance;
use App\Models\Item;
use App\Models\StockTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Filters
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $categoryId = $request->input('category_id');

        // Base Queries
        $itemsQuery = Item::query();
        if ($categoryId) {
            $itemsQuery->where('category_id', $categoryId);
        }

        $transactionsQuery = StockTransaction::query();
        if ($startDate && $endDate) {
            $transactionsQuery->whereBetween('date_created', [$startDate, $endDate]);
        } elseif ($startDate) {
            $transactionsQuery->where('date_created', '>=', $startDate);
        } elseif ($endDate) {
            $transactionsQuery->where('date_created', '<=', $endDate);
        }

        // If filtering by category, we need to join items for transactions
        if ($categoryId) {
            $transactionsQuery->whereHas('item', function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            });
        }

        // 1. Summary Cards
        $totalItems = $itemsQuery->count();
        $inventoryItems = $this->withLiveStock(clone $itemsQuery)->get();
        $totalStockQuantity = (int) $inventoryItems->sum('available_units');
        $totalStockPieces = (int) $inventoryItems->sum('available_pieces');

        $lowStockCount = $inventoryItems->filter(fn ($item) => $item->available_units > 0 && $item->available_units <= (int) $item->reorder_level)->count();
        $outOfStockCount = $inventoryItems->where('available_units', 0)->count();

        $criticalStockCount = $inventoryItems->filter(fn ($item) => $item->available_units <= (int) $item->reorder_level)->count();

        // Total In/Out (based on transactions within date range)
        // Stock In: Count and group by date_created (distinct timestamps)
        $totalStockIn = (clone $transactionsQuery)
            ->where('type', 'IN')
            ->distinct('date_created')
            ->count('date_created');

        // Stock Out: Number of Issuances
        $issuancesQuery = Issuance::query();

        if ($startDate && $endDate) {
            $issuancesQuery->whereBetween('date_issued', [$startDate, $endDate]);
        } elseif ($startDate) {
            $issuancesQuery->where('date_issued', '>=', $startDate);
        } elseif ($endDate) {
            $issuancesQuery->where('date_issued', '<=', $endDate);
        }

        if ($categoryId) {
            $issuancesQuery->whereHas('stockTransactions.item', function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            });
        }

        $totalStockOut = $issuancesQuery->count();

        // Borrowing Stats (Current State)
        $currentBorrowingsQuery = Borrowing::query();
        if ($categoryId) {
            $currentBorrowingsQuery->whereHas('item', function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            });
        }
        $activeBorrowingsCount = (clone $currentBorrowingsQuery)->where('status', 'BORROWED')->count();
        $overdueBorrowingsCount = (clone $currentBorrowingsQuery)->where('status', 'BORROWED')->where('expected_return_date', '<', now())->count();

        // Damaged Items
        $damagedItemsCount = \App\Models\ItemUnit::where('status', 2);
        if ($categoryId) {
            $damagedItemsCount->whereHas('item', function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            });
        }
        $damagedItemsCount = $damagedItemsCount->count();

        // 2. Charts Data

        // Stock Level by Category
        $stockByCategory = Category::select('categories.category_id', 'categories.category_name')
            ->selectSub(function ($query) {
                $query->from('item_units')
                    ->join('items', 'item_units.item_id', '=', 'items.item_id')
                    ->whereColumn('items.category_id', 'categories.category_id')
                    ->where('item_units.status', 1)
                    ->selectRaw('COUNT(*)');
            }, 'total_qty')
            ->selectSub(function ($query) {
                $query->from('item_units')
                    ->join('items', 'item_units.item_id', '=', 'items.item_id')
                    ->whereColumn('items.category_id', 'categories.category_id')
                    ->where('item_units.status', 1)
                    ->selectRaw('COALESCE(SUM(COALESCE(item_units.pcs_per_unit, items.pcs_per_unit, 1)), 0)');
            }, 'total_pcs')
            ->selectSub(function ($query) {
                $query->from('item_units')
                    ->join('items', 'item_units.item_id', '=', 'items.item_id')
                    ->whereColumn('items.category_id', 'categories.category_id')
                    ->where('item_units.status', 0)
                    ->selectRaw('COUNT(*)');
            }, 'issued_qty')
            ->get();

        // Stock In vs Stock Out (Monthly Trend) - Last 6 months or filtered range
        // SQLite compatible date grouping using strftime
        // MySQL uses DATE_FORMAT.
        // I need to check DB driver.
        $dbDriver = DB::connection()->getDriverName();

        $dateFormat = ($dbDriver === 'sqlite') ? '%Y-%m' : '%Y-%m'; // SQLite: strftime('%Y-%m', date_column)
        $sqlDateFunc = ($dbDriver === 'sqlite')
            ? "strftime('%Y-%m', date_created)"
            : "DATE_FORMAT(date_created, '%Y-%m')";

        $stockTrends = StockTransaction::select(
            DB::raw("$sqlDateFunc as month"),
            DB::raw("SUM(CASE WHEN type = 'IN' THEN COALESCE(quantity, 1) ELSE 0 END) as stock_in"),
            DB::raw("SUM(CASE WHEN type = 'OUT' THEN COALESCE(quantity, 1) ELSE 0 END) as stock_out")
        )
            ->when($categoryId, function ($q) use ($categoryId) {
                $q->whereHas('item', function ($sq) use ($categoryId) {
                    $sq->where('category_id', $categoryId);
                });
            })
            ->where('date_created', '>=', now()->subMonths(6)) // Default last 6 months if no filter
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Low Stock vs Normal (Pie Chart)
        $normalStockCount = $totalItems - $lowStockCount - $outOfStockCount;
        $pieData = [
            'Normal' => $normalStockCount,
            'Low' => $lowStockCount,
            'Out' => $outOfStockCount,
        ];

        // Top 10 Most Used Items (Stock Out count)
        $topItems = StockTransaction::where('type', 'OUT')
            ->join('items', 'stock_transactions.item_id', '=', 'items.item_id')
            ->select('items.item_name', DB::raw('SUM(COALESCE(stock_transactions.quantity, 1)) as usage_count'))
            ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                $q->whereBetween('stock_transactions.date_created', [$startDate, $endDate]);
            })
            ->when($categoryId, function ($q) use ($categoryId) {
                $q->where('items.category_id', $categoryId);
            })
            ->groupBy('items.item_id', 'items.item_name')
            ->orderByDesc('usage_count')
            ->limit(10)
            ->get();

        // 3. Low Stock Alert Panel
        $lowStockItems = $inventoryItems
            ->filter(fn ($item) => $item->available_units <= (int) $item->reorder_level)
            ->sortBy('available_units')
            ->take(10);

        // 4. Recent Activity
        $recentActivities = $transactionsQuery->with(['item', 'item.unit']) // item.unit is UOM
            ->orderBy('date_created', 'desc')
            ->limit(10)
            ->get();

        // Get all categories for filter
        $categories = Category::all();

        return view('dashboard', compact(
            'totalItems', 'totalStockQuantity', 'totalStockPieces', 'lowStockCount', 'outOfStockCount',
            'totalStockIn', 'totalStockOut', 'criticalStockCount',
            'activeBorrowingsCount', 'overdueBorrowingsCount',
            'damagedItemsCount',
            'stockByCategory', 'stockTrends', 'pieData', 'topItems',
            'lowStockItems', 'recentActivities', 'categories'
        ));
    }

    public function getItemsByCategory(Request $request)
    {
        $categoryId = $request->input('category_id');

        if (! $categoryId) {
            return response()->json(['error' => 'Category ID is required'], 400);
        }

        $category = Category::find($categoryId);

        if (! $category) {
            return response()->json(['error' => 'Category not found'], 404);
        }

        $items = Item::where('category_id', $categoryId)
            ->select('items.item_id', 'items.item_name')
            ->selectSub(function ($query) {
                $query->from('item_units')->whereColumn('item_units.item_id', 'items.item_id')
                    ->where('item_units.status', 1)->selectRaw('COUNT(*)');
            }, 'available_units')
            ->selectSub(function ($query) {
                $query->from('item_units')->whereColumn('item_units.item_id', 'items.item_id')
                    ->where('item_units.status', 1)
                    ->selectRaw('COALESCE(SUM(COALESCE(item_units.pcs_per_unit, items.pcs_per_unit, 1)), 0)');
            }, 'available_pieces')
            ->selectSub(function ($query) {
                $query->from('item_units')
                    ->whereColumn('item_units.item_id', 'items.item_id')
                    ->where('item_units.status', 0)
                    ->selectRaw('COUNT(*)');
            }, 'issued_qty')
            ->get();

        return response()->json([
            'category_name' => $category->category_name,
            'items' => $items,
        ]);
    }

    private function withLiveStock($query)
    {
        return $query->select('items.*')
            ->selectSub(function ($subQuery) {
                $subQuery->from('item_units')
                    ->whereColumn('item_units.item_id', 'items.item_id')
                    ->where('item_units.status', 1)
                    ->selectRaw('COUNT(*)');
            }, 'available_units')
            ->selectSub(function ($subQuery) {
                $subQuery->from('item_units')
                    ->whereColumn('item_units.item_id', 'items.item_id')
                    ->where('item_units.status', 1)
                    ->selectRaw('COALESCE(SUM(COALESCE(item_units.pcs_per_unit, items.pcs_per_unit, 1)), 0)');
            }, 'available_pieces');
    }
}
