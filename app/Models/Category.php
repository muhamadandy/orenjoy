<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes;

    protected $fillable = ['cat_name', 'description'];

    public function items()
    {
        return $this->hasMany(Item::class);
    }
}