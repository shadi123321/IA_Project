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
  /*, 'throttle:60,1' =1request per minute*/

    Route::middleware([JwtMiddleware::class, 'role:citizen'/*, 'throttle:60,1' */])->group(function () {

        Route::post('/submitComplaint', [UserController::class, 'SubmitComplaint']);
        Route::get('/myComplaints', [UserController::class, 'myComplaints']);
        Route::get('/myComplaintsAtt/{id}', [UserController::class, 'myComplaintsAtt']);
        Route::get('/show/{id}', [UserController::class, 'show']);
        Route::get('/showAtt/{id}', [UserController::class, 'showAtt']);
        Route::post('/addAttachment/{id}', [UserController::class, 'addAttachment']);

        Route::get('/getRole', [UserController::class, 'getRole']);

  });
 ////////admin mmiddleware
  /*, 'throttle:60,1' =1request per minute*/

    Route::middleware([JwtMiddleware::class,'role:admin'/*, 'throttle:60,1' */])->group(function () {

        Route::post('/storeEmployee', [AdminController::class, 'storeEmployee']);//done
        Route::post('/storeEmployee', [AdminController::class, 'storeEmployee']);//done
        Route::get('/showEmployee/{id}', [AdminController::class, 'showEmployee']);
        Route::get('/indexEmployees', [AdminController::class, 'indexEmployees']);
        Route::put('/updateEmployee/{id}', [AdminController::class, 'updateEmployee']);
        Route::delete('/deleteEmployee/{id}', [AdminController::class, 'deleteEmployee']);

        Route::get('/governments', [AdminController::class, 'indexGovernment']);
        Route::get('/complaints', [UserController::class, 'complaints']);
        Route::put('/changeStatus', [UserController::class, 'changeStatus']);
        Route::get('/showComplaint/{reference_number}', [UserController::class, 'showComplaint']);
               Route::get('MonitoringComplains', [AdminController::class, 'MonitoringComplains']);
               Route::get('/statistics', [AdminController::class, 'statistics']);

               Route::post('/search', [AdminController::class, 'search']);

    });

 ////////employee mmiddleware
 /*, 'throttle:60,1' =1request per minute*/
    Route::middleware([JwtMiddleware::class,'role:employee'/*, 'throttle:60,1' */])->group(function () {
        Route::get('/indexByEntity', [UserController::class, 'indexByEntity']);
     Route::post('/EmployeeAddNote', [UserController::class, 'EmployeeAddNote']);

        Route::put('/changeStatus', [UserController::class, 'changeStatus']);
        Route::get('/showComplaint/{reference_number}', [UserController::class, 'showComplaint']);

    });
      // Route::get('/showComplaint/{reference_number}', [UserController::class, 'showComplaint']);


