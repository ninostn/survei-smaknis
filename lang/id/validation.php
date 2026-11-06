<?php

return [
    /*
    | Terjemahan aturan validasi ke bahasa Indonesia.
    | Kunci yang belum ada di sini akan memakai fallback (en).
    */

    'accepted' => ':attribute harus diterima.',
    'boolean' => ':attribute harus bernilai benar atau salah.',
    'confirmed' => 'Konfirmasi :attribute tidak sama.',
    'date' => ':attribute bukan tanggal yang valid.',
    'email' => ':attribute harus berupa alamat email yang valid.',
    'exists' => ':attribute tidak valid.',
    'in' => ':attribute tidak valid.',
    'integer' => ':attribute harus berupa angka bulat.',
    'max' => [
        'array' => ':attribute tidak boleh lebih dari :max item.',
        'file' => ':attribute tidak boleh lebih dari :max kilobita.',
        'numeric' => ':attribute tidak boleh lebih dari :max.',
        'string' => ':attribute tidak boleh lebih dari :max karakter.',
    ],
    'min' => [
        'array' => ':attribute minimal :min item.',
        'file' => ':attribute minimal :min kilobita.',
        'numeric' => ':attribute minimal :min.',
        'string' => ':attribute minimal :min karakter.',
    ],
    'numeric' => ':attribute harus berupa angka.',
    'required' => 'Kolom :attribute wajib diisi.',
    'required_if' => 'Kolom :attribute wajib diisi ketika :other bernilai :value.',
    'required_unless' => 'Kolom :attribute wajib diisi kecuali :other bernilai :values.',
    'required_with' => 'Kolom :attribute wajib diisi ketika :values ada.',
    'required_without' => 'Kolom :attribute wajib diisi ketika :values tidak ada.',
    'same' => ':attribute harus sama dengan :other.',
    'size' => [
        'array' => ':attribute harus berisi :size item.',
        'numeric' => ':attribute harus bernilai :size.',
        'string' => ':attribute harus :size karakter.',
    ],
    'string' => ':attribute harus berupa teks.',
    'unique' => ':attribute sudah digunakan.',
    'url' => ':attribute harus berupa URL yang valid.',

    'attributes' => [
        'class_id' => 'kelas',
        'is_active' => 'status aktif',
        'name' => 'nama',
        'nip' => 'NIP',
        'nis' => 'NIS',
        'password' => 'password',
        'username' => 'username',
    ],
];
