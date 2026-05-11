<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Monthly Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'arial', Times, serif;
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
        .sig-line {
            border-top: 1px solid #ddd;
            margin-top: 40px;
            width: 100%;
            display: inline-block;
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
            .screen-only {
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

        .no-border {
            border: 0;
            outline: none;
            box-shadow: none;
            background: transparent;
        }
    </style>
</head>
<body>
    <div class="position-fixed top-0 end-0 p-3 screen-only" style="z-index: 1000;">
        <button type="button" onclick="window.print()" class="btn btn-primary shadow">Print Report</button>
        <button type="button" onclick="window.close()" class="btn btn-secondary shadow ms-2">Close</button>
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
            <h6><b>4Ps Document Tracking System</b></h6><br>
            <h5>MONTHLY REPORT</h5>
        </div>

        <div class="meta-info">
            @php
                $period = trim((string) ($periodLabel ?? ''));
                if ($period === '') {
                    $period = ($generatedAt ?? now())->format('F Y');
                }
                $incomingCount = (int) ($countsByTransactionType[1] ?? 0);
                $outgoingCount = (int) ($countsByTransactionType[2] ?? 0);
                $totalRecords = is_countable($documents ?? []) ? count($documents) : 0;
            @endphp
            <table class="table table-borderless mb-2">
                <tr>
                    <td style="width: 180px"><strong>Period:</strong></td>
                    <td>{{ $period }}</td>
                    <td style="width: 180px"><strong>Generated:</strong></td>
                    <td>{{ ($generatedAt ?? now())->format('F j, Y g:i A') }}</td>
                </tr>
                <tr>
                    <td style="width: 180px"><strong>Total Records:</strong></td>
                    <td>{{ $totalRecords }}</td>
                    <td></td>
                    <td></td>
                </tr>
            </table>

            @if(isset($criteria) && count($criteria) > 0)
                <div class="cert-text mb-2">
                    @foreach($criteria as $c)
                        <div>{{ $c }}</div>
                    @endforeach
                </div>
            @endif

            <div class="cert-text">
                <p>
                    This is to certify that all incoming and outgoing transactions recorded for the month of {{ $period }}
                    have been properly received, processed, and documented in accordance with established procedures. All incoming
                    documents were checked upon receipt and found to be complete, valid, and in acceptable condition at the time of logging. <br><br>Below are the summary report of transactions:
                </p>
            </div>

            <table class="table-custom" aria-label="Table summary of transactions">
                <thead>
                    <tr>
                        <th style="width: 60%;">Transaction Summary</th>
                        <th style="width: 40%;">Count</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Incoming</td>
                        <td style="text-align: center;">{{ $incomingCount }}</td>
                    </tr>
                    <tr>
                        <td>Outgoing</td>
                        <td style="text-align: center;">{{ $outgoingCount }}</td>
                    </tr>
                    <tr>
                        <td><strong>Total</strong></td>
                        <td style="text-align: center;"><strong>{{ $totalRecords }}</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <table class="table-custom" aria-label="Summary per document types">
            <thead>
                <tr>
                    <th style="width: 60%;">Document Type</th>
                    <th style="width: 40%;">Count</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $typeCounts = $countsByType ?? collect($documents ?? [])->groupBy(function ($d) {
                        $name = '';
                        if (isset($d->type) && isset($d->type->name)) {
                            $name = (string) $d->type->name;
                        }
                        $trimmed = trim($name);
                        return $trimmed !== '' ? $trimmed : 'Unspecified';
                    })->map(function ($g) {
                        return $g->count();
                    })->sortKeys();
                @endphp
                @forelse($typeCounts as $typeName => $count)
                    <tr>
                        <td>{{ $typeName }}</td>
                        <td style="text-align: center;">{{ $count }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" style="text-align: center;">No records found for the selected criteria.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="cert-text">
            <p>
                All transactions listed above were properly verified and recorded prior to completion and were found to be compliant with
                office standards and operational requirements. The responsible personnel acknowledge that all entries are accurate and that
                proper procedures for handling, processing, and monitoring have been observed.
            </p>
            <p>
                This certification is issued to attest to the accuracy, completeness, and proper management of all incoming and outgoing
                transactions for the period stated.
            </p>
        </div>

        <div class="signatures clearfix">
            <div class="sig-block">
                <p>Prepared By:</p>
                <br>
                <input
                    type="text"
                    name="prepared_by"
                    placeholder="Your name"
                    class="no-border"
                    style="font-weight: bold; text-align: left; width: 100%;"
                >
                <br>
                <input
                    type="text"
                    name="prepared_by_position"
                    placeholder="Position"
                    class="no-border"
                    style="text-align: left; width: 100%;"
                >
            </div>
            <div class="sig-block right">
                <p style="text-align: left;">Approved By:
                <br><br><b>JEHMAYMAH L. MOSCATILES</b><br>4PS - ARPC</p>
            </div>
        </div><br><Br>

        <div class="footer" style="margin-bottom: -20px;">
            Generated by 4Ps Document Tracking System | {{ now()->format('F j, Y g:i A') }}
        </div>
    </div>
</body>
</html>
