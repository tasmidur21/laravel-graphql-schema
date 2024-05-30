<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Post extends Model
{
    use HasFactory;
    protected $guarded=["id","created_at","updated_at","deleted_at"];

    public function comments(): HasMany
    {
        return $this->hasMany('App\Models\Comment', 'commentable_id', 'id');
    }
}
