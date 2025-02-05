<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Task, TaskMeta, Project};

class MetaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $inputs = $request->all();
        $perPage = $inputs['per_page'] ?? 10;
        $taskMeta = TaskMeta::latest()->paginate($perPage);
        return $this->successResponse($taskMeta, 'Task meta fetched successfully');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'project_id' => 'required|exists:projects,uuid',
            'type' => 'required',
            'key' => 'required',
            'value' => 'nullable',
        ]);

        $inputs = $request->all();
        $inputs['project_id'] = Project::where('uuid', $inputs['project_id'])->first()->id;
        $inputs['user_id'] = auth()->id();
        $taskMeta = TaskMeta::create($inputs);
        return $this->successResponse($taskMeta, 'Task meta created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $project = Project::where('uuid', $id)->first();
        if(!$project) return $this->errorResponse([], 'Project not found', 422);

        $taskMeta = TaskMeta::where('task_id', $project->id)->latest()->get();
        return $this->successResponse($taskMeta, 'Task meta fetched successfully');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'type' => 'required',
            'key' => 'required',
            'value' => 'nullable',
        ]);

        $inputs = $request->all();
        $taskMeta = TaskMeta::where('uuid', $id)->first();
        if(!$taskMeta) return $this->errorResponse([], 'Task meta not found', 422);
        $taskMeta->update($inputs);
        return $this->successResponse($taskMeta, 'Task meta updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $taskMeta = TaskMeta::where('uuid', $id)->first();
        if(!$taskMeta) return $this->errorResponse([], 'Task meta not found', 422);
        $taskMeta->delete();
        return $this->successResponse([], 'Task meta deleted successfully');
    }
}
