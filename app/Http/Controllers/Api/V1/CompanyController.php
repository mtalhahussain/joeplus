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
        $users = User::select('id','name','email','avatar')->whereIn('id', $userIds)->latest()->paginate($perPage);
        
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

        auth()->user()->companyUsers()->attach(['user_id' => $user->id]);

        DB::commit();

        return $this->successResponse($user, 'User created successfully');
    }
    
}
