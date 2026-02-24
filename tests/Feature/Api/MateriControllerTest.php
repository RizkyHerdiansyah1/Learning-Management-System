<?php

namespace Tests\Feature\Api;

use App\Models\Enrollment;
use App\Models\HasilKuis;
use App\Models\Kelas;
use App\Models\Kuis;
use App\Models\Materi;
use App\Models\Progress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * WHITEBOX TESTING - MateriController
 * 
 * Menguji internal logic dari setiap method:
 * - show(): cek enrollment, cek sequential learning, return konten sesuai tipe
 * - complete(): buat progress + tandai selesai
 * - submitQuiz(): validasi jawaban, hitung nilai, auto-grade, cek attempts
 */
class MateriControllerTest extends TestCase
{
    use RefreshDatabase;

    private function setupKelasMateri()
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $mahasiswa = User::factory()->create(['role' => 'mahasiswa']);
        $kelas = Kelas::factory()->create(['dosen_id' => $dosen->id]);

        Enrollment::create([
            'user_id' => $mahasiswa->id,
            'kelas_id' => $kelas->id,
        ]);

        return [$mahasiswa, $kelas, $dosen];
    }

    // =========================================
    // TEST SHOW MATERI
    // =========================================

    /**
     * TC-MATERI-01: Lihat materi berhasil (tipe text)
     * Path: show() → enrollment OK → sequential OK → return data text
     */
    public function test_lihat_materi_text_berhasil()
    {
        [$mahasiswa, $kelas] = $this->setupKelasMateri();
        $materi = Materi::factory()->create([
            'kelas_id' => $kelas->id,
            'tipe' => 'text',
            'urutan' => 1,
            'konten' => 'Ini adalah konten text pembelajaran.',
        ]);

        $response = $this->actingAs($mahasiswa, 'sanctum')
            ->getJson("/api/materi/{$materi->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.tipe', 'text')
            ->assertJsonPath('data.konten', 'Ini adalah konten text pembelajaran.');
    }

    /**
     * TC-MATERI-02: Lihat materi gagal - belum enroll
     * Path: show() → enrollment check GAGAL → return 403
     */
    public function test_lihat_materi_gagal_belum_enroll()
    {
        $mahasiswa = User::factory()->create(['role' => 'mahasiswa']);
        $dosen = User::factory()->create(['role' => 'dosen']);
        $kelas = Kelas::factory()->create(['dosen_id' => $dosen->id]);
        $materi = Materi::factory()->create([
            'kelas_id' => $kelas->id,
            'urutan' => 1,
        ]);

        $response = $this->actingAs($mahasiswa, 'sanctum')
            ->getJson("/api/materi/{$materi->id}");

        $response->assertStatus(403);
    }

    /**
     * TC-MATERI-03: Lihat materi gagal - materi sebelumnya belum selesai (sequential)
     * Path: show() → urutan > 1 → prevProgress = false → return 403
     */
    public function test_lihat_materi_gagal_sequential_lock()
    {
        [$mahasiswa, $kelas] = $this->setupKelasMateri();

        $materi1 = Materi::factory()->create([
            'kelas_id' => $kelas->id,
            'urutan' => 1,
        ]);
        $materi2 = Materi::factory()->create([
            'kelas_id' => $kelas->id,
            'urutan' => 2,
        ]);

        // Materi 1 belum selesai, coba akses materi 2
        $response = $this->actingAs($mahasiswa, 'sanctum')
            ->getJson("/api/materi/{$materi2->id}");

        $response->assertStatus(403);
    }

    /**
     * TC-MATERI-04: Lihat materi urutan 2 berhasil setelah materi 1 selesai
     * Path: show() → prevProgress exists & completed → return data
     */
    public function test_lihat_materi_sequential_unlock_berhasil()
    {
        [$mahasiswa, $kelas] = $this->setupKelasMateri();

        $materi1 = Materi::factory()->create([
            'kelas_id' => $kelas->id,
            'urutan' => 1,
        ]);
        $materi2 = Materi::factory()->create([
            'kelas_id' => $kelas->id,
            'tipe' => 'text',
            'urutan' => 2,
        ]);

        // Selesaikan materi 1
        Progress::create([
            'user_id' => $mahasiswa->id,
            'materi_id' => $materi1->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($mahasiswa, 'sanctum')
            ->getJson("/api/materi/{$materi2->id}");

        $response->assertStatus(200);
    }

    /**
     * TC-MATERI-05: Materi tidak ditemukan → 404
     */
    public function test_lihat_materi_tidak_ditemukan()
    {
        $mahasiswa = User::factory()->create(['role' => 'mahasiswa']);

        $response = $this->actingAs($mahasiswa, 'sanctum')
            ->getJson('/api/materi/99999');

        $response->assertStatus(404);
    }

    // =========================================
    // TEST COMPLETE MATERI
    // =========================================

    /**
     * TC-MATERI-06: Complete materi berhasil
     * Path: complete() → firstOrCreate progress → update status=completed
     */
    public function test_complete_materi_berhasil()
    {
        [$mahasiswa, $kelas] = $this->setupKelasMateri();
        $materi = Materi::factory()->create([
            'kelas_id' => $kelas->id,
            'tipe' => 'text',
            'urutan' => 1,
        ]);

        $response = $this->actingAs($mahasiswa, 'sanctum')
            ->postJson("/api/materi/{$materi->id}/complete");

        $response->assertStatus(200)
            ->assertJson(['status' => 'success']);

        // Verifikasi progress tersimpan
        $this->assertDatabaseHas('progress', [
            'user_id' => $mahasiswa->id,
            'materi_id' => $materi->id,
            'status' => 'completed',
        ]);
    }

    // =========================================
    // TEST SUBMIT QUIZ
    // =========================================

    /**
     * TC-MATERI-07: Submit quiz berhasil - lulus (nilai >= 70)
     * Path: submitQuiz() → hitung jawaban benar → nilai >= 70 → lulus
     */
    public function test_submit_quiz_lulus()
    {
        [$mahasiswa, $kelas] = $this->setupKelasMateri();
        $materi = Materi::factory()->create([
            'kelas_id' => $kelas->id,
            'tipe' => 'quiz',
            'urutan' => 1,
        ]);

        // Buat 2 soal
        $soal1 = Kuis::factory()->create([
            'materi_id' => $materi->id,
            'jawaban_benar' => 'a',
        ]);
        $soal2 = Kuis::factory()->create([
            'materi_id' => $materi->id,
            'jawaban_benar' => 'b',
        ]);

        $response = $this->actingAs($mahasiswa, 'sanctum')
            ->postJson("/api/materi/{$materi->id}/submit-quiz", [
                'jawaban' => [
                    $soal1->id => 'a',  // benar
                    $soal2->id => 'b',  // benar
                ],
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('lulus', true)
            ->assertJsonPath('nilai', 100);
    }

    /**
     * TC-MATERI-08: Submit quiz gagal - tidak lulus (nilai < 70)
     * Path: submitQuiz() → hitung jawaban → nilai < 70 → lulus = false
     */
    public function test_submit_quiz_tidak_lulus()
    {
        [$mahasiswa, $kelas] = $this->setupKelasMateri();
        $materi = Materi::factory()->create([
            'kelas_id' => $kelas->id,
            'tipe' => 'quiz',
            'urutan' => 1,
        ]);

        // Buat 5 soal
        $soalIds = [];
        for ($i = 0; $i < 5; $i++) {
            $soal = Kuis::factory()->create([
                'materi_id' => $materi->id,
                'jawaban_benar' => 'a',
            ]);
            $soalIds[] = $soal->id;
        }

        // Jawab hanya 1 benar dari 5
        $jawaban = [];
        $jawaban[$soalIds[0]] = 'a';  // benar
        $jawaban[$soalIds[1]] = 'b';  // salah
        $jawaban[$soalIds[2]] = 'b';  // salah
        $jawaban[$soalIds[3]] = 'b';  // salah
        $jawaban[$soalIds[4]] = 'b';  // salah

        $response = $this->actingAs($mahasiswa, 'sanctum')
            ->postJson("/api/materi/{$materi->id}/submit-quiz", [
                'jawaban' => $jawaban,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('lulus', false);
    }

    /**
     * TC-MATERI-09: Submit quiz gagal - sudah lulus sebelumnya
     * Path: submitQuiz() → alreadyPassed = true → return 409
     */
    public function test_submit_quiz_gagal_sudah_lulus()
    {
        [$mahasiswa, $kelas] = $this->setupKelasMateri();
        $materi = Materi::factory()->create([
            'kelas_id' => $kelas->id,
            'tipe' => 'quiz',
            'urutan' => 1,
        ]);

        // Tandai sudah lulus
        Progress::create([
            'user_id' => $mahasiswa->id,
            'materi_id' => $materi->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($mahasiswa, 'sanctum')
            ->postJson("/api/materi/{$materi->id}/submit-quiz", [
                'jawaban' => [],
            ]);

        $response->assertStatus(409);
    }

    /**
     * TC-MATERI-10: Submit quiz gagal - percobaan habis (max 3)
     * Path: submitQuiz() → attempts >= 3 → return 429
     */
    public function test_submit_quiz_gagal_percobaan_habis()
    {
        [$mahasiswa, $kelas] = $this->setupKelasMateri();
        $materi = Materi::factory()->create([
            'kelas_id' => $kelas->id,
            'tipe' => 'quiz',
            'urutan' => 1,
        ]);

        // Buat 3 hasil kuis (percobaan habis)
        for ($i = 0; $i < 3; $i++) {
            HasilKuis::create([
                'user_id' => $mahasiswa->id,
                'materi_id' => $materi->id,
                'nilai' => 50,
                'lulus' => false,
            ]);
        }

        $response = $this->actingAs($mahasiswa, 'sanctum')
            ->postJson("/api/materi/{$materi->id}/submit-quiz", [
                'jawaban' => [],
            ]);

        $response->assertStatus(429);
    }
}
