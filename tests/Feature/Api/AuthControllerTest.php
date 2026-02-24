<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * WHITEBOX TESTING - AuthController
 * 
 * Menguji internal logic dari setiap method:
 * - login(): validasi input, cek password hash, generate token
 * - register(): validasi unik email, hash password, buat user baru
 * - logout(): hapus token aktif
 * - me(): return data user yang sedang login
 */
class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    // =========================================
    // TEST LOGIN
    // =========================================

    /**
     * TC-AUTH-01: Login berhasil dengan kredensial yang benar
     * Path: login() → validasi OK → user ditemukan → Hash::check OK → token dibuat
     */
    public function test_login_berhasil_dengan_kredensial_benar()
    {
        $user = User::factory()->create([
            'email' => 'mahasiswa@test.com',
            'password' => Hash::make('password123'),
            'role' => 'mahasiswa',
            'nama' => 'Test User',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'mahasiswa@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Login berhasil',
            ])
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'nama', 'email', 'role'],
            ]);
    }

    /**
     * TC-AUTH-02: Login gagal - email salah
     * Path: login() → user = null → return 401
     */
    public function test_login_gagal_email_salah()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'tidak.ada@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'status' => 'error',
                'message' => 'Email atau password salah.',
            ]);
    }

    /**
     * TC-AUTH-03: Login gagal - password salah
     * Path: login() → user ditemukan → Hash::check GAGAL → return 401
     */
    public function test_login_gagal_password_salah()
    {
        User::factory()->create([
            'email' => 'user@test.com',
            'password' => Hash::make('benar123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'user@test.com',
            'password' => 'salah999',
        ]);

        $response->assertStatus(401)
            ->assertJson(['status' => 'error']);
    }

    /**
     * TC-AUTH-04: Login gagal - validasi kosong
     * Path: login() → validate() GAGAL → ValidationException (422)
     */
    public function test_login_gagal_validasi_kosong()
    {
        $response = $this->postJson('/api/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    /**
     * TC-AUTH-05: Login gagal - format email tidak valid
     * Path: login() → validate('email') GAGAL
     */
    public function test_login_gagal_email_format_salah()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'bukan-email',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    // =========================================
    // TEST REGISTER
    // =========================================

    /**
     * TC-AUTH-06: Register berhasil
     * Path: register() → validasi OK → User::create → Hash::make → token
     */
    public function test_register_berhasil()
    {
        $response = $this->postJson('/api/register', [
            'nama' => 'Mahasiswa Baru',
            'email' => 'baru@test.com',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'role' => 'mahasiswa',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Registrasi berhasil',
            ])
            ->assertJsonStructure(['token', 'user']);

        // Verifikasi user tersimpan di database
        $this->assertDatabaseHas('users', [
            'email' => 'baru@test.com',
            'nama' => 'Mahasiswa Baru',
            'role' => 'mahasiswa',
        ]);
    }

    /**
     * TC-AUTH-07: Register gagal - email sudah terdaftar
     * Path: register() → validate('unique:users,email') GAGAL
     */
    public function test_register_gagal_email_duplikat()
    {
        User::factory()->create(['email' => 'ada@test.com']);

        $response = $this->postJson('/api/register', [
            'nama' => 'User Baru',
            'email' => 'ada@test.com',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'role' => 'mahasiswa',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * TC-AUTH-08: Register gagal - password tidak cocok
     * Path: register() → validate('confirmed') GAGAL
     */
    public function test_register_gagal_password_tidak_cocok()
    {
        $response = $this->postJson('/api/register', [
            'nama' => 'User Baru',
            'email' => 'baru@test.com',
            'password' => 'rahasia123',
            'password_confirmation' => 'berbeda456',
            'role' => 'mahasiswa',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /**
     * TC-AUTH-09: Register gagal - role tidak valid
     * Path: register() → validate('in:mahasiswa,dosen') GAGAL
     */
    public function test_register_gagal_role_tidak_valid()
    {
        $response = $this->postJson('/api/register', [
            'nama' => 'User Baru',
            'email' => 'baru@test.com',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'role' => 'admin',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['role']);
    }

    // =========================================
    // TEST LOGOUT
    // =========================================

    /**
     * TC-AUTH-10: Logout berhasil
     * Path: logout() → currentAccessToken()->delete()
     */
    public function test_logout_berhasil()
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Logout berhasil',
            ]);

        // Verifikasi token sudah dihapus
        $this->assertCount(0, $user->tokens);
    }

    /**
     * TC-AUTH-11: Logout tanpa token → 401
     */
    public function test_logout_tanpa_token_gagal()
    {
        $response = $this->postJson('/api/logout');

        $response->assertStatus(401);
    }

    // =========================================
    // TEST ME (GET PROFILE)
    // =========================================

    /**
     * TC-AUTH-12: Get profile berhasil
     * Path: me() → return request->user()
     */
    public function test_get_profile_berhasil()
    {
        $user = User::factory()->create([
            'nama' => 'Rizky Test',
            'email' => 'rizky@test.com',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/me');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ])
            ->assertJsonPath('user.email', 'rizky@test.com');
    }

    /**
     * TC-AUTH-13: Get profile tanpa token → 401
     */
    public function test_get_profile_tanpa_auth_gagal()
    {
        $response = $this->getJson('/api/me');

        $response->assertStatus(401);
    }
}
