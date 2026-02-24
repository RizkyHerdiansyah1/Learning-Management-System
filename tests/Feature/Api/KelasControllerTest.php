<?php

namespace Tests\Feature\Api;

use App\Models\Enrollment;
use App\Models\Kelas;
use App\Models\Progress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * WHITEBOX TESTING - KelasController
 * 
 * Menguji internal logic dari setiap method:
 * - index(): query semua kelas + cek enrollment status
 * - show(): detail kelas + hitung progress per materi
 * - enroll(): cek duplikat + cek role dosen
 * - kelasSaya(): filter kelas enrolled + hitung progress
 */
class KelasControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createUserAndLogin($role = 'mahasiswa')
    {
        $user = User::factory()->create(['role' => $role]);
        return $user;
    }

    // =========================================
    // TEST INDEX (LIST SEMUA KELAS)
    // =========================================

    /**
     * TC-KELAS-01: List kelas berhasil
     * Path: index() → query semua kelas + cek enrolledIds → return list
     */
    public function test_list_semua_kelas_berhasil()
    {
        $user = $this->createUserAndLogin();
        $dosen = User::factory()->create(['role' => 'dosen']);
        Kelas::factory()->count(3)->create(['dosen_id' => $dosen->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/kelas');

        $response->assertStatus(200)
            ->assertJson(['status' => 'success'])
            ->assertJsonCount(3, 'data');
    }

    /**
     * TC-KELAS-02: List kelas menunjukkan status enrolled
     * Path: index() → enrolledIds berisi kelas_id → sudah_enroll = true
     */
    public function test_list_kelas_menunjukkan_status_enrolled()
    {
        $user = $this->createUserAndLogin();
        $dosen = User::factory()->create(['role' => 'dosen']);
        $kelas = Kelas::factory()->create(['dosen_id' => $dosen->id]);

        Enrollment::create([
            'user_id' => $user->id,
            'kelas_id' => $kelas->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/kelas');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertTrue($data[0]['sudah_enroll']);
    }

    /**
     * TC-KELAS-03: Akses tanpa token → 401
     */
    public function test_list_kelas_tanpa_auth()
    {
        $response = $this->getJson('/api/kelas');
        $response->assertStatus(401);
    }

    // =========================================
    // TEST SHOW (DETAIL KELAS)
    // =========================================

    /**
     * TC-KELAS-04: Detail kelas berhasil + data materi
     * Path: show() → findOrFail → cek enrollment → hitung progress
     */
    public function test_detail_kelas_berhasil()
    {
        $user = $this->createUserAndLogin();
        $dosen = User::factory()->create(['role' => 'dosen']);
        $kelas = Kelas::factory()->create(['dosen_id' => $dosen->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/kelas/{$kelas->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['id', 'nama_kelas', 'deskripsi', 'dosen', 'sudah_enroll', 'total_materi', 'materi'],
            ]);
    }

    /**
     * TC-KELAS-05: Detail kelas tidak ditemukan → 404
     * Path: show() → findOrFail(999) → ModelNotFoundException
     */
    public function test_detail_kelas_tidak_ditemukan()
    {
        $user = $this->createUserAndLogin();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/kelas/99999');

        $response->assertStatus(404);
    }

    // =========================================
    // TEST ENROLL
    // =========================================

    /**
     * TC-KELAS-06: Enroll berhasil (mahasiswa)
     * Path: enroll() → cek existing = null → cek isDosen = false → create
     */
    public function test_enroll_kelas_berhasil()
    {
        $user = $this->createUserAndLogin('mahasiswa');
        $dosen = User::factory()->create(['role' => 'dosen']);
        $kelas = Kelas::factory()->create(['dosen_id' => $dosen->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/kelas/{$kelas->id}/enroll");

        $response->assertStatus(200)
            ->assertJson(['status' => 'success']);

        // Verifikasi enrollment tersimpan
        $this->assertDatabaseHas('enrollments', [
            'user_id' => $user->id,
            'kelas_id' => $kelas->id,
        ]);
    }

    /**
     * TC-KELAS-07: Enroll gagal - sudah terdaftar (duplikat)
     * Path: enroll() → existing != null → return 409
     */
    public function test_enroll_gagal_sudah_terdaftar()
    {
        $user = $this->createUserAndLogin('mahasiswa');
        $dosen = User::factory()->create(['role' => 'dosen']);
        $kelas = Kelas::factory()->create(['dosen_id' => $dosen->id]);

        Enrollment::create(['user_id' => $user->id, 'kelas_id' => $kelas->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/kelas/{$kelas->id}/enroll");

        $response->assertStatus(409)
            ->assertJson(['message' => 'Anda sudah terdaftar di kelas ini.']);
    }

    /**
     * TC-KELAS-08: Enroll gagal - dosen tidak boleh enroll
     * Path: enroll() → isDosen() = true → return 403
     */
    public function test_enroll_gagal_dosen()
    {
        $dosen = $this->createUserAndLogin('dosen');
        $kelas = Kelas::factory()->create(['dosen_id' => $dosen->id]);

        $response = $this->actingAs($dosen, 'sanctum')
            ->postJson("/api/kelas/{$kelas->id}/enroll");

        $response->assertStatus(403)
            ->assertJson(['message' => 'Dosen tidak bisa enroll kelas.']);
    }

    /**
     * TC-KELAS-09: Enroll kelas tidak ditemukan → 404
     */
    public function test_enroll_kelas_tidak_ditemukan()
    {
        $user = $this->createUserAndLogin('mahasiswa');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/kelas/99999/enroll');

        $response->assertStatus(404);
    }

    // =========================================
    // TEST KELAS SAYA
    // =========================================

    /**
     * TC-KELAS-10: List kelas saya berhasil + progress
     * Path: kelasSaya() → query enrolledIds → hitung progress per kelas
     */
    public function test_kelas_saya_berhasil()
    {
        $user = $this->createUserAndLogin('mahasiswa');
        $dosen = User::factory()->create(['role' => 'dosen']);
        $kelas = Kelas::factory()->create(['dosen_id' => $dosen->id]);

        Enrollment::create(['user_id' => $user->id, 'kelas_id' => $kelas->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/kelas-saya');

        $response->assertStatus(200)
            ->assertJson(['status' => 'success'])
            ->assertJsonCount(1, 'data');
    }

    /**
     * TC-KELAS-11: Kelas saya kosong (belum enroll)
     */
    public function test_kelas_saya_kosong()
    {
        $user = $this->createUserAndLogin('mahasiswa');

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/kelas-saya');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }
}
