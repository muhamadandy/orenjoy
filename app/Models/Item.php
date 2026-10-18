<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Item extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'description', 'price',
        'category_id', 'img', 'is_active',
    ];

    protected static function booted(): void
    {
        static::creating(function (Item $item) {
            if (! empty($item->slug)) {
                return;
            }

            $base = Str::slug($item->name);
            $slug = $base;
            $i = 2;

            while (static::withTrashed()->where('slug', $slug)->exists()) {
                $slug = $base.'-'.$i++;
            }

            $item->slug = $slug;
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * URL foto menu. Kolom `img` bisa berisi URL penuh atau path di storage/app/public.
     * Jika kosong, dipakai gambar placeholder.
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(function (): string {
            if (! $this->img) {
                return asset('assets/customer/img/placeholder.svg');
            }

            return Str::startsWith($this->img, ['http://', 'https://'])
                ? $this->img
                : asset('storage/'.ltrim($this->img, '/'));
        });
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}