<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SlugRedirect extends Model
{
    protected $fillable = [
        'old_slug',
        'post_id'
    ];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}
