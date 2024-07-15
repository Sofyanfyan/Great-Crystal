<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\InvoiceSupplier;
use App\Models\Student;
use App\Models\Teacher;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
   
   public function index()
   {
      try {
         //code...
         session()->flash('page',  $page = (object)[
            'page' => 'dashboard',
            'child' => 'dashboard',
         ]);
         
         $newStudent = Student::where('is_active', true)->orderBy('created_at', 'desc')->get()->count('id');

         $newTeacher = Teacher::where('is_active', true)->get()->count('id');
         
         $newBill = Bill::where('created_at', '>',  Carbon::now()->subDays(30)->setTimezone('Asia/Jakarta')->toDateTimeString())->get()->count('id');

         $billPastDue = Bill::where('paidOf', false)
         ->where('deadline_invoice', '<',  Carbon::now()->setTimezone('Asia/Jakarta')->format('y-m-d'))->get()->count('id');

         $newBillData = Bill::with('student')->orderBy('id', 'desc')->take(6)->get();
         $pastDueData =  Bill::with('student')
         ->where('paidOf', false)
         ->where('deadline_invoice', '<',  Carbon::now()->setTimezone('Asia/Jakarta')->format('y-m-d'))
         ->take(6)
         ->get();

         $teacherData = Teacher::where('is_active', true)->orderBy('id', 'desc')->take(6)->get();

         $studentData = Student::where('is_active', true)->orderBy('id', 'desc')->take(6)->get();
         $invoiceSuppliers = InvoiceSupplier::all();


         $data = (object)[
            'student' => (int)$newStudent,
            'teacher' => (int)$newTeacher,
            'bill' => (int)$newBill,
            'pastDue' => $billPastDue,
            'dataBill' => $newBillData,
            'dataPastDue' => $pastDueData,
            'dataTeacher' => $teacherData,
            'dataStudent' => $studentData,
            'invoiceSuppliers' => $invoiceSuppliers,
         ];

         return view('components.dashboard')->with('data', $data);
      } catch (Exception $err) {
         
         return dd($err);
      }
   }
}