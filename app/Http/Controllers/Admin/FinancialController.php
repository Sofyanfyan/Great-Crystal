<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Expenditure;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;

class FinancialController extends Controller
{
    public function indexIncome()
    {
        session()->flash('page', (object)[
            'page' => 'Financial',
            'child' => 'database income',
        ]);

        // $bills = Bill::where('paidOf', true)->get()->all();
        $bills = Bill::where('paidOf', true)->paginate(10);

        $totalPaid = Bill::where('paidOf', true)->sum('amount');

        return view('components.income.index', [
            'totalpaid' => $totalPaid,
            'bills' => $bills,
        ]);
    }


    public function indexExpenditure(Request $request)
    {
        $totalExpenditure = Expenditure::sum('amount_spent');

        session()->flash('page', (object)[
            'page' => 'Financial',
            'child' => 'database expenditure',
        ]);

        try {
            $form = (object) [
                'sort' => $request->sort ? $request->sort : null,
                'order' => $request->order ? $request->order : null,
                'status' => $request->status ? $request->status : null,
                'search' => $request->search ? $request->search : null,
                'type' => $request->type ? $request->type :  null,
            ];

            $data = [];

            // Mengatur default urutan
            $order = $request->sort ? $request->sort : 'desc';

            // Query data berdasarkan parameter yang diberikan
            if ($request->has('search')) {
                $data = Expenditure::where('type', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('description', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('amount_spent', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('spent_at', 'LIKE', '%' . $request->search . '%')
                    ->orderBy($request->order ?? 'created_at', $order)
                    ->paginate(10);
            } else {
                $data = Expenditure::orderBy('created_at', $order)->paginate(10);
            }

            return view('components.expenditure.index')->with('data', $data)->with('form', $form)->with('totalExpenditure', $totalExpenditure);
        } catch (Exception $err) {
            return dd($err);
        }
    }


    public function createExpenditure()
    {
        return view('components.expenditure.create');
    }


    public function storeExpenditure(Request $request)
    {
        // Validasi input
        $request->validate([
            'type' => 'required',
            'spent_at' => 'required|date_format:d/m/Y',
            'amount_spent' => 'required|numeric', // Ubah aturan validasi menjadi 'numeric'
            'description' => 'required',
        ]);

        // Konversi format tanggal menggunakan Carbon
        $spent_at = Carbon::createFromFormat('d/m/Y', $request->spent_at)->format('Y-m-d');

        // Simpan data pengeluaran ke dalam database
        Expenditure::create([
            'spent_at' => $spent_at,
            'amount_spent' => $request->amount_spent,
            'description' => $request->description,
            'type' => $request->type,
        ]);

        // Redirect ke halaman indeks pengeluaran dengan pesan sukses
        return redirect()->route('expenditure.index')->with('success', 'Expenditure created successfully!');
    }

    public function editExpenditure($id)
    {
        $expenditure = Expenditure::findOrFail($id);
        return view('components.expenditure.edit', ['expenditure' => $expenditure]);
    }

    public function updateExpenditure(Request $request, $id)
    {
        // Validasi input
        $request->validate([
            'type' => 'required',
            'spent_at' => 'required|date_format:d/m/Y',
            'amount_spent' => 'required|numeric', // Ubah aturan validasi menjadi 'numeric'
            'description' => 'required',
        ]);

        // Temukan data yang akan diupdate
        $expenditure = Expenditure::findOrFail($id);

        // Konversi format tanggal menggunakan Carbon
        $spent_at = Carbon::createFromFormat('d/m/Y', $request->spent_at)->format('Y-m-d');

        // Update data pengeluaran
        $expenditure->update([
            'spent_at' => $spent_at,
            'amount_spent' => $request->amount_spent,
            'description' => $request->description,
            'type' => $request->type,
        ]);

        // Redirect ke halaman indeks pengeluaran dengan pesan sukses
        return redirect()->route('expenditure.index')->with('success', 'Expenditure updated successfully!');
    }


    public function destroyExpenditure($id)
    {
        // Temukan data yang akan dihapus
        $expenditure = Expenditure::findOrFail($id);

        // Simpan nilai total pengeluaran sebelum penghapusan
        $totalExpenditureBeforeDeletion = Expenditure::sum('amount_spent');

        // Lakukan soft delete
        $expenditure->delete();

        // Hitung total pengeluaran setelah penghapusan
        $totalExpenditureAfterDeletion = Expenditure::sum('amount_spent');

        // Kurangi jumlah pengeluaran yang dihapus dari total pengeluaran sebelumnya
        $totalExpenditure = $totalExpenditureBeforeDeletion - $expenditure->amount_spent;

        // Simpan total pengeluaran yang baru
        // Misalnya, Anda ingin menyimpannya dalam session
        session()->put('totalExpenditure', $totalExpenditure);

        // Redirect ke halaman indeks pengeluaran dengan pesan sukses
        return redirect()->route('expenditure.index')->with('success', 'Expenditure deleted successfully!');
    }
}
