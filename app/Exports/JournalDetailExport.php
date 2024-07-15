<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\FromView;
use Carbon\Carbon;
use App\Models\Transaction_transfer;
use App\Models\Transaction_send;
use App\Models\Transaction_receive;


class JournalDetailExport implements FromView, ShouldAutoSize
{
    protected $startDate;
    protected $endDate;
    protected $type;
    protected $search;
    protected $sort;
    protected $order;

    public function __construct($startDate, $endDate, $type, $search, $sort, $order)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->type = $type;
        $this->search = $search;
        $this->sort = $sort;
        $this->order = $order;
    }

    public function view(): View
    {
        $transactionDetails = [];

        // Define variables as empty collections
        $transactionTransfers = collect();
        $transactionReceives = collect();
        $transactionSends = collect();

        // Check for correct date format and parse dates
        if ($this->startDate) {
            $startDate = Carbon::createFromFormat('Y-m-d', $this->startDate)->startOfDay();
        } else {
            $startDate = null;
        }
        if ($this->endDate) {
            $endDate = Carbon::createFromFormat('Y-m-d', $this->endDate)->endOfDay();
        } else {
            $endDate = null;
        }

        // Logic for fetching transactions based on transaction type
        if ($this->type === 'transaction_transfer' || empty($this->type)) {
            $transactionTransfers = Transaction_transfer::with(['transferAccount', 'depositAccount'])
                ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
                    return $query->whereBetween('date', [$startDate, $endDate]);
                })
                ->when($this->search, function ($query, $search) {
                    return $query->where('no_transaction', 'LIKE', "%{$search}%");
                })
                ->when($this->sort && $this->order, function ($query) {
                    return $query->orderBy($this->sort, $this->order);
                })
                ->get();

            foreach ($transactionTransfers as $transaction) {
                $transferAccount = $transaction->transferAccount;
                $depositAccount = $transaction->depositAccount;

                $transactionDetails[] = [
                    [
                        'no_transaction' => $transaction->no_transaction ?? 'N/A',
                        'account_number' => $transferAccount->account_no,
                        'account_name' => $transferAccount->name,
                        'debit' => 0,
                        'credit' => $transaction->amount > 0 ? $transaction->amount : 0,
                        'date' => $transaction->date,
                        'description' => $transaction->description,
                        'created_at' => $transaction->created_at
                    ],
                    [
                        'no_transaction' => $transaction->no_transaction ?? 'N/A',
                        'account_number' => $depositAccount->account_no,
                        'account_name' => $depositAccount->name,
                        'debit' => $transaction->amount > 0 ? $transaction->amount : 0,
                        'credit' => 0,
                        'date' => $transaction->date,
                        'description' => $transaction->description,
                        'created_at' => $transaction->created_at
                    ]
                ];
            }
        }

        if ($this->type === 'transaction_send' || empty($this->type)) {
            $transactionSends = Transaction_send::with(['transferAccount', 'depositAccount'])
                ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
                    return $query->whereBetween('date', [$startDate, $endDate]);
                })
                ->get();

            foreach ($transactionSends as $transaction) {
                $transferAccount = $transaction->transferAccount;
                $depositAccount = $transaction->depositAccount;

                $transactionDetails[] = [
                    [
                        'no_transaction' => $transaction->no_transaction ?? 'N/A',
                        'account_number' => $transferAccount->account_no,
                        'account_name' => $transferAccount->name,
                        'debit' => 0,
                        'credit' => $transaction->amount > 0 ? $transaction->amount : 0,
                        'date' => $transaction->date,
                        'description' => $transaction->description,
                        'created_at' => $transaction->created_at
                    ],
                    [
                        'no_transaction' => $transaction->no_transaction ?? 'N/A',
                        'account_number' => $depositAccount->account_no,
                        'account_name' => $depositAccount->name,
                        'debit' => $transaction->amount > 0 ? $transaction->amount : 0,
                        'credit' => 0,
                        'date' => $transaction->date,
                        'description' => $transaction->description,
                        'created_at' => $transaction->created_at
                    ]
                ];
            }
        }

        if ($this->type === 'transaction_receive' || empty($this->type)) {
            $transactionReceives = Transaction_receive::with(['transferAccount', 'depositAccount'])
                ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
                    return $query->whereBetween('date', [$startDate, $endDate]);
                })
                ->get();

            foreach ($transactionReceives as $transaction) {
                $transferAccount = $transaction->transferAccount;
                $depositAccount = $transaction->depositAccount;

                $transactionDetails[] = [
                    [
                        'no_transaction' => $transaction->no_transaction ?? 'N/A',
                        'account_number' => $transferAccount->account_no,
                        'account_name' => $transferAccount->name,
                        'debit' => 0,
                        'credit' => $transaction->amount > 0 ? $transaction->amount : 0,
                        'date' => $transaction->date,
                        'description' => $transaction->description,
                        'created_at' => $transaction->created_at
                    ],
                    [
                        'no_transaction' => $transaction->no_transaction ?? 'N/A',
                        'account_number' => $depositAccount->account_no,
                        'account_name' => $depositAccount->name,
                        'debit' => $transaction->amount > 0 ? $transaction->amount : 0,
                        'credit' => 0,
                        'date' => $transaction->date,
                        'description' => $transaction->description,
                        'created_at' => $transaction->created_at
                    ]
                ];
            }
        }

        $transactionDetails = session('transactionDetails', []);


        return view('components.journal.detail-excel', [
            'transactionDetails' => $transactionDetails,
        ]);
    }
}
