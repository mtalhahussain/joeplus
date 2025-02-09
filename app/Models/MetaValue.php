<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetaValue extends Model
{
    use HasFactory;

    protected $guarded = ['id','created_at','updated_at'];

    public function customField()
    {
        return $this->belongsTo(TaskMeta::class, 'meta_id');
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
