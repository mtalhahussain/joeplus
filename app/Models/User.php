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

    protected $appends = ['role', 'avatar_url', 'unread_notifications_count'];

    protected $hidden = [
        'password',
        'remember_token',
        'roles',
        'google_id',
        'pivot',
        'notifications'
    ];
    
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'integer'
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
        return !is_null($this->avatar) ? asset('storage/users/'.$this->id.'/'.$this->avatar) : 'https://api.dicebear.com/9.x/initials/svg?seed='.str_replace(' ','-',$this->name);
    }

    public function getRoleAttribute()
    {
        return !is_null($this->roles->first()) ? $this->roles->first()->name : null;
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
        return $this->belongsToMany(Project::class,'project_users','user_id','project_id');
    }

    public function companyUsers()
    {
        return $this->belongsToMany(User::class, 'company_users', 'company_id', 'user_id');
    }
    
    public function getUnreadNotificationsCountAttribute()
    {
        return $this->unreadNotifications->count();
    }
}
