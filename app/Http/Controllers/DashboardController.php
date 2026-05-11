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
        $totalStockQuantity = $itemsQuery->sum('current_quantity');

        // Low Stock: Current < Reorder AND Current > 0 (assuming 0 is Out of Stock)
        $lowStockCount = (clone $itemsQuery)->whereColumn('current_quantity', '<=', 'reorder_level')->where('current_quantity', '>', 0)->count();
        $outOfStockCount = (clone $itemsQuery)->where('current_quantity', 0)->count();

        // Critical Stock: Items where Current Quantity <= Reorder Level (Low Stock + Out of Stock)
        $criticalStockCount = (clone $itemsQuery)->whereColumn('current_quantity', '<=', 'reorder_level')->count();

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
            $issuancesQuery->whereHas('itemUnits.item', function ($q) use ($categoryId) {
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
                $query->from('items')
                    ->whereColumn('items.category_id', 'categories.category_id')
                    ->selectRaw('IFNULL(SUM(current_quantity), 0)');
            }, 'total_qty')
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
            DB::raw("SUM(CASE WHEN type = 'IN' THEN 1 ELSE 0 END) as stock_in"),
            DB::raw("SUM(CASE WHEN type = 'OUT' THEN 1 ELSE 0 END) as stock_out")
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
            ->select('items.item_name', DB::raw('count(*) as usage_count'))
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
        $lowStockItems = (clone $itemsQuery)
            ->whereColumn('current_quantity', '<=', 'reorder_level')
            ->orderBy('current_quantity', 'asc')
            ->limit(10) // Limit for dashboard
            ->get();

        // 4. Recent Activity
        $recentActivities = $transactionsQuery->with(['item', 'item.unit']) // item.unit is UOM
            ->orderBy('date_created', 'desc')
            ->limit(10)
            ->get();

        // Get all categories for filter
        $categories = Category::all();

        return view('dashboard', compact(
            'totalItems', 'totalStockQuantity', 'lowStockCount', 'outOfStockCount',
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
            ->select('item_id', 'item_name', 'current_quantity')
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
}
