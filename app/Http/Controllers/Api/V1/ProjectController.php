<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Project, Task, User};
use App\Notifications\ProjectMemberNotification;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $inputs = $request->all();
        $perPage = $inputs['per_page'] ?? 10;

        if(auth()->user()->role == 'admin'){

            $projects = Project::withCount('tasks')->with(['tasks.meta'])->latest()->paginate($perPage);

        }else{
            $projects = auth()->user()->projects()->withCount('tasks')->with(['members' => function ($query) {
                $query->select('users.id', 'users.name', 'users.avatar','users.email')
                      ->addSelect('project_users.role as project_role');
            },'tasks.meta'])->paginate($perPage);
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
                   
                    $project->members()->attach($project->id,$member);
                  
                    if(auth()->id() != $member['user_id']){
                        $member = User::find($member['user_id']);
                        $member->notify(new ProjectMemberNotification(auth()->user()->name,$project));
                    }

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
        $project = Project::where('uuid', $id)->with(['tasks:id,title,project_id','tasks.meta'])->first();
        if(!$project) return $this->errorResponse([], 'Project not found', 422);
        return $this->successResponse($project, 'Project fetched successfully');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'description' => 'nullable',
            'project_members' => 'nullable'
        ]);

        $project = Project::where('uuid', $id)->first();
        if(!$project) return $this->errorResponse([], 'Project not found', 422);
        $project->update($request->all());

        if (isset($request->project_members) && count($request->project_members) > 0) {
            $existingMembers = $project->members->pluck('id')->toArray();
            $project->members()->detach();
            foreach ($request->project_members as $key => $member) {
            $project->members()->attach($project->id, $member);
                if (!in_array($member['user_id'], $existingMembers) && auth()->id() != $member['user_id']) {
                    $newMember = User::find($member['user_id']);
                    $newMember->notify(new ProjectMemberNotification(auth()->user()->name, $project));
                }
            }
        }
        return $this->successResponse($project, 'Project updated successfully');
    }
    public function destroy($id)
    {
        $project = Project::where('uuid', $id)->first();
        if(!$project) return $this->errorResponse([], 'Project not found', 422);
    
        $project->tasks()->each(function ($task) {
            if ($task->attachments()->count() > 0) {
                $task->attachments()->delete();
                $task->subTasks()->each(function ($sub_task) { 
                    if ($sub_task->attachments()->count() > 0) {
                        $sub_task->attachments()->delete(); 
                    }
                });
            }
        });
    
        $project->tasks()->each(function ($task) {
            if ($task->comments()->count() > 0) {
                $task->comments()->delete();
                $task->subTasks()->each(function ($sub_task) { 
                    if ($sub_task->comments()->count() > 0) {
                        $sub_task->comments()->delete(); 
                    }
                });
            }
        });
    
        $project->tasks()->each(function ($task) {
            if ($task->subTasks()->count() > 0) {
                $task->subTasks()->delete();
            }
        });
    
        $project->members()->detach();
    
        $project->delete();
    
        $project->boards()->delete();
    
        $project->tasks()->delete();
    
        return $this->successResponse([], 'Project deleted successfully');
    }
    
}
