<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan kolom role, nama, dan foto_profil ke tabel users yang sudah ada.
     * Kolom-kolom ini dibutuhkan oleh aplikasi LMS untuk membedakan peran pengguna
     * (dosen / mahasiswa) dan menyimpan data profil.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Tambahkan kolom 'nama' setelah 'id' (menggantikan fungsi kolom 'name' bawaan)
            $table->string('nama')->after('id')->nullable();
            // Tambahkan kolom 'role' untuk membedakan dosen dan mahasiswa
            $table->enum('role', ['dosen', 'mahasiswa'])->after('email')->default('mahasiswa');
            // Tambahkan kolom foto profil (opsional)
            $table->string('foto_profil')->nullable()->after('role');
        });
    }

    /**
     * Batalkan penambahan kolom (rollback).
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['nama', 'role', 'foto_profil']);
        });
    }
};
