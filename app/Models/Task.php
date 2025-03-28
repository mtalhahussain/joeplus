<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $guarded = ['id','created_at','updated_at'];

    static function boot()
    {
        parent::boot();

        static::creating(function($model){
            $model->uuid = (string) \Illuminate\Support\Str::uuid();
        });
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function board()
    {
        return $this->belongsTo(Board::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function comments()
    {
        return $this->hasMany(TaskComment::class, 'task_id');
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class, 'task_id');
    }

    public function assignees()
    {
        return $this->belongsToMany(User::class, 'task_assignees', 'task_id', 'user_id');
    }

    public function subTasks()
    {
        return $this->hasMany(SubTask::class);
    }

    public function meta()
    {
        return $this->hasMany(MetaValue::class, 'task_id');
    }
}
