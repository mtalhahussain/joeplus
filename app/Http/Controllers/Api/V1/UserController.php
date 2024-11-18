<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    
    public function index(Request $request)
    {

        $inputs = $request->all();
        $perPage = $request->has('per_page') ? $inputs['per_page'] : 10;

        if(isset($inputs['role'])) $users = User::role($inputs['role'])->paginate($perPage);
        else $users = User::all();
        
        if(count($users) == 0) return $this->errorResponse('No users found', 422);
        
        return $this->successResponse($users, 'Users fetched successfully');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
        ]);

        $inputs = $request->all();
        $inputs['password'] = Hash::make($inputs['password']);

        if($request->hasFile('avatar') && !empty($request->file('avatar'))) {
            $inputs['avatar'] = $this->uploadFile($request->file('avatar'), null,'users')['filename'];
        }
        DB::beginTransaction();
        $user = User::create($inputs);
        $user->assignRole('guest');
        DB::commit();

        return $this->successResponse($user, 'User created successfully');
    }

    public function show(Request $request, $id)
    {
        $user = User::find($id);
        if(!$user) return $this->errorResponse('User not found', 422);

        return $this->successResponse($user, 'User fetched successfully');
    }

    public function update(Request $request, $id)
    {
        $inputs = $request->all();
        $user = User::where('uuid', $id)->first();

        if(!$user) return $this->errorResponse('User not found', 422);

        if(isset($inputs['password'])){
            $request->validate([
                'password' => 'required|confirmed',
            ]);
            $inputs['password'] = Hash::make($inputs['password']);
        }

        if($request->hasFile('avatar') && !empty($request->file('avatar'))) {
            $this->deleteFile('users/'.$user->id.'/'.$user->avatar);
            $inputs['avatar'] = $this->uploadFile($request->file('avatar'), $user->id,'users')['filename'];
        }
        DB::beginTransaction();
        $user->update($inputs);
        DB::commit();
        return $this->successResponse($user, 'User updated successfully');
    }

    public function destroy(Request $request, $id)
    {
        $user = User::where('uuid', $id)->first();

        if(!$user) return $this->errorResponse('User not found', 422);
        DB::beginTransaction();
        $this->deleteFile('users/'.$user->id.'/'.$user->avatar);
        $user->delete();
        // $user->tasks()->delete();
        // $user->boards()->delete();
        // $user->projects()->delete();
        // $user->projects()->tasks()->delete();
        // $user->projects()->boards()->delete();
        // $user->projects()->comments()->delete();
        // $user->projects()->subTasks()->delete();
        DB::commit();
        return $this->successResponse([], 'User deleted successfully');
    }

    public function calculateBookingEndDateTime($startDateTime, $durationInMinutes)
    {
        // Set the start date and time
        $start = Carbon::parse($startDateTime);

        // Set office hours
        $officeStart = 9 * 60; // 9:00 AM in minutes
        $officeEnd = 19 * 60; // 7:00 PM in minutes
        $officeDayMinutes = $officeEnd - $officeStart; // Total working minutes per day

        $remainingMinutes = $durationInMinutes;

        // Loop until we calculate the end date and time within office hours
        while ($remainingMinutes > 0) {
            // If the start time is before office hours, move to 9 AM
            if ($start->hour < 9) {
                $start->setTime(9, 0);
            }

            // If the start time is after office hours, move to the next day at 9 AM
            if ($start->hour >= 19) {
                $start->addDay()->setTime(9, 0);
            }

            // Check if today is within office hours and a working day
            if ($start->isWeekend()) {
                // Move to next Monday if it's a weekend
                $start->addWeekday()->setTime(9, 0);
                continue;
            }

            // Calculate available minutes for the current office day from the start time
            $currentDayEnd = (clone $start)->setTime(19, 0);
            $availableMinutesToday = $start->diffInMinutes($currentDayEnd);

            // Use the lesser of remaining or available minutes for this day
            $minutesToUse = min($remainingMinutes, $availableMinutesToday);

            // Move the start time by the calculated minutes
            $start->addMinutes($minutesToUse);

            // Subtract the used minutes from remaining duration
            $remainingMinutes -= $minutesToUse;
        }

        return $start; // End date and time
    }

}
