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

    /** Catat di session HP pelanggan bahwa ia baru saja scan QR meja ini. */
    public static function rememberInSession(self $table): void
    {
        session([
            'table_id' => $table->id,
            'table_number' => $table->number,
            'table_scanned_at' => time(),
        ]);
    }

    /**
     * Meja milik HP ini, atau null kalau belum scan, masa berlakunya habis,
     * atau mejanya sudah dihapus / dinonaktifkan admin.
     * Data session yang sudah tidak berlaku dibersihkan di sini.
     */
    public static function fromSession(): ?self
    {
        $id = session('table_id');
        $scannedAt = (int) session('table_scanned_at', 0);
        $maxAge = max(1, (int) config('orenjoy.table_session_hours', 3)) * 3600;

        $table = null;

        if ($id && $scannedAt > 0 && (time() - $scannedAt) < $maxAge) {
            $table = static::where('id', $id)->where('is_active', true)->first();
        }

        if (! $table) {
            session()->forget(['table_id', 'table_number', 'table_scanned_at']);
        }

        return $table;
    }
}
