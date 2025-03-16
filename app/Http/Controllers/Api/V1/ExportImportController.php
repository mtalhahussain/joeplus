<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exports\{ProjectExport, ProjectImport};
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

    public function importProject(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'file' => 'required|mimes:csv,xlsx',
            'visibility' => 'required|in:public,private,invited'
        ]);

        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        Excel::import(new ProjectImport($request->name, $user), $request->file('file'));

        return $this->successResponse([], 'Projects imported successfully');

    }
}
