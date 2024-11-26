<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Board};

class BoardController extends Controller
{
    public function index(Request $request)
    {
        $inputs  = $request->all();
        $perPage = $inputs['per_page'] ?? 10;

        $boards = Board::where('user_id', auth()->id())->orderBy('position')->paginate($perPage);

        if(count($boards) == 0) return $this->errorResponse([], 'No boards found', 422);

        return $this->successResponse($boards, 'Boards fetched successfully');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:boards,name,NULL,id,user_id,'.auth()->id(),
            'project_id' => 'nullable',
        ]);

        $inputs = $request->all();
        $inputs['user_id'] = auth()->id();

        $board = Board::create($inputs);
        return $this->successResponse($board, 'Board created successfully');
    }

    public function show($id)
    {
        $board = Board::find($id);
        
        if(!$board) return $this->errorResponse([], 'Board not found', 422);

        return $this->successResponse($board, 'Board fetched successfully');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|unique:boards,name,'.$id.',id,user_id,'.auth()->id(),
            'project_id' => 'nullable',
        ]);
        $board = Board::where('uuid', $id)->first();
        if(!$board) return $this->errorResponse([], 'Board not found', 422);
        $board->update($request->all());
        return $this->successResponse($board, 'Board updated successfully');
    }

    public function destroy($id)
    {
        $board = Board::where('uuid', $id)->first();
        if(!$board) return $this->errorResponse([], 'Board not found', 422);
        $hasTasks = $board->tasks()->get();
        if(count($hasTasks) > 0) return $this->errorResponse([], 'Before deleting board, delete all tasks', 422);
        $board->delete();

        return $this->successResponse([], 'Board deleted successfully');
    }

    public function boardPositionUpdate(Request $request)
    {
        $request->validate([
            'positions' => 'required',
        ]);

        $positions = $request->positions;

        foreach ($positions as $id => $position) {
            Board::find($id)->update(['position' => $position]);
        }

        return $this->successResponse([], 'Position updated successfully');

    }
}
