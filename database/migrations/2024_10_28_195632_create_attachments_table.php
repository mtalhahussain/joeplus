<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('file_name');
            $table->string('file_url');
            $table->unsignedBigInteger('sub_task_id')->nullable();
            $table->unsignedBigInteger('task_id')->nullable();
            $table->unsignedBigInteger('comment_id')->nullable();
            $table->unsignedBigInteger('user_id');
            if(Schema::hasTable('tasks')) $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');

            if(Schema::hasTable('users')) $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            if(Schema::hasTable('task_comments')) $table->foreign('comment_id')->references('id')->on('task_comments')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
