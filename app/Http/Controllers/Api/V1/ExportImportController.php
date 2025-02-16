<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exports\ProjectExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Project;


class ExportImportController extends Controller
{
    public function exportExcel($uuid)
    {
        $project = Project::where('uuid', $uuid)->first();
        if (!$project) return $this->errorResponse([],'Project not found', 422);
        return Excel::download(new ProjectExport($project->id), 'tasks.csv');
    }
}
