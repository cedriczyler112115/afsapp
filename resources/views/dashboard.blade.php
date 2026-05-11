@extends('layouts.app')

@section('title', '4Ps AFS-IS')

@section('content')
<div class="container-flui d px-4">
    <!-- Header & Filters -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
        <form action="{{ route('dashboard') }}" method="GET" class="d-flex flex-wrap gap-2 mt-2 mt-md-0">
            <select name="category_id" class="form-select form-select-sm" style="width: 150px;">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->category_id }}" {{ request('category_id') == $cat->category_id ? 'selected' : '' }}>
                        {{ $cat->category_name }}
                    </option>
                @endforeach
            </select>
            <input type="date" name="start_date" class="form-control form-control-sm" value="{{ request('start_date') }}" style="width: 130px;">
            <input type="date" name="end_date" class="form-control form-control-sm" value="{{ request('end_date') }}" style="width: 130px;">
            <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-filter"></i> Filter</button>
            @if(request()->anyFilled(['category_id', 'start_date', 'end_date']))
                <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
            @endif
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <!-- Total Items -->
        <div class="col-md-4 col-lg-2">
            <div class="card bg-primary text-white h-100 shadow-sm" style="cursor: pointer;" onclick="window.location.href='{{ route('items.index') }}'">
                <div class="card-body p-2 d-flex flex-column justify-content-center align-items-center text-center">
                    <h2 class="fw-bold mb-0">{{ $totalItems }}</h2>
                    <small>Total Items</small>
                </div>
            </div>
        </div>
        <!-- Total Stock Qty -->
        <div class="col-md-4 col-lg-2">
            <div class="card bg-success text-white h-100 shadow-sm" style="cursor: pointer;" onclick="window.location.href='{{ route('stock-in.index') }}'">
                <div class="card-body p-2 d-flex flex-column justify-content-center align-items-center text-center">
                    <h2 class="fw-bold mb-0">{{ number_format($totalStockQuantity) }}</h2>
                    <small>Total Stock Qty</small>
                </div>
            </div>
        </div>
        <!-- Low Stock -->
        <div class="col-md-4 col-lg-2">
            <div class="card bg-warning text-dark h-100 shadow-sm" style="cursor: pointer;" onclick="window.location.href='{{ route('low-stock.index') }}'">
                <div class="card-body p-2 d-flex flex-column justify-content-center align-items-center text-center">
                    <h2 class="fw-bold mb-0">{{ $lowStockCount }}</h2>
                    <small>Low Stock Items</small>
                </div>
            </div>
        </div>
        <!-- Out of Stock -->
        <div class="col-md-4 col-lg-2">
            <div class="card bg-danger text-white h-100 shadow-sm" style="cursor: pointer;" onclick="window.location.href='{{ route('low-stock.index') }}'">
                <div class="card-body p-2 d-flex flex-column justify-content-center align-items-center text-center">
                    <h2 class="fw-bold mb-0">{{ $outOfStockCount }}</h2>
                    <small>Out of Stock</small>
                </div>
            </div>
        </div>
        <!-- Critical Stock -->
        <div class="col-md-4 col-lg-2">
            <div class="card bg-info text-white h-100 shadow-sm" style="cursor: pointer;" onclick="window.location.href='{{ route('low-stock.index') }}'">
                <div class="card-body p-2 d-flex flex-column justify-content-center align-items-center text-center">
                    <h2 class="fw-bold mb-0">{{ number_format($criticalStockCount) }}</h2>
                    <small>Critical Stock</small>
                </div>
            </div>
        </div>
        <!-- Total Issued -->
        <div class="col-md-4 col-lg-2">
            <div class="card bg-secondary text-white h-100 shadow-sm" style="cursor: pointer;" onclick="window.location.href='{{ route('stock-out.index') }}'">
                <div class="card-body p-2 d-flex flex-column justify-content-center align-items-center text-center">
                    <h2 class="fw-bold mb-0">{{ number_format($totalStockOut) }}</h2>
                    <small>Number of Issuances</small>
                </div>
            </div>
        </div>
        <!-- Active Borrowings -->
        <div class="col-md-4 col-lg-2">
            <div class="card text-white h-100 shadow-sm" style="cursor: pointer; background-color: #6610f2;" onclick="window.location.href='{{ route('borrowings.index', ['status' => 'BORROWED']) }}'">
                <div class="card-body p-2 d-flex flex-column justify-content-center align-items-center text-center">
                    <h2 class="fw-bold mb-0">{{ number_format($activeBorrowingsCount) }}</h2>
                    <small>Active Borrowings</small>
                </div>
            </div>
        </div>
        <!-- Overdue Borrowings -->
        <div class="col-md-4 col-lg-2">
            <div class="card bg-danger text-white h-100 shadow-sm" style="cursor: pointer;" onclick="window.location.href='{{ route('borrowings.index', ['status' => 'OVERDUE']) }}'">
                <div class="card-body p-2 d-flex flex-column justify-content-center align-items-center text-center">
                    <h2 class="fw-bold mb-0">{{ number_format($overdueBorrowingsCount) }}</h2>
                    <small>Overdue Items</small>
                </div>
            </div>
        </div>
        <!-- Damaged Items -->
        <div class="col-md-4 col-lg-2">
            <div class="card bg-dark text-white h-100 shadow-sm" style="cursor: pointer;" onclick="window.location.href='{{ route('damaged-items.index') }}'">
                <div class="card-body p-2 d-flex flex-column justify-content-center align-items-center text-center">
                    <h2 class="fw-bold mb-0">{{ number_format($damagedItemsCount) }}</h2>
                    <small>Damaged Items</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 1 -->
    <div class="row mb-4">
        <!-- Stock Level by Category -->
        <div class="col-lg-6 mb-4 mb-lg-0">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary" id="categoryChartTitle">Stock Level by Category</h6>
                    <button class="btn btn-sm btn-outline-secondary" id="resetCategoryChart" style="display: none;">Back to Categories</button>
                </div>
                <div class="card-body">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>
        <!-- Stock In vs Out Trend -->
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Stock In vs Stock Out (Trends)</h6>
                </div>
                <div class="card-body">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 2 -->
    <div class="row mb-4">
        <!-- Stock Status Pie -->
        <div class="col-lg-4 mb-4 mb-lg-0">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Stock Status</h6>
                </div>
                <div class="card-body">
                    <div style="height: 250px; position: relative;">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <!-- Top 10 Most Used Items -->
        <div class="col-lg-8">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Top 10 Most Used Items</h6>
                </div>
                <div class="card-body">
                     <canvas id="topItemsChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Section -->
    <div class="row">
        <!-- Low Stock Alert Panel -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i> Low Stock Alerts</h6>
                    <a href="{{ route('low-stock.index') }}" class="btn btn-sm btn-link">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Item</th>
                                    <th>Qty</th>
                                    <th>Reorder</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($lowStockItems as $item)
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-truncate" style="max-width: 200px;" title="{{ $item->item_name }}">{{ $item->item_name }}</div>
                                            <small class="text-muted">{{ $item->sku }}</small>
                                        </td>
                                        <td class="fw-bold {{ $item->current_quantity == 0 ? 'text-danger' : '' }}">{{ $item->current_quantity }}</td>
                                        <td>{{ $item->reorder_level }}</td>
                                        <td>
                                            @if($item->current_quantity == 0)
                                                <span class="badge bg-danger">Out of Stock</span>
                                            @elseif($item->current_quantity < $item->reorder_level)
                                                <span class="badge bg-danger">Critical</span>
                                            @else
                                                <span class="badge bg-warning text-dark">Low</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-3">No low stock items found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 font-weight-bold text-primary"><i class="bi bi-clock-history me-1"></i> Recent Activity</h6>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @forelse($recentActivities as $activity)
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1 text-truncate" style="max-width: 250px;">{{ $activity->item->item_name ?? 'Unknown Item' }}</h6>
                                    <small class="text-muted">{{ $activity->date_created->diffForHumans() }}</small>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        @if($activity->type == 'IN')
                                            <span class="badge bg-success"><i class="bi bi-arrow-down"></i> Stock In</span>
                                        @elseif($activity->type == 'OUT')
                                            <span class="badge bg-secondary"><i class="bi bi-arrow-up"></i> Stock Out</span>
                                        @else
                                            <span class="badge bg-info">{{ $activity->type }}</span>
                                        @endif
                                        <small class="text-muted ms-2">{{ $activity->item->sku ?? '' }}</small>
                                    </div>
                                    <small>Qty: 1</small>
                                </div>
                            </div>
                        @empty
                            <div class="p-3 text-center text-muted">No recent activity.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // --- 1. Category Chart ---
        const catCtx = document.getElementById('categoryChart').getContext('2d');
        const originalCatData = @json($stockByCategory);
        let categoryChart;

        // Function to render chart
        function renderCategoryChart(labels, stockData, issuedData, title) {
            if (categoryChart) {
                categoryChart.destroy();
            }

            categoryChart = new Chart(catCtx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Stock Quantity',
                            data: stockData,
                            backgroundColor: 'rgba(54, 162, 235, 0.6)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Issued Units',
                            data: issuedData,
                            backgroundColor: 'rgba(255, 159, 64, 0.6)',
                            borderColor: 'rgba(255, 159, 64, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    scales: { y: { beginAtZero: true } },
                    plugins: {
                        title: {
                            display: !!title,
                            text: title || ''
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                        }
                    },
                    interaction: {
                        mode: 'nearest',
                        axis: 'x',
                        intersect: false
                    },
                    onClick: (e) => {
                        const points = categoryChart.getElementsAtEventForMode(e, 'nearest', { intersect: true }, true);
                        if (points.length) {
                            const firstPoint = points[0];
                            
                            // Check if we are in category view (check if button is hidden)
                            if (document.getElementById('resetCategoryChart').style.display === 'none') {
                                // Find category ID using index (safer than name)
                                const category = originalCatData[firstPoint.index];
                                if (category) {
                                    fetchItemsByCategory(category.category_id);
                                }
                            }
                        }
                    },
                    onHover: (event, chartElement) => {
                        event.native.target.style.cursor = chartElement[0] ? 'pointer' : 'default';
                    }
                }
            });
        }

        // Initial render
        renderCategoryChart(
            originalCatData.map(d => d.category_name),
            originalCatData.map(d => d.total_qty),
            originalCatData.map(d => d.issued_qty),
            'Stock Level by Category'
        );

        // Fetch Items Function
        function fetchItemsByCategory(categoryId) {
            fetch(`{{ route('dashboard.items-by-category') }}?category_id=${categoryId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                        return;
                    }
                    
                    // Update Chart
                    renderCategoryChart(
                        data.items.map(i => i.item_name),
                        data.items.map(i => i.current_quantity),
                        data.items.map(i => i.issued_qty),
                        `Stock Level: ${data.category_name}`
                    );

                    // Update Title and Show Back Button
                    document.getElementById('categoryChartTitle').innerText = `Stock Level: ${data.category_name}`;
                    document.getElementById('resetCategoryChart').style.display = 'block';
                })
                .catch(error => console.error('Error:', error));
        }

        // Back Button Click
        document.getElementById('resetCategoryChart').addEventListener('click', function() {
            renderCategoryChart(
                originalCatData.map(d => d.category_name),
                originalCatData.map(d => d.total_qty),
                originalCatData.map(d => d.issued_qty),
                'Stock Level by Category'
            );
            document.getElementById('categoryChartTitle').innerText = 'Stock Level by Category';
            this.style.display = 'none';
        });

        // --- 2. Trends Chart ---
        const trendCtx = document.getElementById('trendChart').getContext('2d');
        const trendData = @json($stockTrends);
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: trendData.map(d => d.month),
                datasets: [
                    {
                        label: 'Stock In',
                        data: trendData.map(d => d.stock_in),
                        borderColor: 'rgba(75, 192, 192, 1)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        tension: 0.3
                    },
                    {
                        label: 'Stock Out',
                        data: trendData.map(d => d.stock_out),
                        borderColor: 'rgba(255, 99, 132, 1)',
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        tension: 0.3
                    }
                ]
            },
            options: {
                responsive: true,
                scales: { y: { beginAtZero: true } }
            }
        });

        // --- 3. Status Chart (Pie) ---
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        const pieData = @json($pieData);
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Normal', 'Low Stock', 'Out of Stock'],
                datasets: [{
                    data: [pieData.Normal, pieData.Low, pieData.Out],
                    backgroundColor: ['#198754', '#ffc107', '#dc3545'],
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });

        // --- 4. Top Items Chart ---
        const topCtx = document.getElementById('topItemsChart').getContext('2d');
        const topData = @json($topItems);
        new Chart(topCtx, {
            type: 'bar',
            data: {
                labels: topData.map(d => d.item_name),
                datasets: [{
                    label: 'Usage Count (Stock Out)',
                    data: topData.map(d => d.usage_count),
                    backgroundColor: 'rgba(153, 102, 255, 0.6)',
                    borderColor: 'rgba(153, 102, 255, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                indexAxis: 'y', // Horizontal bar
                responsive: true,
                scales: { x: { beginAtZero: true } }
            }
        });
    });
</script>
@endpush
