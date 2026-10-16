<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['username' => 'admin', 'fullname' => 'Administrator', 'email' => 'admin@orenjoy.test', 'phone' => '081234567890', 'role' => 'admin'],
            ['username' => 'kasir', 'fullname' => 'Kasir Utama', 'email' => 'kasir@orenjoy.test', 'phone' => '081234567891', 'role' => 'cashier'],
            ['username' => 'koki', 'fullname' => 'Koki Utama', 'email' => 'koki@orenjoy.test', 'phone' => '081234567892', 'role' => 'chef'],
        ];

        foreach ($users as $user) {
            User::firstOrCreate(
                ['username' => $user['username']],
                [
                    'fullname' => $user['fullname'],
                    'email' => $user['email'],
                    'phone' => $user['phone'],
                    'password' => 'password',
                    'role_id' => Role::where('role_name', $user['role'])->value('id'),
                ]
            );
        }
    }
}
