<?php

use App\Http\Controllers\Admin\{
   AdminController,
   BillController,
    BookController,
    DashboardController,
   GradeController,
    PaymentBookController,
    PaymentGradeController,
    PaymentStudentController,
    RegisterController,
   StudentController,
   TeacherController,
};
use App\Http\Controllers\Excel\Report;
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
use Illuminate\Support\Facades\Mail;

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

// Route::get('send-mail', [MailController::class, 'createNotificationUniform']);

Route::middleware(['auth.login'])->prefix('/admin')->group(function () {

   Route::prefix('/dashboard')->group(function () {
      Route::get('/', [DashboardController::class, 'index']);
   });

   Route::prefix('/register')->group(function () {
      Route::get('/', [RegisterController::class, 'index']);
      Route::post('/post', [RegisterController::class, 'register'])->name('actionRegister');
      Route::get('/edit-installment-capital/{bill_id}', [RegisterController::class, 'pageEditInstallment']);
      Route::put('/edit-installment-capital/{bill_id}', [RegisterController::class, 'actionEditInstallment'])->name('action.edit.installment');
   });

   Route::prefix('/list')->group(function () {
      Route::get('/', [StudentController::class, 'index']);
   });

   Route::prefix('/detail')->group(function () {
      Route::get('/{id}', [StudentController::class, 'detail']);
   });
   
   Route::prefix('/update')->group(function () {
      Route::put('/{id}', [StudentController::class, 'actionEdit'])->name('student.update');
      Route::get('/{id}', [StudentController::class, 'edit']);
   });

   Route::prefix('/user')->group(function () {

      Route::get('/change-password', [AdminController::class, 'changeMyPassword']);
      Route::put('/change-password', [AdminController::class, 'actionChangeMyPassword']);
   });

});

Route::middleware(['admin'])->prefix('/admin')->group(function () {

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
   

   Route::prefix('/books')->group(function() {
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

   Route::prefix('/spp-students')->group(function() {
      Route::get('/', [PaymentStudentController::class, 'index']);
      Route::get('/create/{id}', [PaymentStudentController::class, 'createPage']);
      Route::get('/detail/{id}', [PaymentStudentController::class, 'pageDetailSpp']);
      Route::get('/edit/{id}/', [PaymentStudentController::class, 'pageEditSpp']);
      Route::post('/create/{id}', [PaymentStudentController::class, 'actionCreatePayment'])->name('create.static.student');
      Route::put('/actionEdit/{id}/{id_student_payment}', [PaymentStudentController::class, 'actionEditStaticPayment'])->name('update.payment.student-static');
   });

   Route::prefix('/payment-grades')->group(function() {
      Route::get('/', [PaymentGradeController::class, 'index']);
      Route::get('/{id}', [PaymentGradeController::class, 'pageById']);
      Route::get('/{id}/choose-type', [PaymentGradeController::class, 'chooseSection']);
      Route::get('{id}/create/{type}', [PaymentGradeController::class, 'pageCreate']);
      Route::get('/{id}/edit', [PaymentGradeController::class, 'pageEdit']);
      Route::post('action-create/payment-grade/{id}/{type}', [PaymentGradeController::class, 'actionCreate'])->name('create.payment-grades');
      Route::put('/{id}/edit', [PaymentGradeController::class, 'actionEdit'])->name('edit.payment-grades');
      Route::delete('/{id}', [PaymentGradeController::class, 'deletePayment']);
   });

   Route::prefix('payment-books')->group(function(){
      Route::get('/', [PaymentBookController::class, 'index']);
      Route::get('/{id}', [PaymentBookController::class, 'studentBook']);
      Route::get('/{id}/add-books', [PaymentBookController::class, 'pageAddBook']);
      Route::post('/{id}/add-books-action', [PaymentBookController::class, 'actionAddBook'])->name('action.add.book');
   });
   
   Route::prefix('/bills')->group(function() {
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
   });
   
   Route::prefix('/reports')->group(function() {
      Route::get('/', [Report::class, 'index']);
      Route::post('/exports', [Report::class, 'export']);
   });
});

Route::middleware(['superadmin'])->prefix('admin')->group(function () {
   
   Route::prefix('/user')->group(function () {
      Route::get('/', [SuperAdminController::class, 'getUser']);
      Route::get('/register-user', [SuperAdminController::class, 'registerUser']);
      Route::get('/{id}', [SuperAdminController::class, 'getById']);
      Route::post('/register-action', [SuperAdminController::class, 'registerUserAction']);
      Route::put('/change-password/commit/{id}',[SuperAdminController::class, 'changePassword'])->name('user.editPassword');
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

