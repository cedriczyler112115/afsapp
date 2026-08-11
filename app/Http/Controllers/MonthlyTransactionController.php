<?php

namespace App\Http\Controllers;

use App\Models\Borrowing;
use App\Models\Category;
use App\Models\Issuance;
use App\Models\ItemReturn;
use App\Models\StockTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MonthlyTransactionController extends Controller
{
    public function index(Request $request)
    {
        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);
        $categoryId = $request->filled('category_id') ? (int) $request->input('category_id') : null;
        $type = strtoupper(trim((string) $request->input('type', '')));
        $perPageInput = $request->input('per_page', 10);
        $perPage = $perPageInput === 'all'
            ? 'all'
            : (in_array((int) $perPageInput, [10, 20, 30, 50, 100], true) ? (int) $perPageInput : 10);

        $availableTypes = StockTransaction::query()
            ->whereNotNull('type')
            ->distinct()
            ->orderBy('type')
            ->pluck('type')
            ->map(fn ($value) => strtoupper(trim((string) $value)))
            ->filter()
            ->values();

        if ($type !== '' && ! $availableTypes->contains($type)) {
            $type = '';
        }

        $transactions = StockTransaction::query()
            ->with([
                'item:item_id,category_id,item_name,sku,unit_id',
                'item.category',
                'item.unit',
                'unit:id,item_id,serial,full_code,issuance_id,pcs_per_unit',
                'issuance.user:id,name',
                'creator:id,name',
            ])
            ->whereYear('date_created', $year)
            ->whereMonth('date_created', $month)
            ->when($categoryId, fn ($query) => $query->whereHas('item', fn ($item) => $item->where('category_id', $categoryId)))
            ->when($type !== '', fn ($query) => $query->where('type', $type))
            ->orderByDesc('date_created')
            ->orderByDesc('id')
            ->when($perPage === 'all', function ($query) {
                return $query->paginate($query->count() ?: 1);
            }, function ($query) use ($perPage) {
                return $query->paginate($perPage);
            })
            ->withQueryString();

        $this->attachSourceDetails($transactions->getCollection());

        $years = StockTransaction::query()
            ->selectRaw('YEAR(date_created) as transaction_year')
            ->distinct()
            ->orderByDesc('transaction_year')
            ->pluck('transaction_year')
            ->filter()
            ->map(fn ($value) => (int) $value);

        if (! $years->contains($year)) {
            $years->push($year);
        }

        return view('monthly_transactions.index', [
            'transactions' => $transactions,
            'categories' => Category::query()->orderBy('category_name')->get(),
            'availableTypes' => $availableTypes,
            'years' => $years->unique()->sortDesc()->values(),
            'selectedYear' => $year,
            'selectedMonth' => $month,
            'selectedCategoryId' => $categoryId,
            'selectedType' => $type,
            'perPage' => $perPage,
        ]);
    }

    public function print(Request $request)
    {
        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);
        $categoryId = $request->filled('category_id') ? (int) $request->input('category_id') : null;
        $type = strtoupper(trim((string) $request->input('type', '')));

        $transactions = StockTransaction::query()
            ->with([
                'item:item_id,category_id,item_name,sku,unit_id',
                'item.category',
                'item.unit',
                'unit:id,item_id,serial,full_code,issuance_id,pcs_per_unit',
                'issuance.user:id,name',
                'creator:id,name',
            ])
            ->whereYear('date_created', $year)
            ->whereMonth('date_created', $month)
            ->when($categoryId, fn ($query) => $query->whereHas('item', fn ($item) => $item->where('category_id', $categoryId)))
            ->when($type !== '', fn ($query) => $query->where('type', $type))
            ->orderByDesc('date_created')
            ->orderByDesc('id')
            ->get();

        $this->attachSourceDetails($transactions);

        return view('monthly_transactions.print', [
            'transactions' => $transactions,
            'year' => $year,
            'month' => $month,
            'category' => $categoryId ? Category::find($categoryId) : null,
            'type' => $type,
            'printedAt' => now(),
            'printedBy' => auth()->user(),
        ]);
    }

    private function attachSourceDetails($transactions): void
    {
        if ($transactions->isEmpty()) {
            return;
        }

        $unitIds = $transactions->pluck('unit_id')->filter(fn ($id) => (int) $id > 0)->unique()->values();
        $itemIds = $transactions->pluck('item_id')->filter()->unique()->values();

        $borrowings = Borrowing::query()
            ->with(['borrower:id,name', 'issuedBy:id,name'])
            ->whereIn('item_id', $itemIds)
            ->where(function ($query) use ($unitIds) {
                $query->whereIn('item_unit_id', $unitIds)->orWhereNull('item_unit_id');
            })
            ->get();

        $returns = ItemReturn::query()
            ->with(['borrowing.borrower:id,name', 'receivedBy:id,name'])
            ->whereIn('item_id', $itemIds)
            ->where(function ($query) use ($unitIds) {
                $query->whereIn('item_unit_id', $unitIds)->orWhereNull('item_unit_id');
            })
            ->get();

        $issuanceIds = $transactions->pluck('issuance_id')
            ->merge($transactions->pluck('unit.issuance_id'))
            ->filter()->unique()->values();
        $issuances = Issuance::query()
            ->with('user:id,name')
            ->whereIn('id', $issuanceIds)
            ->get()
            ->keyBy('id');

        foreach ($transactions as $transaction) {
            $transaction->setAttribute('party_name', null);
            $transaction->setAttribute('party_role', null);
            $transaction->setAttribute('source_reference', null);
            $transaction->setAttribute('transaction_quantity', max(1, (int) ($transaction->quantity ?? 1)));

            if ($transaction->type === 'BORROW') {
                $record = $this->closestRecord($borrowings, $transaction, 'borrow_date', 'item_unit_id');
                if ($record) {
                    $transaction->setAttribute('party_name', $record->borrower?->name);
                    $transaction->setAttribute('party_role', 'Borrower');
                    $transaction->setAttribute('source_reference', 'Borrowing #'.$record->id);
                    $transaction->setAttribute('transaction_quantity', (int) $record->quantity ?: 1);
                }
            } elseif ($transaction->type === 'RETURN') {
                $record = $this->closestRecord($returns, $transaction, 'return_date', 'item_unit_id');
                if ($record) {
                    $transaction->setAttribute('party_name', $record->borrowing?->borrower?->name);
                    $transaction->setAttribute('party_role', 'Returned by');
                    $transaction->setAttribute('source_reference', 'Return #'.$record->id);
                    $transaction->setAttribute('transaction_quantity', (int) $record->quantity ?: 1);
                }
            } elseif (in_array($transaction->type, ['OUT', 'DAMAGED'], true)) {
                $issuance = $transaction->issuance ?: $issuances->get($transaction->issuance_id ?: $transaction->unit?->issuance_id);
                if ($issuance) {
                    $transaction->setAttribute('party_name', $issuance->receiver_name ?: $issuance->user?->name);
                    $transaction->setAttribute('party_role', $transaction->type === 'DAMAGED' ? 'Reported/received by' : 'Requester / recipient');
                    $transaction->setAttribute('source_reference', ($transaction->type === 'DAMAGED' ? 'Damage report #' : 'Issuance #').$issuance->id);
                }
            }

            if (! $transaction->party_name) {
                $transaction->setAttribute('party_name', $transaction->creator?->name ?: 'Not recorded');
                $transaction->setAttribute('party_role', $transaction->type === 'IN' ? 'Stock recorded by' : 'Recorded by');
            }
        }
    }

    private function closestRecord($records, StockTransaction $transaction, string $dateColumn, string $unitColumn)
    {
        $matches = $records->where('item_id', $transaction->item_id);

        if ((int) $transaction->unit_id > 0) {
            $unitMatches = $matches->where($unitColumn, $transaction->unit_id);
            if ($unitMatches->isNotEmpty()) {
                $matches = $unitMatches;
            }
        } else {
            $matches = $matches->filter(fn ($record) => empty($record->{$unitColumn}));
        }

        return $matches->sortBy(function ($record) use ($transaction, $dateColumn) {
            $recordDate = $record->{$dateColumn} ?: $record->created_at;

            return $recordDate
                ? abs(Carbon::parse($recordDate)->diffInSeconds($transaction->date_created, false))
                : PHP_INT_MAX;
        })->first();
    }
}
