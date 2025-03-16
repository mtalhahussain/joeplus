<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Portfolio extends Model
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

    public function projects()
    {
        return $this->belongsToMany(Project::class, 'portfolio_project');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function nestedPortfolios()
    {
        return $this->hasMany(Portfolio::class, 'parent_portfolio_id');
    }

    public function parentPortfolio()
    {
        return $this->belongsTo(Portfolio::class, 'parent_portfolio_id');
    }

    public function favorites()
    {
        return $this->belongsToMany(User::class, 'favorites', 'portfolio_id', 'user_id');
    }
}
