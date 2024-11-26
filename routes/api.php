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
    SubTaskConroller,
    CompanyController
};


Route::group(['prefix' => 'v1'] , function(){

    Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
        return $request->user();
    });
    

    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('verify-email', [AuthController::class, 'verifyCheckEmail']);
    Route::post('resend-otp', [AuthController::class, 'resendOtp']);
    Route::post('verify-otp', [AuthController::class, 'verifyOtpCode']);

    Route::post('forgot-password', [AuthController::class, 'sendResetLink']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);


    Route::group(['middleware' => 'auth:sanctum'], function(){

        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::post('comment-attachment', [CommentController::class, 'attachmentUpload']);
        Route::delete('remove-attachments', [TaskController::class, 'removeTaskAttachments']);
        Route::get('board-tasks/{board_id}', [TaskController::class, 'getBoardTasks']);
        Route::get('project-tasks/{project_id}', [TaskController::class, 'getProjectTasks']);

        Route::apiResources([
            'users' => UserController::class,
            'company' => CompanyController::class,
            'boards' => BoardController::class,
            'tasks' => TaskController::class,
            'projects' => ProjectController::class,
            'comments' => CommentController::class,
            'sub-tasks' => SubTaskConroller::class,
        ]);

    });

});
