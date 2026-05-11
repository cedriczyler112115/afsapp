<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Certification of Issuance - Print</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* General Reset & Typography */
        body {
            font-family: 'arial', Times, serif;
            font-size: 12pt;
            line-height: 1.5;
            color: #000;
            background-color: #f0f0f0; /* Grey background for screen preview */
            margin: 0;
            padding: 20px;
        }

        /* A4 Page Simulation for Screen */
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

        /* Table Styling */
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
            background-color: #f8f9fa !important; /* Force light grey in print if background printing enabled */
            font-weight: bold;
            text-align: center;
        }

        /* Header & Footer */
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
            position: relative; /* For absolute positioning of logos */
            min-height: 120px; /* Ensure enough height for logos */
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
            height: 40px; /* Adjust as needed */
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
        .meta-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }

        .cert-text {
            text-align: justify;
            margin-bottom: 20px;
        }

        /* Signatures */
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
            text-align: right; /* Align content to right, but text inside generic left/center? */
        }
        .sig-line {
            border-top: 1px solid #ddd;
            margin-top: 40px;
            width: 100%;
            display: inline-block;
        }
        
        /* Footer */
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

        /* Print Media Queries */
        @media print {
            @page {
                size: A4;
                margin: 0; /* We handle margins in .page padding */
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
                padding: 15mm; /* Minimum 10mm requested, using 15mm for safety */
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

        /* Utilities */
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
    </style>
</head>
<body>

    <!-- Floating Action Buttons for Screen -->
    <div class="position-fixed top-0 end-0 p-3 no-print" style="z-index: 1000;">
        <button onclick="window.print()" class="btn btn-primary shadow">
            <i class="bi bi-printer-fill"></i> Print Certification
        </button>
        <button onclick="window.close()" class="btn btn-secondary shadow ms-2">
            Close
        </button>
    </div>

    <div class="page">
        <!-- Header -->
        <div class="header">
            <div class="header-logos">
                <img src="{{ asset('img/dswd1.png') }}" alt="DSWD" style="margin-top: -100px;">   
                <img src="{{ asset('img/4pslogo.jpg') }}" style="margin-top: -115px;" alt="Bagong Pilipinas">
                <img src="{{ asset('img/bagongpilipinas1.png') }}" style="margin-top: -115px;" alt="Bagong Pilipinas">
                
                
            </div>
            
            <p>Department of Social Welfare and Development</p>
            <p>Pantawid Pamilyang Pilipino Program</p>
            <p>Field Office CARAGA</p>

            <h6><b>4Ps Storage Inventory System</b></h6><br>
            <h5>CERTIFICATION OF ISSUANCE</h5>
        </div>

        <!-- Meta Info -->
        <div class="meta-info">
            <table class="table table-borderless">
                <tr>
                    <td style="width: auto"><strong>Purpose:</strong></td>
                    <td>{{ $group->purpose }}</td>
                    <td style="width: 150px"><strong>Date Printed:</strong></td>
                    <td>{{ $group->date_printed->format('F d, Y h:i A') }}</td>
                </tr>
                <tr>
                    <td style="width: 150px"><strong>Control No:</strong></td>
                    <td>{{ str_pad($group->id, 6, '0', STR_PAD_LEFT) }}</td>
                    <td><strong>Printed By:</strong></td>
                    <td>{{ Auth::user()->name }}</td>
                </tr>                
            </table>



        </div>

        <!-- Certification Text -->
        <div class="cert-text">
            <p>This is to certify that the following items have been issued in good condition, are free from defects, and are within their valid expiration dates at the time of issuance.</p>
        </div>

        <!-- Items Table -->
        <table class="table-custom">
            <thead>
                <tr>
                    <th style="width: 5%">No.</th>
                    <th style="width: 25%">Item Name</th>
                    <th style="width: 20%">Serial/Code</th>
                    <th style="width: 15%">Category</th>
                    <th style="width: 15%">Date Released</th>
                </tr>
            </thead>
            <tbody>
                @php $counter = 1; @endphp
                @foreach($group->issuances as $issuance)
                    @foreach($issuance->itemUnits as $unit)
                    <tr>
                        <td style="text-align: center;">{{ $counter++ }}</td>
                        <td>{{ $unit->item->item_name }}</td>
                        <td>{{ $unit->serial ?? $unit->full_code }}</td>
                        <td>{{ $unit->item->category->category_name ?? '-' }}</td>
                        <td style="text-align: center;">{{ $issuance->date_issued->format('Y-m-d') }}</td>
                    </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
        <div class="cert-text">
            <p>All items above were properly inspected prior to release and were found to be safe, functional, and suitable for official use. The recipient acknowledges receipt of the items in satisfactory condition and accepts responsibility for their proper use, safekeeping, and handling in accordance with office policies and applicable regulations.</p>
            <p>This certification is issued to attest to the quality, condition, and validity of the issued items.</p>
        </div>
        <!-- Signatures -->
        <div class="signatures clearfix">
            <div class="sig-block">
                <p>Issued & Certified by:</p>
                <br><br><br>
                <div class="text-center">
                    <span class="fw-bold text-uppercase" style="margin-bottom: 10px;">{{ Auth::user()->name }}</span>
                    <div class="sig-line" style="margin-top: -10px"></div>
                    <small>Authorized Personnel</small>
                </div>
            </div>

            <div class="sig-block right">
                <p style="text-align: left;">Received & Conformed by:</p>
                <br><br><br>
                <div class="text-center">
                    <span class="fw-bold text-uppercase" style="margin-bottom: 10px;"><input type="text" id="received_conformed_by" name="received_conformed_by" value="{{ $group->received_conformed_by }}" class="no-border" style="text-align: center;width: 400px;border:0;font-weight: bold;" placeholder="Write the accountable person"></span>
                    <div class="sig-line" style="margin-top: -10px"></div>
                    <small>Recipient Signature over Printed Name</small>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer" style="margin-bottom: -20px;">
            Generated by 4Ps Storage Inventory System | {{ now()->format('Y-m-d H:i:s') }}
        </div>
    </div>

    <!-- Bootstrap Icons (for the print button) -->
    
    
    <script>
        // Auto-print option (optional, user can click button)
        // window.onload = function() { window.print(); }

        const receiverInput = document.getElementById('received_conformed_by');
        
        function saveReceiver() {
            const value = receiverInput.value;
            
            fetch("{{ route('stock-out.group.update-receiver', $group->id) }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    received_conformed_by: value
                })
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    console.log('Receiver updated successfully');
                }
            })
            .catch(error => console.error('Error saving receiver:', error));
        }

        receiverInput.addEventListener('blur', saveReceiver);
        receiverInput.addEventListener('change', saveReceiver);
    </script>
</body>
</html>
