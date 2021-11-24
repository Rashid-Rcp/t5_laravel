<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TestController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ClubController;
use App\Http\Controllers\DiscussionController;
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


Route::post('/test',[TestController::class,'socketTest']);

Route::post('/user',[UserController::class,'createUser']);
Route::post('/user/numbers',[UserController::class,'getUsers']);
Route::post('/user/update',[UserController::class,'updateProfile']);
Route::get('/user/public_data/{profileId}',[UserController::class,'publicProfile']);
Route::post('/login',[UserController::class,'loginUser']);
Route::get('/user/data/{userId}',[UserController::class,'getUserData']);
Route::get('/user/club/{userId}',[ClubController::class,'userClub']);
Route::post('/club/create',[ClubController::class,'create']);
Route::post('/club/members',[ClubController::class,'addMembers']);
Route::get('/club/members/{clubName}',[ClubController::class,'getMembers']);
Route::get('/club/search/{word}',[ClubController::class,'clubSearch']);
Route::get('/club/{clubId}',[ClubController::class,'clubDetails']);
Route::get('/club/list/{type}/{user}',[ClubController::class,'clubList']);
Route::get('/user_clubs/{userId}',[ClubController::class,'getUserClubs']);
Route::post('/discussion/create',[DiscussionController::class,'create']);
Route::post('/discussion/remove',[DiscussionController::class,'remove']);
Route::get('/discussion/user/{userId}',[DiscussionController::class,'getUserDiscussion']);
Route::get('/discussion/manage/{discussionId}',[DiscussionController::class,'getDiscussionManage']);
Route::get('/discussion/all_comments/{discussionId}',[DiscussionController::class,'getDiscussionAllComments']);
Route::get('/discussion/user_follow/all/{userId}',[DiscussionController::class,'getUserFollowDiscussionAll']);
Route::get('/discussion/user_follow/participant/{userId}',[DiscussionController::class,'getUserFollowDiscussionParticipant']);
Route::post('/discussion/answer',[DiscussionController::class,'discussionAnswer']);
Route::get('/discussion/details/{discussionId}',[DiscussionController::class,'discussionDetails']);
Route::get('/discussion/votes/{discussionId}/{userId}',[DiscussionController::class,'discussionVotes']);
Route::post('/discussion/vote',[DiscussionController::class,'postDiscussionVotes']);
Route::post('/discussion/comment',[DiscussionController::class,'postDiscussionComment']);
Route::get('/discussion/comment/{discussionId}',[DiscussionController::class,'DiscussionComments']);
Route::delete('/discussion/comment/{discussionId}',[DiscussionController::class,'DeleteDiscussionComments']);
Route::get('/discussion/suggestion/{userId}',[DiscussionController::class,'DiscussionSuggestion']);
Route::get('/discussion/search/{word}',[DiscussionController::class,'DiscussionSearch']);


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
