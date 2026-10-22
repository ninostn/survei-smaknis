<?php

namespace Database\Seeders;

use App\Models\Survey;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Data contoh untuk menguji alur isi survey + halaman hasil
 * sebelum CRUD soal (M2/M3) selesai.
 *
 * Jalankan terpisah, tidak otomatis lewat DatabaseSeeder:
 *   php artisan db:seed --class=DemoSurveySeeder
 */
class DemoSurveySeeder extends Seeder
{
    public function run(): void
    {
        $guru = User::where('role', 'guru')->first();

        if (! $guru) {
            $this->command->warn('Belum ada user guru. Jalankan DatabaseSeeder dulu.');

            return;
        }

        $survey = Survey::firstOrCreate(
            ['judul' => 'Survei Kepuasan Siswa (Contoh)'],
            [
                'deskripsi' => 'Survey contoh untuk uji coba: 2 soal Likert, 1 pilihan ganda, 1 esai.',
                'status' => 'aktif',
                'created_by' => $guru->id,
            ]
        );

        if ($survey->questions()->exists()) {
            $this->command->info('Survey contoh sudah punya soal, seeder dilewati.');

            return;
        }

        $kebersihan = $survey->questions()->create([
            'teks' => 'Bagaimana kebersihan kelas?',
            'type' => 'likert',
            'urutan' => 1,
        ]);

        foreach ([
            'Sangat buruk' => 1,
            'Buruk' => 2,
            'Cukup' => 3,
            'Baik' => 4,
            'Sangat baik' => 5,
        ] as $label => $nilai) {
            $kebersihan->options()->create(['label' => $label, 'nilai' => $nilai]);
        }

        $kantin = $survey->questions()->create([
            'teks' => 'Bagaimana kualitas makanan di kantin?',
            'type' => 'likert',
            'urutan' => 2,
        ]);

        foreach ([
            'Sangat buruk' => 1,
            'Buruk' => 2,
            'Cukup' => 3,
            'Baik' => 4,
            'Sangat baik' => 5,
        ] as $label => $nilai) {
            $kantin->options()->create(['label' => $label, 'nilai' => $nilai]);
        }

        $ekskul = $survey->questions()->create([
            'teks' => 'Ekstrakurikuler mana yang paling kamu minati?',
            'type' => 'pg',
            'urutan' => 3,
        ]);

        foreach ([
            'Pramuka' => 1,
            'Basket' => 2,
            'Seni musik' => 3,
        ] as $label => $nilai) {
            $ekskul->options()->create(['label' => $label, 'nilai' => $nilai]);
        }

        $survey->questions()->create([
            'teks' => 'Kritik dan saran untuk sekolah',
            'type' => 'essay',
            'urutan' => 4,
        ]);

        $this->command->info('Survey contoh dibuat: id=' . $survey->id . ', 4 soal.');
    }
}
