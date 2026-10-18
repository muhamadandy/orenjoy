<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DiningTable extends Model
{
    protected $table = 'tables';

    protected $fillable = ['number', 'token', 'is_active'];

    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (DiningTable $table) {
            if (empty($table->token)) {
                $table->token = static::newToken();
            }
        });
    }

    /** Token acak 24 karakter (huruf kecil + angka), dijamin belum dipakai. */
    public static function newToken(): string
    {
        do {
            $token = Str::lower(Str::random(24));
        } while (static::where('token', $token)->exists());

        return $token;
    }

    /** Ganti token (dipakai kalau QR lama bocor atau perlu dicabut). */
    public function regenerateToken(): void
    {
        $this->update(['token' => static::newToken()]);
    }

    /** Alamat yang dibuka saat QR di-scan. */
    public function scanUrl(): string
    {
        return route('table', $this->token);
    }
}
