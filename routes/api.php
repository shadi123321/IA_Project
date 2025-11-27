<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\JwtMiddleware;

Route::get('/', function () {
    return response()->json(['message' => 'Hello world!']);
 });
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/verifyEmail/{id}', [AuthController::class, 'verifyEmail']);
Route::get('/resendCode/{id}', [AuthController::class, 'resendCode']);


Route::middleware([JwtMiddleware::class])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});

 ////////citizen mmiddleware 
    Route::middleware([JwtMiddleware::class, 'role:citizen'])->group(function () {
           Route::post('/OwisaddAttachment', [UserController::class, 'OwisaddAttachment']);
          Route::get('/Owisshow', [UserController::class, 'Owisshow']);
          Route::get('/ OwismyComplaints ', [UserController::class, ' OwismyComplaints']);
          Route::post('/ OwisSubmitComplain', [UserController::class, ' OwisSubmitComplain']);
          Route::get('/getRole', [UserController::class, 'getRole']);
     
  });
 ////////admin mmiddleware 
    Route::middleware([JwtMiddleware::class,'role:admin'])->group(function () {
      
              Route::post('/storeEmployee', [AdminController::class, 'storeEmployee']);//done
         Route::post('/storeEmployee', [AdminController::class, 'storeEmployee']);//done
        Route::get('/showEmployee/{id}', [AdminController::class, 'showEmployee']);
                Route::get('/indexEmployees', [AdminController::class, 'indexEmployees']);
        Route::put('/updateEmployee/{id}', [AdminController::class, 'updateEmployee']);
        Route::delete('/deleteEmployee/{id}', [AdminController::class, 'deleteEmployee']);
    });

 ////////employee mmiddleware 
Route::middleware([JwtMiddleware::class,'role:employee'])->group(function () {
          Route::get('/indexByEntity', [UserController::class, 'indexByEntity']);
         Route::get('/showComplaint/{reference_number}', [UserController::class, 'showComplaint']);
                 Route::put('/changeStatus', [UserController::class, 'changeStatus']);

        });

       
