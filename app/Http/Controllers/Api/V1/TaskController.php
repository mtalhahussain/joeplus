<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Task, Board};

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
            
            $tasks = Task::with(['assignees:id,name,avatar'])->where('board_id', $value)->take(10)->get();
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

        $task = Task::create($request->all());
        if(isset($request->assignees) && count($request->assignees) > 0){
            $task->assignees()->attach($request->assignees);
        }else{
            $task->assignees()->attach(auth()->id());
        }
        return $this->successResponse($task, 'Task created successfully');
    }

    public function show($id)
    {
        $task = Task::where('uuid', $id)->with(['assignees:id,name,avatar', 'attachments'])->first();
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
        $task = Task::where('uuid', $id)->first();
        if(!$task) return $this->errorResponse([], 'Task not found', 422);
        $task->update($request->all());
        if(isset($request->assignees) && count($request->assignees) > 0){
            $task->assignees()->sync($request->assignees);
        }else{
            $task->assignees()->sync(auth()->id());
        }
        return $this->successResponse($task, 'Task updated successfully');
    }

    public function destroy($id)
    {
        $task = Task::find($id);
        $task->delete();
        return response()->json(['message' => 'Task deleted']);
    }
}
