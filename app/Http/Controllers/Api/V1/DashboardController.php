<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request) {

        if(auth()->user()->role !== 'admin'){

            $taskQuery = auth()->user()->tasks()->where('is_completed', true);
            $projectQuery = auth()->user()->projects();
            $userQuery = auth()->user()->companyUsers();
            
            if(isset($request->task_start_date) && !isset($request->task_end_date)){
                $start_date = Carbon::parse($request->task_start_date)->toDateString();
                $taskQuery->whereate('created_at',$start_date);
                // $taskQuery->whereDate('due_start','=',$start_date);
            }elseif(isset($request->task_start_date) && isset($request->task_end_date)){
                $start_date = Carbon::parse($request->task_start_date)->toDateString();
                $end_date = Carbon::parse($request->task_end_date)->toDateString();
                // $taskQuery->whereDate('due_start','>=',$start_date)->whereDate('due_end','<=',$end_date);
                $taskQuery->whereBetween('created_at',[$start_date,$end_date]);
                
            }
    
            if(isset($request->user_start_date) && !isset($request->user_end_date)){
                $start_date = Carbon::parse($request->user_start_date)->startOfDay()->toDateTimeString();
                $userQuery->where('created_at','=',$start_date);
            
            }elseif(isset($request->user_start_date) && isset($request->user_end_date)){
                $start_date = Carbon::parse($request->user_start_date)->startOfDay()->toDateTimeString();
                $end_date = Carbon::parse($request->user_end_date)->endOfDay()->toDateTimeString();
                $userQuery->whereBetween('created_at',[$start_date,$end_date]);
            }
    
            $up_coming_tasks = auth()->user()->tasks()
                ->with(['creator:id,uuid,name,email', 'assignees:id,uuid,name,email'])
                ->where('is_completed', false)
                ->whereDate('due_start', '>', Carbon::now())
                ->orderBy('due_start', 'asc')
                ->get();
    
            $due_tasks = auth()->user()->tasks()
                ->with(['creator:id,uuid,name,email', 'assignees:id,uuid,name,email'])
                ->where('is_completed', false)
                ->whereDate('due_end', '<', Carbon::now())
                ->orderBy('due_end', 'asc')
                ->get();
    
           
            $data = [
                'completed_count' => $taskQuery->count(),
                'projects_count' => $projectQuery->count(),
                'users' => $userQuery->count(),
                'tasks' => [
                    'up_coming_count' => $up_coming_tasks->count(),
                    'due_count' => $due_tasks->count(),
                    'up_coming' => $up_coming_tasks,
                    'due' => $due_tasks
                ],
                'projects' => $projectQuery->limit(5)->get(),
            ];
            return $this->successResponse($data, 'Dashboard data fetched successfully');
        }
    }
    
}
