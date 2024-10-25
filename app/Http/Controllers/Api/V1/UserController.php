<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    
    public function index(Request $request)
    {

        $inputs = $request->all();
        $perPage = $request->has('per_page') ? $inputs['per_page'] : 10;

        if(isset($inputs['role'])){
            $users = User::role($inputs['role'])->paginate($perPage);
        }else{
            $users = User::all();
        }

        if(count($users) == 0) return $this->errorResponse('No users found', 422);
        
        return $this->successResponse($users, 'Users fetched successfully');
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

        $user->update($inputs);

        return $this->successResponse($user, 'User updated successfully');
    }

    public function destroy(Request $request, $id)
    {
        $user = User::find($id);

        if(!$user) return $this->errorResponse('User not found', 422);

        $this->deleteFile('users/'.$user->id.'/'.$user->avatar);
        $user->delete();

        return $this->successResponse([], 'User deleted successfully');
    }
    
}
