<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\Mail\BookMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\DemoMail;
use App\Mail\FeeRegisMail;
use App\Mail\PaymentSuccessMail;
use App\Mail\SppMail;
use App\Models\Bill;
use App\Models\Book;
use App\Models\statusInvoiceMail;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Carbon;

class NotificationBillCreated extends Controller
{
    
    public function spp()
    {
        
      DB::beginTransaction();

      try {
         //code...
         date_default_timezone_set('Asia/Jakarta');

         $billCreated = [];
         
         $data = Student::with(['relationship', 'spp_student' => function($query) {
            $query->where('type', 'SPP')->get();
         }, 
         'grade' => function($query) {
            $query->with(['spp' => function($query) {
               $query->where('type', 'SPP')->get();
            }]);
         }])->where('is_active', true)->orderBy('id', 'asc')->get();
         
         foreach($data as $student)
         {
            $createBill = Bill::create([
               'student_id' => $student->id,
               'type' => 'SPP',
               'subject' => 'SPP - ' . date('M Y'),
               'amount' => $student->spp_student? $student->spp_student->amount : $student->grade->spp->amount,
               'paidOf' => false,
               'discount' => $student->spp_student ? ($student->spp_student->discount? $student->spp_student->discount : null) : null,
               'deadline_invoice' => Carbon::now()->setTimezone('Asia/Jakarta')->addDays(10)->format('Y-m-d'),
               'installment' => 0,
            ]);
            
            $mailDatas = [
               'student' => $student,
               'bill' => [$createBill],
               'past_due' => false,
            ];

            array_push($billCreated, $mailDatas);          
         }
         
         DB::commit();
         
         foreach($billCreated as $idx => $mailData) {

               $pdfBill = Bill::with(['student' => function ($query) {
                  $query->with('grade');
               }, 'bill_collection', 'bill_installments'])
               ->where('id', $mailData['bill'][0]->id)
               ->first();
                
                $pdf = app('dompdf.wrapper');
                $pdf->loadView('components.bill.pdf.paid-pdf', ['data' => $pdfBill])->setPaper('a4', 'portrait');         
               
            try {
               foreach($data[$idx]->relationship as $el)
               {
                     //code...
                     $mailData['name'] = $el->name;
                     Mail::to($el->email)->send(new SppMail($mailData, "Tagihan SPP " . $data[$idx]->name.  " bulan ini, ". date('l, d F Y') ." sudah dibuat.", $pdf));
               
               }
               statusInvoiceMail::create([
                     'bill_id' => $pdfBill->id,
                  ]);

            } catch (Exception) {
                     
               statusInvoiceMail::create([
                  'bill_id' => $pdfBill->id,
                  'status' => false,
               ]);
            }
         }

         info("Cron Job create spp success at ". date('d-m-Y'));
      } catch (Exception $err) {
         //throw $th;
         DB::rollBack();
         info("Cron Job create spp error: ". $err, []);
         return dd($err);
      }

    }

    public function paket() 
    {
        try {
  
           $data = Student::with([
              'bill' => function($query)  {
                 $query
                 ->where('type', "Capital Fee")
                 ->where('deadline_invoice', '=', Carbon::now()->setTimezone('Asia/Jakarta')->addDays(9)->format('y-m-d'))
                 ->where('paidOf', false)
                 ->get();
           },
              'relationship'
           ])
           ->whereHas('bill', function($query) {
                 $query
                 ->where('type', "Capital Fee")
                 ->where('deadline_invoice', '=', Carbon::now()->setTimezone('Asia/Jakarta')->addDays(9)->format('y-m-d'))
                 ->where('paidOf', false);
           })
           ->get();
  
           
           foreach ($data as $student) {
              
              foreach ($student->bill as $createBill) {

                $mailData = [
                    'student' => $student,
                    'bill' => [$createBill],
                    'past_due' => false,
                ];
  
  
                $pdfBill = Bill::with(['student' => function ($query) {
                   $query->with('grade');
                }, 'bill_installments'])
                ->where('id', $createBill->id)
                ->first();
  
                  
                  $pdf = app('dompdf.wrapper');
                  $pdf->loadView('components.bill.pdf.paid-pdf', ['data' => $pdfBill])->setPaper('a4', 'portrait'); 
  
                  $pdfReport = null;
  
                 if($createBill->installment){
                    
                    $pdfReport = app('dompdf.wrapper');
                    $pdfReport->loadView('components.bill.pdf.installment-pdf', ['data' => $pdfBill])->setPaper('a4', 'portrait'); 
                 }
  
                try {
  
                foreach($student->relationship as $parent)
                {
                   $mailData['name'] = $parent->name;
                   // return view('emails.fee-regis-mail')->with('mailData', $mailData);
                   Mail::to($parent->email)->send(new FeeRegisMail($mailData, "Tagihan Capital Fee " . $student->name.  " bulan ini, ". date('l, d F Y') ." sudah dibuat.", $pdf, $pdfReport));
                }
  
                 statusInvoiceMail::create([
                    'status' =>true,
                    'bill_id' => $createBill->id,
                 ]);
  
                 } catch (Exception $err) {
  
                    statusInvoiceMail::create([
                    'status' =>false,
                    'bill_id' => $createBill->id,
                    ]);
                 }
                 
              }
           }
  
           info('Cron notification Fee Register success at ' . now());
           
        } catch (Exception $err) {

           info('Cron notification Fee Register error at ' . now());
        }
    }

    public function feeRegister()
    {
        try {
  
           $data = Student::with([
              'bill' => function($query)  {
                 $query
                 ->where('type', "Capital Fee")
                 ->where('deadline_invoice', '=', Carbon::now()->setTimezone('Asia/Jakarta')->addDays(9)->format('y-m-d'))
                 ->where('paidOf', false)
                 ->get();
           },
              'relationship'
           ])
           ->whereHas('bill', function($query) {
                 $query
                 ->where('type', "Capital Fee")
                 ->where('deadline_invoice', '=', Carbon::now()->setTimezone('Asia/Jakarta')->addDays(9)->format('y-m-d'))
                 ->where('paidOf', false);
           })
           ->get();
  
           
           foreach ($data as $student) {
              
              foreach ($student->bill as $createBill) {
                 
                 // return 'nyampe';
                 $mailData = [
                    'student' => $student,
                    'bill' => [$createBill],
                    'past_due' => false,
                 ];
  
  
                 $pdfBill = Bill::with(['student' => function ($query) {
                    $query->with('grade');
                 }, 'bill_installments'])
                 ->where('id', $createBill->id)
                 ->first();
  
                  
                  $pdf = app('dompdf.wrapper');
                  $pdf->loadView('components.bill.pdf.paid-pdf', ['data' => $pdfBill])->setPaper('a4', 'portrait'); 
  
                  $pdfReport = null;
  
                 if($createBill->installment){
                    
                    $pdfReport = app('dompdf.wrapper');
                    $pdfReport->loadView('components.bill.pdf.installment-pdf', ['data' => $pdfBill])->setPaper('a4', 'portrait'); 
                 }
  
                try {
  
                foreach($student->relationship as $parent)
                {
                   $mailData['name'] = $parent->name;
                   // return view('emails.fee-regis-mail')->with('mailData', $mailData);
                   Mail::to($parent->email)->send(new FeeRegisMail($mailData, "Tagihan Capital Fee " . $student->name.  " bulan ini, ". date('l, d F Y') ." sudah dibuat.", $pdf, $pdfReport));
                }
  
                 statusInvoiceMail::create([
                    'status' =>true,
                    'bill_id' => $createBill->id,
                 ]);
  
                 } catch (Exception $err) {
  
                    statusInvoiceMail::create([
                    'status' =>false,
                    'bill_id' => $createBill->id,
                    ]);
                 }
                 
              }
           }
  
           info('Cron notification Fee Register success at ' . now());
           
        } catch (Exception $err) {

           info('Cron notification Fee Register error at ' . now());
        }
    }

    public function book() 
    {
        try {
           //sementara gabisa kirim email push array dulu
  
           $data = Student::with([
              'bill' => function($query)  {
                 $query
                 ->with('bill_collection')
                 ->where('type', "Book")
                 ->where('created_at', '>=', Carbon::now()->setTimezone('Asia/Jakarta')->subDay()->format('Y-m-d H:i:s'))
                 ->where('paidOf', false)
                 ->get();
           },
              'relationship'
           ])
           ->whereHas('bill', function($query) {
                 $query
                 ->where('type', "Book")
                 ->where('created_at', '>=', Carbon::now()->setTimezone('Asia/Jakarta')->subDay()->format('Y-m-d H:i:s'))
                 ->where('paidOf', false);
           })
           ->get();
           
           foreach ($data as $student) {
              
              foreach ($student->bill as $createBill) {
                 
                 $mailData = [
                    'student' => $student,
                    'bill' => $createBill,
                    'past_due' => false,
                 ];
  
  
                 $pdfBill = Bill::with(['student' => function ($query) {
                    $query->with('grade');
                 }, 'bill_installments', 'bill_collection'])
                 ->where('id', $createBill->id)
                 ->first();
  
                  
                  $pdf = app('dompdf.wrapper');
                  $pdf->loadView('components.bill.pdf.paid-pdf', ['data' => $pdfBill])->setPaper('a4', 'portrait'); 
  
  
                 
                 try {
  
                    foreach($student->relationship as $parent)
                 {
                    $mailData['name'] = $parent->name;
                    Mail::to($parent->email)->send(new BookMail($mailData, "Tagihan Buku " . $student->name.  " ". date('l, d F Y') ." sudah dibuat.", $pdf));
                 }
  
                 statusInvoiceMail::create([
                    'status' =>true,
                    'bill_id' => $createBill->id,
                 ]);
  
                 } catch (Exception $err) {
                    statusInvoiceMail::create([
                    'status' =>false,
                    'bill_id' => $createBill->id,
                    ]);
                 }
                 
              }
           }
  
  
           info('Cron notification Books success at ' . now());
           
        } catch (Exception $err) {
            
           return dd($err);
           info('Cron notification Books error at ' . now());
        }
    }

    public function uniform() 
    {
        try {
           //sementara gabisa kirim email push array dulu
  
           $data = Student::with([
              'bill' => function($query)  {
                 $query
                 ->where('type', "Uniform")
                 ->where('created_at', '>=', Carbon::now()->setTimezone('Asia/Jakarta')->subDay()->format('Y-m-d H:i:s'))
                 ->where('paidOf', false)
                 ->get();
           },
              'relationship'
           ])
           ->whereHas('bill', function($query) {
                 $query
                 ->where('type', "Uniform")
                 ->where('created_at', '>=', Carbon::now()->setTimezone('Asia/Jakarta')->subDay()->format('Y-m-d H:i:s'))
                 ->where('paidOf', false);
           })
           ->get();
  
           
           foreach ($data as $student) {
              
              foreach ($student->bill as $createBill) {
                 
                 // return 'nyampe';
                 $mailData = [
                    'student' => $student,
                    'bill' => [$createBill],
                    'past_due' => false,
                 ];
  
  
                 $pdfBill = Bill::with(['student' => function ($query) {
                    $query->with('grade');
                 }])
                 ->where('id', $createBill->id)
                 ->first();
  
                  
                  $pdf = app('dompdf.wrapper');
                  $pdf->loadView('components.bill.pdf.paid-pdf', ['data' => $pdfBill])->setPaper('a4', 'portrait'); 
  
                 try {
  
                    foreach($student->relationship as $parent)
                 {
                    $mailData['name'] = $parent->name;
                    // return view('emails.spp-mail')->with('mailData', $mailData);
                    Mail::to($parent->email)->send(new SppMail($mailData, "Tagihan Uniform " . $student->name.  " bulan ini, ". date('l, d F Y') ." sudah dibuat.", $pdf));
                    
                 }
  
                 statusInvoiceMail::create([
                    'status' =>true,
                    'bill_id' => $createBill->id,
                 ]);
  
                 } catch (Exception $err) {
  
                    statusInvoiceMail::create([
                    'status' =>false,
                    'bill_id' => $createBill->id,
                    ]);
                 }
                 
              }
           }
  
  
           info('Cron notification Fee Register success at ' . now());
           
        } catch (Exception $err) {
           
           return dd($err);
           info('Cron notification Fee Register error at ' . now());
        }
    }
}
