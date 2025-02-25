<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request) {

        if(auth()->user()->role !== 'admin'){

            $taskQuery = auth()->user()->tasks();
            $projectQuery = auth()->user()->projects();
            $userQuery = auth()->user()->projects()->whereHas('members');

            if(isset($request->task_start_date) && !isset($request->task_end_date)){
                $start_date = Carbon::parse($request->task_start_date)->toDateString();
                $taskQuery->whereDate('due_start','=',$start_date);
            }elseif(isset($request->task_start_date) && isset($request->task_end_date)){
                $start_date = Carbon::parse($request->task_start_date)->toDateString();
                $end_date = Carbon::parse($request->task_end_date)->toDateString();
                $taskQuery->whereDate('due_start','>=',$start_date)->whereDate('due_end','<=',$end_date);
            }

            if(isset($request->user_start_date) && !isset($request->user_end_date)){
                $start_date = Carbon::parse($request->user_start_date)->startOfDay()->toDateTimeString();
                $userQuery->where('created_at','=',$start_date);
            
            }elseif(isset($request->user_start_date) && isset($request->user_end_date)){
                $start_date = Carbon::parse($request->user_start_date)->startOfDay()->toDateTimeString();
                $end_date = Carbon::parse($request->user_end_date)->endOfDay()->toDateTimeString();
                $userQuery->whereBetween('created_at',[$start_date,$end_date]);
            }


            $data = [
                'completed_tasks' => $taskQuery->where('is_completed',true)->count(),
                'projects' => $projectQuery->count(),
                'users' => $userQuery->count(),
            ];
            return $this->successResponse($data, 'Dashboard data fetched successfully');

        }
    }
}
