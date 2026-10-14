<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['nis' => '1001'],
            [
                'nama' => 'Admin',
                'password' => 'password123',
                'role' => 'admin',
                'kelas' => null,
            ]
        );

        User::firstOrCreate(
            ['nis' => '1002'],
            [
                'nama' => 'Guru',
                'password' => 'password123',
                'role' => 'guru',
                'kelas' => null,
            ]
        );

        User::firstOrCreate(
            ['nis' => '1003'],
            [
                'nama' => 'Murid',
                'password' => 'password123',
                'role' => 'murid',
                'kelas' => 'X-RPL-1',
            ]
        );
    }
}
