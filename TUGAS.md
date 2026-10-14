# Survei SMAKNIS — Pembagian Tugas (6 Orang)

Kerangka dulu, tanpa UI. View cukup HTML polos: tabel + form + tombol + pesan. Tanpa Tailwind, tanpa CSS baru, tanpa ubah layout. UI rapih belakangan oleh ketua.

Yang belum bisa dasar Git / Laravel / PHP, pelajari mandiri dulu (install, clone, migrate, route, controller, Blade). Instruksi di bawah hanya kontrak kerja, bukan tutorial dasar.

## 1. Kondisi Awal (sudah selesai)

- Migrasi 8/8 Ran: users, surveys, questions, options, responses, answers + cache, jobs, sessions
- 6 model + relasi, seeder 3 akun, auth NIS + role admin/guru/murid
- CRUD Survey + publish + error pages 403/404
- Commit acuan: fa521b9, branch utama: main

## 2. Branch (nama dikunci)

| Orang | Branch | Area |
|---|---|---|
| Ketua | main + lead/* | Inti + merge + UI belakangan + hosting |
| M1 | feat/m1-users | Kelola User |
| M2 | feat/m2-questions | CRUD Soal |
| M3 | feat/m3-options | CRUD Opsi |
| M4 | feat/m4-murid | Halaman murid |
| M5 | feat/m5-hasil | Halaman hasil mentah |

Aturan: kerja hanya di branch sendiri. Push langsung ke main dilarang. Merge ke main hanya ketua.

## 3. Setup Awal (sekali saja, semua orang)

```bash
git clone https://github.com/ninostn/survei-smaknis.git
cd survei-smaknis
composer install
copy .env.example .env
php artisan key:generate
```

Buat database `survei_smaknis` di phpMyAdmin. Sesuaikan `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=survei_smaknis
DB_USERNAME=root
DB_PASSWORD=
```

```bash
php artisan migrate:fresh --seed
php artisan serve
```

Cek: login 1001 (admin) / 1002 (guru) / 1003 (murid), password `password123`.

## 4. Ketua — Inti + Integrasi

- H3: transaksi isi survey (`responses + answers` satu transaksi), cek aktif/403, tolak double.
- H4: query hasil (`$counts`, `$avgLikert`, `$essays`), tanpa grafik.
- Policy pemilik, publish, review + merge, testing 3 role, hosting prep, seluruh UI belakangan.
- Mengunci kontrak route + variabel + validasi. Perubahan kontrak hanya oleh ketua.

## 5. M1 — Kelola User

- Branch: `feat/m1-users`
- Route: `/admin/users`, middleware `role:admin`
- File: `UserController` (index, create, store, edit, update, destroy, reset), `views/users/*`
- Validasi: nama required, nis required + unique, role admin/guru/murid, kelas nullable, password min 8
- Dilarang: ubah migrasi, .env, file orang lain
- Selesai jika: tambah, edit, hapus, reset password jalan. Non-admin kena 403.

## 6. M2 — CRUD Soal

- Branch: `feat/m2-questions`
- Route: `surveys/{survey}/questions` (nested, admin/guru)
- File: `QuestionController` + `views/questions/*`
- Validasi: teks required, type pg/likert/essay, urutan integer min 0
- Butuh kolom baru: lapor ketua, jangan ubah migrasi sendiri
- Selesai jika: 1 survey bisa punya 3 jenis soal, edit/hapus/urutan jalan.

## 7. M3 — CRUD Opsi

- Branch: `feat/m3-options`
- Route: `questions/{question}/options` (nested, admin/guru)
- File: `OptionController` + `views/options/*`
- Validasi: label required max 150, nilai nullable integer
- Aturan: soal type essay dilarang tambah opsi (tolak + pesan)
- Selesai jika: PG/Likert bisa tambah opsi + nilai, essay ditolak dengan pesan.

## 8. M4 — Halaman Murid

- Branch: `feat/m4-murid`
- File: daftar survey aktif + form isi polos (radio + textarea), riwayat isian
- Variabel dari ketua: daftar terima `$surveys`, form terima `$survey + $questions`
- Dilarang ubah query/transaksi milik ketua. Hanya Blade + form.
- Selesai jika: daftar tampil, form terkirim, pesan Sudah Mengisi dan 403 tampil.

## 9. M5 — Halaman Hasil Mentah

- Branch: `feat/m5-hasil`
- File: `views/results/show` tabel angka polos (tanpa grafik, tanpa print cantik)
- Variabel dari ketua: `$counts`, `$avgLikert`, `$essays`
- Dilarang ubah query agregat
- Selesai jika: tabel COUNT + rata-rata + 20 essay tampil dan angkanya benar.

## 10. SOP Harian

Mulai (sekali saja):

```bash
git checkout -b feat/mX-nama
git pull origin main
```

Selesai 1 halaman:

```bash
git add <file milikmu saja>
git commit -m "m1: tambah validasi NIS unik"
git push origin feat/mX-nama
```

Dilarang: `git add .`, `push --force`, push ke main, commit file orang, edit migrasi yang sudah di-push.

Konflik:

```bash
git pull origin main
```

Selesaikan tanda `<<<<<<<`, cek `php artisan migrate:status`, buka halaman, commit, lapor ketua. Mentok lebih dari 1 jam: berhenti, kirim screenshot + nama file ke ketua.

## 11. Checklist Merge (ketua, Rabu + Sabtu)

- migrate:status 8/8 Ran
- Login 1001/1002/1003 masuk dashboard masing-masing
- Halaman yang diubah terbuka tanpa error 500
- Tidak ada `dd()`, password asli, atau akun tes nyangkut
- Push main, anggota pull main

## 12. Demo (10 menit)

- M1: kelola user. M2+M3: tambah soal + opsi. M4: isi sebagai murid. M5: tampil hasil. Ketua: publish + 403 + hosting.
- Masing-masing hafalkan 1 jawaban: kenapa validasi/aturan di fiturnya ada.
