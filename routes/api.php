<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\JwtMiddleware;

Route::get('/', function () {
    return response()->json(['message' => 'Hello world!']);
});
Route::post('/SubmitComplaint', [UserController::class, 'SubmitComplaint']);
Route::post('/addAttachment/{refernce}', [UserController::class, 'addAttachment']);
Route::get('/show/{refernce}', [UserController::class, 'show']);
Route::get('/showAtt/{refernce}', [UserController::class, 'showAtt']);
Route::get('/showall', [UserController::class, 'myComplaints']);
Route::get('/myComplaintsAtt', [UserController::class, 'myComplaintsAtt']);


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
        Route::post('/verifyEmail/{id}', [AuthController::class, 'verifyEmail']);
        Route::get('/resendCode/{id}', [AuthController::class, 'resendCode']);
Route::middleware([JwtMiddleware::class])->group(function () {
    Route::get('/user', [AuthController::class, 'getUser']);



    Route::put('/user', [AuthController::class, 'updateUser']);
    Route::post('/logout', [AuthController::class, 'logout']);

});

    Route::get('/indexByEntity', [UserController::class, 'indexByEntity']);

