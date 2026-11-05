# Project Overview

## Nama
Survei SMAKNIS

## Tujuan
Membangun aplikasi survei berbasis web untuk lingkungan internal SMK Nurul Islam Affandiyah.

Aplikasi digunakan untuk mengumpulkan pendapat, evaluasi, kepuasan, dan aspirasi warga sekolah.

## Pengguna
1. Admin
2. Guru
3. Siswa

## Hak Pengguna

### Admin
- Mengelola akun guru dan siswa.
- Mengelola data kelas, mata pelajaran, dan teaching assignments.
- Mengelola seluruh survei.
- Menentukan target siswa dan/atau guru pada survei.
- Melihat seluruh hasil survei sekolah.
- Tidak mengisi survei sebagai responden.

### Guru
- Membuat dan mengelola survei miliknya.
- Menentukan target guru, serta target siswa hanya untuk kelas yang diajar.
- Mengisi survei yang ditujukan kepada guru.
- Melihat hasil survei yang menjadi haknya.

### Siswa
- Melihat survei yang ditujukan kepada seluruh siswa atau kelasnya.
- Mengisi survei.
- Melihat ringkasan hasil yang dipublikasikan.

## Target Survei

- Satu survei dapat menargetkan siswa dan/atau guru.
- Target siswa: `none`, `all`, atau kelas tertentu.
- Target guru: `none`, `all`, atau guru tertentu.
- Minimal satu target harus aktif sebelum survei dipublikasikan.
- Admin bukan responden survei.

## Teknologi
- Laravel 13
- MySQL
- Blade
- Tailwind CSS
- Vite

## Batasan
Aplikasi hanya digunakan untuk ekosistem internal sekolah. Tidak mencakup orang tua, alumni, atau masyarakat umum.