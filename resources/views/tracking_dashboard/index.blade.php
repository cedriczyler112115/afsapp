@extends('layouts.app')

@section('title', __('tracking_dashboard.title'))

@section('content')
<div class="container-fluid px-4">
    <div id="tracking-dashboard-alerts" class="mb-3" aria-live="polite" aria-atomic="true"></div>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3">
        <div>
            <h1 class="h3 mb-0 text-gray-800">{{ __('tracking_dashboard.title') }}</h1>
            <div class="text-secondary small">{{ __('tracking_dashboard.subtitle') }}</div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a class="btn btn-outline-secondary btn-sm" id="export-csv" href="#" role="button">
                <i class="bi bi-filetype-csv me-1"></i>{{ __('tracking_dashboard.export_csv') }}
            </a>
            <a class="btn btn-outline-secondary btn-sm" id="export-pdf" href="#" role="button">
                <i class="bi bi-filetype-pdf me-1"></i>{{ __('tracking_dashboard.export_pdf') }}
            </a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex flex-column flex-lg-row gap-2 justify-content-between align-items-start align-items-lg-center">
            <div class="fw-semibold">{{ __('tracking_dashboard.filters') }}</div>
            <div class="text-secondary small" id="last-refreshed" aria-live="polite"></div>
        </div>
        <div class="card-body">
            <form id="filters-form" class="row g-2 align-items-end" autocomplete="off">
                <div class="col-12 col-md-4 col-lg-3">
                    <label for="q" class="form-label small mb-1">{{ __('tracking_dashboard.search') }}</label>
                    <input id="q" name="q" type="text" class="form-control form-control-sm" placeholder="{{ __('tracking_dashboard.search_placeholder') }}">
                </div>

                <div class="col-6 col-md-2 col-lg-2">
                    <label for="date_from" class="form-label small mb-1">{{ __('tracking_dashboard.date_from') }}</label>
                    <input id="date_from" name="date_from" type="date" class="form-control form-control-sm">
                </div>

                <div class="col-6 col-md-2 col-lg-2">
                    <label for="date_to" class="form-label small mb-1">{{ __('tracking_dashboard.date_to') }}</label>
                    <input id="date_to" name="date_to" type="date" class="form-control form-control-sm">
                </div>

                <div class="col-6 col-md-2 col-lg-2">
                    <label for="transaction_type" class="form-label small mb-1">{{ __('tracking_dashboard.transaction_type') }}</label>
                    <select id="transaction_type" name="transaction_type" class="form-select form-select-sm">
                        <option value="">{{ __('tracking_dashboard.all') }}</option>
                        <option value="1">{{ __('tracking_dashboard.incoming') }}</option>
                        <option value="2">{{ __('tracking_dashboard.outgoing') }}</option>
                    </select>
                </div>

                <div class="col-6 col-md-2 col-lg-2">
                    <label for="status" class="form-label small mb-1">{{ __('tracking_dashboard.status') }}</label>
                    <select id="status" name="status" class="form-select form-select-sm">
                        <option value="">{{ __('tracking_dashboard.all') }}</option>
                        <option value="RECEIVED">{{ __('tracking_dashboard.status_received') }}</option>
                        <option value="FORWARDED">{{ __('tracking_dashboard.status_forwarded') }}</option>
                        <option value="ARCHIVED">{{ __('tracking_dashboard.status_archived') }}</option>
                    </select>
                </div>

                <div class="col-12 col-md-4 col-lg-3">
                    <label for="document_type_id" class="form-label small mb-1">{{ __('tracking_dashboard.document_type') }}</label>
                    <select id="document_type_id" name="document_type_id" class="form-select form-select-sm">
                        <option value="">{{ __('tracking_dashboard.all') }}</option>
                        @foreach($types as $t)
                            <option value="{{ $t->id }}">{{ $t->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-4 col-lg-3">
                    <label for="personnel_id" class="form-label small mb-1">{{ __('tracking_dashboard.assigned_personnel') }}</label>
                    <select id="personnel_id" name="personnel_id" class="form-select form-select-sm" @if(count($users) === 0) disabled @endif>
                        <option value="">{{ __('tracking_dashboard.all') }}</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </select>
                    @if(count($users) === 0)
                        <div class="form-text">{{ __('tracking_dashboard.assigned_personnel_limited') }}</div>
                    @endif
                </div>

                <div class="col-6 col-md-2 col-lg-2">
                    <label for="per_page" class="form-label small mb-1">{{ __('tracking_dashboard.per_page') }}</label>
                    <select id="per_page" name="per_page" class="form-select form-select-sm">
                        <option value="10">10</option>
                        <option value="25" selected>25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>

                <div class="col-6 col-md-2 col-lg-2 d-grid">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="reset-filters">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>{{ __('tracking_dashboard.reset') }}
                    </button>
                </div>
            </form>

            <div class="small text-secondary mt-2">
                {{ __('tracking_dashboard.auto_refresh') }}
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-lg-4">
            <div class="card h-100">
                <div class="card-header fw-semibold">{{ __('tracking_dashboard.chart_status_distribution') }}</div>
                <div class="card-body">
                    <canvas id="statusChart" aria-label="{{ __('tracking_dashboard.chart_status_distribution') }}" role="img"></canvas>
                    <div class="visually-hidden" id="statusChartSummary" aria-live="polite"></div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card h-100">
                <div class="card-header fw-semibold">{{ __('tracking_dashboard.chart_document_types') }}</div>
                <div class="card-body">
                    <canvas id="typeChart" aria-label="{{ __('tracking_dashboard.chart_document_types') }}" role="img"></canvas>
                    <div class="visually-hidden" id="typeChartSummary" aria-live="polite"></div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card h-100">
                <div class="card-header fw-semibold">{{ __('tracking_dashboard.chart_processing_time') }}</div>
                <div class="card-body">
                    <canvas id="processingChart" aria-label="{{ __('tracking_dashboard.chart_processing_time') }}" role="img"></canvas>
                    <div class="small text-secondary mt-2" id="avgProcessing"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
            <div class="fw-semibold">{{ __('tracking_dashboard.documents') }}</div>
            <div class="text-secondary small" id="tableMeta"></div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-bordered align-middle mb-0" aria-label="{{ __('tracking_dashboard.documents') }}">
                    <caption class="visually-hidden">{{ __('tracking_dashboard.documents') }}</caption>
                    <thead class="table-light">
                        <tr>
                            <th scope="col" style="width: 160px;">{{ __('tracking_dashboard.col_reference') }}</th>
                            <th scope="col" style="width: 160px;">{{ __('tracking_dashboard.col_drn') }}</th>
                            <th scope="col" style="width: 220px;">{{ __('tracking_dashboard.col_subject') }}</th>
                            <th scope="col" style="width: 140px;">{{ __('tracking_dashboard.col_transaction_type') }}</th>
                            <th scope="col" style="width: 180px;">{{ __('tracking_dashboard.col_document_type') }}</th>
                            <th scope="col" style="width: 180px;">{{ __('tracking_dashboard.col_source') }}</th>
                            <th scope="col" style="width: 160px;">{{ __('tracking_dashboard.col_status') }}</th>
                            <th scope="col" style="width: 180px;">{{ __('tracking_dashboard.col_assigned_to') }}</th>
                            <th scope="col" style="width: 160px;">{{ __('tracking_dashboard.col_uploaded_at') }}</th>
                            <th scope="col" style="width: 160px;">{{ __('tracking_dashboard.col_delivery_confirmed') }}</th>
                        </tr>
                    </thead>
                    <tbody id="rowsBody">
                        <tr>
                            <td colspan="10" class="text-center py-4 text-secondary">{{ __('tracking_dashboard.loading') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center">
            <button class="btn btn-sm btn-outline-secondary" id="prevPage" type="button">{{ __('tracking_dashboard.prev') }}</button>
            <div class="small text-secondary" id="pageInfo"></div>
            <button class="btn btn-sm btn-outline-secondary" id="nextPage" type="button">{{ __('tracking_dashboard.next') }}</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    (function () {
        const routes = {
            data: @json(route('tracking-dashboard.data')),
            exportCsv: @json(route('tracking-dashboard.export.csv')),
            exportPdf: @json(route('tracking-dashboard.export.pdf')),
            showDoc: @json(route('incoming-documents.index')),
        };

        let statusChart = null;
        let typeChart = null;
        let processingChart = null;

        let currentPage = 1;
        let refreshTimer = null;
        let inFlight = false;

        function alertHtml(kind, message) {
            const safeKind = kind === 'danger' ? 'danger' : (kind === 'success' ? 'success' : (kind === 'warning' ? 'warning' : 'info'));
            return `
                <div class="alert alert-${safeKind} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('tracking_dashboard.close') }}"></button>
                </div>
            `;
        }

        function setAlert(kind, message) {
            $('#tracking-dashboard-alerts').html(alertHtml(kind, message));
        }

        function clearAlert() {
            $('#tracking-dashboard-alerts').html('');
        }

        function getParams() {
            const form = document.getElementById('filters-form');
            const fd = new FormData(form);
            const params = new URLSearchParams();
            for (const [k, v] of fd.entries()) {
                if (v !== null && String(v).trim() !== '') params.set(k, String(v));
            }
            params.set('page', String(currentPage));
            return params;
        }

        function setExports() {
            const base = getParams();
            base.delete('page');
            const csv = routes.exportCsv + '?' + base.toString();
            const pdf = routes.exportPdf + '?' + base.toString();
            $('#export-csv').attr('href', csv);
            $('#export-pdf').attr('href', pdf);
        }

        function toSummary(labels, values) {
            const parts = [];
            for (let i = 0; i < labels.length; i++) {
                parts.push(`${labels[i]}: ${values[i]}`);
            }
            return parts.join(', ');
        }

        function renderCharts(data) {
            const status = data.charts.status || { labels: [], values: [] };
            const types = data.charts.types || { labels: [], values: [] };
            const buckets = data.charts.processing_buckets || { labels: [], values: [] };

            $('#statusChartSummary').text(toSummary(status.labels, status.values));
            $('#typeChartSummary').text(toSummary(types.labels, types.values));

            const statusCtx = document.getElementById('statusChart').getContext('2d');
            if (statusChart) statusChart.destroy();
            statusChart = new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: status.labels,
                    datasets: [{
                        data: status.values,
                        backgroundColor: ['#0d6efd', '#ffc107', '#6c757d'],
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { position: 'bottom' } }
                }
            });

            const typeCtx = document.getElementById('typeChart').getContext('2d');
            if (typeChart) typeChart.destroy();
            typeChart = new Chart(typeCtx, {
                type: 'bar',
                data: {
                    labels: types.labels,
                    datasets: [{
                        label: @json(__('tracking_dashboard.count')),
                        data: types.values,
                        backgroundColor: '#198754',
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true } }
                }
            });

            const procCtx = document.getElementById('processingChart').getContext('2d');
            if (processingChart) processingChart.destroy();
            processingChart = new Chart(procCtx, {
                type: 'bar',
                data: {
                    labels: buckets.labels,
                    datasets: [{
                        label: @json(__('tracking_dashboard.documents')),
                        data: buckets.values,
                        backgroundColor: '#0dcaf0',
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true } }
                }
            });

            const avg = data.summary && data.summary.average_processing_hours !== null ? data.summary.average_processing_hours : null;
            $('#avgProcessing').text(avg === null ? '' : `{{ __('tracking_dashboard.average_processing_hours') }}: ${avg}`);
        }

        function renderTable(data) {
            const rows = Array.isArray(data.rows) ? data.rows : [];
            const tbody = document.getElementById('rowsBody');
            tbody.innerHTML = '';

            if (rows.length === 0) {
                const tr = document.createElement('tr');
                tr.innerHTML = `<td colspan="10" class="text-center py-4 text-secondary">{{ __('tracking_dashboard.no_results') }}</td>`;
                tbody.appendChild(tr);
                return;
            }

            for (const r of rows) {
                const link = @json(route('incoming-documents.show', ['incoming_document' => 0])).replace(/0$/, String(r.id));
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><a href="${link}" class="text-decoration-none" target="_blank" rel="noopener noreferrer">${escapeHtml(r.document_reference_number || '')}</a></td>
                    <td>${escapeHtml(r.drn || '')}</td>
                    <td>${escapeHtml(r.subject || '')}</td>
                    <td>${escapeHtml(r.transaction_type || '')}</td>
                    <td>${escapeHtml(r.document_type || '')}</td>
                    <td>${escapeHtml(r.source || '')}</td>
                    <td>${escapeHtml(r.current_status || '')}</td>
                    <td>${escapeHtml(r.assigned_to || '')}</td>
                    <td>${escapeHtml(r.uploaded_at || '')}</td>
                    <td>${escapeHtml(r.delivery_confirmed_at || '')}</td>
                `;
                tbody.appendChild(tr);
            }
        }

        function escapeHtml(value) {
            return String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }

        async function loadData() {
            if (inFlight) return;
            inFlight = true;
            clearAlert();
            setExports();

            try {
                const params = getParams();
                const url = routes.data + '?' + params.toString();
                const resp = await fetch(url, {
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                if (!resp.ok) {
                    throw new Error(`HTTP ${resp.status}`);
                }
                const data = await resp.json();
                if (!data || data.success !== true) {
                    throw new Error('Invalid response');
                }

                renderCharts(data);
                renderTable(data);

                const meta = data.meta || {};
                const total = Number(meta.total || 0);
                const per = Number(meta.per_page || 25);
                const page = Number(meta.page || 1);
                const maxPage = Math.max(1, Math.ceil(total / per));

                $('#tableMeta').text(`{{ __('tracking_dashboard.total') }}: ${total}`);
                $('#pageInfo').text(`{{ __('tracking_dashboard.page') }} ${page} / ${maxPage}`);
                $('#prevPage').prop('disabled', page <= 1);
                $('#nextPage').prop('disabled', page >= maxPage);
                $('#last-refreshed').text(`{{ __('tracking_dashboard.last_refreshed') }}: ${new Date().toLocaleTimeString()}`);
            } catch (e) {
                setAlert('danger', `{{ __('tracking_dashboard.errors.load_failed') }} (${escapeHtml(e.message || 'error')})`);
            } finally {
                inFlight = false;
            }
        }

        function resetFilters() {
            document.getElementById('filters-form').reset();
            currentPage = 1;
            loadData();
        }

        function scheduleRefresh() {
            if (refreshTimer) clearInterval(refreshTimer);
            refreshTimer = setInterval(function () {
                if (document.hidden) return;
                loadData();
            }, 30000);
        }

        $(document).ready(function () {
            $('#document_type_id').select2({ theme: 'bootstrap-5', width: '100%', placeholder: '{{ __('tracking_dashboard.all') }}', allowClear: true });
            if (!$('#personnel_id').prop('disabled')) {
                $('#personnel_id').select2({ theme: 'bootstrap-5', width: '100%', placeholder: '{{ __('tracking_dashboard.all') }}', allowClear: true });
            }

            $('#filters-form').on('change', 'input,select', function () {
                currentPage = 1;
                loadData();
            });
            $('#q').on('keydown', function (e) {
                if (e.key !== 'Enter') return;
                e.preventDefault();
                currentPage = 1;
                loadData();
            });

            $('#reset-filters').on('click', resetFilters);
            $('#prevPage').on('click', function () {
                currentPage = Math.max(1, currentPage - 1);
                loadData();
            });
            $('#nextPage').on('click', function () {
                currentPage = currentPage + 1;
                loadData();
            });

            scheduleRefresh();
            loadData();
        });
    })();
</script>
@endpush
