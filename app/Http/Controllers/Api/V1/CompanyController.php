<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class CompanyController extends Controller
{

    public function index(Request $request)
    {
        $inputs = $request->all();
        $perPage = $request->has('per_page') ? $inputs['per_page'] : 10;

        $userIds = auth()->user()->companyUsers()->pluck('user_id');
        if(count($userIds) == 0) return $this->errorResponse([],'No users found', 422);
        if(isset($inputs['status']) && $inputs['status'] == 'pending'){

            $users = User::where('is_active',false)->whereIn('id', $userIds)->latest()->paginate($perPage); 

        }else{

            $users = User::whereIn('id', $userIds)->latest()->paginate($perPage);
        }
        
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
        $inputs['is_active'] = false;

        DB::beginTransaction();

        $user = User::create($inputs);

        $user->assignRole('guest');

        auth()->user()->companyUsers()->attach(['user_id' => $user->id]);

        if($request->hasFile('avatar') && !empty($request->file('avatar'))) {
           
            $inputs['avatar'] = $this->uploadFile($request->file('avatar'), $user->id,'users')['filename'];
            $user->update(['avatar' => $inputs['avatar']]);
        }
        DB::commit();

        return $this->successResponse($user, 'User created successfully');
    }

    public function show(Request $request, $id)
    {
        $user = User::where('uuid', $id)->first();

        if(!$user) return $this->errorResponse('User not found', 422);

        return $this->successResponse($user, 'User fetched successfully');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email,'.$id.',uuid',
        ]);
        $inputs = $request->all();  
        $user = User::where('uuid', $id)->first();
        if(!$user) return $this->errorResponse([], 'User not found', 422);
        
        DB::beginTransaction();

        $user->update($inputs);

        if($request->hasFile('avatar') && !empty($request->file('avatar'))) {

            $inputs['avatar'] = $this->uploadFile($request->file('avatar'), $user->id,'users')['filename'];
            $user->update(['avatar' => $inputs['avatar']]);
        }

        if(isset($inputs['password'])) $user->update(['password' => Hash::make($inputs['password'])]);
        
        DB::commit();

        return $this->successResponse($user, 'User updated successfully');
    }

    public function destroy(Request $request, $id)
    {
        $user = User::where('uuid', $id)->first();
        if(!$user) return $this->errorResponse([], 'User not found', 422);
        DB::beginTransaction();
        $this->deleteFile('users/'.$user->id.'/'.$user->avatar);
        $user->delete();
        DB::commit();
        return $this->successResponse([], 'User deleted successfully');
    }

}
