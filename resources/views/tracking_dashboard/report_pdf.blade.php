<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ __('tracking_dashboard.title') }}</title>
        <style>
            body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
            h1 { font-size: 16px; margin: 0 0 8px 0; }
            .meta { color: #444; margin-bottom: 12px; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #ddd; padding: 6px; vertical-align: top; }
            th { background: #f5f5f5; text-align: left; }
        </style>
    </head>
    <body>
        <h1>{{ __('tracking_dashboard.title') }}</h1>
        <div class="meta">{{ __('tracking_dashboard.generated_at') }}: {{ $generatedAt->format('Y-m-d H:i') }}</div>
        <table>
            <thead>
                <tr>
                    <th>{{ __('tracking_dashboard.col_reference') }}</th>
                    <th>{{ __('tracking_dashboard.col_drn') }}</th>
                    <th>{{ __('tracking_dashboard.col_subject') }}</th>
                    <th>{{ __('tracking_dashboard.col_transaction_type') }}</th>
                    <th>{{ __('tracking_dashboard.col_document_type') }}</th>
                    <th>{{ __('tracking_dashboard.col_source') }}</th>
                    <th>{{ __('tracking_dashboard.col_status') }}</th>
                    <th>{{ __('tracking_dashboard.col_uploaded_at') }}</th>
                    <th>{{ __('tracking_dashboard.col_delivery_confirmed') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $r)
                    <tr>
                        <td>{{ $r->document_reference_number }}</td>
                        <td>{{ $r->drn }}</td>
                        <td>{{ $r->subject }}</td>
                        <td>
                            @if((int) $r->transaction_type === 1)
                                {{ __('tracking_dashboard.incoming') }}
                            @elseif((int) $r->transaction_type === 2)
                                {{ __('tracking_dashboard.outgoing') }}
                            @endif
                        </td>
                        <td>{{ $r->document_type_name }}</td>
                        <td>{{ $r->source_name }}</td>
                        <td>{{ $r->current_status }}</td>
                        <td>{{ $r->created_at ? \Illuminate\Support\Carbon::parse($r->created_at)->format('Y-m-d H:i') : '' }}</td>
                        <td>{{ isset($r->delivery_confirmed_at) && $r->delivery_confirmed_at ? \Illuminate\Support\Carbon::parse($r->delivery_confirmed_at)->format('Y-m-d H:i') : '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </body>
</html>
