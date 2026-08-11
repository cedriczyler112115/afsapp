<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Monthly Transactions Report - Print</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12pt;
            line-height: 1.5;
            color: #000;
            background-color: #f0f0f0;
            margin: 0;
            padding: 20px;
        }
        .page {
            width: 300mm;
            min-height: 297mm;
            padding: 20mm;
            margin: 0 auto;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            position: relative;
            box-sizing: border-box;
        }
        .table-custom {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 11pt;
        }
        .table-custom th, .table-custom td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            vertical-align: top;
        }
        .table-custom th {
            background-color: #f8f9fa !important;
            font-weight: bold;
            text-align: center;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
            position: relative;
            min-height: 120px;
        }
        .header-logos {
            position: absolute;
            left: 0;
            top: 0;
            display: flex;
            align-items: center;
            height: 100%;
        }
        .header-logos img {
            height: 40px;
            width: auto;
            margin-right: 10px;
            margin-top: 10px;
        }
        .header h2 {
            margin: 0;
            font-weight: bold;
            font-size: 18pt;
            text-transform: uppercase;
        }
        .header p {
            margin: 0;
            font-size: 10pt;
        }
        .meta-info {
            margin-bottom: 20px;
        }
        .cert-text {
            text-align: justify;
            margin-bottom: 20px;
        }
        .signatures {
            margin-top: 50px;
            page-break-inside: avoid;
        }
        .sig-block {
            float: left;
            width: 45%;
        }
        .sig-block.right {
            float: right;
            text-align: right;
        }
        .sig-header {
            margin-bottom: 40px;
        }
        .sig-name {
            font-weight: 700;
            text-transform: uppercase;
            text-align: left;
            min-height: 28px;
        }
        .sig-input {
            width: 100%;
            border: 0;
            outline: 0;
            background: transparent;
            font: inherit;
            color: #6c757d;
            font-weight: 600;
            text-transform: uppercase;
            padding: 0;
        }
        .sig-line {
            border-top: 1px solid #ddd;
            margin-top: 6px;
            width: 100%;
            display: inline-block;
        }
        .sig-caption {
            text-align: left;
            margin-top: 2px;
            font-size: 10pt;
        }
        .footer {
            position: absolute;
            bottom: 15mm;
            left: 20mm;
            right: 20mm;
            font-size: 9pt;
            border-top: 1px solid #ccc;
            padding-top: 5px;
            text-align: center;
            color: #555;
        }
        @media print {
            @page {
                size: A4;
                margin: 0;
            }
            body {
                background-color: white;
                margin: 0;
                padding: 0;
            }
            .page {
                width: 100%;
                min-height: auto;
                box-shadow: none;
                margin: 0;
                padding: 15mm;
                page-break-after: always;
            }
            .no-print {
                display: none !important;
            }
            .table-custom th {
                background-color: #f0f0f0 !important;
                -webkit-print-color-adjust: exact;
            }
            .footer {
                position: fixed;
                bottom: 10mm;
            }
        }
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
    </style>
</head>
<body>
    <div class="position-fixed top-0 end-0 p-3 no-print" style="z-index: 1000;">
        <button onclick="window.print()" class="btn btn-primary shadow">
            <i class="bi bi-printer-fill"></i> Print Report
        </button>
        <button onclick="window.close()" class="btn btn-secondary shadow ms-2">
            Close
        </button>
    </div>

    <div class="page">
        <div class="header">
            <div class="header-logos">
                <img src="{{ asset('img/dswd1.png') }}" alt="DSWD" style="margin-top: -100px;">
                <img src="{{ asset('img/4pslogo.jpg') }}" style="margin-top: -115px;" alt="4Ps">
                <img src="{{ asset('img/bagongpilipinas1.png') }}" style="margin-top: -115px;" alt="Bagong Pilipinas">
            </div>

            <p>Department of Social Welfare and Development</p>
            <p>Pantawid Pamilyang Pilipino Program</p>
            <p>Field Office CARAGA</p>
            <h6><b>4Ps Storage Inventory System</b></h6><br>
            <h5>MONTHLY TRANSACTIONS REPORT</h5>
        </div>

        <div class="meta-info">
            <table class="table table-borderless">
                <tr>
                    <td><strong>Year:</strong> {{ $year }}</td>
                    <td><strong>Month:</strong> {{ \Carbon\Carbon::create(2000, $month, 1)->format('F') }}</td>
                    <td><strong>Category:</strong> {{ $category?->category_name ?? 'All Categories' }}</td>
                </tr>
                <tr>
                    <td><strong>Total Records:</strong> {{ number_format($transactions->count()) }}</td>
                    <td><strong>Date Printed:</strong> {{ $printedAt->format('F d, Y h:i A') }}</td>
                    <td colspan="2"><strong>Printed By:</strong> {{ $printedBy?->name ?? auth()->user()->name }}</td>
                </tr>
            </table>
        </div>

        <div class="cert-text">
            <p>This report summarizes the storage inventory for the selected month, including the available stock, inventory movements, and other relevant inventory details. <br><br>Below is the list of transactions recorded for the month of {{ \Carbon\Carbon::create(2000, $month, 1)->format('F') }} {{ $year }} for monthly reporting and monitoring purposes.</p>
        </div>

        <table class="table-custom">
            <thead>
                <tr>
                    <th style="width: 7%">No.</th>
                    <th style="width: 13%">Date & Time</th>
                    <th style="width: 10%">Type</th>
                    <th style="width: 18%">Item</th>
                    <th style="width: 13%">Category</th>
                    <th style="width: 12%">Unit / Serial</th>
                    <th style="width: 10%">Qty</th>
                    <th style="width: 12%">Party</th>
                    <th style="width: 15%">Recorded By</th>
                    <th style="width: 12%">Reference</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $i => $transaction)
                    <tr>
                        <td style="text-align: center;">{{ $i + 1 }}</td>
                        <td>
                            <div>{{ $transaction->date_created?->format('M d, Y') }}</div>
                            <small>{{ $transaction->date_created?->format('h:i A') }}</small>
                        </td>
                        <td style="text-align: center;">{{ $transaction->type }}</td>
                        <td>{{ $transaction->item?->item_name ?? 'Deleted item' }}@if($transaction->item?->sku)<br><small>SKU: {{ $transaction->item->sku }}</small>@endif</td>
                        <td>{{ $transaction->item?->category?->category_name ?? 'Uncategorized' }}</td>
                        <td>
                            @if((int) $transaction->unit_id > 0)
                                <div>{{ $transaction->unit?->full_code ?? '#'.$transaction->unit_id }}</div>
                                @if($transaction->unit?->serial)<small>{{ $transaction->unit->serial }}</small>@endif
                            @else
                                Bulk / non-serialized
                            @endif
                        </td>
                        <td style="text-align: center;">{{ number_format($transaction->transaction_quantity) }}@if($transaction->type === 'OUT')<br><small>PC/S · {{ ($transaction->issue_mode ?? 'BOX') === 'BOX' ? 'Box' : 'By piece' }}</small>@endif</td>
                        <td>
                            <div>{{ $transaction->party_name }}</div>
                            <small>{{ $transaction->party_role }}</small>
                        </td>
                        <td>{{ $transaction->creator?->name ?? 'Unknown' }}</td>
                        <td>{{ $transaction->source_reference ?? 'Transaction #'.$transaction->id }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" style="text-align: center;">No transactions found for the selected filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="signatures clearfix">
            <div class="sig-block">
                <div class="sig-header">Prepared by:</div>
                <div class="sig-name">{{ $printedBy?->name ?? auth()->user()->name }}</div>
                <div class="sig-line"></div>
                <div class="sig-caption">Authorized Personnel</div>
            </div>

            <div class="sig-block right">
                <div class="sig-header" style="text-align: left;">Approved by:</div>
                <input type="text" class="sig-input" value="{{ old('received_conformed_by') }}" placeholder="Name of your supervisor" aria-label="Write the accountable person">
                <div class="sig-line"></div>
                <div class="sig-caption">Recipient Signature over Printed Name</div>
            </div>
        </div>

        <div class="footer" style="margin-bottom: -20px;">
            Generated by 4Ps Storage Inventory System | {{ now()->format('Y-m-d H:i:s') }}
        </div>
    </div>
</body>
</html>
