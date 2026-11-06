<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CreateAdminCommand extends Command
{
    /**
     * Nama command dan deskripsinya.
     *
     * @var string
     */
    protected $signature = 'app:create-admin';

    /**
     * Deskripsi command.
     *
     * @var string
     */
    protected $description = 'Membuat akun admin secara interaktif (username unik, password minimal 12 karakter)';

    /**
     * Jalankan command.
     */
    public function handle(): int
    {
        $username = trim((string) $this->ask('Username admin'));

        if ($username === '') {
            $this->error('Username wajib diisi.');

            return self::FAILURE;
        }

        if (User::where('username', $username)->exists()) {
            $this->error("Username '{$username}' sudah digunakan.");

            return self::FAILURE;
        }

        $password = (string) $this->secret('Password');
        $confirmation = (string) $this->secret('Konfirmasi password');

        if ($password !== $confirmation) {
            $this->error('Konfirmasi password tidak sama.');

            return self::FAILURE;
        }

        $user = User::create([
            'username' => $username,
            'password' => $password, // otomatis di-hash oleh cast 'hashed' di model User
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->info("Admin '{$user->username}' berhasil dibuat.");

        // ponytail: tidak ada notifikasi/pencatatan tambahan; tambah bila fitur audit log masuk scope.
        return self::SUCCESS;
    }
}
