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
        Schema::table('boards', function (Blueprint $table) {
           
            if(!Schema::hasColumn('boards', 'position')) $table->integer('position')->default(0)->after('project_id');
            
        });

        Schema::table('boards', function (Blueprint $table) {
           
            if(!Schema::hasColumn('boards', 'position')) $table->integer('position')->default(0)->after('project_id');
            
        });
        
        Schema::table('projects', function (Blueprint $table) {
           
            if(!Schema::hasColumn('projects', 'status')) $table->enum('status', ['on_track', 'at_risk', 'off_track'])->default('on_track')->after('visibility');
            
        });
        Schema::table('task_assignees', function (Blueprint $table) {
        
            if(!Schema::hasColumn('task_assignees', 'sub_task_id')) $table->unsignedBigInteger('sub_task_id')->nullable()->change();
            $table->unsignedBigInteger('task_id')->nullable()->change();
        });

        Schema::table('tasks', function (Blueprint $table) {
        
            if(!Schema::hasColumn('tasks', 'priority')) $table->string('priority',99)->nullable()->after('status');
            if (Schema::hasColumn('tasks', 'due_date')) {
                \DB::statement('ALTER TABLE tasks CHANGE COLUMN `due_date` `due_start` DATE NULL AFTER `description`');
            }
            if(!Schema::hasColumn('tasks', 'due_start')) $table->date('due_start')->nullable()->after('description');
            if(!Schema::hasColumn('tasks', 'due_end')) $table->date('due_end')->nullable()->after('due_start');
            if(!Schema::hasColumn('tasks', 'is_completed')) $table->boolean('is_completed')->default(0)->after('priority');
            if(!Schema::hasColumn('tasks', 'completed_at')) $table->timestamp('completed_at')->nullable()->after('is_completed');
            if(!Schema::hasColumn('tasks', 'user_id')) $table->unsignedBigInteger('user_id')->nullable()->after('uuid');
            $table->text('title')->change();
        });
        Schema::table('sub_tasks', function (Blueprint $table) {
        
            if(!Schema::hasColumn('sub_tasks', 'priority')) $table->string('priority',99)->nullable()->after('status');
            if(!Schema::hasColumn('sub_tasks', 'due_start')) $table->date('due_start')->nullable()->after('description');
            if(!Schema::hasColumn('sub_tasks', 'due_end')) $table->date('due_end')->nullable()->after('due_start');
            if(!Schema::hasColumn('sub_tasks', 'is_completed')) $table->boolean('is_completed')->default(0)->after('priority');
            if(!Schema::hasColumn('sub_tasks', 'completed_at')) $table->timestamp('completed_at')->nullable()->after('is_completed');
            if(!Schema::hasColumn('sub_tasks', 'user_id')) $table->unsignedBigInteger('user_id')->nullable()->after('uuid');
            $table->text('title')->change();

        });
        if (Schema::hasTable('task_metas')){

            Schema::table('task_metas', function (Blueprint $table) {
            
                if(!Schema::hasColumn('task_metas', 'project_id')) $table->unsignedBigInteger('project_id')->after('task_id');
                $table->unsignedBigInteger('task_id')->nullable()->change();
                $table->string('value',255)->nullable()->change();
    
            });
        }

        if (Schema::hasTable('meta_values')){

            Schema::table('meta_values', function (Blueprint $table) {
    
                if(!Schema::hasColumn('meta_values', 'uuid')) $table->string('uuid', 36)->after('id');
               
            });
        }
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
