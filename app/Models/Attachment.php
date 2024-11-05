<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    use HasFactory;

    protected $guarded = ['id','created_at','updated_at'];

    protected $appends = ['original_url'];

    static function boot()
    {
        parent::boot();

        static::creating(function($model){
            $model->uuid = (string) \Illuminate\Support\Str::uuid();
        });
    }

    public function getOriginalUrlAttribute()
    {
        if(!$this->task_id && !$this->sub_task_id && !$this->comment_id) return 'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=mp&f=y';
        return asset('storage/'.$this->file_url);
    }


    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comment()
    {
        return $this->belongsTo(TaskComment::class);
    }
}
