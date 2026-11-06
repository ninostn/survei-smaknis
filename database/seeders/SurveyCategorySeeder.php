<?php

namespace Database\Seeders;

use App\Models\SurveyCategory;
use Illuminate\Database\Seeder;

class SurveyCategorySeeder extends Seeder
{
    /**
     * Kategori survei bawaan untuk lingkungan sekolah.
     *
     * Idempotent: kategori dicocokkan berdasarkan nama, sehingga seeder aman
     * dijalankan berulang tanpa membuat baris duplikat.
     */
    public function run(): void
    {
        $categories = [
            'Kepuasan Fasilitas Sekolah' => 'Menilai kepuasan terhadap fasilitas fisik sekolah.',
            'Evaluasi Pembelajaran' => 'Evaluasi proses pembelajaran dari siswa.',
            'Kegiatan Sekolah' => 'Kepuasan terhadap kegiatan dan ekstrakurikuler sekolah.',
            'Lingkungan Kerja Guru' => 'Menilai lingkungan kerja dan dukungan bagi guru.',
        ];

        foreach ($categories as $name => $description) {
            SurveyCategory::updateOrCreate(
                ['name' => $name],
                ['description' => trim($description)],
            );
        }
    }
}
