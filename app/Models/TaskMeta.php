<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskMeta extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'value' => 'array'
    ];

    static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = \Str::uuid();
        });
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function values()
    {
        return $this->hasMany(MetaValue::class, 'meta_id');
    }
}
