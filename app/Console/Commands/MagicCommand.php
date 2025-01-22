<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\{Task, SubTask};

class MagicCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:magic';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tasks = Task::where('user_id', 0)->get();
        foreach($tasks as $task)
            $task->update(['user_id' => null]);

        $sub_tasks = SubTask::where('user_id', 0)->get();
        foreach($sub_tasks as $sub_task)
            $sub_task->update(['user_id' => null]);
    }
}
