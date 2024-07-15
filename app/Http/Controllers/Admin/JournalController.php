<?php

namespace App\Http\Controllers\Admin;

use Exception;
use Carbon\Carbon;

use App\Models\Cash;
use Barryvdh\DomPDF\PDF;
use Illuminate\Http\Request;
use App\Models\Accountnumber;
use App\Imports\JournalImport;
use App\Models\Accountcategory;
use App\Models\Transaction_send;
use Illuminate\Support\Facades\DB;
use App\Models\Transaction_receive;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Transaction_transfer;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Pagination\LengthAwarePaginator;

class JournalController extends Controller
{
    public function indexJournal(Request $request)
    {
        session()->flash('preloader', true);
        session()->flash('page', (object) [
            'page' => 'Journal',
            'child' => 'database Journal',
        ]);

        $selectedItems = $request->id ?? [];

        $form = (object) [
            'sort' => $request->sort ?? 'date', // Default sort by date
            'order' => $request->order ?? 'desc', // Default descending
            'status' => $request->status ?? null,
            'search' => $request->search ?? null,
            'type' => $request->type ?? null,
            'start_date' => $request->start_date ?? null,
            'end_date' => $request->end_date ?? null,
        ];

        $transactionTransfers = DB::table('transaction_transfers')
            ->select(
                'transaction_transfers.id',
                'transaction_transfers.no_transaction',
                'accountnumbers_transfer.name as transfer_account_name',
                'accountnumbers_transfer.account_no as transfer_account_no',
                'accountnumbers_deposit.name as deposit_account_name',
                'accountnumbers_deposit.account_no as deposit_account_no',
                'transaction_transfers.amount',
                'transaction_transfers.date',
                'transaction_transfers.created_at',
                DB::raw('"transaction_transfer" as type')
            )
            ->join('accountnumbers as accountnumbers_transfer', 'transaction_transfers.transfer_account_id', '=', 'accountnumbers_transfer.id')
            ->join('accountnumbers as accountnumbers_deposit', 'transaction_transfers.deposit_account_id', '=', 'accountnumbers_deposit.id');

        $transactionSends = DB::table('transaction_sends')
            ->select(
                'transaction_sends.id',
                'transaction_sends.no_transaction',
                'accountnumbers_transfer.name as transfer_account_name',
                'accountnumbers_transfer.account_no as transfer_account_no',
                'accountnumbers_deposit.name as deposit_account_name',
                'accountnumbers_deposit.account_no as deposit_account_no',
                'transaction_sends.amount',
                'transaction_sends.date',
                'transaction_sends.created_at',
                DB::raw('"transaction_send" as type')
            )
            ->join('accountnumbers as accountnumbers_transfer', 'transaction_sends.transfer_account_id', '=', 'accountnumbers_transfer.id')
            ->join('accountnumbers as accountnumbers_deposit', 'transaction_sends.deposit_account_id', '=', 'accountnumbers_deposit.id');

        $transactionReceives = DB::table('transaction_receives')
            ->select(
                'transaction_receives.id',
                'transaction_receives.no_transaction',
                'accountnumbers_transfer.name as transfer_account_name',
                'accountnumbers_transfer.account_no as transfer_account_no',
                'accountnumbers_deposit.name as deposit_account_name',
                'accountnumbers_deposit.account_no as deposit_account_no',
                'transaction_receives.amount',
                'transaction_receives.date',
                'transaction_receives.created_at',
                DB::raw('"transaction_receive" as type')
            )
            ->join('accountnumbers as accountnumbers_transfer', 'transaction_receives.transfer_account_id', '=', 'accountnumbers_transfer.id')
            ->join('accountnumbers as accountnumbers_deposit', 'transaction_receives.deposit_account_id', '=', 'accountnumbers_deposit.id');

        $unionQuery = $transactionTransfers->unionAll($transactionSends)->unionAll($transactionReceives);

        $query = DB::table(DB::raw("({$unionQuery->toSql()}) as sub"))
            ->mergeBindings($unionQuery);

        Log::info('Data sebelum filter tanggal:', $query->get()->toArray());

        if ($form->start_date && $form->end_date) {
            $start_date = Carbon::createFromFormat('Y-m-d', $form->start_date)->startOfDay();
            $end_date = Carbon::createFromFormat('Y-m-d', $form->end_date)->endOfDay();

            $query->whereBetween('date', [$start_date, $end_date]);
        }

        Log::info('Data setelah filter tanggal:', $query->get()->toArray());

        if ($form->type) {
            $query->where('type', $form->type);
        }

        if ($form->search) {
            $query->where(function ($query) use ($form) {
                $query->where('no_transaction', 'like', '%' . $form->search . '%')
                    ->orWhere('transfer_account_name', 'like', '%' . $form->search . '%')
                    ->orWhere('deposit_account_name', 'like', '%' . $form->search . '%')
                    ->orWhere('transfer_account_no', 'like', '%' . $form->search . '%')
                    ->orWhere('deposit_account_no', 'like', '%' . $form->search . '%');
            });
        }

        if ($form->sort) {
            $query->orderBy($form->sort, $form->order);
        }

        $allData = $query->paginate(10);

        return view('components.journal.index', compact('allData', 'form', 'selectedItems'));
    }


   

    public function showFilterJournalDetail(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $type = $request->input('type');
        $search = $request->input('search');
        $sort = $request->input('sort');
        $order = $request->input('order');

        // Log debugging
        Log::info('Request Data: ', $request->all());

        // Check for correct date format and parse dates
        if ($startDate) {
            $startDate = \Carbon\Carbon::createFromFormat('Y-m-d', $startDate)->startOfDay();
        }
        if ($endDate) {
            $endDate = \Carbon\Carbon::createFromFormat('Y-m-d', $endDate)->endOfDay();
        }

        $transactionDetails = [];

        // Define variables as empty collections
        $transactionTransfers = collect();
        $transactionReceives = collect();
        $transactionSends = collect();

        // Logika untuk mengambil transaksi berdasarkan jenis transaksi
        if ($type === 'transaction_transfer' || empty($type)) {
            $transactionTransfers = Transaction_transfer::with(['transferAccount', 'depositAccount'])
                ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
                    return $query->whereBetween('date', [$startDate, $endDate]);
                })
                ->when($search, function ($query, $search) {
                    return $query->where('no_transaction', 'LIKE', "%{$search}%");
                })
                ->when($sort && $order, function ($query) use ($sort, $order) {
                    return $query->orderBy($sort, $order);
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

        if ($type === 'transaction_send' || empty($type)) {
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

        if ($type === 'transaction_receive' || empty($type)) {
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

        // Store transaction details in session for export
        session(['transactionDetails' => $transactionDetails]);

        return view('components.journal.selected-detail', [
            'transactionDetails' => $transactionDetails,
            'selectedNoTransactions' => $transactionDetails,
        ]);
    }

    public function showFilterJournalDetailpdf(Request $request)
    {
        $selectedNoTransactions = $request->selectedNoTransactions ?? [];
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $type = $request->input('type');
        $search = $request->input('search');
        $sort = $request->input('sort');
        $order = $request->input('order');

        // Log debugging
        Log::info('Request Data: ', $request->all());

        // Check for correct date format and parse dates
        if ($startDate) {
            $startDate = \Carbon\Carbon::createFromFormat('Y-m-d', $startDate)->startOfDay();
        }
        if ($endDate) {
            $endDate = \Carbon\Carbon::createFromFormat('Y-m-d', $endDate)->endOfDay();
        }

        $transactionDetails = [];

        // Define variables as empty collections
        $transactionTransfers = collect();
        $transactionReceives = collect();
        $transactionSends = collect();

        // Logika untuk mengambil transaksi berdasarkan jenis transaksi
        if ($type === 'transaction_transfer' || empty($type)) {
            $transactionTransfers = Transaction_transfer::with(['transferAccount', 'depositAccount'])
                ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
                    return $query->whereBetween('date', [$startDate, $endDate]);
                })
                ->when($search, function ($query, $search) {
                    return $query->where('no_transaction', 'LIKE', "%{$search}%");
                })
                ->when($sort && $order, function ($query) use ($sort, $order) {
                    return $query->orderBy($sort, $order);
                })
                ->get();

            foreach ($transactionTransfers as $transaction) {
                $transferAccount = $transaction->transferAccount;
                $depositAccount = $transaction->depositAccount;

                $transactionDetails[] = [
                    'no_transaction' => $transaction->no_transaction ?? 'N/A',
                    'account_number' => $transferAccount->account_no,
                    'account_name' => $transferAccount->name,
                    'debit' => 0,
                    'credit' => $transaction->amount > 0 ? $transaction->amount : 0,
                    'date' => $transaction->date,
                    'description' => $transaction->description,
                    'created_at' => $transaction->created_at
                ];

                $transactionDetails[] = [
                    'no_transaction' => $transaction->no_transaction ?? 'N/A',
                    'account_number' => $depositAccount->account_no,
                    'account_name' => $depositAccount->name,
                    'debit' => $transaction->amount > 0 ? $transaction->amount : 0,
                    'credit' => 0,
                    'date' => $transaction->date,
                    'description' => $transaction->description,
                    'created_at' => $transaction->created_at
                ];
            }
        }

        if ($type === 'transaction_send' || empty($type)) {
            $transactionSends = Transaction_send::with(['transferAccount', 'depositAccount'])
                ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
                    return $query->whereBetween('date', [$startDate, $endDate]);
                })
                ->get();

            foreach ($transactionSends as $transaction) {
                $transferAccount = $transaction->transferAccount;
                $depositAccount = $transaction->depositAccount;

                $transactionDetails[] = [
                    'no_transaction' => $transaction->no_transaction ?? 'N/A',
                    'account_number' => $transferAccount->account_no,
                    'account_name' => $transferAccount->name,
                    'debit' => 0,
                    'credit' => $transaction->amount > 0 ? $transaction->amount : 0,
                    'date' => $transaction->date,
                    'description' => $transaction->description,
                    'created_at' => $transaction->created_at
                ];

                $transactionDetails[] = [
                    'no_transaction' => $transaction->no_transaction ?? 'N/A',
                    'account_number' => $depositAccount->account_no,
                    'account_name' => $depositAccount->name,
                    'debit' => $transaction->amount > 0 ? $transaction->amount : 0,
                    'credit' => 0,
                    'date' => $transaction->date,
                    'description' => $transaction->description,
                    'created_at' => $transaction->created_at
                ];
            }
        }

        if ($type === 'transaction_receive' || empty($type)) {
            $transactionReceives = Transaction_receive::with(['transferAccount', 'depositAccount'])
                ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
                    return $query->whereBetween('date', [$startDate, $endDate]);
                })
                ->get();

            foreach ($transactionReceives as $transaction) {
                $transferAccount = $transaction->transferAccount;
                $depositAccount = $transaction->depositAccount;

                $transactionDetails[] = [
                    'no_transaction' => $transaction->no_transaction ?? 'N/A',
                    'account_number' => $transferAccount->account_no,
                    'account_name' => $transferAccount->name,
                    'debit' => 0,
                    'credit' => $transaction->amount > 0 ? $transaction->amount : 0,
                    'date' => $transaction->date,
                    'description' => $transaction->description,
                    'created_at' => $transaction->created_at
                ];

                $transactionDetails[] = [
                    'no_transaction' => $transaction->no_transaction ?? 'N/A',
                    'account_number' => $depositAccount->account_no,
                    'account_name' => $depositAccount->name,
                    'debit' => $transaction->amount > 0 ? $transaction->amount : 0,
                    'credit' => 0,
                    'date' => $transaction->date,
                    'description' => $transaction->description,
                    'created_at' => $transaction->created_at
                ];
            }
        }

        $allTransactions = $transactionTransfers->merge($transactionSends)->merge($transactionReceives);


        // Load the PDF view with the transaction details
        $nameFormatPdf = Carbon::now()->format('YmdHis') . mt_rand(1000, 9999) . '_journal_detail.pdf';

        $pdf = app('dompdf.wrapper');
        $pdf->loadView('components.journal.selected-detail-pdf', [
            'transactionDetails' => $transactionDetails,
            'selectedNoTransactions' => $allTransactions->pluck('no_transaction')->toArray(),

        ])->setPaper('a4', 'landscape');

        return $pdf->stream($nameFormatPdf);
    }


    public function showJournalDetail(Request $request, $id, $type)
    {
        $selectedItems = $request->id ?? [];

        // try {
        // Variabel untuk menyimpan data detail transaksi
        $transactionDetails = [];

        // Sesuaikan pengecekan berdasarkan tipe transaksi
        if ($type === 'transaction_transfer') {
            $transaction = Transaction_transfer::find($id);

            if ($transaction) {
                // Mengambil data transfer account
                $transferAccount = $transaction->transferAccount;
                // Mengambil data deposit account
                $depositAccount = $transaction->depositAccount;

                $transactionDetails = [
                    [
                        'no_transaction' => $transferAccount->no_transaction,
                        'account_number' => $transferAccount->account_no,
                        'account_name' => $transferAccount->name,
                        'debit' => 0,
                        'credit' => $transaction->amount > 0 ? $transaction->amount : 0,
                        'date' => $transaction->date,
                        'description' => $transaction->description,
                        'created_at' => $transaction->created_at
                    ],
                    [
                        'no_transaction' => $depositAccount->no_transaction,
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
        } elseif ($type === 'transaction_send') {
            $transaction = Transaction_send::find($id);

            if ($transaction) {
                $transferAccount = $transaction->transferAccount;
                $depositAccount = $transaction->depositAccount;

                $transactionDetails = [
                    [
                        'account_number' => $transferAccount->account_no,
                        'account_name' => $transferAccount->name,
                        'debit' => 0,
                        'credit' => $transaction->amount > 0 ? $transaction->amount : 0,
                        'date' => $transaction->date,
                        'description' => $transaction->description,
                        'created_at' => $transaction->created_at
                    ],
                    [
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
        } elseif ($type === 'transaction_receive') {
            $transaction = Transaction_receive::find($id);

            if ($transaction) {
                $transferAccount = $transaction->transferAccount;
                $depositAccount = $transaction->depositAccount;

                $transactionDetails = [
                    [
                        'account_number' => $transferAccount->account_no,
                        'account_name' => $transferAccount->name,
                        'debit' => 0,
                        'credit' => $transaction->amount > 0 ? $transaction->amount : 0,
                        'date' => $transaction->date,
                        'description' => $transaction->description,
                        'created_at' => $transaction->created_at
                    ],
                    [
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
        } else {
            // Jika tipe transaksi tidak valid, kembalikan ke halaman index dengan pesan error
            return redirect()->route('journal.index')->with('error', 'Invalid transaction type.');
        }

        // Jika data ditemukan, tampilkan detailnya
        if ($transaction !== null) {
            return view('components.journal.detail', [
                'transaction' => $transaction,
                'transactionDetails' => $transactionDetails,
                'type' => $type,
                'selectedItems' => $selectedItems

            ]);
        } else {
            // Jika data tidak ditemukan, kembalikan ke halaman index dengan pesan error
            return redirect()->route('journal.index')->with('error', 'Transaction details not found.');
        }
        // } catch (Exception $e) {
        //     // Tangani kesalahan dengan mengembalikan ke halaman index dengan pesan error
        //     // return redirect()->route('journal.index')->with('error', 'Failed to fetch transaction details.');
        // }
    }


    public function generatePdfJournalDetail($id, $type)
    {
        session()->flash('page', (object)[
            'page' => 'Journal',
            'child' => 'Journal Details'
        ]);

        try {
            // Variabel untuk menyimpan data detail transaksi
            $transactionDetails = [];

            // Sesuaikan pengecekan berdasarkan tipe transaksi
            if ($type === 'transaction_transfer') {
                $transaction = Transaction_transfer::find($id);

                if ($transaction) {
                    // Mengambil data transfer account
                    $transferAccount = $transaction->transferAccount;
                    // Mengambil data deposit account
                    $depositAccount = $transaction->depositAccount;

                    $transactionDetails = [
                        [
                            'account_number' => $transferAccount->account_no,
                            'account_name' => $transferAccount->name,
                            'debit' => 0,
                            'credit' => $transaction->amount > 0 ? $transaction->amount : 0,
                            'date' => $transaction->date,
                            'description' => $transaction->description,
                            'created_at' => $transaction->created_at
                        ],
                        [
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
            } elseif ($type === 'transaction_send') {
                $transaction = Transaction_send::find($id);

                if ($transaction) {
                    $transferAccount = $transaction->transferAccount;
                    $depositAccount = $transaction->depositAccount;

                    $transactionDetails = [
                        [
                            'account_number' => $transferAccount->account_no,
                            'account_name' => $transferAccount->name,
                            'debit' => 0,
                            'credit' => $transaction->amount > 0 ? $transaction->amount : 0,
                            'date' => $transaction->date,
                            'description' => $transaction->description,
                            'created_at' => $transaction->created_at
                        ],
                        [
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
            } elseif ($type === 'transaction_receive') {
                $transaction = Transaction_receive::find($id);

                if ($transaction) {
                    $transferAccount = $transaction->transferAccount;
                    $depositAccount = $transaction->depositAccount;

                    $transactionDetails = [
                        [
                            'account_number' => $transferAccount->account_no,
                            'account_name' => $transferAccount->name,
                            'debit' => 0,
                            'credit' => $transaction->amount > 0 ? $transaction->amount : 0,
                            'date' => $transaction->date,
                            'description' => $transaction->description,
                            'created_at' => $transaction->created_at
                        ],
                        [
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
            } else {
                // Jika tipe transaksi tidak valid, kembalikan ke halaman index dengan pesan error
                return redirect()->route('journal.index')->with('error', 'Invalid transaction type.');
            }

            $nameFormatPdf = Carbon::now()->format('YmdHis') . mt_rand(1000, 9999) . '_journal_detail.pdf';

            $pdf = app('dompdf.wrapper');
            $pdf->loadView('components.journal.detail-pdf', [
                'transaction' => $transaction,
                'transactionDetails' => $transactionDetails,
                'type' => $type,
            ])->setPaper('a4', 'landscape');

            return $pdf->stream($nameFormatPdf);
        } catch (Exception $e) {
            return abort(500, 'Failed to fetch transaction details.');
        }
    }

}
