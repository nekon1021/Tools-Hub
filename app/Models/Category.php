<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name','slug','sort_order','is_active'];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order'=> 'integer',
    ];

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    // name から slug 自動生成（重複回避）
    protected static function booted()
    {
        static::creating(function (Category $c) {
            if (blank($c->slug)) {
                $base = Str::slug($c->name);
                $slug = $base ?: Str::lower(Str::random(8));
                $i = 2;
                while (static::where('slug',$slug)->exists()) {
                    $slug = "{$base}-{$i}";
                    $i++;
                }
                $c->slug = $slug;
            }
        });
    }

    // Admin用: アクティブ優先 + 並び順
    public function scopeActiveSorted(Builder $q): Builder
    {
        return $q->where('is_active', true)->orderBy('sort_order')->orderBy('name');
    }
}
