<?php

namespace App\Http\Controllers\Admin;

use Exception;
use Carbon\Carbon;
use App\Models\SupplierData;
use Illuminate\Http\Request;
use App\Models\Accountnumber;
use App\Models\Accountcategory;
use App\Models\InvoiceSupplier;
use App\Models\Transaction_send;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\TransactionSendSupplier;
use Illuminate\Support\Facades\File;

class InvoiceSupplierController extends Controller
{
    public function indexInvoiceSupplier(Request $request)
    {
        session()->flash('preloader', true);
        session()->flash('page', (object)[
            'page' => 'Supplier',
            'child' => 'database Invoice Supplier',
        ]);

        try {
            // Inisialisasi objek form dengan nilai default
            $form = (object) [
                'search' => $request->search ?? null,
                'sort' => $request->sort ?? null,
                'status' => $request->status ?? null,
                'date' => $request->date ?? null,
                'type' => $request->type ?? null,
                'order' => $request->order ?? null,
            ];

            // Query data berdasarkan parameter pencarian yang diberikan
            $query = InvoiceSupplier::with(['transferAccount', 'supplier']);

            if ($request->filled('search')) {
                $searchTerm = '%' . $request->search . '%';
                $query->where('supplier_name', 'LIKE', $searchTerm)
                    ->orWhere('no_invoice', 'LIKE', $searchTerm)
                    ->orWhere('nota', 'LIKE', $searchTerm)
                    ->orWhere('amount', 'LIKE', $searchTerm)
                    ->orWhere('date', 'LIKE', $searchTerm);
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

            // Filter data berdasarkan status pembayaran
            if ($request->filled('status')) {
                if ($request->status === 'Paid') {
                    $query->where('payment_status', 'Paid');
                } elseif ($request->status === 'Not Yet') {
                    $query->where('payment_status', 'Not Yet');
                }
            }

            // Memuat data dengan pagination dan menambahkan parameter filter ke URL paginasi
            $data = $query->paginate(10)->appends([
                'search' => $request->search,
                'sort' => $request->sort,
                'status' => $request->status,
                'date' => $request->date,
            ]);

            // Menampilkan view dengan data dan form
            return view('components.supplier.invoice.index', compact('data', 'form'));
        } catch (Exception $err) {
            // Menampilkan pesan error jika terjadi kesalahan
            return dd($err);
        }
    }


    public function uploadProofOfPaymentView($id)
    {
        $invoice = InvoiceSupplier::findOrFail($id);
        $accountNumbers = Accountnumber::all();
        $accountCategory = Accountcategory::all();


        return view('components.supplier.invoice.upload-proof', compact('invoice', 'accountNumbers', 'accountCategory'));
    }

    public function uploadProofOfPayment(Request $request, $id)
    {
        try {
            // Ambil invoice berdasarkan ID
            $invoice = InvoiceSupplier::findOrFail($id);

            // Validasi data request
            $request->validate([
                'image_path' => 'required|image|mimes:jpeg,png,jpg,gif',
                'description' => 'required|string',
                'payment_status' => 'required|in:Paid,Not Yet',
                'payment_method' => 'required|in:Cash,Bank',
                'transfer_account_id' => 'required',
                // 'deposit_account_id' => 'required',
            ]);

            // Handle file upload
            if ($request->hasFile('image_path')) {
                $image = $request->file('image_path');
                $imageName = time() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('uploads'), $imageName);

                // Update invoice supplier data
                $invoice->update([
                    'image_path' => $imageName,
                    'payment_status' => $request->payment_status,
                    'payment_method' => $request->payment_method,
                    'description' => $request->description,
                    'transfer_account_id' => $request->transfer_account_id,
                    // 'deposit_account_id' => $request->deposit_account_id,
                ]);

                // Debugging log
                Log::info('Invoice updated: ', $invoice->toArray());

                return redirect()->route('invoice-supplier.index')->with('success', 'Proof of payment uploaded successfully.');
            } else {
                return back()->withErrors(['error' => 'Failed to upload proof of payment. Please try again.']);
            }
        } catch (\Exception $err) {
            Log::error('Error uploading proof of payment: ' . $err->getMessage());
            return back()->withErrors(['error' => 'Failed to upload proof of payment. Please try again.']);
        }
    }

    public function storeAccount(Request $request)
    {
        try {
            // Validasi input
            $request->validate([
                'name' => 'required',
                'account_no' => 'required',
                'account_category_id' => 'required',
                'description' => 'required',
                // 'amount' => ['required', 'numeric'], // Validasi numerik
            ]);

            // Buat data akun baru
            Accountnumber::create([
                'name' => $request->name,
                'account_no' => $request->account_no,
                'account_category_id' => $request->account_category_id,
                'description' => $request->description,
                // 'amount' => str_replace('.', '', $request->amount), // Hapus pemisah ribuan sebelum menyimpan
            ]);

            // Redirect ke halaman indeks akun dengan pesan sukses
            return redirect()->route('invoice-supplier.upload-proof', $request->invoice_id)->with('success', 'Accountnumber created successfully!');
        } catch (\Illuminate\Database\QueryException $ex) {
            if ($ex->errorInfo[1] == 1062) {
                // Handle kesalahan pelanggaran integritas constraint
                $errorMessage = "The account name already exists.";
                return redirect()->back()->withErrors(['name' => $errorMessage]);
            } else {
                // Handle kesalahan database lainnya
                return redirect()->back()->withErrors(['message' => 'Database error occurred. Please try again later.']);
            }
        }
    }





    public function createInvoiceSupplier()
    {
        $supplierDatas = SupplierData::all();

        return view('components.supplier.invoice.create', [
            'supplierDatas' => $supplierDatas,
        ]);
    }

    // public function storeInvoiceSupplier(Request $request)
    // {
    //     try {
    //         $request->validate([
    //             'no_invoice' => 'required|unique:invoice_suppliers,no_invoice',
    //             'supplier_id' => 'required|exists:supplier_data,id',
    //             'amount' => 'required|numeric',
    //             'date' => 'required|date_format:Y-m-d',
    //             'nota' => 'required',
    //             'deadline_invoice' => 'required|date_format:Y-m-d',
    //             // 'pph' => 'required'
    //         ]);

    //         $amount = $request->amount;
    //         $pph_percentage = 0;

    //         if ($request->ppn_status === '2%') {
    //             $pph_percentage = 2;
    //             $amount *= 0.98;
    //         } else if ($request->ppn_status === '15%') {
    //             $pph_percentage = 15;
    //             $amount *= 0.85;
    //         }

    //         $invoice = new InvoiceSupplier();
    //         $invoice->no_invoice = $request->no_invoice;
    //         $invoice->supplier_id = $request->supplier_id; // Gunakan supplier_id langsung dari request
    //         $invoice->amount = $amount;
    //         $invoice->pph = $request->pph;
    //         $invoice->pph_percentage = $pph_percentage;
    //         $invoice->date = Carbon::parse($request->date)->format('Y-m-d');
    //         $invoice->nota = $request->nota;
    //         $invoice->deadline_invoice = Carbon::parse($request->deadline_invoice)->format('Y-m-d');
    //         $invoice->payment_status = 'Not Yet';
    //         $invoice->payment_method = 'Cash';
    //         $invoice->save();

    //         return redirect()->route('invoice-supplier.index')->with('success', 'Invoice Supplier Created Successfully!');
    //     } catch (\Exception $e) {
    //         Log::error('Error creating invoice supplier: ' . $e->getMessage());
    //         return back()->withErrors(['error' => 'Something went wrong. Please try again.']);
    //     }
    // }


    public function storeInvoiceSupplier(Request $request)
    {
        try {
            $request->validate([
                'no_invoice' => 'required|unique:invoice_suppliers,no_invoice',
                'supplier_id' => 'required|exists:supplier_data,id',
                'amount' => 'required|numeric',
                'date' => 'required|date_format:Y-m-d',
                'nota' => 'required',
                'deadline_invoice' => 'required|date_format:Y-m-d',
                // Kolom pph dan pph_percentage tidak wajib diisi
            ]);

            $amount = $request->amount;
            $pph_percentage = null;

            if ($request->pph === '2%') {
                $pph_percentage = 2;
                $amount *= 0.98; // Mengurangi 2%
            } else if ($request->pph === '15%') {
                $pph_percentage = 15;
                $amount *= 0.85; // Mengurangi 15%
            }

            $invoice = new InvoiceSupplier();
            $invoice->no_invoice = $request->no_invoice;
            $invoice->supplier_id = $request->supplier_id; // Gunakan supplier_id langsung dari request
            $invoice->amount = $amount;
            $invoice->pph = $request->pph ?? null; // Tetapkan null jika tidak diisi
            $invoice->pph_percentage = $pph_percentage ?? null; // Tetapkan null jika tidak diisi
            $invoice->date = Carbon::parse($request->date)->format('Y-m-d');
            $invoice->nota = $request->nota;
            $invoice->deadline_invoice = Carbon::parse($request->deadline_invoice)->format('Y-m-d');
            $invoice->payment_status = 'Not Yet';
            $invoice->payment_method = 'Cash';
            $invoice->description = $request->description;
            $invoice->save();

            return redirect()->route('invoice-supplier.index')->with('success', 'Invoice Supplier Created Successfully!');
        } catch (\Exception $e) {
            Log::error('Error creating invoice supplier: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Something went wrong. Please try again.']);
        }
    }




    public function destroyInvoiceSupplier($id)
    {
        try {
            $invoice = InvoiceSupplier::findOrFail($id);

            // Ambil path gambar
            $imagePath = public_path('uploads/' . $invoice->image_path);

            // Hapus file gambar jika ada
            if (File::exists($imagePath)) {
                File::delete($imagePath);
            }

            // Hapus data invoice dari database
            $invoice->delete();

            return response()->json(['message' => 'Invoice supplier deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete invoice supplier.']);
        }
    }



    // Punya Supplier
    public function indexsupplier(Request $request)
    {
        session()->flash('preloader', true);
        session()->flash('page', (object)[
            'page' => 'Supplier',
            'child' => 'database Supplier Data',
        ]);

        try {
            // Inisialisasi objek form dengan nilai default
            $form = (object) [
                'search' => $request->search ?? null,
                'type' => $request->type ?? null,
                'sort' => $request->sort ?? null,
                'order' => $request->order ?? null,
                'status' => $request->status ?? null,
                'created_at' => $request->created_at ?? null,
            ];

            // Query data berdasarkan parameter pencarian yang diberikan
            $query = SupplierData::query();

            if ($request->filled('search')) {
                $searchTerm = '%' . $request->search . '%';
                $query->where('name', 'LIKE', $searchTerm)
                    ->orWhere('instansi_name', 'LIKE', $searchTerm)
                    ->orWhere('no_rek', 'LIKE', $searchTerm)
                    ->orWhere('created_at', 'LIKE', $searchTerm);
            }

            // Filter data berdasarkan tanggal
            if ($request->filled('created_at')) {
                $searchDate = date('Y-m-d', strtotime($request->created_at));
                $query->whereDate('created_at', $searchDate);
            }

            // Mengatur urutan berdasarkan parameter yang dipilih
            if ($request->filled('sort')) {
                if ($request->sort === 'oldest') {
                    $query->orderBy('created_at', 'asc');
                } elseif ($request->sort === 'newest') {
                    $query->orderBy('created_at', 'desc');
                }
            }

            // Memuat data dengan pagination
            $data = $query->paginate(10);

            // Menampilkan view dengan data dan form
            return view('components.supplier.data.index', compact('data', 'form'));
        } catch (Exception $err) {
            // Menampilkan pesan error jika terjadi kesalahan
            return dd($err);
        }
    }

    public function createSupplier()
    {
        return view('components.supplier.data.create');
    }

    public function storeSupplier(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'no_telp' => 'required',
            'email' => 'required',
            'address' => 'required',
            'city' => 'required',
            'province' => 'required',
            'accountnumber' => 'required',
            'accountnumber_holders_name' => 'required',
            'bank_name' => 'required',

        ]);

        SupplierData::create([
            'name' => $request->name,
            'no_telp' => $request->no_telp,
            'email' => $request->email,
            'fax' => $request->fax,
            'address' => $request->address,
            'city' => $request->city,
            'province' => $request->province,
            'post_code' => $request->post_code,
            'accountnumber' => $request->accountnumber,
            'accountnumber_holders_name' => $request->accountnumber_holders_name,
            'bank_name' => $request->bank_name,
            'description' => $request->description,
        ]);

        return redirect()->route('supplier.index')->with('success', 'Supplier Data Created successfully!');
    }

    public function destroySupplier($id)
    {
        try {
            $invoice = SupplierData::findOrFail($id);

            $invoice->delete();

            return response()->json(['message' => 'Supplier Data deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete Supplier Data.']);
        }
    }
}
