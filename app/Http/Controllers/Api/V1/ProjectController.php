<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Project, Task};
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $inputs = $request->all();
        $perPage = $inputs['per_page'] ?? 10;

        if(auth()->user()->role == 'admin'){

            $projects = Project::withCount('tasks')->latest()->paginate($perPage);

        }else{
            $projects = auth()->user()->projects()->withCount('tasks')->with('members:id,name,avatar')->paginate($perPage);
        }
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
        $project = null;
        DB::transaction(function () use ($inputs, $request, &$project) {
            
            $project = Project::create($inputs);

            if (isset($request->members) && count($request->members) > 0) {
                foreach ($request->members as $key => $member) {
                    // dd($member);
                    $project->members()->attach($project->id,$member);
                }
                $project->members()->attach($project->id, ['user_id' => auth()->id(),'role' => 'admin']);

            } else {
               
                $project->members()->attach($project->id, ['user_id' => auth()->id(),'role' => 'admin']);
            }
        });

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

        if (isset($request->members) && count($request->members) > 0) {
            $project->members()->detach();
            foreach ($request->members as $key => $member) {
               
                $project->members()->attach($project->id,$member);
            }
            $project->members()->attach($project->id, ['user_id' => auth()->id(),'role' => 'admin']);

        } else {
           
            $project->members()->attach($project->id, ['user_id' => auth()->id(),'role' => 'admin']);
        }
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
