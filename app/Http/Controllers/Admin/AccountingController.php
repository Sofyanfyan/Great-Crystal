<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Accountcategory;
use App\Models\Accountnumber;
use App\Models\BalanceAccount;
use App\Models\Student;
use App\Models\Transaction_receive;
use App\Models\Transaction_send;
use App\Models\Transaction_transfer;
use App\Models\TransactionSendSupplier;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AccountingController extends Controller
{
    public function indexAccount(Request $request)
    {
        session()->flash('preloader', true);
        session()->flash('page', (object)[
            'page' => 'AccountNumber',
            'child' => 'Database Account Number',
        ]);

        try {
            $form = (object) [
                'sort' => $request->sort ?? null,
                'order' => $request->order ?? 'desc',
                'search' => $request->search ?? null,
                'date' => $request->date ?? null,
            ];

            $query = Accountnumber::query();

            // Filter berdasarkan parameter pencarian
            if ($request->filled('search')) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'LIKE', '%' . $request->search . '%')
                        ->orWhere('account_no', 'LIKE', '%' . $request->search . '%')
                        ->orWhere('amount', 'LIKE', '%' . $request->search . '%')
                        ->orWhere('created_at', 'LIKE', '%' . $request->search . '%');
                });
            }

            // Filter berdasarkan tanggal
            if ($request->filled('date')) {
                $searchDate = date('Y-m-d', strtotime($request->date));
                $query->whereDate('created_at', $searchDate);
            }

            // Mengatur urutan berdasarkan parameter yang dipilih
            if ($request->filled('sort') && $request->filled('order')) {
                $query->orderBy($request->sort, $request->order);
            } else {
                $query->orderBy('created_at', 'desc');
            }

            $data = $query->paginate(15);

            $categories = Accountcategory::all();

            return view('components.account.index')->with('data', $data)->with('categories', $categories)->with('form', $form);
        } catch (Exception $err) {
            return dd($err);
        }
    }


    public function createAccount()
    {
        $categories = Accountcategory::all();

        return view('components.account.create-account', [
            'categories' => $categories,
        ]);
    }

    public function storeAccount(Request $request)
    {
        try {
            // Validasi input
            $request->validate([
                'name' => 'required',
                'account_no' => 'required',
                'account_category_id' => 'required',
                // 'amount' => 'required|numeric',
                'description' => 'required',
                // 'beginning_balance' => 'required|numeric',
            ]);

            Accountnumber::create([
                'name' => $request->name,
                'account_no' => $request->account_no,
                'account_category_id' => $request->account_category_id,
                'amount' => $request->amount,
                'description' => $request->description,
                // 'beginning_balance' => $request->beginning_balance,
                // 'ending_balance' => $request->ending_balance,
                // 'transactions_total' => 0, // Set transactions_total default ke 0
            ]);

            // Redirect ke halaman indeks pengeluaran dengan pesan sukses
            return redirect()->route('account.index')->with('success', 'Accountnumber created successfully!');
        } catch (\Illuminate\Database\QueryException $ex) {
            if ($ex->errorInfo[1] == 1062) {
                // Handle the integrity constraint violation error
                $errorMessage = "The account name already exists.";
                return redirect()->back()->withErrors(['name' => $errorMessage]);
            } else {
                // Handle other database errors
                return redirect()->back()->withErrors(['message' => 'Database error occurred. Please try again later.']);
            }
        }
    }


    public function storeAccountCategory(Request $request)
    {
        try {
            $request->validate([
                'category_name' => 'required|string|max:255',
            ]);

            $category = new Accountcategory();
            $category->category_name = $request->category_name;
            $category->save();

            return redirect()->route('create-account.create')->with('success', 'Category created successfully!');
        } catch (\Illuminate\Database\QueryException $ex) {
            if ($ex->errorInfo[1] == 1062) {
                // Handle the integrity constraint violation error
                $errorMessage = "The category name already exists.";
                return redirect()->back()->withErrors(['category_name' => $errorMessage])->withInput();
            } else {
                // Handle other database errors
                return redirect()->back()->withErrors(['message' => 'Database error occurred. Please try again later.'])->withInput();
            }
        }
    }


    public function editAccount($id)
    {
        $accountNumbers = Accountnumber::findOrFail($id);
        $categories = Accountcategory::all();

        return view('components.account.edit-account', [
            'accountNumbers' => $accountNumbers,
            'categories' => $categories,
        ]);
    }

    public function updateAccount(Request $request, $id)
    {
        // Validasi input
        $request->validate([
            'name' => 'required',
            'account_no' => 'required',
            'account_category_id' => 'required',
            'amount' => 'required|numeric',
            'description' => 'required',
            // 'beginning_balance' => 'required|numeric',
        ]);

        $accountNumbers = Accountnumber::findOrFail($id);

        $accountNumbers->update([
            'name' => $request->name,
            'account_no' => $request->account_no,
            'account_category_id' => $request->account_category_id,
            'amount' => $request->amount,
            'description' => $request->description,
            // 'beginning_balance' => $request->beginning_balance,

        ]);

        // Redirect ke halaman indeks pengeluaran dengan pesan sukses
        return redirect()->route('account.index')->with('success', 'Account Updated successfully!');
    }

    public function destroyAccount($id)
    {
        try {
            $accountNumbers = Accountnumber::findOrFail($id);

            $accountNumbers->delete();

            return response()->json(['message' => 'Accountnumber deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete Accountnumber.']);
        }
    }

    public function calculateAll(Request $request)
    {
        try {
            // Ambil semua account numbers
            $accounts = Accountnumber::all();

            // Loop untuk menghitung ending balance untuk setiap account
            foreach ($accounts as $account) {
                $ending_balance = $account->calculateEndingBalance();
                $account->update(['ending_balance' => $ending_balance]);

                // Tentukan tipe debit atau kredit berdasarkan ending balance
                $position = $account->getBalanceType(); // Fungsi getBalanceType dari model Accountnumber

                // Update kolom position dengan nilai debit atau kredit
                $account->update(['position' => $position]);
            }

            // Redirect dengan pesan sukses
            return redirect()->route('account.index')->with('success', 'Ending balances calculation successful for all accounts.');
        } catch (\Exception $ex) {
            return redirect()->route('account.index')->with('error', 'Failed to calculate ending balances.');
        }
    }

    public function indexTransfer(Request $request)
    {
        session()->flash('preloader', true);
        session()->flash('page', (object)[
            'page' => 'Transaction',
            'child' => 'Database Transaction Transfer',
        ]);

        try {
            // Inisialisasi objek form dengan nilai default
            $form = (object) [
                'search' => $request->search ?? null,
                'type' => $request->type ?? null,
                'sort' => $request->sort ?? null,
                'order' => $request->order ?? null,
                'status' => $request->status ?? null,
                'date' => $request->date ?? null,
            ];

            // Query data berdasarkan parameter pencarian yang diberikan
            $query = Transaction_transfer::with(['transferAccount', 'depositAccount']);

            if ($request->filled('search')) {
                $searchTerm = '%' . $request->search . '%';
                $query->where(function ($q) use ($searchTerm) {
                    $q->whereHas('transferAccount', function ($q) use ($searchTerm) {
                        $q->where('name', 'LIKE', $searchTerm)
                            ->orWhere('account_no', 'LIKE', $searchTerm);
                    })->orWhereHas('depositAccount', function ($q) use ($searchTerm) {
                        $q->where('name', 'LIKE', $searchTerm)
                            ->orWhere('account_no', 'LIKE', $searchTerm);
                    })->orWhere('amount', 'LIKE', $searchTerm)
                        ->orWhere('date', 'LIKE', $searchTerm);
                });
            }

            // Filter data berdasarkan tanggal
            if ($request->filled('date')) {
                $searchDate = date('Y-m-d', strtotime($request->date));
                $query->whereDate('date', $searchDate);
            }

            // Mengatur urutan berdasarkan parameter yang dipilih
            if ($request->filled('sort')) {
                if ($request->sort === 'oldest') {
                    $query->orderBy('date', 'asc');
                } elseif ($request->sort === 'newest') {
                    $query->orderBy('date', 'desc');
                }
            }

            // Memuat data dengan pagination
            $data = $query->paginate(10);

            // Menampilkan view dengan data dan form
            return view('components.cash&bank.index-transfer', compact('data', 'form'));
        } catch (Exception $err) {
            // Menampilkan pesan error jika terjadi kesalahan
            return dd($err);
        }
    }

    public function createTransactionTransfer()
    {
        $accountCategory = Accountcategory::all();

        $accountNumbers = AccountNumber::all(); // Ambil semua data dari tabel accountnumbers

        return view('components.cash&bank.create-transaction-transfer', [
            'accountNumbers' => $accountNumbers,
            'accountCategory' => $accountCategory,
        ]);
    }

    public function storeTransactionTransfer(Request $request)
    {
        try {
            $request->validate([
                'transfer_account_id' => 'required',
                'deposit_account_id' => 'required',
                'amount' => 'required|numeric',
                'date' => 'required|date_format:d/m/Y',
                'description' => 'required',
                'no_transaction' => 'required',
            ]);

            $date = Carbon::createFromFormat('d/m/Y', $request->date)->format('Y-m-d');
            $month = Carbon::createFromFormat('d/m/Y', $request->date)->startOfMonth()->format('Y-m-d');

            // Create the transaction record
            $transaction = Transaction_transfer::create([
                'transfer_account_id' => $request->transfer_account_id,
                'deposit_account_id' => $request->deposit_account_id,
                'amount' => $request->amount,
                'date' => $date,
                'description' => $request->description,
                'no_transaction' => $request->no_transaction,
            ]);

            // Update the amount in transfer account (kredit)
            $transferAccount = Accountnumber::find($request->transfer_account_id);
            $transferAccount->amount -= $request->amount; // Kredit
            $transferAccount->save();

            // Update the amount in deposit account (debit)
            $depositAccount = Accountnumber::find($request->deposit_account_id);
            $depositAccount->amount += $request->amount; // Debit
            $depositAccount->save();



            // Update balance_accounts for transfer account (credit)
            $transferBalanceAccount = BalanceAccount::firstOrNew([
                'accountnumber_id' => $transferAccount->id,
                'month' => $month
            ]);
            $transferBalanceAccount->credit += $request->amount;
            $transferBalanceAccount->debit = $transferBalanceAccount->debit ?? 0;
            $transferBalanceAccount->save();

            // Update balance_accounts for deposit account (debit)
            $depositBalanceAccount = BalanceAccount::firstOrNew([
                'accountnumber_id' => $depositAccount->id,
                'month' => $month
            ]);
            $depositBalanceAccount->debit += $request->amount;
            $depositBalanceAccount->credit = $depositBalanceAccount->credit ?? 0;
            $depositBalanceAccount->save();

            return redirect()->route('transaction-transfer.index')->with('success', 'Transaction Transfer Created Successfully!');
        } catch (Exception $err) {
            // Handle errors here
            return dd($err);
        }
    }

    public function storeAccountTransactionTransfer(Request $request)
    {
        try {
            // Validasi input
            $request->validate([
                'name' => 'required',
                'account_no' => 'required',
                'account_category_id' => 'required',
                // 'amount' => 'required|numeric',
                'description' => 'required',
                // 'beginning_balance' => 'required|numeric',
            ]);

            Accountnumber::create([
                'name' => $request->name,
                'account_no' => $request->account_no,
                'account_category_id' => $request->account_category_id,
                'amount' => $request->amount,
                'description' => $request->description,
                // 'beginning_balance' => $request->beginning_balance,
                // 'ending_balance' => $request->ending_balance,
                // 'transactions_total' => 0, // Set transactions_total default ke 0
            ]);

            // Redirect ke halaman indeks pengeluaran dengan pesan sukses
            return redirect()->route('transaction-transfer.create')->with('success', 'Accountnumber created successfully!');
        } catch (\Illuminate\Database\QueryException $ex) {
            if ($ex->errorInfo[1] == 1062) {
                // Handle the integrity constraint violation error
                $errorMessage = "The account name already exists.";
                return redirect()->back()->withErrors(['name' => $errorMessage]);
            } else {
                // Handle other database errors
                return redirect()->back()->withErrors(['message' => 'Database error occurred. Please try again later.']);
            }
        }
    }

    public function deleteTransactionTransfer($id)
    {
        try {
            $transactionTransfer = Transaction_transfer::findOrFail($id);

            // Debug: Log transactionTransfer details
            Log::info('Deleting Transaction Transfer:', $transactionTransfer->toArray());

            // Debug: Log original date
            Log::info('Original Date:', ['date' => $transactionTransfer->date]);

            // Make sure date is in the correct format
            $month = Carbon::createFromFormat('Y-m-d H:i:s', $transactionTransfer->date)->startOfMonth()->format('Y-m-d');

            // Debug: Log the computed month
            Log::info('Computed Month:', ['month' => $month]);

            // Update the amount in transfer account (kredit)
            $transferAccount = Accountnumber::find($transactionTransfer->transfer_account_id);
            if ($transferAccount) {
                $transferAccount->amount += $transactionTransfer->amount; // Reverse Kredit
                $transferAccount->save();
            } else {
                Log::error("Transfer account not found: " . $transactionTransfer->transfer_account_id);
                return response()->json(['error' => 'Transfer account not found.']);
            }

            // Update the amount in deposit account (debit)
            $depositAccount = Accountnumber::find($transactionTransfer->deposit_account_id);
            if ($depositAccount) {
                $depositAccount->amount -= $transactionTransfer->amount; // Reverse Debit
                $depositAccount->save();
            } else {
                Log::error("Deposit account not found: " . $transactionTransfer->deposit_account_id);
                return response()->json(['error' => 'Deposit account not found.']);
            }

            // Update balance_accounts for transfer account (credit)
            $transferBalanceAccount = BalanceAccount::where('accountnumber_id', $transferAccount->id)
                ->where('month', $month)
                ->first();
            if ($transferBalanceAccount) {
                $transferBalanceAccount->credit -= $transactionTransfer->amount;
                $transferBalanceAccount->save();
            } else {
                Log::error("Balance account for transfer account not found: accountnumber_id={$transferAccount->id}, month={$month}");
            }

            // Update balance_accounts for deposit account (debit)
            $depositBalanceAccount = BalanceAccount::where('accountnumber_id', $depositAccount->id)
                ->where('month', $month)
                ->first();
            if ($depositBalanceAccount) {
                $depositBalanceAccount->debit -= $transactionTransfer->amount;
                $depositBalanceAccount->save();
            } else {
                Log::error("Balance account for deposit account not found: accountnumber_id={$depositAccount->id}, month={$month}");
            }

            // Delete the transaction record
            $transactionTransfer->delete();

            return response()->json(['message' => 'Transaction Transfer deleted successfully.']);
        } catch (\Exception $e) {
            Log::error("Failed to delete Transaction Transfer: " . $e->getMessage());
            return response()->json(['error' => 'Failed to delete Transaction Transfer.']);
        }
    }



    // milik transaction send
    public function indexTransactionSend(Request $request)
    {
        session()->flash('preloader', true);
        session()->flash('page', (object)[
            'page' => 'Transaction',
            'child' => 'Database Transaction Send',
        ]);

        try {
            // Inisialisasi objek form dengan nilai default
            $form = (object) [
                'search' => $request->search ?? null,
                'type' => $request->type ?? null,
                'sort' => $request->sort ?? null,
                'order' => $request->order ?? null,
                'status' => $request->status ?? null,
                'date' => $request->date ?? null,
            ];

            // Query data berdasarkan parameter pencarian yang diberikan
            $query = Transaction_send::with(['transferAccount', 'depositAccount']);

            if ($request->filled('search')) {
                $searchTerm = '%' . $request->search . '%';
                $query->where(function ($q) use ($searchTerm) {
                    $q->whereHas('transferAccount', function ($q) use ($searchTerm) {
                        $q->where('name', 'LIKE', $searchTerm)
                            ->orWhere('account_no', 'LIKE', $searchTerm);
                    })->orWhereHas('depositAccount', function ($q) use ($searchTerm) {
                        $q->where('name', 'LIKE', $searchTerm)
                            ->orWhere('account_no', 'LIKE', $searchTerm);
                    })->orWhere('amount', 'LIKE', $searchTerm)
                        ->orWhere('date', 'LIKE', $searchTerm);
                });
            }

            // Filter data berdasarkan tanggal
            if ($request->filled('date')) {
                $searchDate = date('Y-m-d', strtotime($request->date));
                $query->whereDate('date', $searchDate);
            }

            // Mengatur urutan berdasarkan parameter yang dipilih
            if ($request->filled('sort')) {
                if ($request->sort === 'oldest') {
                    $query->orderBy('date', 'asc');
                } elseif ($request->sort === 'newest') {
                    $query->orderBy('date', 'desc');
                }
            }

            // Memuat data dengan pagination
            $data = $query->paginate(10);

            // Menampilkan view dengan data dan form
            return view('components.cash&bank.index-send', compact('data', 'form'));
        } catch (Exception $err) {
            // Menampilkan pesan error jika terjadi kesalahan
            return dd($err);
        }
    }

    public function createTransactionSend()
    {
        $suppliers = TransactionSendSupplier::all();
        $accountCategory = Accountcategory::all();
        $accountNumbers = AccountNumber::all(); // Ambil semua data dari tabel accountnumbers

        return view('components.cash&bank.create-transaction-send', [
            'accountNumbers' => $accountNumbers,
            'suppliers' => $suppliers,
            'accountCategory' => $accountCategory,
        ]);
    }

    public function storeTransactionSend(Request $request)
    {
        try {
            $request->validate([
                'transfer_account_id' => 'required',
                'deposit_account_id' => 'required',
                'amount' => 'required|numeric',
                'date' => 'required|date_format:d/m/Y',
                'description' => 'required',
                'no_transaction' => 'required',
                'transaction_send_supplier_id' => 'required'
            ]);

            $date = Carbon::createFromFormat('d/m/Y', $request->date)->format('Y-m-d');
            $month = Carbon::createFromFormat('d/m/Y', $request->date)->startOfMonth()->format('Y-m-d');

            Transaction_send::create([
                'transfer_account_id' => $request->transfer_account_id,
                'deposit_account_id' => $request->deposit_account_id,
                'amount' => $request->amount,
                'date' => $date,
                'description' => $request->description,
                'no_transaction' => $request->no_transaction,
                'transaction_send_supplier_id' => $request->transaction_send_supplier_id,
            ]);

            // Update the amount in transfer account (allowing it to go negative)
            $transferAccount = Accountnumber::find($request->transfer_account_id);
            $transferAccount->amount -= $request->amount;
            $transferAccount->save();

            // Update the amount in deposit account
            $depositAccount = Accountnumber::find($request->deposit_account_id);
            $depositAccount->amount += $request->amount;
            $depositAccount->save();




            // Update balance_accounts for transfer account (credit)
            $transferBalanceAccount = BalanceAccount::firstOrNew([
                'accountnumber_id' => $transferAccount->id,
                'month' => $month
            ]);
            $transferBalanceAccount->credit += $request->amount;
            $transferBalanceAccount->debit = $transferBalanceAccount->debit ?? 0;
            $transferBalanceAccount->save();

            // Update balance_accounts for deposit account (debit)
            $depositBalanceAccount = BalanceAccount::firstOrNew([
                'accountnumber_id' => $depositAccount->id,
                'month' => $month
            ]);
            $depositBalanceAccount->debit += $request->amount;
            $depositBalanceAccount->credit = $depositBalanceAccount->credit ?? 0;
            $depositBalanceAccount->save();

            return redirect()->route('transaction-send.index')->with('success', 'Transaction Send Created Successfully!');
        } catch (Exception $err) {
            // Tangani kesalahan di sini
            return dd($err);
        }
    }

    public function storeSupplierTransactionSend(Request $request)
    {
        $request->validate([
            'supplier_name' => 'required|string|max:255|unique:transaction_send_suppliers,supplier_name',
            'supplier_role' => 'required|string|max:255',
        ]);

        $supplier = new TransactionSendSupplier();
        $supplier->supplier_name = $request->supplier_name;
        $supplier->supplier_role = $request->supplier_role;
        $supplier->save();

        // return redirect('create-account.create');
        return redirect()->route('transaction-send.create');
    }

    public function storeAccountTransactionSend(Request $request)
    {
        try {
            // Validasi input
            $request->validate([
                'name' => 'required',
                'account_no' => 'required',
                'account_category_id' => 'required',
                // 'amount' => 'required|numeric',
                'description' => 'required',
                // 'beginning_balance' => 'required|numeric',
            ]);

            Accountnumber::create([
                'name' => $request->name,
                'account_no' => $request->account_no,
                'account_category_id' => $request->account_category_id,
                'amount' => $request->amount,
                'description' => $request->description,
                // 'beginning_balance' => $request->beginning_balance,
                // 'ending_balance' => $request->ending_balance,
                // 'transactions_total' => 0, // Set transactions_total default ke 0
            ]);

            // Redirect ke halaman indeks pengeluaran dengan pesan sukses
            return redirect()->route('transaction-send.create')->with('success', 'Accountnumber created successfully!');
        } catch (\Illuminate\Database\QueryException $ex) {
            if ($ex->errorInfo[1] == 1062) {
                // Handle the integrity constraint violation error
                $errorMessage = "The account name already exists.";
                return redirect()->back()->withErrors(['name' => $errorMessage]);
            } else {
                // Handle other database errors
                return redirect()->back()->withErrors(['message' => 'Database error occurred. Please try again later.']);
            }
        }
    }

    public function deleteTransactionSend($id)
    {
        try {
            $transactionSend = Transaction_send::findOrFail($id);

            // Debug: Log transactionTransfer details
            Log::info('Deleting Transaction Send:', $transactionSend->toArray());

            // Debug: Log original date
            Log::info('Original Date:', ['date' => $transactionSend->date]);

            // Make sure date is in the correct format
            $month = Carbon::createFromFormat('Y-m-d H:i:s', $transactionSend->date)->startOfMonth()->format('Y-m-d');

            // Debug: Log the computed month
            Log::info('Computed Month:', ['month' => $month]);

            // Update the amount in transfer account (kredit)
            $transferAccount = Accountnumber::find($transactionSend->transfer_account_id);
            if ($transferAccount) {
                $transferAccount->amount += $transactionSend->amount; // Reverse Kredit
                $transferAccount->save();
            } else {
                Log::error("Transfer account not found: " . $transactionSend->transfer_account_id);
                return response()->json(['error' => 'Transfer account not found.']);
            }

            // Update the amount in deposit account (debit)
            $depositAccount = Accountnumber::find($transactionSend->deposit_account_id);
            if ($depositAccount) {
                $depositAccount->amount -= $transactionSend->amount; // Reverse Debit
                $depositAccount->save();
            } else {
                Log::error("Deposit account not found: " . $transactionSend->deposit_account_id);
                return response()->json(['error' => 'Deposit account not found.']);
            }

            // Update balance_accounts for transfer account (credit)
            $transferBalanceAccount = BalanceAccount::where('accountnumber_id', $transferAccount->id)
                ->where('month', $month)
                ->first();
            if ($transferBalanceAccount) {
                $transferBalanceAccount->credit -= $transactionSend->amount;
                $transferBalanceAccount->save();
            } else {
                Log::error("Balance account for transfer account not found: accountnumber_id={$transferAccount->id}, month={$month}");
            }

            // Update balance_accounts for deposit account (debit)
            $depositBalanceAccount = BalanceAccount::where('accountnumber_id', $depositAccount->id)
                ->where('month', $month)
                ->first();
            if ($depositBalanceAccount) {
                $depositBalanceAccount->debit -= $transactionSend->amount;
                $depositBalanceAccount->save();
            } else {
                Log::error("Balance account for deposit account not found: accountnumber_id={$depositAccount->id}, month={$month}");
            }

            // Delete the transaction record
            $transactionSend->delete();

            return response()->json(['message' => 'Transaction Send deleted successfully.']);
        } catch (\Exception $e) {
            Log::error("Failed to delete Transaction Send: " . $e->getMessage());
            return response()->json(['error' => 'Failed to delete Transaction Send.']);
        }
    }







    // milik transaction receive
    public function indexTransactionReceive(Request $request)
    {
        session()->flash('preloader', true);
        session()->flash('page', (object)[
            'page' => 'Transaction',
            'child' => 'Database Transaction Receive',
        ]);

        try {
            // Inisialisasi objek form dengan nilai default
            $form = (object) [
                'search' => $request->search ?? null,
                'type' => $request->type ?? null,
                'sort' => $request->sort ?? null,
                'order' => $request->order ?? null,
                'status' => $request->status ?? null,
                'date' => $request->date ?? null,
            ];

            // Query data berdasarkan parameter pencarian yang diberikan
            $query = Transaction_receive::with(['transferAccount', 'depositAccount']);

            if ($request->filled('search')) {
                $searchTerm = '%' . $request->search . '%';
                $query->where(function ($q) use ($searchTerm) {
                    $q->whereHas('transferAccount', function ($q) use ($searchTerm) {
                        $q->where('name', 'LIKE', $searchTerm)
                            ->orWhere('account_no', 'LIKE', $searchTerm);
                    })->orWhereHas('depositAccount', function ($q) use ($searchTerm) {
                        $q->where('name', 'LIKE', $searchTerm)
                            ->orWhere('account_no', 'LIKE', $searchTerm);
                    })->orWhere('amount', 'LIKE', $searchTerm)
                        ->orWhere('date', 'LIKE', $searchTerm);
                });
            }

            // Filter data berdasarkan tanggal
            if ($request->filled('date')) {
                $searchDate = date('Y-m-d', strtotime($request->date));
                $query->whereDate('date', $searchDate);
            }

            // Mengatur urutan berdasarkan parameter yang dipilih
            if ($request->filled('sort')) {
                if ($request->sort === 'oldest') {
                    $query->orderBy('date', 'asc');
                } elseif ($request->sort === 'newest') {
                    $query->orderBy('date', 'desc');
                }
            }

            // Memuat data dengan pagination
            $data = $query->paginate(10);

            // Menampilkan view dengan data dan form
            return view('components.cash&bank.index-receive', compact('data', 'form'));
        } catch (Exception $err) {
            // Menampilkan pesan error jika terjadi kesalahan
            return dd($err);
        }
    }

    public function createTransactionReceive()
    {
        $students = Student::all();
        $accountCategory = Accountcategory::all();
        $accountNumbers = AccountNumber::all(); // Ambil semua data dari tabel accountnumbers

        return view('components.cash&bank.create-transaction-receive', [
            'accountNumbers' => $accountNumbers,
            'students' => $students,
            'accountCategory' => $accountCategory,
        ]);
    }

    public function storeTransactionReceive(Request $request)
    {
        try {
            $request->validate([
                'transfer_account_id' => 'required',
                'deposit_account_id' => 'required',
                'student_id' => 'required|exists:students,id',
                'amount' => 'required|numeric',
                'date' => 'required|date_format:d/m/Y',
                'description' => 'required',
                'no_transaction' => 'required',
            ]);

            $date = Carbon::createFromFormat('d/m/Y', $request->date)->format('Y-m-d');
            $month = Carbon::createFromFormat('d/m/Y', $request->date)->startOfMonth()->format('Y-m-d');


            Transaction_receive::create([
                'transfer_account_id' => $request->transfer_account_id,
                'deposit_account_id' => $request->deposit_account_id,
                'student_id' => $request->student_id,
                'amount' => $request->amount,
                'date' => $date,
                'description' => $request->description,
                'no_transaction' => $request->no_transaction,
            ]);

            // Update the amount in transfer account (allowing it to go negative)
            $transferAccount = Accountnumber::find($request->transfer_account_id);
            $transferAccount->amount -= $request->amount;
            $transferAccount->save();

            // Update the amount in deposit account
            $depositAccount = Accountnumber::find($request->deposit_account_id);
            $depositAccount->amount += $request->amount;
            $depositAccount->save();




            // Update balance_accounts for transfer account (credit)
            $transferBalanceAccount = BalanceAccount::firstOrNew([
                'accountnumber_id' => $transferAccount->id,
                'month' => $month
            ]);
            $transferBalanceAccount->credit += $request->amount;
            $transferBalanceAccount->debit = $transferBalanceAccount->debit ?? 0;
            $transferBalanceAccount->save();

            // Update balance_accounts for deposit account (debit)
            $depositBalanceAccount = BalanceAccount::firstOrNew([
                'accountnumber_id' => $depositAccount->id,
                'month' => $month
            ]);
            $depositBalanceAccount->debit += $request->amount;
            $depositBalanceAccount->credit = $depositBalanceAccount->credit ?? 0;
            $depositBalanceAccount->save();


            return redirect()->route('transaction-receive.index')->with('success', 'Transaction Receive Created Successfully!');
        } catch (Exception $err) {
            // Tangani kesalahan di sini
            return dd($err);
        }
    }

    public function storeAccountTransactionReceive(Request $request)
    {
        try {
            // Validasi input
            $request->validate([
                'name' => 'required',
                'account_no' => 'required',
                'account_category_id' => 'required',
                // 'amount' => 'required|numeric',
                'description' => 'required',
                // 'beginning_balance' => 'required|numeric',
            ]);

            Accountnumber::create([
                'name' => $request->name,
                'account_no' => $request->account_no,
                'account_category_id' => $request->account_category_id,
                'amount' => $request->amount,
                'description' => $request->description,
                // 'beginning_balance' => $request->beginning_balance,
                // 'ending_balance' => $request->ending_balance,
                // 'transactions_total' => 0, // Set transactions_total default ke 0
            ]);

            // Redirect ke halaman indeks pengeluaran dengan pesan sukses
            return redirect()->route('transaction-receive.create')->with('success', 'Accountnumber created successfully!');
        } catch (\Illuminate\Database\QueryException $ex) {
            if ($ex->errorInfo[1] == 1062) {
                // Handle the integrity constraint violation error
                $errorMessage = "The account name already exists.";
                return redirect()->back()->withErrors(['name' => $errorMessage]);
            } else {
                // Handle other database errors
                return redirect()->back()->withErrors(['message' => 'Database error occurred. Please try again later.']);
            }
        }
    }

    public function deleteTransactionReceive($id)
    {
        try {
            $transactionReceive = Transaction_receive::findOrFail($id);

            // Debug: Log transactionTransfer details
            Log::info('Deleting Transaction Receive:', $transactionReceive->toArray());

            // Debug: Log original date
            Log::info('Original Date:', ['date' => $transactionReceive->date]);

            // Make sure date is in the correct format
            $month = Carbon::createFromFormat('Y-m-d H:i:s', $transactionReceive->date)->startOfMonth()->format('Y-m-d');

            // Debug: Log the computed month
            Log::info('Computed Month:', ['month' => $month]);

            // Update the amount in transfer account (kredit)
            $transferAccount = Accountnumber::find($transactionReceive->transfer_account_id);
            if ($transferAccount) {
                $transferAccount->amount += $transactionReceive->amount; // Reverse Kredit
                $transferAccount->save();
            } else {
                Log::error("Transfer account not found: " . $transactionReceive->transfer_account_id);
                return response()->json(['error' => 'Transfer account not found.']);
            }

            // Update the amount in deposit account (debit)
            $depositAccount = Accountnumber::find($transactionReceive->deposit_account_id);
            if ($depositAccount) {
                $depositAccount->amount -= $transactionReceive->amount; // Reverse Debit
                $depositAccount->save();
            } else {
                Log::error("Deposit account not found: " . $transactionReceive->deposit_account_id);
                return response()->json(['error' => 'Deposit account not found.']);
            }

            // Update balance_accounts for transfer account (credit)
            $transferBalanceAccount = BalanceAccount::where('accountnumber_id', $transferAccount->id)
                ->where('month', $month)
                ->first();
            if ($transferBalanceAccount) {
                $transferBalanceAccount->credit -= $transactionReceive->amount;
                $transferBalanceAccount->save();
            } else {
                Log::error("Balance account for transfer account not found: accountnumber_id={$transferAccount->id}, month={$month}");
            }

            // Update balance_accounts for deposit account (debit)
            $depositBalanceAccount = BalanceAccount::where('accountnumber_id', $depositAccount->id)
                ->where('month', $month)
                ->first();
            if ($depositBalanceAccount) {
                $depositBalanceAccount->debit -= $transactionReceive->amount;
                $depositBalanceAccount->save();
            } else {
                Log::error("Balance account for deposit account not found: accountnumber_id={$depositAccount->id}, month={$month}");
            }

            // Delete the transaction record
            $transactionReceive->delete();

            return response()->json(['message' => 'Transaction Receive deleted successfully.']);
        } catch (\Exception $e) {
            Log::error("Failed to delete Transaction Receive: " . $e->getMessage());
            return response()->json(['error' => 'Failed to delete Transaction Receive.']);
        }
    }
}
