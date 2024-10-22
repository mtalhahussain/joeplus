<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $guard_name = 'sanctum';

    protected $guarded = ['id','created_at','updated_at']; 

    protected $appends = ['role', 'avatar_url'];

    protected $hidden = [
        'password',
        'remember_token',
        'roles',
    ];

    
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function getAvatarUrlAttribute()
    {
        return $this->avatar ? asset('storage/users/'.$this->id.'/'.$this->avatar) : 'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=mp&f=y'.$this->email.'&color=7F9CF5&background=EBF4FF';
    }

    public function getRoleAttribute()
    {
        return $this->roles->first()->name;
    }
}
