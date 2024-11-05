<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\{
    AuthController,
    UserController,
    BoardController,
    TaskController,
    ProjectController,
    CommentController,
    SubTaskConroller
};


Route::group(['prefix' => 'v1'] , function(){

    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('verify-email', [AuthController::class, 'verifyCheckEmail']);

    Route::group(['middleware' => 'auth:sanctum'], function(){

        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::post('comment-attachment', [CommentController::class, 'attachmentUpload']);
        // removeTaskAttachments
        Route::delete('remove-attachments', [TaskController::class, 'removeTaskAttachments']);

        Route::apiResources([
            'users' => UserController::class,
            'boards' => BoardController::class,
            'tasks' => TaskController::class,
            'projects' => ProjectController::class,
            'comments' => CommentController::class,
            'sub-tasks' => SubTaskConroller::class,
        ]);

    });

});
