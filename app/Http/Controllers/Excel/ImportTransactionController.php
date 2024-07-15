<?php

namespace App\Http\Controllers\Excel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Imports\JournalImport;
use Maatwebsite\Excel\Facades\Excel;

class ImportTransactionController extends Controller
{
    public function importExcel(Request $request)
    {
        $request->validate([
            'import_transaction' => 'required|mimes:xlsx'
        ]);

        try {
            Excel::import(new JournalImport, $request->file('import_transaction'));
            return redirect()->back()->with('success', 'Transactions data imported successfully.');
        } catch (\Exception $exception) {
            return redirect()->back()->with('error', 'Failed to import transactions data. Error: ' . $exception->getMessage());
        }
    }

    public function downloadTemplate()
    {
        try {
            return response()->download(public_path('downloads/import_transaction.xlsx'));
        } catch (\Exception $err) {
            return redirect()->back()->with('error', 'Failed to download template file.');
        }
    }
}
