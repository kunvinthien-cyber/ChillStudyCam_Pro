<?php

use App\Http\Controllers\AiController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\StudySessionController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/rooms', [RoomController::class, 'index']);
Route::get('/rooms/{room}', [RoomController::class, 'show']);
Route::get('/rooms/{room}/messages', [RoomController::class, 'getMessages']);
Route::get('/rooms/{room}/reactions', [RoomController::class, 'getRecentReactions']);
Route::get('/rooms/{room}/participants', [RoomController::class, 'getParticipants']);
Route::post('/rooms/{room}/verify-passcode', [RoomController::class, 'verifyPasscode']);

Route::get('/products', [ShopController::class, 'getProducts']);
Route::get('/leaderboard', [StudySessionController::class, 'getLeaderboard']);
Route::get('/documents', [DocumentController::class, 'index']);
Route::get('/ai/tts', [AiController::class, 'tts']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post('/rooms', [RoomController::class, 'store']);
    Route::post('/rooms/{room}/messages', [RoomController::class, 'sendMessage']);
    Route::post('/rooms/{room}/reactions', [RoomController::class, 'sendReaction']);
    Route::post('/rooms/{room}/voice/join', [RoomController::class, 'joinVoice']);
    Route::post('/rooms/{room}/voice/leave', [RoomController::class, 'leaveVoice']);
    Route::post('/rooms/{room}/goal', [RoomController::class, 'updateGoal']);
    Route::post('/rooms/{room}/invite', [RoomController::class, 'sendInvite']);
    Route::get('/my-invites', [RoomController::class, 'getMyInvites']);
    Route::post('/invites/{id}/respond', [RoomController::class, 'respondInvite']);
    Route::post('/rooms/{room}/request-join', [RoomController::class, 'requestJoin']);
    Route::get('/rooms/{room}/check-approval', [RoomController::class, 'checkJoinStatus']);
    Route::post('/rooms/{room}/approve/{participant}', [RoomController::class, 'approveMember']);
    Route::get('/users/search', [RoomController::class, 'searchUsers']);

    Route::get('/user/profile', [UserController::class, 'profile']);
    Route::put('/user/profile', [UserController::class, 'update']);
    Route::get('/user/orders', [UserController::class, 'orders']);
    Route::post('/study/complete', [StudySessionController::class, 'complete']);
    Route::post('/orders', [ShopController::class, 'createOrder']);
    Route::post('/documents/{document}/download', [DocumentController::class, 'download']);

    Route::get('/tasks', [TaskController::class, 'index']);
    Route::post('/tasks', [TaskController::class, 'store']);
    Route::patch('/tasks/{task}/toggle', [TaskController::class, 'toggle']);
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy']);

    Route::post('/ai/ask', [AiController::class, 'ask']);
    Route::get('/ai/conversations', [AiController::class, 'getConversations']);
    Route::get('/ai/conversations/{id}', [AiController::class, 'getConversationMessages']);
    Route::delete('/ai/conversations/{id}', [AiController::class, 'deleteConversation']);
    Route::patch('/ai/messages/{id}/star', [AiController::class, 'toggleStar']);
    Route::get('/ai/history', [AiController::class, 'getHistory']);
    Route::delete('/ai/history', [AiController::class, 'clearHistory']);
});
