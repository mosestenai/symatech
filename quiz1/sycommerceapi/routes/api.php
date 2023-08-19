<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\BuyerController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('users')->controller(UserController::class)->group(function () {
    //site common routes
    Route::post('register', 'register');
    Route::post('login', 'login');
    //assets
    Route::get('assets/{filename}', 'Fetchassets');
});

//Buyer routes
Route::prefix('buyer')->controller(BuyerController::class)->group(function () {
    //basics
    Route::post('updateuserinfo', 'Updateuserinfo')->middleware(['auth:sanctum', 'abilities:Buyer']);
    Route::post('syncuserdetails', 'Syncuserdetails')->middleware(['auth:sanctum', 'abilities:Buyer']);
    Route::post('changepassword', 'Changepassword')->middleware(['auth:sanctum', 'abilities:Buyer']);
    Route::post('uploadpic', 'Uploadprofilepic')->middleware(['auth:sanctum', 'abilities:Buyer']);
    //orders
    Route::post('fetchmyorders', 'Fetchmyorders')->middleware(['auth:sanctum', 'abilities:Buyer']);
    Route::post('makeorder', 'Makeorder')->middleware(['auth:sanctum', 'abilities:Buyer']);
});

//Admin routes
Route::prefix('admin')->controller(AdminController::class)->group(function () {
    //products
    Route::post('deleteproducts', 'Deleteproducts')->middleware(['auth:sanctum', 'abilities:Superadmin']);
    Route::post('postproducts', 'Postproducts')->middleware(['auth:sanctum', 'abilities:Superadmin']);
    //Orders
    Route::post('fetchallorders', 'Fetchallorders')->middleware(['auth:sanctum', 'abilities:Superadmin']);
    //users
    Route::post('fetchusers', 'Fetchusers')->middleware(['auth:sanctum', 'abilities:Superadmin']);
    Route::post('changeactivationstatus', 'Changeactivationstatus')->middleware(['auth:sanctum', 'abilities:Superadmin']);
    //activity logs
    Route::post('fetchactivitylogs', 'Fetchactivitylogs')->middleware(['auth:sanctum', 'abilities:Superadmin']);
});
