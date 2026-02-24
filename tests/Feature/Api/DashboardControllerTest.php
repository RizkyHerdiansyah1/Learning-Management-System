<?php

namespace Tests\Feature\Api;

use App\Models\Enrollment;
use App\Models\Kelas;
use App\Models\Materi;
use App\Models\Progress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * WHITEBOX TESTING - DashboardController
 * 
 * Menguji internal logic:
 * - dashboard(): routing berdasarkan role (dosen vs mahasiswa)
 * - dosenDashboard(): hitung total kelas + total mahasiswa
 * - mahasiswaDashboard(): hitung enrolled, completed, progress per kelas
 */
class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    // =========================================
    // TEST DASHBOARD ROUTING
    // =========================================

    /**
     * TC-DASH-01: Dashboard mahasiswa berhasil
     * Path: dashboard() → role = mahasiswa → mahasiswaDashboard()
     */
    public function test_dashboard_mahasiswa_berhasil()
    {
        $mahasiswa = User::factory()->create(['role' => 'mahasiswa']);

        $response = $this->actingAs($mahasiswa, 'sanctum')
            ->getJson('/api/dashboard');

        $response->assertStatus(200)
            ->assertJson(['role' => 'mahasiswa'])
            ->assertJsonStructure([
                'data' => ['total_enrolled', 'completed_kelas', 'kelas'],
            ]);
    }

    /**
     * TC-DASH-02: Dashboard dosen berhasil
     * Path: dashboard() → role = dosen → dosenDashboard()
     */
    public function test_dashboard_dosen_berhasil()
    {
        $dosen = User::factory()->create(['role' => 'dosen']);

        $response = $this->actingAs($dosen, 'sanctum')
            ->getJson('/api/dashboard');

        $response->assertStatus(200)
            ->assertJson(['role' => 'dosen'])
            ->assertJsonStructure([
                'data' => ['total_kelas', 'total_mahasiswa', 'kelas'],
            ]);
    }

    /**
     * TC-DASH-03: Dashboard tanpa auth → 401
     */
    public function test_dashboard_tanpa_auth()
    {
        $response = $this->getJson('/api/dashboard');
        $response->assertStatus(401);
    }

    // =========================================
    // TEST DOSEN DASHBOARD
    // =========================================

    /**
     * TC-DASH-04: Dosen dashboard menghitung total kelas benar
     * Path: dosenDashboard() → Kelas::where(dosen_id) → count
     */
    public function test_dosen_dashboard_hitung_kelas()
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        Kelas::factory()->count(3)->create(['dosen_id' => $dosen->id]);

        $response = $this->actingAs($dosen, 'sanctum')
            ->getJson('/api/dashboard');

        $response->assertStatus(200)
            ->assertJsonPath('data.total_kelas', 3);
    }

    /**
     * TC-DASH-05: Dosen dashboard menghitung total mahasiswa enrolled
     * Path: dosenDashboard() → count unique user_id in enrollments
     */
    public function test_dosen_dashboard_hitung_mahasiswa()
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $kelas = Kelas::factory()->create(['dosen_id' => $dosen->id]);

        // 2 mahasiswa enroll
        $mhs1 = User::factory()->create(['role' => 'mahasiswa']);
        $mhs2 = User::factory()->create(['role' => 'mahasiswa']);
        Enrollment::create(['user_id' => $mhs1->id, 'kelas_id' => $kelas->id]);
        Enrollment::create(['user_id' => $mhs2->id, 'kelas_id' => $kelas->id]);

        $response = $this->actingAs($dosen, 'sanctum')
            ->getJson('/api/dashboard');

        $response->assertStatus(200)
            ->assertJsonPath('data.total_mahasiswa', 2);
    }

    // =========================================
    // TEST MAHASISWA DASHBOARD
    // =========================================

    /**
     * TC-DASH-06: Mahasiswa dashboard menghitung enrolled kelas benar
     */
    public function test_mahasiswa_dashboard_hitung_enrolled()
    {
        $mahasiswa = User::factory()->create(['role' => 'mahasiswa']);
        $dosen = User::factory()->create(['role' => 'dosen']);
        $kelas1 = Kelas::factory()->create(['dosen_id' => $dosen->id]);
        $kelas2 = Kelas::factory()->create(['dosen_id' => $dosen->id]);

        Enrollment::create(['user_id' => $mahasiswa->id, 'kelas_id' => $kelas1->id]);
        Enrollment::create(['user_id' => $mahasiswa->id, 'kelas_id' => $kelas2->id]);

        $response = $this->actingAs($mahasiswa, 'sanctum')
            ->getJson('/api/dashboard');

        $response->assertStatus(200)
            ->assertJsonPath('data.total_enrolled', 2);
    }

    /**
     * TC-DASH-07: Mahasiswa dashboard menghitung progress per kelas
     * Path: mahasiswaDashboard() → per kelas hitung done/totalMateri → persen
     */
    public function test_mahasiswa_dashboard_hitung_progress()
    {
        $mahasiswa = User::factory()->create(['role' => 'mahasiswa']);
        $dosen = User::factory()->create(['role' => 'dosen']);
        $kelas = Kelas::factory()->create(['dosen_id' => $dosen->id]);

        // 2 materi, 1 selesai
        $materi1 = Materi::factory()->create(['kelas_id' => $kelas->id, 'urutan' => 1]);
        $materi2 = Materi::factory()->create(['kelas_id' => $kelas->id, 'urutan' => 2]);

        Enrollment::create(['user_id' => $mahasiswa->id, 'kelas_id' => $kelas->id]);
        Progress::create([
            'user_id' => $mahasiswa->id,
            'materi_id' => $materi1->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($mahasiswa, 'sanctum')
            ->getJson('/api/dashboard');

        $response->assertStatus(200);
        $kelasData = $response->json('data.kelas.0');
        $this->assertEquals(1, $kelasData['completed_materi']);
        $this->assertEquals(50, $kelasData['progress_persen']);
    }

    /**
     * TC-DASH-08: Mahasiswa tanpa kelas enrolled → data kosong
     */
    public function test_mahasiswa_dashboard_kosong()
    {
        $mahasiswa = User::factory()->create(['role' => 'mahasiswa']);

        $response = $this->actingAs($mahasiswa, 'sanctum')
            ->getJson('/api/dashboard');

        $response->assertStatus(200)
            ->assertJsonPath('data.total_enrolled', 0)
            ->assertJsonPath('data.completed_kelas', 0);
    }
}
