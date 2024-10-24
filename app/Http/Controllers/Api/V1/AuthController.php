<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Requests\{RegisterRequest, LoginRequest};
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $inputs = $request->validated();
        
        if($request->has('google_id')){
          
            $is_user = User::where('google_id',$inputs['google_id'])->exists();

           if(!$is_user){

                $inputs['password'] = Hash::make($inputs['email']);

                DB::beginTransaction();
                    $user = User::updateOrCreate(['google_id' => $inputs['google_id'],],$inputs);
                    if ($this->isCommonEmailProvider($user->email)) $user->assignRole('guest');
                    else $user->assignRole('company');
                DB::commit();

           }else{

                return $this->errorResponse('This email has been already registered', 422);
            }

        }else{

            $inputs['password'] = Hash::make($inputs['password']);
            DB::beginTransaction();
            $user = User::create($inputs);
            if ($this->isCommonEmailProvider($user->email)) $user->assignRole('guest');
            else $user->assignRole('company');
            DB::commit();
        }
        
        if($request->hasFile('avatar') && !empty($request->file('avatar'))) {
            $user->avatar = $this->uploadFile($request->file('avatar'), $user->id,'users')['filename'];
            $user->save();
        }
        
        $token = $user->createToken('auth-token')->plainTextToken;
        
        return $this->successResponse($user, 'User created successfully');
    }

    public function login(LoginRequest $request)
    {
        $inputs = $request->validated();

        if ($request->filled('google_id')) {

            $is_user = User::where('google_id',$request->google_id)->first();

            if(!$is_user){
                $inputs['name'] = Str::before($request->email, '@');
                $inputs['password'] = Hash::make($request->email);
                DB::beginTransaction();
                $user = User::updateOrCreate(
                    ['google_id' => $request->google_id],
                    $inputs
                );
                if ($this->isCommonEmailProvider($user->email)) $user->assignRole('guest');
                else $user->assignRole('company');
                DB::commit();

            }else{
               
                $userIsGoogle = User::where('google_id',$request->google_id)->first();
                if(!$userIsGoogle) return $this->errorResponse([],'Invalid credentials, This email is not register by Google', 422);
               
                // dd($userIsGoogle->email,$request->email);
                if($userIsGoogle->email !== $request->email) return $this->errorResponse([],'Invalid credentials, This email is not register by Google', 422);
                elseif(Auth::attempt(['email' => $request->email , 'password' => $request->email])) $user = Auth::user();
                else return $this->errorResponse([],'Invalid credentials', 422);

            } 
            
            $token = $user->createToken('auth-token')->plainTextToken;
           
            if ($user && $user->status === 0) return $this->errorResponse([],'Your account is blocked by Admin. Please contact support.', 422);

            return $this->successResponse(['token' => $token, 'user' => $user], 'User logged in successfully');

        } else {

            if (Auth::attempt($request->only('email', 'password'))) {

                $user = Auth::user();

                $token = $user->createToken('auth-token')->plainTextToken;

                if ($user && $user->status === 0) return $this->errorResponse([],'Your account is blocked by Admin. Please contact support.', 422);
                
                return $this->successResponse(['token' => $token, 'user' => $user], 'User logged in successfully');

            } else {
                    
                return $this->errorResponse([],'Invalid credentials', 422);
            }
        }

    }

    private function isCommonEmailProvider($email)
    {
        $commonProviders = ['gmail.com', 'yahoo.com', 'outlook.com', 'hotmail.com'];
        $domain = substr(strrchr($email, "@"), 1);

        return in_array($domain, $commonProviders);
    }

    public function verifyCheckEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->exists();

        if($user) return $this->errorResponse([],'User already exists', 422);

        return $this->successResponse([], 'Registration can continue');
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return $this->successResponse([], 'User logged out successfully');
    }

    public function me(Request $request)
    {
        return $this->successResponse($request->user(), 'User fetched successfully');
    }
}
