<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Requests\RegisterRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);
      
        DB::beginTransaction();
        $user = User::create($data);
        DB::commit();

        if ($this->isCommonEmailProvider($data['email'])) $user->assignRole('guest');
        else $user->assignRole('company');

        if($request->hasFile('avatar') && !empty($request->file('avatar'))) {
            $user->avatar = $this->uploadFile($request->file('avatar'), $user->id,'users')['filename'];
            $user->save();
        }

        return $this->successResponse($user, 'User created successfully');
    }

    private function isCommonEmailProvider($email)
    {
        $commonProviders = ['gmail.com', 'yahoo.com', 'outlook.com', 'hotmail.com'];
        $domain = substr(strrchr($email, "@"), 1);

        return in_array($domain, $commonProviders);
    }
}
