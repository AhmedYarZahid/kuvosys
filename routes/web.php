<?php

use App\Http\Controllers\CommuteController;
use Illuminate\Support\Facades\Route;
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

Route::get('/', function () {
    return redirect('/distance-calculator');
});

Route::get('/distance-calculator', function (Request $request) {
    return view('distance-calculator');
});

Route::get('/commute-calculator', function () {
    return view('commute-calculator');
});

Route::post('/process-commute', [CommuteController::class, 'processCommuteFile']);

Route::post('/process-payment', [CommuteController::class, 'processPayment']);

Route::post('/send-commute-file', [CommuteController::class, 'sendCommuteFile']);
