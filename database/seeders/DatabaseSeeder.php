<?php

namespace Database\Seeders;

use App\Models\SurveyCategory;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Hanya memanggil seeder data awal yang idempotent. Akun admin dibuat
     * terpisah lewat `php artisan app:create-admin` dan tidak boleh dibuat
     * otomatis di sini.
     */
    public function run(): void
    {
        $this->call([
            SurveyCategorySeeder::class,
        ]);
    }
}
