<?php

namespace App\Support;

class IdMessages
{
    /**
     * Pesan validasi bahasa Indonesia yang berlaku untuk semua kolom.
     * Dipakai sebagai argumen ke-2 di $request->validate().
     */
    public static function rules(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'string' => ':attribute harus berupa teks.',
            'integer' => ':attribute harus berupa angka bulat.',
            'unique' => ':attribute sudah dipakai.',
            'exists' => ':attribute yang dipilih tidak valid.',
            'image' => ':attribute harus berupa gambar.',
            'mimes' => 'Format :attribute harus :values.',
            'uploaded' => 'Unggah :attribute gagal. Ukuran file mungkin melebihi batas server.',
            'max.string' => ':attribute maksimal :max karakter.',
            'max.numeric' => ':attribute maksimal :max.',
            'max.file' => 'Ukuran :attribute maksimal :max KB.',
            'min.numeric' => ':attribute minimal :min.',
        ];
    }
}