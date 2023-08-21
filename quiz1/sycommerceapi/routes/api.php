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
    //Site visits
    Route::post('recordsitevisit', 'Recordsitevisit');
    //products
    Route::post('fetchproducts', 'Fetchproducts');

    //basics
    Route::post('updateuserinfo', 'Updateuserinfo')->middleware(['auth:sanctum', 'ability:Buyer,Superadmin']);
    Route::post('syncuserdetails', 'Syncuserdetails')->middleware(['auth:sanctum', 'ability:Buyer,Superadmin']);
    Route::post('changepassword', 'Changepassword')->middleware(['auth:sanctum', 'ability:Buyer,Superadmin']);
    Route::post('uploadpic', 'Uploadprofilepic')->middleware(['auth:sanctum', 'ability:Buyer,Superadmin']);
});

//Buyer routes
Route::prefix('buyer')->controller(BuyerController::class)->group(function () {

    //orders
    Route::post('fetchmyorders', 'Fetchmyorders')->middleware(['auth:sanctum', 'abilities:Buyer']);
    Route::post('makeorder', 'Makeorder')->middleware(['auth:sanctum', 'abilities:Buyer']);

    //mpesa
    Route::post('completetransaction', 'Completedarajatrans');
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
    Route::post('changeuserstatus', 'Changeactivationstatus')->middleware(['auth:sanctum', 'abilities:Superadmin']);
    //activity logs
    Route::post('fetchactivitylogs', 'Fetchactivitylogs')->middleware(['auth:sanctum', 'abilities:Superadmin']);
    //Site visits
    Route::post('fetchsitevisits', 'Fetchsitevisits')->middleware(['auth:sanctum', 'abilities:Superadmin']);
});
