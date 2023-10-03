<?php

use App\Http\Controllers\Admin\{
   AdminController,
   DashboardController,
   GradeController,
   PaymentPerGradeController,
   RegisterController,
   StudentController,
   TeacherController,
};

use App\Http\Controllers\SuperAdmin\{
   SuperAdminController,
   StudentController as SuperStudentController
};

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Livewire\Counter;

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
Route::get('/counter', Counter::class);

Route::middleware(['admin'])->prefix('/admin')->group(function () {
   
   Route::prefix('/dashboard')->group(function () {
      Route::get('/', [DashboardController::class, 'index']);
   });

   Route::prefix('/register')->group(function () {
      Route::get('/', [RegisterController::class, 'index']);
      Route::post('/post', [RegisterController::class, 'register'])->name('actionRegister');
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
      
      Route::prefix('/payment-grades')->group(function() {
         Route::get('/{id}', [PaymentPerGradeController::class, 'index']);
         Route::get('/{id}/create', [PaymentPerGradeController::class, 'pageCreate']);
         Route::get('/{id}/edit', [PaymentPerGradeController::class, 'pageEdit']);
         Route::post('/{id}/create', [PaymentPerGradeController::class, 'actionCreate'])->name('create.paymentGrade');
         Route::put('/{id}/edit', [PaymentPerGradeController::class, 'actionEdit'])->name('edit.paymentGrade');
         Route::delete('/{id}', [PaymentPerGradeController::class, 'deletePayment']);
      });
   });


});

Route::middleware(['check.superadmin'])->prefix('admin')->group(function () {
   
   
   Route::prefix('/student')->group(function () {
      Route::patch('/{id}', [SuperStudentController::class, 'inactiveStudent']);
   });
   
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
   });

});