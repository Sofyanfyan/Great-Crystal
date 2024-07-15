<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\Jobs\SendPaymentReceived;
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

class NotificationPaymentSuccess extends Controller
{

   public function paymentSuccess($type = 'SPP')
   {
      DB::beginTransaction();
      date_default_timezone_set('Asia/Jakarta');
      try {
         //code...

         if ($type == 'etc') {

            $students = Student::with([
               'bill' => function ($query) {
                  $query
                     ->whereNotIn('type', ["SPP", "Capital Fee", "Paket", "Book", "Uniform"])
                     ->where('paid_date', '>=', Carbon::now()->setTimezone('Asia/Jakarta')->subDay()->format('Y-m-d H:i:s'))
                     ->where('paidOf', true)
                     ->get();
               },
               'relationship'
            ])
               ->whereHas('bill', function ($query) {
                  $query
                     ->whereNotIn('type', ["SPP", "Capital Fee", "Paket", "Book", "Uniform"])
                     ->where('paid_date', '>=', Carbon::now()->setTimezone('Asia/Jakarta')->subDay()->format('Y-m-d H:i:s'))
                     ->where('paidOf', true);
               })->get();
         } else {

            $students = Student::with([
               'bill' => function ($query) use ($type) {
                  $query
                     ->where('type', $type)
                     ->where('paid_date', '>=', Carbon::now()->setTimezone('Asia/Jakarta')->subDay()->format('Y-m-d H:i:s'))
                     ->where('paidOf', true)
                     ->get();
               },
               'relationship'
            ])
               ->whereHas('bill', function ($query) use ($type) {
                  $query
                     ->where('type', $type)
                     ->where('paid_date', '>=', Carbon::now()->setTimezone('Asia/Jakarta')->subDay()->format('Y-m-d H:i:s'))
                     ->where('paidOf', true);
               })->get();
         }

         foreach ($students as $student) {

            foreach ($student->bill as $bill) {
               # code...
               $mailData = [
                  'student' => $student,
                  'bill' => [$bill],
               ];

               $pdfBill = Bill::with(['student' => function ($query) {
                  $query->with('grade');
               }, 'bill_collection', 'bill_installments'])
                  ->where('id', $bill->id)
                  ->first();

               $pdf = app('dompdf.wrapper');
               $pdf->loadView('components.bill.pdf.paid-pdf', ['data' => $pdfBill])->setPaper('a4', 'portrait');

               $pdfReport = null;

               if ($pdfBill->installment) {

                  $pdfReport = app('dompdf.wrapper');
                  $pdfReport->loadView('components.bill.pdf.installment-pdf', ['data' => $pdfBill])->setPaper('a4', 'portrait');
               }

               // return view('emails.payment-success')->with('mailData', $mailData);
               try {
                  //code...
                  foreach ($student->relationship as $relationship) {
                     $mailData['name'] = $relationship->name;
                     Mail::to($relationship->email)->send(new PaymentSuccessMail($mailData, "Payment " . $type . " " . $student->name . " has confirmed!", $pdf, $pdfReport));
                     statusInvoiceMail::create([
                        'bill_id' => $pdfBill->id,
                        'status' => false,
                        'is_paid' => true,
                     ]);
                  }
               } catch (Exception) {

                  statusInvoiceMail::create([
                     'bill_id' => $pdfBill->id,
                     'status' => false,
                     'is_paid' => true,
                  ]);
               }
            }
         }

         DB::commit();
         info('Cron job send notification payment success at ' . now());
      } catch (Exception $err) {

         DB::rollBack();
         info('Cron job send notification payment error at ' . $err);
      }
   }


   public function successClicked($bill_id = 12)
   {
      DB::beginTransaction();
      date_default_timezone_set('Asia/Jakarta');
      info('payment clicked running 1 with id ' . $bill_id);
      try {
         //code...
         $student = Student::with(['bill' => function ($query) use ($bill_id) {
            $query->where('id', $bill_id);
         }, 'relationship'])
            ->whereHas('bill', function ($query) use ($bill_id) {
               $query->where('id', $bill_id);
            })
            ->first();


         // return $student;

         foreach ($student->bill as $bill) {
            # code...
            $mailData = [
               'student' => $student,
               'bill' => [$bill],
            ];

            $pdfBill = Bill::with(['student' => function ($query) {
               $query->with('grade');
            }, 'bill_collection', 'bill_installments'])
               ->where('id', $bill->id)
               ->first();

            try {
               //code...
               $array_email = [];
               foreach ($student->relationship as $idx => $parent) {


                  if ($idx == 0) $mailData['name'] = $parent->name;

                  array_push($array_email, $parent->email);

                  $pdf = app('dompdf.wrapper');
                  $pdf->loadView('components.bill.pdf.paid-pdf', ['data' => $pdfBill])->setPaper('a4', 'portrait');

                  $pdfReport = null;

                  if ($pdfBill->installment) {

                     $pdfReport = app('dompdf.wrapper');
                     $pdfReport->loadView('components.emails.payment-success-pdf', ['data' => $pdfBill])->setPaper('a4', 'portrait');
                  }

                  // Mail::to($array_email)->send(new PaymentSuccessMail($mailData, "Payment for School Fee – " . date('F Y') . " " . $student->name . " has confirmed!", $pdf, $pdfReport));
                  Mail::to($array_email)->send(new PaymentSuccessMail($mailData, "Pembayaran Monthly Fee " . $student->name . " Telah Dikonfirmasi!", $pdf, $pdfReport));

               }
               // return view('emails.payment-success')->with('mailData', $mailData);
               dispatch(new SendPaymentReceived($array_email, $mailData, "Payment " . $bill->type . " " . $student->name . " has confirmed!", $pdfBill));
            } catch (Exception $err) {

               statusInvoiceMail::create([
                  'bill_id' => $pdfBill->id,
                  'status' => false,
                  'is_paid' => true,
               ]);
            }
         }

         DB::commit();
      } catch (Exception $err) {
         info('Error at sent email payment success was clicked');
         info('Errors : ' . $err->getMessage());
         statusInvoiceMail::create([
            'bill_id' => $bill_id,
            'status' => false,
            'is_paid' => true,
         ]);
      }
   }

   public function sendPaymentSuccessNotification($bill_id)
   {
      try {
         // Validasi apakah bill_id valid
         if (!$bill_id) {
            throw new \InvalidArgumentException("Invalid bill ID provided.");
         }

         // Mulai transaksi database
         DB::beginTransaction();

         // Ambil data siswa dengan tagihan yang sesuai
         $student = Student::with(['bill' => function ($query) use ($bill_id) {
            $query->where('id', $bill_id);
         }, 'relationship'])->whereHas('bill', function ($query) use ($bill_id) {
            $query->where('id', $bill_id);
         })->first();

         // Jika tidak ada siswa ditemukan untuk bill ID yang diberikan, lemparkan pengecualian
         if (!$student) {
            throw new \Exception("Student not found for the provided bill ID.");
         }

         // Siapkan data email dan PDF
         foreach ($student->bill as $bill) {
            $mailData = [
               'student' => $student,
               'bill' => [$bill],
            ];

            $pdfBill = Bill::with(['student' => function ($query) {
               $query->with('grade');
            }, 'bill_collection', 'bill_installments'])->where('id', $bill->id)->first();

            $array_email = [];
            foreach ($student->relationship as $idx => $parent) {
               if ($idx == 0) {
                  $mailData['name'] = $parent->name;
               }
               array_push($array_email, $parent->email);
            }

            $pdf = app('dompdf.wrapper');
            $pdf->loadView('components.bill.pdf.paid-pdf', ['data' => $pdfBill])->setPaper('a4', 'portrait');

            $pdfReport = null;
            if ($pdfBill->installment) {
               $pdfReport = app('dompdf.wrapper');
               $pdfReport->loadView('components.emails.payment-success-pdf', ['data' => $pdfBill])->setPaper('a4', 'portrait');
            }

            // Kirim email dan jadwalkan pekerjaan asinkron untuk mengirim pemberitahuan pembayaran
            Mail::to($array_email)->send(new PaymentSuccessMail($mailData, "Payment " . $bill->type . " " . $student->name . " has confirmed!", $pdf, $pdfReport));
            dispatch(new SendPaymentReceived($array_email, $mailData, "Payment " . $bill->type . " " . $student->name . " has confirmed!", $pdfBill));
         }

         // Commit transaksi
         DB::commit();

         // Log keberhasilan pengiriman pemberitahuan pembayaran
         info('Payment success notification sent successfully.');

         // Beri respons sukses
         return redirect('/admin/bills')->with('success', 'Email successfully sent');
      } catch (\Exception $err) {
         // Rollback transaksi jika terjadi kesalahan
         DB::rollBack();

         // Log kesalahan
         info('Error sending payment success notification: ' . $err->getMessage());

         // Buat entri email status invoice jika terjadi kesalahan
         if (isset($pdfBill)) {
            statusInvoiceMail::create([
               'bill_id' => $pdfBill->id,
               'status' => false,
               'is_paid' => true,
            ]);
         }

         // Beri respons dengan pesan kesalahan
         return back()->with('error', 'Failed to send email: ' . $err->getMessage());
      }
   }
}
