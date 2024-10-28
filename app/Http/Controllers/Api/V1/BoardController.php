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

        $boards = Board::where('user_id', auth()->id())->paginate($perPage);

        if(count($boards) == 0) return $this->errorResponse([], 'No boards found', 422);

        return $this->successResponse($boards, 'Boards fetched successfully');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
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
        $board = Board::find($id);
        $board->update($request->all());
        return response()->json($board);
    }

    public function destroy($id)
    {
        $board = Board::find($id);
        $board->delete();
        return response()->json(['message' => 'Board deleted']);
    }
}
