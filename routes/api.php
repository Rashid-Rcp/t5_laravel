<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TestController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ClubController;
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


Route::get('/test',[TestController::class,'axiosTest']);
Route::post('/user',[UserController::class,'createUser']);
Route::post('/user/numbers',[UserController::class,'getUsers']);
Route::post('/login',[UserController::class,'loginUser']);
Route::get('/user/club/{userId}',[ClubController::class,'userClub']);
Route::post('/club/create',[ClubController::class,'create']);
Route::post('/club/members',[ClubController::class,'addMembers']);
Route::get('/club/members/{clubName}',[ClubController::class,'getMembers']);
Route::get('/user_clubs/{userId}',[ClubController::class,'getUserClubs']);


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
