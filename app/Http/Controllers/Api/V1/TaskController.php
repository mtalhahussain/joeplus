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

        $borards = Board::where('user_id', auth()->id())->pluck('id', 'name');
        if(count($borards) == 0) return $this->errorResponse([], 'No boards found', 422);
        $tasks = Task::all();
        return response()->json($tasks);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'description' => 'nullable',
            'board_id' => 'required',
        ]);

        $task = Task::create($request->all());
        return response()->json($task);
    }

    public function show($id)
    {
        $task = Task::find($id);
        return response()->json($task);
    }

    public function update(Request $request, $id)
    {
        $task = Task::find($id);
        $task->update($request->all());
        return response()->json($task);
    }

    public function destroy($id)
    {
        $task = Task::find($id);
        $task->delete();
        return response()->json(['message' => 'Task deleted']);
    }
}
