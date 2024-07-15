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

class NotificationBillCreated extends Controller
{


   public function test()
   {
      try {
         //code...
         $details['email'] = 'your_email@gmail.com';

         // dispatch(new SendEmailJob($details));

         info('cron test running at ' . now());
      } catch (Exception $err) {
         //throw $th;
         info('cron error at ' . $err->getMessage());
      }
   }

   public function spp()
   {

      DB::beginTransaction();

      try {
         //code...
         date_default_timezone_set('Asia/Jakarta');

         $billCreated = [];

         $data = Student::with([
            'relationship', 'spp_student' => function ($query) {
               $query->where('type', 'SPP')->get();
            },
            'grade' => function ($query) {
               $query->with(['spp' => function ($query) {
                  $query->where('type', 'SPP')->get();
               }]);
            }
         ])->where('is_active', true)->orderBy('id', 'asc')->get();

         foreach ($data as $student) {
            $createBill = Bill::create([
               'student_id' => $student->id,
               'type' => 'SPP',
               'subject' => 'SPP - ' . date('M Y'),
               'amount' => $student->spp_student ? $student->spp_student->amount : $student->grade->spp->amount,
               'paidOf' => false,
               'discount' => $student->spp_student ? ($student->spp_student->discount ? $student->spp_student->discount : null) : null,
               'deadline_invoice' => Carbon::now()->setTimezone('Asia/Jakarta')->format('Y-m-10'),
               'installment' => 0,
            ]);

            $mailDatas = [
               'student' => $student,
               'bill' => [$createBill],
               'past_due' => false,
               'charge' => false,
               'change' => false,
               'is_paid' => false,
            ];

            array_push($billCreated, $mailDatas);
         }

         //    foreach ($billCreated as $idx => $mailData) {
         //       try {
         //           $array_email = [];
         //           foreach ($mailData['student']->relationship as $el) {
         //               $mailData['name'] = $mailData['student']->relationship[0]->name;
         //               array_push($array_email, $el->email);
         //               $pdf = app('dompdf.wrapper');
         //               // Perhatikan bahwa kita menggunakan $mailData['bill'][0] untuk setiap siswa
         //               $pdf->loadView('components.bill.pdf.paid-pdf', ['data' => $mailData['bill'][0]])->setPaper('a4', 'portrait');
         //             //   Mail::to($el->email)->send(new SppMail($mailData, "Tagihan SPP " . $mailData['student']->name .  " bulan ini, " . date('F Y') . " sudah dibuat.", $pdf));
         //             Mail::to($el->email)->send(new SppMail($mailData, "Tagihan Monthly Fee " . $mailData['student']->name .  " bulan " . date('F Y') . " sudah dibuat.", $pdf));

         //          }
         //           dispatch(new SendEmailJob($array_email, 'SPP', $mailData, "Pemberitahuan Tagihan Monthly Fee " .  " " . date('F Y') . ".", $mailData['bill'][0]->id));
         //       } catch (Exception $e) {
         //           statusInvoiceMail::create([
         //               'bill_id' => $mailData['bill'][0]->id,
         //               'status' => false,
         //           ]);
         //       }
         //   }

         foreach ($billCreated as $idx => $mailData) {
            try {
               $array_email = [];
               $currentYear = date('Y');
               $nextYear = $currentYear + 1;
               $schoolYear = $currentYear . '/' . $nextYear;

               foreach ($mailData['student']->relationship as $el) {
                  $mailData['name'] = $mailData['student']->relationship[0]->name;
                  array_push($array_email, $el->email);
                  $pdf = app('dompdf.wrapper');
                  $pdf->loadView('components.bill.pdf.paid-pdf', ['data' => $mailData['bill'][0]])->setPaper('a4', 'portrait');
                  // Mail::to($el->email)->send(new PaketMail($mailData, "Tagihan Paket " . $mailData['student']->name . " bulan " . date('F Y') . " sudah dibuat.", $pdf));
                  Mail::to($el->email)->send(new PaketMail($mailData, "Tagihan Biaya Kegiatan " . $mailData['student']->name . " School Year " . $schoolYear . " sudah dibuat.", $pdf));
               }
               dispatch(new SendEmailJob($array_email, 'Paket', $mailData, "Pemberitahuan Tagihan Paket " . " " . date('F Y') . ".", $mailData['bill'][0]->id));
            } catch (Exception $e) {
               statusInvoiceMail::create([
                  'bill_id' => $mailData['bill'][0]->id,
                  'status' => false,
               ]);
            }
         }

         DB::commit();

         info("Cron Job create spp success at " . date('d-m-Y'));
      } catch (Exception $err) {
         //throw $th;
         DB::rollBack();
         info("Cron Job create spp error: " . $err, []);
         return dd($err);
      }
   }


   public function paket()
   {
      try {

         $data = Student::with([
            'bill' => function ($query) {
               $query
                  ->where('type', "Paket")
                  ->where('deadline_invoice', '=', Carbon::now()->setTimezone('Asia/Jakarta')->addDays(9)->format('y-m-d'))
                  ->where('paidOf', false)
                  ->where('subject', '!=', 'Paket')
                  ->where('subject', '!=', '1')
                  ->orWhere('type', "Paket")
                  ->where('created_at', '>=', Carbon::now()->setTimezone('Asia/Jakarta')->subDay()->format('Y-m-d H:i:s'))
                  ->where('installment', null)
                  ->where('subject', 'Paket')
                  ->where('paidOf', false)
                  ->get();
            },
            'relationship'
         ])
            ->whereHas('bill', function ($query) {
               $query
                  ->where('type', "Paket")
                  ->where('deadline_invoice', '=', Carbon::now()->setTimezone('Asia/Jakarta')->addDays(9)->format('y-m-d'))
                  ->where('paidOf', false)
                  ->where('subject', '!=', 'Paket')
                  ->where('subject', '!=', '1')
                  ->orWhere('type', "Paket")
                  ->where('created_at', '>=', Carbon::now()->setTimezone('Asia/Jakarta')->subDay()->format('Y-m-d H:i:s'))
                  ->where('installment', null)
                  ->where('subject', 'Paket')
                  ->where('paidOf', false);
            })
            ->get();

         //   return $data;

         foreach ($data as $student) {

            foreach ($student->bill as $createBill) {

               $mailData = [
                  'student' => $student,
                  'bill' => [$createBill],
                  'past_due' => false,
                  'charge' => false,
                  'change' => false,
                  'is_paid' => false,
               ];


               $subject = $createBill->installment ? "Pemberitahuan Tagihan Package " . $student->name .  " " . date('F Y') . "." : "Pemberitahuan Tagihan Package " . $student->name . ".";

               try {

                  $array_email = [];

                  foreach ($student->relationship as $idx => $parent) {
                     if ($idx == 0) {
                        $mailData['name'] = $parent->name;
                     }
                     array_push($array_email, $parent->email);
                     //  return view('emails.paket-mail')->with('mailData', $mailData);
                  }

                  dispatch(new SendEmailJob($array_email, 'paket', $mailData, $subject, $mailData['bill'][0]->id));
                  // Mail::to($parent->email)->send(new PaketMail($mailData, $subject, $pdf, $pdfReport));

               } catch (Exception $err) {

                  statusInvoiceMail::create([
                     'status' => false,
                     'bill_id' => $createBill->id,
                  ]);
               }
            }
         }

         info('Cron notification Paket success at ' . now());
      } catch (Exception $err) {

         info('Cron notification Paket error at ' . now());
      }
   }

   public function feeRegister()
   {
      try {

         // return  Carbon::now()->setTimezone('Asia/Jakarta')->addDays(9)->format('y-m-d');

         $data = Student::with([
            'bill' => function ($query) {
               $query
                  ->where('type', "Capital Fee")
                  ->where('deadline_invoice', '=', Carbon::now()->setTimezone('Asia/Jakarta')->addDays(9)->format('y-m-d'))
                  ->where('paidOf', false)
                  ->where('subject', '!=', 'Capital Fee')
                  ->where('subject', '!=', '1')
                  ->orWhere('type', "Capital Fee")
                  ->where('created_at', '>=', Carbon::now()->setTimezone('Asia/Jakarta')->subDay()->format('Y-m-d H:i:s'))
                  ->where('installment', null)
                  ->where('subject', 'Capital Fee')
                  ->where('paidOf', false)
                  ->orWhere('type', "Capital Fee")
                  ->where('created_at', '>=', Carbon::now()->setTimezone('Asia/Jakarta')->subDay()->format('Y-m-d H:i:s'))
                  ->where('subject', '1')
                  ->where('paidOf', false)
                  ->get();
            },
            'relationship'
         ])
            ->whereHas('bill', function ($query) {
               $query
                  ->where('type', "Capital Fee")
                  ->where('deadline_invoice', '=', Carbon::now()->setTimezone('Asia/Jakarta')->addDays(9)->format('y-m-d'))
                  ->where('subject', '!=', 'Capital Fee')
                  ->where('subject', '!=', '1')
                  ->where('paidOf', false)
                  ->orWhere('type', "Capital Fee")
                  ->where('created_at', '>=', Carbon::now()->setTimezone('Asia/Jakarta')->subDay()->format('Y-m-d H:i:s'))
                  ->where('installment', null)
                  ->where('subject', 'Capital Fee')
                  ->where('paidOf', false)
                  ->orWhere('type', "Capital Fee")
                  ->where('created_at', '>=', Carbon::now()->setTimezone('Asia/Jakarta')->subDay()->format('Y-m-d H:i:s'))
                  ->where('subject', '1')
                  ->where('paidOf', false);
            })
            ->get();

         //   return $data;

         foreach ($data as $student) {

            foreach ($student->bill as $createBill) {

               // return 'nyampe';
               $mailData = [
                  'student' => $student,
                  'bill' => [$createBill],
                  'past_due' => false,
                  'charge' => false,
                  'change' => false,
                  'is_paid' => false,
               ];

               $subject = $createBill->installment ? "Pemeberitahuan Tagihan Capital Fee " . $student->name .  " " . date('F Y') . "." : "Tagihan Capital Fee " . $student->name . ".";

               try {

                  $array_email = [];

                  foreach ($student->relationship as $idx => $parent) {
                     if ($idx == 0) {
                        $mailData['name'] = $parent->name;
                     }
                     array_push($array_email, $parent->email);
                     //  return view('emails.fee-regis-mail')->with('mailData', $mailData);
                  }

                  dispatch(new SendEmailJob($array_email, 'capital fee', $mailData, $subject, $createBill->id));
               } catch (Exception $err) {

                  statusInvoiceMail::create([
                     'status' => false,
                     'bill_id' => $createBill->id,
                  ]);
               }
            }
         }

         info('Cron notification Fee Register success at ' . now());
      } catch (Exception $err) {

         info('Cron notification Fee Register error at ' . now());
         return dd($err);
      }
   }

   public function book()
   {
      try {
         //sementara gabisa kirim email push array dulu


         $data = Student::with([
            'bill' => function ($query) {
               $query
                  ->with('bill_collection')
                  ->where('type', "Book")
                  ->where('paidOf', false)
                  ->where('created_at', '>=', Carbon::now()->setTimezone('Asia/Jakarta')->subDay()->format('Y-m-d H:i:s'))
                  ->orWhere('date_change_bill', '>=', Carbon::now()->setTimezone('Asia/Jakarta')->subDay()->format('Y-m-d H:i:s'))
                  ->where('type', "Book")
                  ->where('paidOf', false)
                  ->get();
            },
            'relationship'
         ])
            ->whereHas('bill', function ($query) {
               $query
                  ->where('type', "Book")
                  ->where('paidOf', false)
                  ->where('created_at', '>=', Carbon::now()->setTimezone('Asia/Jakarta')->subDay()->format('Y-m-d H:i:s'))
                  ->orWhere('date_change_bill', '>=', Carbon::now()->setTimezone('Asia/Jakarta')->subDay()->format('Y-m-d H:i:s'))
                  ->where('type', "Book")
                  ->where('paidOf', false);
            })
            ->get();

         foreach ($data as $student) {

            foreach ($student->bill as $createBill) {

               $mailData = [
                  'student' => $student,
                  'bill' => $createBill,
                  'past_due' => false,
                  'charge' => false,
                  'change' => false,
                  'is_paid' => false,
               ];

               $is_change = $createBill->date_change_bill ? true : false;
               $mailData['change'] = $is_change;

               try {

                  $array_email = [];

                  foreach ($student->relationship as $key => $parent) {

                     if ($key == 0) {

                        $mailData['name'] = $parent->name;
                     }

                     array_push($array_email, $parent->email);
                     //   return view('emails.book-mail')->with('mailData', $mailData);
                     //   Mail::to($parent->email)->send(new BookMail($mailData, "Tagihan Buku " . $student->name. " sudah dibuat.", $pdf));
                  }

                  dispatch(new SendEmailJob($array_email, 'book', $mailData, "Tagihan Buku " . $student->name . " sudah dibuat.", $createBill->id));
               } catch (Exception $err) {
                  statusInvoiceMail::create([
                     'status' => false,
                     'bill_id' => $createBill->id,
                     'is_change' => $is_change,
                  ]);
               }
            }
         }


         info('Cron notification Books success at ' . now());
      } catch (Exception $err) {

         info('Cron notification Books error at ' . now());
         return dd($err);
      }
   }

   public function uniform()
   {
      try {
         //sementara gabisa kirim email push array dulu

         $data = Student::with([
            'bill' => function ($query) {
               $query
                  ->where('type', "Uniform")
                  ->where('paidOf', false)
                  ->where('created_at', '>=', Carbon::now()->setTimezone('Asia/Jakarta')->subDay()->format('Y-m-d H:i:s'))
                  ->orWhere('date_change_bill', '>=', Carbon::now()->setTimezone('Asia/Jakarta')->subDay()->format('Y-m-d H:i:s'))
                  ->where('type', "Uniform")
                  ->where('paidOf', false)
                  ->get();
            },
            'relationship'
         ])
            ->whereHas('bill', function ($query) {
               $query
                  ->where('type', "Uniform")
                  ->where('paidOf', false)
                  ->where('created_at', '>=', Carbon::now()->setTimezone('Asia/Jakarta')->subDay()->format('Y-m-d H:i:s'))
                  ->orWhere('date_change_bill', '>=', Carbon::now()->setTimezone('Asia/Jakarta')->subDay()->format('Y-m-d H:i:s'))
                  ->where('type', "Uniform")
                  ->where('paidOf', false);
            })
            ->get();

         //   return $data;

         foreach ($data as $student) {

            foreach ($student->bill as $createBill) {

               // return 'nyampe';
               $mailData = [
                  'student' => $student,
                  'bill' => [$createBill],
                  'past_due' => false,
               ];

               $is_change = $createBill->date_change_bill ? true : false;
               $mailData['change'] = $is_change;

               try {

                  $array_email = [];

                  foreach ($student->relationship as $key => $parent) {
                     if ($key == 0) {
                        $mailData['name'] = $parent->name;
                     }

                     array_push($array_email, $parent->email);
                     //   return view('emails.spp-mail')->with('mailData', $mailData);
                     //   Mail::to($parent->email)->send(new SppMail($mailData, "Tagihan Uniform " . $student->name. " sudah dibuat.", $pdf));
                  }

                  dispatch(new SendEmailJob($array_email, 'uniform', $mailData, "Tagihan Uniform " . $student->name . " sudah dibuat.", $createBill->id));
               } catch (Exception $err) {

                  statusInvoiceMail::create([
                     'status' => false,
                     'bill_id' => $createBill->id,
                     'is_change' => $is_change,
                  ]);
               }
            }
         }


         info('Cron notification Fee Register success at ' . now());
      } catch (Exception $err) {

         info('Cron notification Fee Register error at ' . now());
         return dd($err);
      }
   }

   public function changePaket()
   {
      try {

         $data = Student::with([
            'bill' => function ($query) {
               $query
                  ->where('type', "Paket")
                  ->where('paidOf', false)
                  ->where('date_change_bill', '>=', Carbon::now()->setTimezone('Asia/Jakarta')->subDay()->format('Y-m-d H:i:s'))
                  ->where('subject', '1')
                  ->get();
            },
            'relationship'
         ])
            ->whereHas('bill', function ($query) {
               $query
                  ->where('type', "Paket")
                  ->where('date_change_bill', '>=', Carbon::now()->setTimezone('Asia/Jakarta')->subDay()->format('Y-m-d H:i:s'))
                  ->where('subject', '1')
                  ->where('paidOf', false);
            })
            ->get();

         // return $data;


         foreach ($data as $student) {

            foreach ($student->bill as $createBill) {


               $past_due = false;

               if (strtotime($createBill->deadline_invoice) < strtotime(date('y-m-d'))) {
                  $past_due = true;
               }

               $mailData = [
                  'student' => $student,
                  'bill' => [$createBill],
                  'change' => true,
                  'past_due' => $past_due,
                  'charge' => false,
                  'is_paid' => false,
               ];

               try {

                  $array_email = [];

                  foreach ($student->relationship as $key => $parent) {
                     if ($key == 0) $mailData['name'] = $parent->name;
                     array_push($array_email, $parent->email);
                     //  return view('emails.paket-mail')->with('mailData', $mailData);
                     //  Mail::to($parent->email)->send(new PaketMail($mailData, "Tagihan Paket " . $student->name.  " berhasil diubah, pada tanggal ". date('l, d F Y'), $pdf, $pdfReport));
                  }

                  dispatch(new SendEmailJob($array_email, 'paket', $mailData, "Tagihan Paket " . $student->name .  " berhasil diubah, pada tanggal " . date('l, d F Y'), $createBill->id));
               } catch (Exception $err) {

                  statusInvoiceMail::create([
                     'status' => false,
                     'bill_id' => $createBill->id,
                     'is_change' => true,
                  ]);
               }
            }
         }

         info('Cron notification Fee Register success at ' . now());
      } catch (Exception $err) {

         info('Cron notification Fee Register error at ' . now());
      }
   }

   public function etc()
   {
      try {
         //sementara gabisa kirim email push array dulu

         $data = Student::with([
            'bill' => function ($query) {
               $query
                  ->whereNotIn('type', ["SPP", "Capital Fee", "Book", "Uniform", "Paket"])
                  ->where('created_at', '>=', Carbon::now()->setTimezone('Asia/Jakarta')->subDay()->format('Y-m-d H:i:s'))
                  ->where('paidOf', false)
                  ->get();
            },
            'relationship'
         ])
            ->whereHas('bill', function ($query) {
               $query
                  ->whereNotIn('type', ["SPP", "Capital Fee", "Book", "Uniform", "Paket"])
                  ->where('created_at', '>=', Carbon::now()->setTimezone('Asia/Jakarta')->subDay()->format('Y-m-d H:i:s'))
                  ->where('paidOf', false);
            })
            ->get();

         //   return $data;

         foreach ($data as $student) {

            foreach ($student->bill as $createBill) {

               // return 'nyampe';
               $mailData = [
                  'student' => $student,
                  'bill' => [$createBill],
                  'past_due' => false,
                  'charge' => false,
                  'change' => false,
                  'is_paid' => false,
               ];


               //   $pdfBill = Bill::with(['student' => function ($query) {
               //      $query->with('grade');
               //   }])
               //   ->where('id', $createBill->id)
               //   ->first();


               //    $pdf = app('dompdf.wrapper');
               //    $pdf->loadView('components.bill.pdf.paid-pdf', ['data' => $pdfBill])->setPaper('a4', 'portrait'); 

               try {

                  $array_email = [];

                  foreach ($student->relationship as $key => $parent) {
                     if ($key == 0) $mailData['name'] = $parent->name;

                     array_push($array_email, $parent->email);
                     //   return view('emails.spp-mail')->with('mailData', $mailData);
                     //   Mail::to($parent->email)->send(new SppMail($mailData, "Tagihan ". $pdfBill->type ." " . $student->name.  " bulan ini, ". date('F Y') ." sudah dibuat.", $pdf));
                  }

                  dispatch(new SendEmailJob($array_email, $createBill->type, $mailData, "Pemberitahuan Tagihan " . $createBill->type . " " . date('F Y') . ".", $createBill->id));

                  statusInvoiceMail::create([
                     'status' => true,
                     'bill_id' => $createBill->id,
                  ]);
               } catch (Exception $err) {

                  statusInvoiceMail::create([
                     'status' => false,
                     'bill_id' => $createBill->id,
                  ]);
               }
            }
         }


         info('Cron notification etc success at ' . now());
      } catch (Exception $err) {

         info('Cron notification etc error at ' . now());
         return dd($err);
      }
   }
}
