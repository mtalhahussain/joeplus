<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $guarded = ['id','created_at','updated_at'];
    protected $hidden = ['pivot'];

    static function boot()
    {
        parent::boot();

        static::creating(function($model){
            $model->uuid = (string) \Illuminate\Support\Str::uuid();
        });
    }

    public function boards()
    {
        return $this->hasMany(Board::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function members()
    {
        return $this->belongsToMany(User::class,'project_users','project_id','user_id')->withPivot('role');
    }

    public function meta()
    {
        return $this->hasMany(TaskMeta::class, 'project_id', 'id');
    }
}
