<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Category extends Model
{
    use SoftDeletes;

    protected $fillable = ['cat_name', 'slug', 'description'];

    protected static function booted(): void
    {
        static::creating(function (Category $category) {
            if (! empty($category->slug)) {
                return;
            }

            $base = Str::slug($category->cat_name);
            $slug = $base;
            $i = 2;

            while (static::withTrashed()->where('slug', $slug)->exists()) {
                $slug = $base.'-'.$i++;
            }

            $category->slug = $slug;
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function items()
    {
        return $this->hasMany(Item::class);
    }
}
