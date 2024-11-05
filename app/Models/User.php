<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Str;

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
        'google_id',
        'pivot'
    ];

    
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    static function boot()
    {
        parent::boot();

        static::creating(function($model){
            $model->uuid = (string) Str::uuid();
        });
    }

    public function getAvatarUrlAttribute()
    {
        return $this->avatar ? asset('storage/users/'.$this->id.'/'.$this->avatar) : 'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=mp&f=y'.$this->email.'&color=7F9CF5&background=EBF4FF';
    }

    public function getRoleAttribute()
    {
        return $this->roles->first()->name;
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function boards()
    {
        return $this->hasMany(Board::class);
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public function companyUsers()
    {
        return $this->belongsToMany(User::class, 'company_users', 'company_id', 'user_id');
    }
    

}
