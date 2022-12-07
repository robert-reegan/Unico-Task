<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\ExpenseController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::post('register', [ApiController::class, 'register']);
Route::post('login', [ApiController::class, 'login']);
Route::get('user_details', [ApiController::class, 'user_details']);


Route::group(['prefix' => 'expense', 'middleware' => ['auth:sanctum']], function () use ($router) {
    $router->post('add', [ExpenseController::class, 'store']);
});
Route::group(['prefix' => 'expense_details', 'middleware' => ['auth:sanctum']], function () use ($router) {
    $router->get('/', [ExpenseController::class, 'expense_details']);
});
