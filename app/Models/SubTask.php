<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubTask extends Model
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

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function assignees()
    {
        return $this->belongsToMany(User::class, 'task_assignees', 'sub_task_id', 'user_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class, 'sub_task_id');
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class, 'sub_task_id');
    }
}
