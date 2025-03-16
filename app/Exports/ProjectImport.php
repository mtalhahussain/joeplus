<?php

namespace App\Exports;

use App\Models\{Project, Task, SubTask, Board};
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProjectImport implements ToModel, WithHeadingRow
{
    protected $projectName;
    protected $author;

    public function __construct($projectName, $author)
    {
        $this->projectName = $projectName;
        $this->author = $author;
       
    }

    public function model(array $row)
    {
       
        if (!isset($row['name'])) {
            return null;
        }

        $project = Project::firstOrCreate([
            'name' => $this->projectName,
            'owner_id' => $this->author->id
        ]);

        $sectionName = $row['sectioncolumn'] ?? null;
       
        $section = Board::where([
            'name' => $sectionName,
            'project_id' => $project->id
        ])->first();
        
        if (!$section && empty($row['parent_task'])) {
            $section = Board::create([
                'name' => $row['sectioncolumn'],
                'project_id' => $project->id,
                'user_id' => $this->author->id,
                'position' => Board::where('project_id', $project->id)->max('position') + 1
            ]);
        }

        if(empty($row['parent_task'])){

            $task = Task::firstOrCreate(
                ['title' => $row['name'], 'board_id' => $section->id],
                [
                    'user_id' => $this->author->id,
                    'created_at' => isset($row['created_at']) ? Carbon::parse($row['created_at']) : null,
                    'completed_at' => isset($row['completed_at']) ? Carbon::parse($row['completed_at']) : null,
                    'updated_at' => isset($row['last_modified']) ? Carbon::parse($row['last_modified']) : null,
                    'due_start' => isset($row['start_date']) ? Carbon::parse($row['start_date'])->toDateString() : null,
                    'due_end' => isset($row['due_date']) ? Carbon::parse($row['due_date'])->toDateString() : null,
                    'description' => $row['notes'] ?? null,
                    'priority' => $row['priority'] ?? null,
                    'project_id' => $project->id ?? null,
                    'is_completed' => isset($row['completed_at']) ? 1 : 0,
                ]
            );
            if (!empty($row['assignee_email'])) {
                $users = $this->author->companyUsers()->where('email', $row['assignee_email'])->get();
                foreach ($users as $user) {
                    $task->assignees()->attach($user->id);
                }
            }
        }

        if (!empty($row['parent_task'])) {
            $parentTask = Task::where('title', $row['parent_task'])->first();
            if ($parentTask) {
                $subTask = SubTask::firstOrCreate(
                    ['task_id' => $parentTask->id],
                    [
                        'user_id' => $this->author->id,
                        'title' => $row['name'],
                        'created_at' => isset($row['created_at']) ? Carbon::parse($row['created_at']) : null,
                        'completed_at' => isset($row['completed_at']) ? Carbon::parse($row['completed_at']) : null,
                        'updated_at' => isset($row['last_modified']) ? Carbon::parse($row['last_modified']) : null,
                        'due_start' => isset($row['start_date']) ? Carbon::parse($row['start_date'])->toDateString() : null,
                        'due_end' => isset($row['due_date']) ? Carbon::parse($row['due_date'])->toDateString() : null,
                        'description' => $row['notes'] ?? null,
                        'priority' => $row['priority'] ?? null,
                        'is_completed' => isset($row['completed_at']) ? 1 : 0,
                    ]
                );

                if (!empty($row['assignee_email'])) {
                    $users = $this->author->companyUsers()->where('email', $row['assignee_email'])->get();
                    foreach ($users as $user) {
                        $subTask->assignees()->attach($user->id);
                    }
                }
                
            }
        }

        return $project;
    }
}
