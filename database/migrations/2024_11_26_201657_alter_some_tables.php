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

        Schema::table('task_assignees', function (Blueprint $table) {
        
            $table->unsignedBigInteger('sub_task_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
