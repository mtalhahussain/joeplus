<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{SubTask, Task};
use Illuminate\Support\Facades\DB;

class SubTaskConroller extends Controller
{
    public function index(Request $request)
    {
        $inputs = $request->all();
        $perPage = $inputs['per_page'] ?? 10;
        $task_id = $inputs['task_id'] ?? null;
        if($task_id) $subTasks = SubTask::where('task_id', $task_id)->with(['task:id,title', 'assignees:id,name,avatar','attachments:id,sub_task_id,file_url'])->paginate($perPage);
        else $subTasks = SubTask::with(['task:id,title', 'assignees:id,name,avatar'])->paginate($perPage);
        
        return $this->successResponse($subTasks, 'SubTasks fetched successfully');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'description' => 'nullable',
            'task_id' => 'required',
        ]);
        DB::beginTransaction();
        $subTask = SubTask::create($request->all());
        if(isset($request->assignees) && count($request->assignees) > 0){
            $subTask->assignees()->attach($request->assignees);
        }
        if($request->hasFile('attachments')){
            $attachments = [];
            foreach($request->file('attachments') as $attachment){
                $attachments[] = [
                    'file_name' => $this->uploadFile($attachment,'subtask-'.$subTask->id,'attachments')['filename'],
                    'file_url' => $this->uploadFile($attachment,'subtask-'.$subTask->id,'attachments')['path'],
                    'user_id' => auth()->id()
                ];
            }
            $subTask->attachments()->createMany($attachments);
        }
        DB::commit();
        return $this->successResponse($subTask, 'SubTask created successfully');
    }

    public function show($id)
    {
        $subTask = SubTask::where('uuid', $id)->with(['task:id,title', 'assignees:id,name,avatar'])->first();
        if(!$subTask) return $this->errorResponse([], 'SubTask not found', 422);
        return $this->successResponse($subTask, 'SubTask fetched successfully');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required',
            'task_id' => 'nullable',
        ]);
        $subTask = SubTask::where('uuid', $id)->first();
        if(!$subTask) return $this->errorResponse([], 'SubTask not found', 422);
        DB::beginTransaction();
        $subTask->update($request->all());
        if(isset($request->assignees) && count($request->assignees) > 0){
            $subTask->assignees()->sync($request->assignees);
        }
        if($request->hasFile('attachments')){
            $attachments = [];
            foreach($request->file('attachments') as $attachment){
                $attachments[] = [
                    'file_name' => $this->uploadFile($attachment,'subtask-'.$subTask->id,'attachments')['filename'],
                    'file_url' => $this->uploadFile($attachment,'subtask-'.$subTask->id,'attachments')['path'],
                    'user_id' => auth()->id()
                ];
            }
            $subTask->attachments()->createMany($attachments);
        }
        DB::commit();
        return $this->successResponse($subTask, 'SubTask updated successfully');
    }

    public function destroy($id)
    {
        $subTask = SubTask::where('uuid', $id)->first();
        if(!$subTask) return $this->errorResponse([], 'SubTask not found', 422);
        $subTask->delete();
        $subTask->assignees()->detach();
        $subTask->attachments()->delete();
        $subTask->comments()->delete();
        return $this->successResponse([], 'SubTask deleted successfully');
    }
}
