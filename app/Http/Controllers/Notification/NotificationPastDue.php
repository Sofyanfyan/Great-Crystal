<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\Jobs\SendEmailJob;
use App\Mail\BookMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\DemoMail;
use App\Mail\FeeRegisMail;
use App\Mail\PaketMail;
use App\Mail\PaymentSuccessMail;
use App\Mail\SppMail;
use App\Models\Bill;
use App\Models\Book;
use App\Models\statusInvoiceMail;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Carbon;
use PDO;

class NotificationPastDue extends Controller
{
   public function cronChargePastDue($type = 'SPP', $charge = false)
   {
      DB::beginTransaction();
      try {

         date_default_timezone_set('Asia/Jakarta');

         //   if($charge) 
         //   {
         //        $billCharge = Bill::with('bill_installments')->where('paidOf', false)->where('deadline_invoice', '<', date('Y-m-d'))->where('type', $type)->get(['id', 'amount', 'charge', 'installment', 'amount_installment']);
         //        foreach ($billCharge as $bill) {
         //           # code...
         //           Bill::where('id', $bill->id)->update([
         //              'amount'=> $bill->amount + 100_000,
         //              'charge'=> $bill->charge + 100_000,
         //              'amount_installment' => $bill->installment? $bill->amount_installment + 100_000 : $bill->amount_installment,
         //             ]);

         //          foreach ($bill->bill_installments as $installment){

         //             if($installment->pivot->main_id != $installment->pivot->child_id) {

         //                Bill::where('id', $installment->pivot->child_id)->update([
         //                   'amount'=> $bill->amount + 100_000,
         //                ]);
         //             }

         //           }
         //        }
         //   }   

         // if ($charge) {
         //    $billCharge = Bill::with('bill_installments')->where('paidOf', false)->where('deadline_invoice', '<', date('Y-m-d'))->where('type', $type)->get(['id', 'amount', 'charge', 'installment', 'amount_installment']);
         //    foreach ($billCharge as $bill) {
         //       # code...
         //       Bill::where('id', $bill->id)->update([
         //          'charge' => 100000, // Set charge menjadi 100.000
         //       ]);

         //       foreach ($bill->bill_installments as $installment) {
         //          if ($installment->pivot->main_id != $installment->pivot->child_id) {
         //             Bill::where('id', $installment->pivot->child_id)->update([
         //                'charge' => 100000, // Set charge pada tagihan anak menjadi 100.000
         //             ]);
         //          }
         //       }
         //    }
         // }

         if ($charge) {
            $billCharge = Bill::with('bill_installments')->where('paidOf', false)->where('deadline_invoice', '<', date('Y-m-d'))->where('type', $type)->get(['id', 'amount', 'charge', 'installment', 'amount_installment']);
            foreach ($billCharge as $bill) {
               // Update charge tanpa mengubah amount asli
               $updatedCharge = 100000; // Set charge menjadi 100.000
               $totalAmount = $bill->amount + $updatedCharge; // Hitung total pembayaran

               Bill::where('id', $bill->id)->update([
                  'charge' => $updatedCharge, // Perbarui charge menjadi 100.000
                  'amount' => $totalAmount, // Perbarui total pembayaran
               ]);

               foreach ($bill->bill_installments as $installment) {
                  if ($installment->pivot->main_id != $installment->pivot->child_id) {
                     $childBill = Bill::find($installment->pivot->child_id);
                     $childTotalAmount = $childBill->amount + $updatedCharge; // Hitung total pembayaran untuk tagihan anak

                     Bill::where('id', $installment->pivot->child_id)->update([
                        'charge' => $updatedCharge, // Perbarui charge pada tagihan anak
                        'amount' => $childTotalAmount, // Perbarui total pembayaran pada tagihan anak
                     ]);
                  }
               }
            }
         }


         if ($type == 'etc') {

            $data = Student::with(['bill' => function ($query) use ($type) {
               $query
                  ->whereNotIn('type', ['SPP', "Capital Fee", "Paket", "Book", "uniform"])
                  ->where('deadline_invoice', '<', date('Y-m-d'))
                  ->where('paidOf', false)
                  ->get();
            }, 'relationship'])->whereHas('bill', function ($query) use ($type) {
               $query
                  ->whereNotIn('type', ['SPP', "Capital Fee", "Paket", "Book", "uniform"])
                  ->where('paidOf', false)
                  ->where('deadline_invoice', '<', date('Y-m-d'));
            })->get();
         } else {

            $data = Student::with(['bill' => function ($query) use ($type) {
               $query
                  ->where('type', $type)
                  ->where('deadline_invoice', '<', date('Y-m-d'))
                  ->where('paidOf', false)
                  ->get();
            }, 'relationship'])->whereHas('bill', function ($query) use ($type) {
               $query
                  ->where('type', $type)
                  ->where('paidOf', false)
                  ->where('deadline_invoice', '<', date('Y-m-d'));
            })->get();
         }



         info($data);

         foreach ($data as $student) {

            foreach ($student->bill as $bill) {
               # code...
               $mailData = [
                  'student' => $student,
                  'bill' => strtolower($type) == 'book' ? $bill : [$bill],
                  'past_due' => true,
                  'charge' => $charge,
                  'change' => false,
                  'is_paid' => false,
               ];

               try {
                  //code...

                  $bill_type = $bill->type;

                  if ($bill->type == "SPP") {
                     $bill_type = "Monthly Fee";
                  } else if ($bill->type == "Book") {
                     $bill_type = "Material Fee";
                  }

                  $subs = $charge ? "Pemberitahuan Tagihan " . $bill->type . " terkena charge karena sudah melewati jatuh tempo" : "Tagihan " . $bill->type . " " . $student->name . " sudah melewati jatuh tempo";

                  $array_email = [];

                  foreach ($student->relationship as $key => $parent) {

                     if ($key == 0) $mailData['name'] = $parent->name;

                     array_push($array_email, $parent->email);
                     //   return view('emails.spp-mail')->with('mailData', $mailData);

                     //   return view('emails.fee-regis-mail')->with('mailData', $mailData);

                     //   return view('emails.paket-mail')->with('mailData', $mailData);

                     // return view('emails.book-mail')->with('mailData', $mailData);
                     $pdf = app('dompdf.wrapper');
                     // Perhatikan bahwa kita menggunakan $mailData['bill'][0] untuk setiap siswa
                     $pdf->loadView('components.bill.pdf.paid-pdf', ['data' => $mailData['bill'][0]])->setPaper('a4', 'portrait');
                     // Mail::to($parent->email)->send(new SppMail($mailData, "Tagihan SPP " . $mailData['student']->name .  " bulan ini, " . date('F Y') . " sudah dibuat.", $pdf));
                     Mail::to($parent->email)->send(new SppMail($mailData, "Tagihan Monthly Fee " . $mailData['student']->name .  " bulan " . date('F Y') . " sudah dibuat.", $pdf));
                  }

                  dispatch(new SendEmailJob($array_email, strtolower($type), $mailData, $subs, $bill->id));
               } catch (Exception) {

                  statusInvoiceMail::create([
                     'bill_id' => $bill->id,
                     'status' => false,
                     'charge' => $charge,
                     'past_due' => true,
                  ]);
               }
            }
         }

         DB::commit();

         info("Cron Job past due success at " . date('d-m-Y'));
      } catch (Exception $err) {
         DB::rollBack();
         info("Cron Job pastdue Error at: " . $err);
      }
   }
}
