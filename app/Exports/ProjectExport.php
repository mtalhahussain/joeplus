<?php

namespace App\Exports;

use App\Models\Project;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProjectExport implements FromCollection, WithHeadings, WithMapping
{
    protected $projectId;

    public function __construct($projectId)
    {
        $this->projectId = $projectId;
    }

    public function collection()
    {
        return Project::with(['tasks', 'tasks.assignees', 'tasks.board'])
            ->whereHas('tasks')
            ->orWhereHas('tasks.subTasks')
            ->find($this->projectId);
    }

    public function headings(): array
    {
        return [
            'Task ID',
            'Created At',
            'Completed At',
            'Last Modified',
            'Name',
            'Section/Column',
            'Assignee',
            'Assignee Email',
            'Start Date',
            'Due Date',
            'Notes',
            'Projects',
            'Parent task',
            'Blocked By (Dependencies)',
            'Blocking (Dependencies)',
            'Priority'
        ];
    }

    public function map($project): array
    {
        $rows = [];
        
        foreach ($project->tasks as $task) {
            $rows[] = [
                $task->uuid,
                $task->created_at,
                $task->is_completed ? 'Yes' : 'No',
                $task->updated_at,
                $task->title,
                $task->board->name ?? '',
                implode(', ', $task->assignees->pluck('name')->toArray()),
                implode(', ', $task->assignees->pluck('email')->toArray()),
                $task->due_start ?? '',
                $task->due_end ?? '',
                implode(', ', $task->tags->pluck('name')->toArray()),
                $task->description ?? '',
                $project->name,
                // implode(', ', $task->blockedBy->pluck('name')->toArray()),
                // implode(', ', $task->blocking->pluck('name')->toArray()),
                // '',
                // '',
                $task->priority ?? '',
            ];
        }
        return $rows;
    }
}
