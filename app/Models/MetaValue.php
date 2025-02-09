<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetaValue extends Model
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

    public function customField()
    {
        return $this->belongsTo(TaskMeta::class, 'meta_id');
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
