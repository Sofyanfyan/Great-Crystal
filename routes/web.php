<?php

use App\Http\Controllers\Admin\{
   AccountingController,
   AdminController,
   BalanceController,
   BillController,
   BookController,
   DashboardController,
   FinancialController,
   GradeController,
   InvoiceSupplierController,
    JournalController,
    PaymentBookController,
   PaymentGradeController,
   PaymentStudentController,
   RegisterController,
   StudentController,
   TeacherController,
};
use App\Http\Controllers\Excel\Report;
use App\Http\Controllers\Excel\Import;
use App\Http\Controllers\MailController;
use App\Http\Controllers\Notification\NotificationBillCreated;
use App\Http\Controllers\Notification\NotificationPastDue;
use App\Http\Controllers\Notification\NotificationPaymentSuccess;
use App\Http\Controllers\Notification\StatusMailSend;
use App\Http\Controllers\SuperAdmin\{
   SuperAdminController,
   StudentController as SuperStudentController
};
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Jobs\SendEmailJob;
use App\Jobs\SendMailReminder;
use App\Livewire\Counter;
use App\Mail\SendEmailTest;
use Faker\Provider\ar_EG\Payment;
use Illuminate\Notifications\Notification;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Mail;
use App\Exports\JournalDetailExport;
use App\Http\Controllers\Excel\ImportTransactionController;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [UserController::class, 'login']);
Route::post('/login', [UserController::class, 'actionLogin'])->name('actionLogin');
Route::get('/logout', [UserController::class, 'logout']);
Route::get('/counter', Counter::class);

Route::get('send-mail', [NotificationBillCreated::class, 'book']);

Route::middleware(['auth.login'])->prefix('/admin')->group(function () {

   Route::prefix('/dashboard')->group(function () {
      Route::get('/', [DashboardController::class, 'index']);
   });

   Route::prefix('/user')->group(function () {

      Route::get('/change-password', [AdminController::class, 'changeMyPassword']);
      Route::put('/change-password', [AdminController::class, 'actionChangeMyPassword']);
   });

   Route::prefix('/detail')->group(function () {
      Route::get('/{id}', [StudentController::class, 'detail']);
   });

   Route::prefix('/bills')->group(function () {
      Route::get('/', [BillController::class, 'index']);
      Route::get('/create', [BillController::class, 'chooseStudent']);
      Route::get('/create-bills/{id}', [BillController::class, 'pageCreateBill']);
      Route::get('/detail-payment/{id}', [BillController::class, 'detailPayment']);
      Route::get('/create-payment/{id}', [BillController::class, 'pagePayment']);
      Route::get('/change-paket/{student_id}/{bill_id}', [BillController::class, 'pageChangePaket']);
      Route::get('/intallment-paket/{bill_id}', [BillController::class, 'pagePaketInstallment']);
      Route::get('/paid/pdf/{bill_id}', [BillController::class, 'pagePdf']);
      Route::get('/installment-pdf/{bill_id}', [BillController::class, 'reportInstallmentPdf']);
      Route::get('/edit-installment-paket/{bill_id}', [BillController::class, 'pageEditInstallment']);
      Route::get('/status', [StatusMailSend::class, 'index']);
      Route::get('/status/{status_id}', [StatusMailSend::class, 'view']);
      Route::post('/post-bill/{id}', [BillController::class, 'actionCreateBill'])->name('create.bill');
      Route::post('/post-intallment-paket/{bill_id}', [BillController::class, 'actionPaketInstallment'])->name('create.installment');
      Route::put('/change-paket/{bill_id}/{student_id}', [BillController::class, 'actionChangePaket'])->name('action.edit.paket');
      Route::patch('/status/{id}', [StatusMailSend::class, 'send']);
      Route::patch('/update-paid/{bill_id}/{student_id}', [BillController::class, 'paidOfBook'])->name('action.book.payment');
      Route::patch('/update-paid/{id}', [BillController::class, 'paidOf']);

      Route::post('/send-payment-notification/{bill_id}', [NotificationPaymentSuccess::class, 'sendPaymentSuccessNotification'])->name('admin.bills.sendPaymentNotification');
   });

   Route::prefix('/reports')->group(function () {
      Route::get('/', [Report::class, 'index']);
      Route::post('/exports', [Report::class, 'export']);
   });
});

Route::middleware(['admin'])->prefix('/admin')->group(function () {

   Route::prefix('/register')->group(function () {
      Route::get('/', [RegisterController::class, 'index']);
      Route::post('/post', [RegisterController::class, 'register'])->name('actionRegister');
      Route::get('/edit-installment-capital/{bill_id}', [RegisterController::class, 'pageEditInstallment']);
      Route::put('/edit-installment-capital/{bill_id}', [RegisterController::class, 'actionEditInstallment'])->name('action.edit.installment');
      Route::get('/imports', [Import::class, 'index']);
      Route::post('/imports', [Import::class, 'upload'])->name('import.register');
      Route::get('/templates/students', [Import::class, 'downloadTemplate']);
   });

   Route::prefix('/list')->group(function () {
      Route::get('/', [StudentController::class, 'index']);
   });


   Route::prefix('/update')->group(function () {
      Route::put('/{id}', [StudentController::class, 'actionEdit'])->name('student.update');
      Route::get('/{id}', [StudentController::class, 'edit']);
   });

   Route::prefix('/teachers')->group(function () {

      Route::get('/', [TeacherController::class, 'index']);
      Route::post('/', [TeacherController::class, 'actionPost'])->name('actionRegisterTeacher');
      Route::put('/{id}', [TeacherController::class, 'actionEdit'])->name('actionUpdateTeacher');
      Route::get('/register', [TeacherController::class, 'pagePost']);
      Route::get('/{id}', [TeacherController::class, 'editPage']);
      Route::get('/detail/{id}', [TeacherController::class, 'getById']);
   });

   Route::prefix('/grades')->group(function () {
      Route::get('/', [GradeController::class, 'index']);
      Route::get('/create', [GradeController::class, 'pageCreate']);
      Route::get('/{id}', [GradeController::class, 'detailGrade']);
      Route::get('/edit/{id}', [GradeController::class, 'pageEdit']);
      Route::get('/pdf/{id}', [GradeController::class, 'pagePDF']);
      Route::post('/', [GradeController::class, 'actionPost'])->name('actionCreateGrade');
      Route::put('/{id}', [GradeController::class, 'actionPut'])->name('actionUpdateGrade');
   });


   Route::prefix('/books')->group(function () {
      Route::get('/', [BookController::class, 'index']);
      Route::get('/create', [BookController::class, 'pageCreate']);
      Route::get('/edit/{id}', [BookController::class, 'pageEdit']);
      Route::get('/detail/{id}', [BookController::class, 'detail']);
      Route::post('/post', [BookController::class, 'postCreate'])->name('action.create.book');
      Route::patch('/post/{id}', [BookController::class, 'actionUpdate'])->name('action.update.book');
      Route::delete('/{id}', [BookController::class, 'destroy']);
   });

   Route::prefix('/student')->group(function () {
      Route::get('/re-registration/{student_id}', [SuperStudentController::class, 'pageReRegis']);
      Route::patch('/{id}', [SuperStudentController::class, 'inactiveStudent']);
      Route::patch('/activate/{student_id}', [SuperStudentController::class, 'activateStudent']);
      Route::patch('/re-registration/{student_id}', [SuperStudentController::class, 'actionReRegis'])->name('action.re-regis');
   });
});

Route::middleware(['accounting'])->prefix('admin')->group(function () {

   Route::prefix('/spp-students')->group(function () {
      Route::get('/', [PaymentStudentController::class, 'index']);
      Route::get('/create/{id}', [PaymentStudentController::class, 'createPage']);
      Route::get('/detail/{id}', [PaymentStudentController::class, 'pageDetailSpp']);
      Route::get('/edit/{id}/', [PaymentStudentController::class, 'pageEditSpp']);
      Route::post('/create/{id}', [PaymentStudentController::class, 'actionCreatePayment'])->name('create.static.student');
      Route::put('/actionEdit/{id}/{id_student_payment}', [PaymentStudentController::class, 'actionEditStaticPayment'])->name('update.payment.student-static');
   });

   Route::prefix('/payment-grades')->group(function () {
      Route::get('/', [PaymentGradeController::class, 'index']);
      Route::get('/{id}', [PaymentGradeController::class, 'pageById']);
      Route::get('/{id}/choose-type', [PaymentGradeController::class, 'chooseSection']);
      Route::get('{id}/create/{type}', [PaymentGradeController::class, 'pageCreate']);
      Route::get('/{id}/edit', [PaymentGradeController::class, 'pageEdit']);
      Route::post('action-create/payment-grade/{id}/{type}', [PaymentGradeController::class, 'actionCreate'])->name('create.payment-grades');
      Route::put('/{id}/edit', [PaymentGradeController::class, 'actionEdit'])->name('edit.payment-grades');
      Route::delete('/{id}', [PaymentGradeController::class, 'deletePayment']);
   });

   Route::prefix('payment-books')->group(function () {
      Route::get('/', [PaymentBookController::class, 'index']);
      Route::get('/{id}', [PaymentBookController::class, 'studentBook']);
      Route::get('/{id}/add-books', [PaymentBookController::class, 'pageAddBook']);
      Route::post('/{id}/add-books-action', [PaymentBookController::class, 'actionAddBook'])->name('action.add.book');
   });

   Route::prefix('/income')->group(function () {
      Route::get('/', [FinancialController::class, 'indexIncome'])->name('income.index');
   });

   Route::prefix('/expenditure')->group(function () {
      Route::get('/', [FinancialController::class, 'indexExpenditure'])->name('expenditure.index');
      Route::get('/create', [FinancialController::class, 'createExpenditure'])->name('expenditure.create');
      Route::post('/store', [FinancialController::class, 'storeExpenditure'])->name('expenditure.store');
      Route::get('/{id}/edit', [FinancialController::class, 'editExpenditure'])->name('expenditure.edit');
      Route::put('/{id}', [FinancialController::class, 'updateExpenditure'])->name('expenditure.update');
      Route::delete('/{id}', [FinancialController::class, 'destroyExpenditure'])->name('expenditure.destroy');
   });

   Route::prefix('/transaction')->group(function () {
      Route::get('/transaction-transfer', [AccountingController::class, 'indexTransfer'])->name('transaction-transfer.index');
      Route::get('/transaction-transfer/create', [AccountingController::class, 'createTransactionTransfer'])->name('transaction-transfer.create');
      Route::post('/transaction-transfer', [AccountingController::class, 'storeTransactionTransfer'])->name('transaction-transfer.store');
      Route::post('/create-account-transfer/store', [AccountingController::class, 'storeAccountTransactionTransfer'])->name('transaction-transfer.account.store');
      Route::delete('/transaction-transfer/{id}', [AccountingController::class, 'deleteTransactionTransfer'])->name('transaction-transfer.destroy');


      Route::get('/transaction-send', [AccountingController::class, 'indexTransactionSend'])->name('transaction-send.index');
      Route::get('/transaction-send/create', [AccountingController::class, 'createTransactionSend'])->name('transaction-send.create');
      Route::post('/transaction-send', [AccountingController::class, 'storeTransactionSend'])->name('transaction-send.store');
      Route::delete('/transaction-send/{id}', [AccountingController::class, 'deleteTransactionSend'])->name('transaction-send.destroy');
      Route::post('/create-transaction-send/store', [AccountingController::class, 'storeSupplierTransactionSend'])->name('transaction-send-supplier.store');
      Route::post('/create-account-send/store', [AccountingController::class, 'storeAccountTransactionSend'])->name('transaction-send.account.store');



      Route::get('/transaction-receive', [AccountingController::class, 'indexTransactionReceive'])->name('transaction-receive.index');
      Route::get('/transaction-receive/create', [AccountingController::class, 'createTransactionReceive'])->name('transaction-receive.create');
      Route::post('/transaction-receive', [AccountingController::class, 'storeTransactionReceive'])->name('transaction-receive.store');
      Route::delete('/transaction-receive/{id}', [AccountingController::class, 'deleteTransactionReceive'])->name('transaction-receive.destroy');
      Route::post('/create-account-receive/store', [AccountingController::class, 'storeAccountTransactionReceive'])->name('transaction-receive.account.store');
   });

   Route::prefix('/journal')->group(function () {
      Route::get('/', [JournalController::class, 'indexJournal'])->name('journal.index');
      Route::get('/detail/{id}/{type}', [JournalController::class, 'showJournalDetail'])->name('journal.detail');
      Route::get('/detail/{id}/{type}/pdf', [JournalController::class, 'generatePdfJournalDetail'])->name('journal.detail.pdf');
      // Route::get('/detail/selected', [JournalController::class, 'showSelectedJournalDetail'])->name('journal.detail.selected');
      Route::get('/journal/detail', [JournalController::class, 'showFilterJournalDetail'])->name('journal.detail.selected');
      Route::get('/journal/detail/selected/pdf', [JournalController::class, 'showFilterJournalDetailpdf'])->name('journal.detail.selected.pdf');
      Route::get('/journal/detail/selected/excel', function (Request $request) {
         $transactionDetails = session('transactionDetails');
         return Excel::download(new JournalDetailExport(
            $request->start_date,
            $request->end_date,
            $request->type,
            $request->search,
            $request->sort,
            $request->order
         ), 'journal-details.xlsx');
      })->name('journal.detail.selected.excel');
      Route::post('/journal/import', [ImportTransactionController::class, 'importExcel'])->name('journal.import');
      Route::get('/journal/templates/import', [ImportTransactionController::class, 'downloadTemplate']);
   });

   Route::prefix('/account')->group(function () {
      Route::get('/', [AccountingController::class, 'indexAccount'])->name('account.index');
      Route::get('/create-account', [AccountingController::class, 'createAccount'])->name('create-account.create');
      Route::post('/create-account/store', [AccountingController::class, 'storeAccount'])->name('account.store');
      Route::get('/{id}/edit', [AccountingController::class, 'editAccount'])->name('account.edit');
      Route::put('/{id}', [AccountingController::class, 'updateAccount'])->name('account.update');
      Route::delete('/{id}', [AccountingController::class, 'destroyAccount'])->name('account.destroy');
      Route::post('/create-account-category/store', [AccountingController::class, 'storeAccountCategory'])->name('account-category.store');
      Route::post('/calculate-total/{id}', [AccountingController::class, 'calculateTotal'])->name('account.calculateTotal');
      Route::post('/account/calculate-all', [AccountingController::class, 'calculateAll'])->name('account.calculateAll');

      Route::get('/account/balance', [BalanceController::class, 'indexBalance'])->name('balance.index');
      Route::get('/account/balance-create', [BalanceController::class, 'createBalance'])->name('balance.create');
      Route::post('/account/balance-create', [BalanceController::class, 'storeBalance'])->name('balance.store');
      Route::delete('/account/balance/{id}', [BalanceController::class, 'deleteBalance'])->name('balance.destroy');



      Route::post('/balances/post', [BalanceController::class, 'postMonthlyBalances'])->name('balance.post');
      Route::post('/balances/unpost', [BalanceController::class, 'unpostMonthlyBalances'])->name('balance.unpost');
   });

   Route::prefix('/supplier')->group(function () {
      Route::get('/', [InvoiceSupplierController::class, 'indexsupplier'])->name('supplier.index');
      Route::get('/create-supplier', [InvoiceSupplierController::class, 'createSupplier'])->name('create-supplier.create');
      Route::post('/create-supplier/store', [InvoiceSupplierController::class, 'storeSupplier'])->name('supplier.store');
      Route::delete('/supplier/{id}', [InvoiceSupplierController::class, 'destroySupplier'])->name('supplier.destroy');
   });

   Route::prefix('/invoice-supplier')->group(function () {
      Route::get('/', [InvoiceSupplierController::class, 'indexInvoiceSupplier'])->name('invoice-supplier.index');
      Route::get('/upload-proof-of-payment/{id}', [InvoiceSupplierController::class, 'uploadProofOfPaymentView'])->name('invoice-supplier.upload-proof-view');
      Route::post('/upload-proof-of-payment/{id}', [InvoiceSupplierController::class, 'uploadProofOfPayment'])->name('invoice-supplier.upload-proof');
      Route::get('/create-invoice-supplier', [InvoiceSupplierController::class, 'createInvoiceSupplier'])->name('create-invoice-supplier.create');
      Route::post('/create-invoice-supplier/store', [InvoiceSupplierController::class, 'storeInvoiceSupplier'])->name('invoice-supplier.store');
      Route::delete('/invoice-supplier/{id}', [InvoiceSupplierController::class, 'destroyInvoiceSupplier'])
         ->name('invoice-supplier.destroy');
      Route::post('/create-account/store', [InvoiceSupplierController::class, 'storeAccount'])->name('invoice-supplier.account.store');
   });
});

Route::middleware(['superadmin'])->prefix('admin')->group(function () {

   Route::prefix('/user')->group(function () {
      Route::get('/', [SuperAdminController::class, 'getUser']);
      Route::get('/register-user', [SuperAdminController::class, 'registerUser']);
      Route::get('/{id}', [SuperAdminController::class, 'getById']);
      Route::post('/register-action', [SuperAdminController::class, 'registerUserAction']);
      Route::put('/change-password/commit/{id}', [SuperAdminController::class, 'changePassword'])->name('user.editPassword');
      Route::delete('{id}', [SuperAdminController::class, 'deleteUser']);
   });

   Route::prefix('/grades')->group(function () {
      Route::get('/promotions/{id}', [GradeController::class, 'pagePromotion']);
      Route::put('/promotions/post/action', [GradeController::class, 'actionPromotion'])->name('actionPromotion');
   });

   Route::prefix('/teachers')->group(function () {

      Route::put('/deactivated/{id}', [TeacherController::class, 'deactivated']);
      Route::put('/activated/{id}', [TeacherController::class, 'activated']);
   });
});
