<?php

namespace App\Support;

/**
 * Aturan alur status pesanan dan siapa yang boleh mengubahnya.
 * Semua aturan ada di sini supaya mudah dicek dan diubah di satu tempat.
 *
 * Alur: pending -> settlement -> cooked -> completed (cancelled dari pending/settlement)
 */
final class OrderFlow
{
    public const STATUSES = ['pending', 'settlement', 'cooked', 'completed', 'cancelled'];

    /** Status yang masih perlu ditangani staf. */
    public const ACTIVE = ['pending', 'settlement', 'cooked'];

    /** Dari status => ke status => role yang boleh. Admin selalu boleh. */
    private const TRANSITIONS = [
        'pending' => [
            'settlement' => ['cashier'],
            'cancelled' => ['cashier'],
        ],
        'settlement' => [
            'cooked' => ['chef'],
            'cancelled' => ['cashier'],
        ],
        'cooked' => [
            'completed' => ['cashier'],
        ],
    ];

    /** Label dan warna badge tiap status. */
    private const LABELS = [
        'pending' => ['Menunggu bayar', 'bg-warning text-dark'],
        'settlement' => ['Diproses dapur', 'bg-info'],
        'cooked' => ['Siap saji', 'bg-primary'],
        'completed' => ['Selesai', 'bg-success'],
        'cancelled' => ['Dibatalkan', 'bg-secondary'],
    ];

    /** Teks tombol aksi menuju status tertentu: [label, kelas tombol, perlu konfirmasi]. */
    private const ACTIONS = [
        'settlement' => ['Terima pembayaran', 'btn-success', false],
        'cooked' => ['Selesai dimasak', 'btn-primary', false],
        'completed' => ['Sudah diantar', 'btn-success', false],
        'cancelled' => ['Batalkan', 'btn-outline-danger', true],
    ];

    public static function label(string $status): string
    {
        return self::LABELS[$status][0] ?? ucfirst($status);
    }

    public static function badge(string $status): string
    {
        return self::LABELS[$status][1] ?? 'bg-secondary';
    }

    /** @return array{0:string,1:string,2:bool} */
    public static function action(string $target): array
    {
        return self::ACTIONS[$target] ?? [ucfirst($target), 'btn-secondary', false];
    }

    /** Boleh tidak role ini mengubah status $from menjadi $to? */
    public static function can(string $from, string $to, ?string $role): bool
    {
        $roles = self::TRANSITIONS[$from][$to] ?? null;

        if ($roles === null || $role === null) {
            return false;
        }

        return $role === 'admin' || in_array($role, $roles, true);
    }

    /** Daftar status tujuan yang boleh dipilih role ini dari status $from. */
    public static function targets(string $from, ?string $role): array
    {
        return array_values(array_filter(
            array_keys(self::TRANSITIONS[$from] ?? []),
            fn (string $to) => self::can($from, $to, $role)
        ));
    }

    /** Status pesanan yang boleh dilihat role ini. Koki hanya melihat pesanan yang sudah dibayar. */
    public static function visibleStatuses(?string $role): array
    {
        return $role === 'chef' ? ['settlement', 'cooked'] : self::STATUSES;
    }
}