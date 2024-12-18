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
        $projects = Project::withCount('tasks')->latest()->paginate($perPage);
        return $this->successResponse($projects, 'Projects fetched successfully');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'description' => 'nullable',
            'members' => 'nullable|array',
        ]);
        $inputs = $request->all();
        $inputs['owner_id'] = auth()->id();
        $project = Project::create($inputs);

        // if ($request->members && count($request->members) > 0) {
        //     foreach ($request->members as $key => $user_id) {
              
        //         $project->users()->attach($user_id, ['role' => $request->roles[$key]]);
        //     }
        // } else {
            
        //     $project->users()->attach(Auth::id(), ['role' => 'admin']);
        // }

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
            'name' => 'required',
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
     
        if($project->tasks()->whereHas('attachments')->count()) $project->tasks()->attachments()->delete();
        if($project->tasks()->whereHas('comments')->count() > 0) $project->tasks()->comments()->delete();
        if($project->tasks()->whereHas('subTasks')->count() > 0) $project->tasks()->subTasks()->delete();
        $project->delete();
        $project->boards()->delete();
        $project->tasks()->delete();
        return $this->successResponse([], 'Project deleted successfully');
    }
}
