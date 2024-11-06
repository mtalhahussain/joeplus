<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Project, Task};

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $inputs = $request->all();
        $perPage = $inputs['per_page'] ?? 10;
        $projects = Project::withCount('tasks')->paginate($perPage);
        return $this->successResponse($projects, 'Projects fetched successfully');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'description' => 'nullable',
        ]);
        $inputs = $request->all();
        $inputs['owner_id'] = auth()->id();
        $project = Project::create($inputs);
        return $this->successResponse($project, 'Project created successfully');
    }

    public function show($id)
    {
        $project = Project::where('uuid', $id)->with(['tasks:id,title,project_id'])->first();
        if(!$project) return $this->errorResponse([], 'Project not found', 422);
        return $this->successResponse($project, 'Project fetched successfully');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required',
            'description' => 'nullable',
        ]);

        $project = Project::where('uuid', $id)->first();
        if(!$project) return $this->errorResponse([], 'Project not found', 422);
        $project->update($request->all());
        return $this->successResponse($project, 'Project updated successfully');
    }

    public function destroy($id)
    {
        $project = Project::where('uuid', $id)->first();
        if(!$project) return $this->errorResponse([], 'Project not found', 422);
        $project->delete();
        $project->tasks()->delete();
        $project->boards()->delete();
        $project->tasks()->attachments()->delete();
        $project->tasks()->subTasks()->delete();
        $project->tasks()->comments()->delete();
        return $this->successResponse([], 'Project deleted successfully');
    }
}
