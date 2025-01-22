<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Task, Board, Attachment, Project};
use Illuminate\Support\Facades\DB;

class TaskController extends Controller
{
    public function index(Request $request)
    {
       
        $inputs = $request->all();
        $perPage = $inputs['per_page'] ?? 10;
        $myTasks = [];
        $boards = Board::select('id','uuid','name','position')->where('user_id', auth()->id())->orderBy('position')->whereNull('project_id')->get();
        if(count($boards) == 0) return $this->errorResponse([], 'No boards found', 200);
        foreach($boards as $key => $board){
           
            $tasks = Task::with(['assignees:id,name,email,avatar','creator:id,name,email,avatar'])->withCount('subTasks')->where('board_id', $board->id)->latest()->take(10)->get();
            $myTasks[] = ['id' => $board->id, 'board_uuid' => $board->uuid, 'name' => $board->name, 'position' => $board->position, 'tasks' => $tasks];
        }
        return $this->successResponse($myTasks, 'Tasks fetched successfully');
    }

    public function getBoardTasks(Request $request, $board_id)
    {
       
        $offset = $request->get('offset', 0); 
        $limit = $request->get('limit', 10);

        $tasks = Task::with(['assignees:id,name,email,avatar','creator:id,name,email,avatar'])->withCount('subTasks')->where('board_id', $board_id)->latest()
                    ->offset($offset)
                    ->limit($limit)
                    ->get();

        $totalTasks = Task::where('board_id', $board_id)->count();

        return $this->successResponse([
            'tasks' => $tasks,
            'offset' => $offset,
            'limit' => $limit,
            'total' => $totalTasks,
            'has_more' => $offset + $limit < $totalTasks,
        ], 'Tasks fetched successfully');
    }


    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'description' => 'nullable',
            'board_id' => 'required',
        ]);

        $inputs = $request->all();
        $inputs['user_id'] = auth()->id();
        DB::beginTransaction();
        $task = Task::create($inputs);
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
        $task = Task::where('uuid', $id)->with(['assignees:id,name,avatar', 'attachments:id,task_id,file_url','subTasks'])->first();
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
        if(isset($request->assignees_id) && count($request->assignees_id) > 0){
            $task->assignees()->sync($request->assignees_id);
        }else{

            $task->assignees()->detach();
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

    public function getProjectTasks(Request $request, $project_id)
    {
       
        $perPage = $request->per_page ?? 10;
        $project = Project::where('uuid', $project_id)->with(['members' => function ($query) {
                $query->select('users.id', 'users.name', 'users.avatar','users.email')
                      ->addSelect('project_users.role as project_role');
            }])->first();
        if(!$project) return $this->errorResponse([], 'Project not found', 422);

        $boards = Board::select('id','uuid', 'name', 'position')->where('user_id', auth()->id())->orderBy('position')->where('project_id',$project->id)->get();
        if(count($boards) == 0) return $this->errorResponse(['tasks' => [], 'project' => $project], 'No boards found', 200);

        $myTasks = [];
       
        foreach ($boards as $key => $board) {
            
            $tasks = Task::with([
                'assignees:id,name,email,avatar',
                'creator:id,name,email,avatar'
            ])
            ->withCount('subTasks')
            ->where('project_id', $project->id)
            ->where('board_id', $board->id)
            // ->whereHas('assignees', function ($query) use ($project) {
            //     $query->whereIn('user_id', $project->members->pluck('id'));
            // })
            ->latest()
            ->take(10)
            ->get();
        
            $myTasks[] = [
                'id' => $board->id,
                'board_uuid' => $board->uuid,
                'name' => $board->name,
                'position' => $board->position,
                'tasks' => $tasks,
            ];
        }        
        
        return $this->successResponse(['tasks' => $myTasks, 'project' => $project], 'Tasks fetched successfully');
    }
}
