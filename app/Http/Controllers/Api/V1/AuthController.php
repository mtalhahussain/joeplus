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
use Illuminate\Support\Facades\Mail;
use App\Mail\UserRegistrationOtp;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Password;
use App\Notifications\ResetPasswordNotification;

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

            $is_user = User::where('email',$request->email)->first();

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
               
                if($userIsGoogle->email !== $request->email) return $this->errorResponse([],'Invalid credentials, This email is not register by Google', 422);
                elseif(Auth::attempt(['email' => $request->email , 'password' => $request->email])) $user = Auth::user();
                else return $this->errorResponse([],'Invalid credentials', 422);

            } 
            
            $token = $user->createToken('auth-token')->plainTextToken;
           
            if ($user && $user->status === 0) return $this->errorResponse([],'Your account is inactive by Admin. Please contact support.', 422);

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
    
        $emailExistsInUsers = User::where('email', $request->email)->exists();
        $emailExistsInOtps = DB::table('otps')->where('email', $request->email)->exists();
    
        if ($emailExistsInUsers)  return $this->errorResponse([], 'Email already in use', 422);

        $otp = rand(100000, 999999);

        DB::table('otps')->insert([
            'email' => $request->email,
            'code' => $otp,
            'created_at' => now(),
            'expires_at' => now()->addMinutes(config('app.otp.expiration')),
            'updated_at' => now(),
        ]);

        $logo = asset('images/logo.png');
        $expirationTime = now()->addMinutes(config('app.otp.expiration'))->diffInMinutes();

        Mail::to($request->email)->send(new UserRegistrationOtp($otp, $logo, $expirationTime));
    
        return $this->successResponse([], 'Email verified successfully and OTP sent');
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

    public function resetNewPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|confirmed|min:8',
        ]);

        $email = Input::get('email');
        $password = Input::get('password');
        $passwordConfirmation = Input::get('password_confirmation');
        $user = User::where('email', $email)->first();

        if(!is_null($user)) {

            $user->password = Hash::make($password);
            $user->save();
            return response()->json(['message' => 'Your password has been changed successfully'],200);
        }
    }

    public function resendOtp(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $email = $request->email;
        $otp = rand(100000, 999999);
       
        $hasRecord = DB::table('otps')->where('email', $email)->latest()->first();
        if(!$hasRecord) return $this->errorResponse([],'Your email is not registered', 422);

        DB::table('otps')->where('email', $email)->delete();
        DB::table('otps')->insert([
            'email' => $email,
            'code' => $otp,
            'created_at' => now(),
            'expires_at' => now()->addMinutes(config('app.otp.expiration')),
            'updated_at' => now(),
        ]);

        $logo = asset('images/logo.png');
        $expirationTime = now()->addMinutes(config('app.otp.expiration'))->diffInMinutes();

        Mail::to($email)->send(new UserRegistrationOtp($otp, $logo, $expirationTime));
        return response()->json(['message' => 'OTP resent successfully'], 200);
    }

    public function verifyOtpCode(Request $request)
    {
        $request->validate(['email' => 'required' ,'otp' => 'required']);
        
        $otpRecord = DB::table('otps')->where('email', $request->email)->latest()->first();
        
        if(!$otpRecord) return $this->errorResponse([],'Otp not found', 422);
        $checkExpiration = now()->diffInMinutes($otpRecord->expires_at) <= 0 ? true : false;
        if ($checkExpiration) return $this->errorResponse([],'Otp code is expired, Please resend', 422);
        if (!$otpRecord || $otpRecord->code != $request->otp) return $this->errorResponse([],'Invalid otp code', 422);
        DB::table('otps')->where('email', $request->email)->delete();
        return $this->successResponse([], 'Otp verified successfully', 200);
    }
    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);
    
        $user = User::where('email', $request->email)->first();
    
        if ($user) {
            $token = Password::createToken($user);

            $user->notify(new ResetPasswordNotification($token));
            return $this->successResponse([], 'Reset password link sent successfully');
        } else {
            return $this->errorResponse([], 'Email not found', 422);
        }
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => __($status)], 200);
        } else {
            return response()->json(['message' => __($status)], 422);
        }
    }

}
