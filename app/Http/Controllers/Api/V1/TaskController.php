<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Task, Board, Attachment};
use Illuminate\Support\Facades\DB;

class TaskController extends Controller
{
    public function index(Request $request)
    {
       
        $inputs = $request->all();
        $perPage = $inputs['per_page'] ?? 10;
        $myTasks = [];
        $borards = Board::where('user_id', auth()->id())->whereNull('project_id')->pluck('id', 'name');
        if(count($borards) == 0) return $this->errorResponse([], 'No boards found', 200);
        foreach($borards as $key => $value){
            
            $tasks = Task::with(['assignees:id,name,avatar'])->withCount('subTasks')->where('board_id', $value)->take(10)->get();
            $myTasks[] = ['id' => $value, 'name' => $key, 'tasks' => $tasks];
        }
        return $this->successResponse($myTasks, 'Tasks fetched successfully');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'description' => 'nullable',
            'board_id' => 'required',
        ]);
        DB::beginTransaction();
        $task = Task::create($request->all());
        if(isset($request->assignees) && count($request->assignees) > 0){
            $task->assignees()->attach($request->assignees);
        }

        if($request->hasFile('attachments')){
            $attachments = [];
            foreach($request->file('attachments') as $attachment){
                $attachments[] = [
                    'file_name' => $this->uploadFile($attachment,'task-'.$task->id,'attachments')['filename'],
                    'file_url' => $this->uploadFile($attachment,'task-'.$task->id,'attachments')['path'],
                    'user_id' => auth()->id()
                ];
            }
            $task->attachments()->createMany($attachments);
        }
        DB::commit();
        return $this->successResponse($task, 'Task created successfully');
    }

    public function show($id)
    {
        $task = Task::where('uuid', $id)->with(['assignees:id,name,avatar', 'attachments:id,task_id,file_url'])->first();
        if(!$task) return $this->errorResponse([], 'Task not found', 422);
        return $this->successResponse($task, 'Task fetched successfully');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required',
            'description' => 'nullable',
            'board_id' => 'nullable',
        ]);
        DB::beginTransaction();
        $task = Task::where('uuid', $id)->first();
        if(!$task) return $this->errorResponse([], 'Task not found', 422);
        $task->update($request->all());
        if(isset($request->assignees) && count($request->assignees) > 0){
            $task->assignees()->sync($request->assignees);
        }

        if($request->hasFile('attachments')){
            $attachments = [];
            foreach($request->file('attachments') as $attachment){
                $attachments[] = [
                    'file_name' => $this->uploadFile($attachment,'task-'.$task->id,'attachments')['filename'],
                    'file_url' => $this->uploadFile($attachment,'task-'.$task->id,'attachments')['path'],
                    'user_id' => auth()->id()
                ];
            }
            $task->attachments()->createMany($attachments);
        }
        DB::commit();
        return $this->successResponse($task, 'Task updated successfully');
    }

    public function destroy($id)
    {
        $task = Task::find($id);
        $task->delete();
        $task->assignees()->detach();
        $task->comments()->delete();
        $task->attachments()->delete();
        return response()->json(['message' => 'Task deleted']);
    }

    public function removeTaskAttachments(Request $request)
    {
        $request->validate([
            'attachment_id' => 'required',
        ]);
        $attachment = Attachment::find($request->attachment_id);
        if(!$attachment) return $this->errorResponse([], 'Attachment not found', 422);
        $attachment->delete();
        $this->deleteFile($attachment->file_url);
        return $this->successResponse([], 'Attachment removed successfully');
        
    }
}
