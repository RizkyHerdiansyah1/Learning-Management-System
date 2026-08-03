<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan semua migrasi tabel LMS.
     * Urutan pembuatan tabel mengikuti relasi antar model:
     * users (sudah ada) -> kelas -> materi -> kuis -> enrollment -> progress -> hasil_kuis
     */
    public function up(): void
    {
        // =========================================================
        // 1. Tabel KELAS
        // Menyimpan data kelas/kursus yang dibuat oleh dosen.
        // =========================================================
        Schema::create('kelas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dosen_id')->constrained('users')->onDelete('cascade');
            $table->string('nama_kelas');
            $table->text('deskripsi')->nullable();
            $table->string('kategori')->nullable();
            $table->integer('total_courses')->default(0);
        });

        // =========================================================
        // 2. Tabel MATERI
        // Menyimpan konten pembelajaran (video, teks, kuis) dalam sebuah kelas.
        // =========================================================
        Schema::create('materi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelas_id')->constrained('kelas')->onDelete('cascade');
            $table->string('judul');
            $table->enum('tipe', ['video', 'text', 'quiz']);
            $table->longText('konten')->nullable();
            $table->integer('urutan')->default(0);
            $table->integer('durasi_menit')->nullable();
        });

        // =========================================================
        // 3. Tabel KUIS
        // Menyimpan soal-soal pilihan ganda untuk materi bertipe 'quiz'.
        // =========================================================
        Schema::create('kuis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('materi_id')->constrained('materi')->onDelete('cascade');
            $table->text('pertanyaan');
            $table->string('pilihan_a');
            $table->string('pilihan_b');
            $table->string('pilihan_c');
            $table->string('pilihan_d');
            $table->char('jawaban_benar', 1); // A, B, C, atau D
            $table->integer('poin')->default(10);
        });

        // =========================================================
        // 4. Tabel ENROLLMENT
        // Menyimpan data pendaftaran mahasiswa ke dalam sebuah kelas.
        // =========================================================
        Schema::create('enrollment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('kelas_id')->constrained('kelas')->onDelete('cascade');
            $table->unique(['user_id', 'kelas_id']); // Satu mahasiswa hanya bisa daftar sekali per kelas
        });

        // =========================================================
        // 5. Tabel PROGRESS
        // Melacak status penyelesaian setiap materi oleh setiap mahasiswa.
        // =========================================================
        Schema::create('progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('materi_id')->constrained('materi')->onDelete('cascade');
            $table->enum('status', ['locked', 'in_progress', 'completed'])->default('locked');
            $table->timestamp('tanggal_mulai')->nullable();
            $table->timestamp('tanggal_selesai')->nullable();
            $table->unique(['user_id', 'materi_id']); // Satu record progress per mahasiswa per materi
        });

        // =========================================================
        // 6. Tabel HASIL_KUIS
        // Menyimpan hasil pengerjaan kuis oleh setiap mahasiswa.
        // =========================================================
        Schema::create('hasil_kuis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('materi_id')->constrained('materi')->onDelete('cascade');
            $table->integer('skor')->default(0);
            $table->integer('total_soal')->default(0);
            $table->json('jawaban_detail')->nullable(); // Menyimpan detail jawaban dalam format JSON
        });
    }

    /**
     * Batalkan semua migrasi (rollback).
     * Urutan penghapusan HARUS kebalikan dari urutan pembuatan
     * agar tidak ada error foreign key constraint.
     */
    public function down(): void
    {
        Schema::dropIfExists('hasil_kuis');
        Schema::dropIfExists('progress');
        Schema::dropIfExists('enrollment');
        Schema::dropIfExists('kuis');
        Schema::dropIfExists('materi');
        Schema::dropIfExists('kelas');
    }
};
