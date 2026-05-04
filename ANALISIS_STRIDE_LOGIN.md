# Analisis STRIDE — Form Login Journey Learn LMS

**Komponen yang Dianalisis:**
- `LoginActivity.java` (Android Client)
- `AuthController.php` — `POST /api/login` (Laravel Backend)
- `SessionManager.java` (Penyimpanan Token)
- `ApiClient.java` (Konfigurasi HTTP)
- `network_security_config.xml` (Keamanan Jaringan)

---

## Gambaran Alur Login

```
[User Input]             [Android]                    [Laravel API]              [Database]
email + password  -->  LoginActivity.java  --HTTP-->  AuthController@login  -->  users table
                            |                               |
                       ApiClient.java              Hash::check(password)
                       (Retrofit + OkHttp)               |
                            |                     createToken() (Sanctum)
                       SessionManager              <-- token + user data
                       (SharedPreferences)
```

---

## Tabel Analisis STRIDE

### S — Spoofing (Pemalsuan Identitas)

> Ancaman: Penyerang berpura-pura menjadi pengguna lain untuk mendapatkan akses.

| ID | Ancaman | Komponen Terdampak | Bukti di Kode | Tingkat Risiko |
|----|---------|-------------------|---------------|----------------|
| S-1 | **Brute Force Login** — Penyerang mencoba ribuan kombinasi email/password secara otomatis | `POST /api/login`, `AuthController.php` | Tidak ada rate limiting di `AuthController::login()`. Tidak ada CAPTCHA. `btnLogin` hanya di-disable saat request berlangsung (baris 73), bukan antar percobaan | TINGGI |
| S-2 | **Credential Stuffing** — Penyerang menggunakan daftar email/password yang bocor dari situs lain | `POST /api/login` | Tidak ada deteksi login dari lokasi/device baru, tidak ada notifikasi email saat login mencurigakan | TINGGI |
| S-3 | **Token Spoofing** — Penyerang memalsukan token Sanctum di header `Authorization` | `ApiClient.java` baris 35 | Token Sanctum berformat `id|hash` — server memverifikasi lewat database. Namun tidak ada validasi format token di sisi Android sebelum dikirim | RENDAH |
| S-4 | **Man-in-the-Middle Login** — Penyerang mencegat request login dan mengirim ulang dengan credential valid | `ApiClient.java` baris 17 | Koneksi menggunakan **HTTP plaintext** (bukan HTTPS). `network_security_config.xml` mengizinkan `cleartextTrafficPermitted="true"` | KRITIS |

**Mitigasi yang Sudah Ada:**
- Token lama dihapus sebelum token baru dibuat (`$user->tokens()->delete()` — baris 34 AuthController)
- Password di-hash dengan `bcrypt` via `Hash::check()`

**Mitigasi yang Belum Ada:**
- Rate limiting / throttling pada endpoint `/api/login`
- Account lockout setelah N kali gagal login
- HTTPS / TLS enkripsi

---

### T — Tampering (Manipulasi Data)

> Ancaman: Penyerang memodifikasi data dalam perjalanan antara Android dan server.

| ID | Ancaman | Komponen Terdampak | Bukti di Kode | Tingkat Risiko |
|----|---------|-------------------|---------------|----------------|
| T-1 | **Request Tampering** — Penyerang intercept dan modifikasi request login (email/password) sebelum sampai ke server | `ApiClient.java`, `LoginActivity.java` baris 75-77 | Koneksi HTTP plaintext tanpa enkripsi TLS. Body JSON `{email, password}` bisa dibaca dan dimodifikasi oleh penyerang di jaringan yang sama | KRITIS |
| T-2 | **Response Tampering** — Penyerang modifikasi response dari server: mengubah `status: "error"` menjadi `"success"`, atau memasukkan token palsu | `LoginActivity.java` baris 88-90 | Tidak ada signature/MAC verification pada response body. Android hanya mengecek `response.isSuccessful()` dan field `status` | TINGGI |
| T-3 | **SharedPreferences Tampering** — Di device yang di-root, penyerang bisa mengubah nilai token di SharedPreferences | `SessionManager.java` baris 18 | `MODE_PRIVATE` melindungi dari app lain, **tapi tidak dari root access**. Tidak ada enkripsi tambahan pada SharedPreferences | SEDANG |
| T-4 | **Input Injection** — Penyerang memasukkan karakter khusus di field email/password untuk manipulasi query | `LoginActivity.java` baris 64-65, `AuthController.php` baris 24 | Android hanya cek `isEmpty()`. Di server, Eloquent ORM menggunakan **parameter binding** sehingga SQL Injection sudah dicegah. Namun tidak ada sanitasi khusus di sisi Android | RENDAH |

**Mitigasi yang Sudah Ada:**
- Eloquent ORM mencegah SQL Injection via prepared statements
- Validasi `required|email` di backend (baris 19-22 AuthController)

**Mitigasi yang Belum Ada:**
- HTTPS/TLS untuk enkripsi data in-transit
- Response integrity verification (HMAC/signature)
- EncryptedSharedPreferences (AndroidX Security)

---

### R — Repudiation (Penyangkalan)

> Ancaman: Pengguna atau penyerang menyangkal telah melakukan aksi tertentu karena tidak ada bukti audit.

| ID | Ancaman | Komponen Terdampak | Bukti di Kode | Tingkat Risiko |
|----|---------|-------------------|---------------|----------------|
| R-1 | **Penyangkalan Login** — Pengguna menyangkal telah login, karena tidak ada log yang mencatat waktu, IP, dan device saat login | `AuthController.php` baris 17-49 | Tidak ada pencatatan log login (IP address, timestamp, user-agent) ke database atau file log | SEDANG |
| R-2 | **Penyangkalan Percobaan Gagal** — Tidak ada bukti berapa kali seseorang mencoba login dengan credential yang salah | `AuthController.php` baris 26-31 | Tidak ada tabel `login_attempts` atau pencatatan kegagalan login | SEDANG |
| R-3 | **Token Abuse Tanpa Jejak** — Token digunakan untuk aksi tertentu tanpa jejak audit yang terhubung ke sesi login asli | Semua endpoint protected | Tabel `personal_access_tokens` menyimpan token tapi tidak menyimpan IP atau device saat token digunakan | SEDANG |

**Mitigasi yang Sudah Ada:**
- Laravel Sanctum mencatat token di tabel `personal_access_tokens` dengan `last_used_at`
- Token dihapus saat logout (non-repudiation parsial)

**Mitigasi yang Belum Ada:**
- Tabel audit log: `login_logs(user_id, ip_address, user_agent, status, created_at)`
- Pencatatan gagal login untuk deteksi anomali

---

### I — Information Disclosure (Kebocoran Informasi)

> Ancaman: Informasi sensitif terekspos ke pihak yang tidak berwenang.

| ID | Ancaman | Komponen Terdampak | Bukti di Kode | Tingkat Risiko |
|----|---------|-------------------|---------------|----------------|
| I-1 | **Password Plaintext di Jaringan** — Password dikirim tanpa enkripsi lewat HTTP | `ApiClient.java` baris 17, `LoginActivity.java` baris 77 | `BASE_URL = "http://10.0.2.2:8000/api/"` — plaintext HTTP. Password terlihat jelas di Wireshark/proxy oleh siapapun di jaringan yang sama | KRITIS |
| I-2 | **Token Terekspos di Log** — Sanctum token terlihat di OkHttp logging yang aktif di mode debug | `ApiClient.java` baris 22-23 | `logging.setLevel(HttpLoggingInterceptor.Level.BODY)` — level BODY mencatat seluruh request/response termasuk token di header `Authorization` dan body response | TINGGI |
| I-3 | **Informasi Enumerasi User** — Response error yang terlalu spesifik bisa memberi tahu penyerang apakah email terdaftar atau tidak | `AuthController.php` baris 27-31 | Saat ini response `"Email atau password salah."` sudah digabung (tidak membedakan email vs password salah) — ini sudah baik | RENDAH |
| I-4 | **Data User di SharedPreferences** — Nama, email, role tersimpan plaintext di SharedPreferences | `SessionManager.java` baris 31-37 | `editor.putString(KEY_USER_EMAIL, email)` tanpa enkripsi. Di device root, file SharedPreferences bisa dibaca langsung | SEDANG |
| I-5 | **Token di SharedPreferences** — Sanctum token tersimpan plaintext | `SessionManager.java` baris 22-25 | `editor.putString(KEY_TOKEN, token)` — token plaintext. Jika device di-root atau backup diakses, token bisa dicuri | TINGGI |

**Mitigasi yang Sudah Ada:**
- Password di-hash bcrypt sebelum disimpan di database (tidak pernah disimpan plaintext)
- Response error tidak membedakan "email tidak ada" vs "password salah"

**Mitigasi yang Belum Ada:**
- HTTPS untuk enkripsi password in-transit
- `HttpLoggingInterceptor.Level.NONE` di production build
- `EncryptedSharedPreferences` untuk token dan data user
- ProGuard/R8 untuk obfuscate kode APK

---

### D — Denial of Service (Penolakan Layanan)

> Ancaman: Penyerang membuat sistem tidak tersedia bagi pengguna sah.

| ID | Ancaman | Komponen Terdampak | Bukti di Kode | Tingkat Risiko |
|----|---------|-------------------|---------------|----------------|
| D-1 | **Login Flooding** — Penyerang mengirim ribuan request login secara bersamaan, membebani server | `POST /api/login`, `AuthController.php` | Tidak ada rate limiting pada endpoint `/api/login`. Laravel tidak dikonfigurasi dengan throttle middleware untuk API login | TINGGI |
| D-2 | **Database Connection Exhaustion** — Setiap request login membuka koneksi ke database. Request massal bisa menghabiskan connection pool | `AuthController.php` baris 24 | `User::where('email',...)->first()` — setiap login melakukan query DB. Tanpa throttling, bisa DoS database | TINGGI |
| D-3 | **Token Table Bloat** — Setiap login menghapus dan membuat token baru, menambah beban tabel `personal_access_tokens` | `AuthController.php` baris 34-35 | `$user->tokens()->delete()` sudah membersihkan token lama per user — namun jika login flooding dari banyak akun, tabel bisa membengkak | SEDANG |
| D-4 | **UI Freeze** — Tidak ada timeout pada request HTTP di Android, sehingga UI bisa hang tanpa batas | `ApiClient.java` baris 43-47 | Tidak ada konfigurasi `.connectTimeout()`, `.readTimeout()`, `.writeTimeout()` pada `OkHttpClient.Builder` | SEDANG |

**Mitigasi yang Sudah Ada:**
- `btnLogin.setEnabled(false)` saat request berlangsung (mencegah double submit dari user yang sama)

**Mitigasi yang Belum Ada:**
- `throttle:5,1` middleware pada route `/api/login` (maks 5 percobaan per menit)
- HTTP timeout di OkHttpClient
- Server-side rate limiting / Web Application Firewall

---

### E — Elevation of Privilege (Eskalasi Hak Akses)

> Ancaman: Pengguna mendapatkan hak akses melebihi yang seharusnya.

| ID | Ancaman | Komponen Terdampak | Bukti di Kode | Tingkat Risiko |
|----|---------|-------------------|---------------|----------------|
| E-1 | **Role Manipulation via Response** — Penyerang dengan MitM mengubah field `role` di response login dari `"mahasiswa"` menjadi `"dosen"` | `LoginActivity.java` baris 99, `SessionManager.java` baris 35 | `sessionManager.saveUser(..., user.get("role").getAsString())` — role disimpan langsung dari response tanpa verifikasi tambahan. Jika response di-tamper (lihat T-2), role bisa diubah | TINGGI |
| E-2 | **SharedPreferences Role Tampering** — Di device root, penyerang edit nilai `user_role` di SharedPreferences dari `"mahasiswa"` ke `"dosen"` | `SessionManager.java` baris 47-49 | Role dibaca dari SharedPreferences tanpa verifikasi ke server. Tapi server tetap memvalidasi token — jika role di client berubah tapi token tidak berubah, akses API tetap dibatasi oleh server | SEDANG |
| E-3 | **Bypass Login Client-Side** — Penyerang memodifikasi APK untuk skip pemeriksaan `sessionManager.isLoggedIn()` dan langsung masuk ke Dashboard | `LoginActivity.java` baris 36-40 | Tidak ada verifikasi token ke server saat membuka Dashboard (`isLoggedIn()` hanya cek token non-null di SharedPreferences). Jika token expired atau dihapus dari server, app bisa salah anggap sudah login | SEDANG |
| E-4 | **API Endpoint Access Tanpa Role Check** — Mahasiswa mencoba akses endpoint dosen | `AuthController.php`, middleware | Role-based access control sudah ada di middleware server — tidak tergantung nilai di Android | RENDAH |

**Mitigasi yang Sudah Ada:**
- Server selalu memvalidasi token Sanctum di setiap request (middleware `auth:sanctum`)
- Role restriction di server tidak bergantung pada nilai di Android
- `$user->tokens()->delete()` mencegah multiple active sessions

**Mitigasi yang Belum Ada:**
- HTTPS untuk mencegah response tampering (E-1)
- Verifikasi token ke server saat app dibuka (`GET /api/me` saat splash screen)
- APK integrity check / certificate pinning

---

## Rekap Matriks Risiko

| Kategori STRIDE | ID Ancaman | Deskripsi Singkat | Risiko | Status |
|----------------|-----------|-------------------|--------|--------|
| **Spoofing** | S-1 | Brute Force Login | TINGGI | Belum dimitigasi |
| | S-2 | Credential Stuffing | TINGGI | Belum dimitigasi |
| | S-3 | Token Spoofing | RENDAH | Dimitigasi sebagian |
| | S-4 | MitM Login (HTTP) | KRITIS | Belum dimitigasi |
| **Tampering** | T-1 | Request Tampering | KRITIS | Belum dimitigasi |
| | T-2 | Response Tampering | TINGGI | Belum dimitigasi |
| | T-3 | SharedPreferences Tamper | SEDANG | Dimitigasi sebagian |
| | T-4 | Input Injection | RENDAH | Dimitigasi (ORM) |
| **Repudiation** | R-1 | Penyangkalan Login | SEDANG | Belum dimitigasi |
| | R-2 | Penyangkalan Gagal Login | SEDANG | Belum dimitigasi |
| | R-3 | Token Abuse | SEDANG | Dimitigasi sebagian |
| **Info Disclosure** | I-1 | Password Plaintext (HTTP) | KRITIS | Belum dimitigasi |
| | I-2 | Token di OkHttp Log | TINGGI | Belum dimitigasi |
| | I-3 | Enumerasi User | RENDAH | Sudah dimitigasi |
| | I-4 | Data User di SharedPrefs | SEDANG | Belum dimitigasi |
| | I-5 | Token di SharedPrefs | TINGGI | Belum dimitigasi |
| **DoS** | D-1 | Login Flooding | TINGGI | Belum dimitigasi |
| | D-2 | DB Connection Exhaustion | TINGGI | Belum dimitigasi |
| | D-3 | Token Table Bloat | SEDANG | Dimitigasi sebagian |
| | D-4 | UI Freeze (No Timeout) | SEDANG | Belum dimitigasi |
| **Elevation** | E-1 | Role Manipulation MitM | TINGGI | Belum dimitigasi |
| | E-2 | SharedPrefs Role Tamper | SEDANG | Dimitigasi sebagian |
| | E-3 | Bypass Login Client-Side | SEDANG | Belum dimitigasi |
| | E-4 | API Endpoint Access | RENDAH | Sudah dimitigasi |

### Distribusi Tingkat Risiko

| Tingkat | Jumlah | Ancaman |
|---------|--------|---------|
| KRITIS  | 3 | S-4, T-1, I-1 (semua terkait HTTP plaintext) |
| TINGGI  | 8 | S-1, S-2, T-2, I-2, I-5, D-1, D-2, E-1 |
| SEDANG  | 8 | T-3, R-1, R-2, R-3, I-4, D-3, D-4, E-2, E-3 |
| RENDAH  | 3 | S-3, T-4, I-3, E-4 |

---

## Rekomendasi Perbaikan (Prioritas)

### Prioritas 1 — KRITIS (Harus segera)

| No | Rekomendasi | Implementasi |
|----|-------------|--------------|
| 1 | **Gunakan HTTPS** — Enkripsi seluruh komunikasi | Deploy dengan SSL certificate. Ubah `BASE_URL` ke `https://`. Hapus `cleartextTrafficPermitted="true"` |
| 2 | **Rate Limiting Login** | Tambah `throttle:5,1` di `routes/api.php` pada route `/api/login` |

```php
// routes/api.php
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1'); // maks 5 request per menit per IP
```

### Prioritas 2 — TINGGI

| No | Rekomendasi | Implementasi |
|----|-------------|--------------|
| 3 | **Matikan OkHttp Logging di Production** | Gunakan `BuildConfig.DEBUG` untuk set log level |
| 4 | **Enkripsi SharedPreferences** | Ganti ke `EncryptedSharedPreferences` (AndroidX Security) |
| 5 | **HTTP Timeout di OkHttpClient** | Tambah timeout di `ApiClient.java` |
| 6 | **Audit Log Login** | Buat tabel `login_logs` dan catat setiap login/gagal login |

```java
// ApiClient.java — perbaikan timeout + logging
HttpLoggingInterceptor logging = new HttpLoggingInterceptor();
logging.setLevel(BuildConfig.DEBUG
    ? HttpLoggingInterceptor.Level.BODY
    : HttpLoggingInterceptor.Level.NONE); // matikan di production

OkHttpClient client = new OkHttpClient.Builder()
    .connectTimeout(10, TimeUnit.SECONDS)
    .readTimeout(15, TimeUnit.SECONDS)
    .writeTimeout(10, TimeUnit.SECONDS)
    .addInterceptor(logging)
    .build();
```

### Prioritas 3 — SEDANG

| No | Rekomendasi | Implementasi |
|----|-------------|--------------|
| 7 | **Verifikasi Token saat App Dibuka** | Panggil `GET /api/me` di splash screen; jika 401, paksa logout |
| 8 | **Certificate Pinning** | Tambah `CertificatePinner` di OkHttpClient agar tidak bisa MitM |
| 9 | **Account Lockout** | Kunci akun setelah 5 kali gagal login, kirim notifikasi email |

---

## Kesimpulan

Ancaman **paling kritis** pada form login Journey Learn LMS adalah penggunaan **HTTP plaintext** yang menyebabkan tiga kategori STRIDE (Spoofing S-4, Tampering T-1, Information Disclosure I-1) sekaligus berada di level KRITIS. Semua ancaman kritis ini dapat dieliminasi dengan satu tindakan: **migrasi ke HTTPS**.

Sistem sudah memiliki fondasi keamanan yang baik di sisi server (bcrypt hashing, Sanctum token, Eloquent ORM), namun lapisan transport dan client-side storage perlu diperkuat.

---

*Analisis STRIDE — Journey Learn LMS*
*Tanggal: Maret 2026*
*Komponen: Form Login (Android + Laravel API)*
