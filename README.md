# 📚 Journey Learn LMS
### *Learning Management System — Laravel 11 + Android Native*

> Platform pembelajaran online modern berbasis web (Laravel) dan mobile (Android) yang mendukung manajemen kelas, materi bertahap, dan sistem kuis otomatis.

---

## 📋 Daftar Isi
1. [Latar Belakang](#latar-belakang)
2. [Use Case](#use-case)
3. [Fitur Aplikasi](#fitur-aplikasi)
4. [Struktur Aplikasi](#struktur-aplikasi)
5. [Arsitektur Aplikasi](#arsitektur-aplikasi)
6. [Tahapan Pembuatan Proyek](#tahapan-pembuatan-proyek)
7. [Proses SDLC](#proses-sdlc)
8. [Logika Aplikasi](#logika-aplikasi)
9. [Sequence Diagram](#sequence-diagram)
10. [Database & Tabel](#database--tabel)
11. [Cara Menjalankan](#cara-menjalankan)

---

## 🎯 Latar Belakang

Perkembangan teknologi digital mendorong transformasi proses belajar-mengajar dari tatap muka konvensional menuju platform digital interaktif. Namun, banyak institusi pendidikan masih kesulitan menyediakan sistem LMS yang:

- **Terjangkau** — tidak bergantung pada platform berbayar seperti Moodle atau Google Classroom
- **Mudah dikustomisasi** — dapat disesuaikan kebutuhan lokal institusi
- **Terintegrasi** — mendukung web *dan* mobile dalam satu ekosistem

**Journey Learn LMS** hadir sebagai solusi open-source berbasis Laravel 11 + Android Native yang mendukung:
- Pembelajaran bertahap (*sequential learning*) — materi terbuka secara berurutan
- Tiga tipe konten: **Video** (YouTube embed), **Text**, dan **Quiz** pilihan ganda
- Auto-grading quiz dengan batas percobaan dan nilai minimum
- Dual platform: **Web (dosen & mahasiswa)** + **Mobile Android (mahasiswa)**

---

## 👤 Use Case

### Diagram Use Case

```mermaid
graph LR
    subgraph System["Journey Learn LMS"]
        subgraph DosenUC["👨‍🏫 Dosen"]
            UC1["Login / Logout"]
            UC2["Lihat Dashboard & Statistik"]
            UC3["Buat / Edit / Hapus Kelas"]
            UC4["Tambah Materi Video/Text/Quiz"]
            UC5["Tambah Soal Quiz"]
            UC6["Lihat Nilai Mahasiswa"]
        end

        subgraph MahasiswaUC["👨‍🎓 Mahasiswa"]
            UC7["Login / Logout"]
            UC8["Lihat Dashboard & Progress"]
            UC9["Browse Kelas"]
            UC10["Enroll Kelas"]
            UC11["Lihat & Selesaikan Materi"]
            UC12["Kerjakan Quiz"]
            UC13["Lihat Nilai & Riwayat"]
        end
    end

    Dosen["👨‍🏫 Dosen"] --> UC1
    Dosen --> UC2
    Dosen --> UC3
    Dosen --> UC4
    Dosen --> UC5
    Dosen --> UC6

    Mahasiswa["👨‍🎓 Mahasiswa"] --> UC7
    Mahasiswa --> UC8
    Mahasiswa --> UC9
    Mahasiswa --> UC10
    Mahasiswa --> UC11
    Mahasiswa --> UC12
    Mahasiswa --> UC13
```

### Tabel Use Case Detail

| ID | Aktor | Use Case | Deskripsi | Prasyarat |
|----|-------|----------|-----------|-----------|
| UC-D01 | Dosen | Login/Logout | Autentikasi ke sistem | Memiliki akun dosen |
| UC-D02 | Dosen | Lihat Dashboard | Statistik kelas & mahasiswa | Sudah login |
| UC-D03 | Dosen | Kelola Kelas | Buat, edit, hapus kelas | Role = dosen |
| UC-D04 | Dosen | Tambah Materi | Upload video/text/quiz | Kelas sudah ada |
| UC-D05 | Dosen | Kelola Quiz | Tambah soal pilihan ganda | Materi tipe quiz tersedia |
| UC-D06 | Dosen | Lihat Nilai | Melihat hasil quiz mahasiswa | Ada mahasiswa yg sudah submit |
| UC-M01 | Mahasiswa | Login/Logout | Autentikasi ke sistem | Memiliki akun mahasiswa |
| UC-M02 | Mahasiswa | Lihat Dashboard | Kelas diikuti & progress | Sudah login |
| UC-M03 | Mahasiswa | Browse Kelas | Melihat semua kelas tersedia | Sudah login |
| UC-M04 | Mahasiswa | Enroll Kelas | Mendaftar ke kelas | Belum enroll di kelas tsb |
| UC-M05 | Mahasiswa | Lihat Materi | Akses materi secara berurutan | Sudah enroll & materi unlocked |
| UC-M06 | Mahasiswa | Kerjakan Quiz | Submit jawaban pilihan ganda | Materi quiz unlocked, < 3 percobaan |
| UC-M07 | Mahasiswa | Lihat Nilai | Riwayat hasil quiz | Sudah mengerjakan quiz |

### Business Rules

| Aturan | Detail |
|--------|--------|
| **Sequential Learning** | Materi N+1 hanya bisa diakses setelah Materi N selesai |
| **Quiz Limit** | Maksimal 3 kali percobaan per quiz |
| **Nilai Minimum** | Skor ≥ 70 untuk dianggap lulus (unlock materi berikutnya) |
| **Role Restriction** | Dosen tidak bisa enroll kelas; mahasiswa tidak bisa buat kelas |
| **Ownership** | Hanya dosen pemilik kelas yang bisa edit/delete |

---

## ✨ Fitur Aplikasi

### Platform Web (Laravel)
| Fitur | Dosen | Mahasiswa |
|-------|-------|-----------|
| Autentikasi (Login/Register/Logout) | ✅ | ✅ |
| Dashboard statistik | ✅ Total kelas & mahasiswa | ✅ Progress & kelas enrolled |
| Manajemen Kelas (CRUD) | ✅ | ❌ |
| Browse Kelas | ✅ | ✅ |
| Enroll Kelas | ❌ | ✅ |
| Materi Video (YouTube Embed) | ✅ Upload | ✅ Tonton |
| Materi Text (Rich Content) | ✅ Upload | ✅ Baca |
| Materi Quiz (Pilihan Ganda) | ✅ Buat soal | ✅ Kerjakan |
| Auto-grading Quiz | — | ✅ Otomatis |
| Progress Tracking | — | ✅ Per materi |
| Lihat Nilai Mahasiswa | ✅ | ✅ (nilai sendiri) |

### Platform Mobile (Android)
- Login & Session Management (Sanctum Token)
- Browse & Enroll Kelas
- Tampilan Detail Kelas + Daftar Materi
- Buka Materi Video (WebView), Text, Quiz (RadioGroup)
- Submit Quiz & lihat hasil
- Progress tracking per kelas

---

## 📁 Struktur Aplikasi

```
LMS-Laravel/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/                     ← REST API Controllers
│   │   │   │   ├── AuthController.php
│   │   │   │   ├── KelasController.php
│   │   │   │   ├── MateriController.php
│   │   │   │   └── DashboardController.php
│   │   │   ├── DashboardController.php  ← Web Controllers
│   │   │   ├── KelasController.php
│   │   │   └── MateriController.php
│   │   └── Middleware/
│   │       └── RoleMiddleware.php
│   └── Models/
│       ├── User.php
│       ├── Kelas.php
│       ├── Materi.php
│       ├── Enrollment.php
│       ├── Progress.php
│       ├── Kuis.php
│       └── HasilKuis.php
├── database/
│   └── migrations/
│       └── ...                          ← 7 tabel migrasi
├── resources/
│   └── views/
│       ├── auth/                        ← Login, Register
│       ├── dashboard/                   ← Dashboard view
│       ├── kelas/                       ← Browse, detail, kelola kelas
│       └── materi/                      ← View materi, kuis
├── routes/
│   ├── web.php                          ← Route web (dengan Blade)
│   └── api.php                          ← Route REST API (12 endpoint)
├── tests/
│   └── Feature/Api/
│       ├── AuthControllerTest.php       ← 13 test cases
│       ├── KelasControllerTest.php      ← 11 test cases
│       ├── MateriControllerTest.php     ← 10 test cases
│       └── DashboardControllerTest.php  ← 8 test cases
└── bootstrap/
    └── app.php                          ← Registrasi route & middleware
```

---

## 🏗️ Arsitektur Aplikasi

### Layer Architecture

```mermaid
graph TB
    subgraph CLIENT["🖥️ Client Layer"]
        WebBrowser["Web Browser"]
        AndroidApp["Android App"]
    end

    subgraph PRESENTATION["🎨 Presentation Layer"]
        BladeViews["Blade Templates (Web)"]
        AndroidUI["XML Layouts + Activities (Mobile)"]
    end

    subgraph APPLICATION["⚙️ Application Layer — Laravel 11"]
        Middleware["Middleware (Auth, Role, Sanctum)"]
        WebControllers["Web Controllers"]
        ApiControllers["API Controllers"]
        Models["Eloquent Models (7 Models)"]
    end

    subgraph DATA["🗄️ Data Layer"]
        MySQL[("MySQL — db_elearning\n7 Tables")]
    end

    WebBrowser --> BladeViews
    AndroidApp --> AndroidUI
    BladeViews --> Middleware
    AndroidUI -->|"HTTP + Bearer Token"| Middleware
    Middleware --> WebControllers
    Middleware --> ApiControllers
    WebControllers --> Models
    ApiControllers --> Models
    Models --> MySQL

    style CLIENT fill:#1a1a2e,color:#fff
    style PRESENTATION fill:#16213e,color:#fff
    style APPLICATION fill:#0f3460,color:#fff
    style DATA fill:#533483,color:#fff
```

### MVC Pattern

```mermaid
graph LR
    User["👤 User / Android App"]
    Routes["Routes\n(web.php / api.php)"]
    Controller["Controller\n(Business Logic)"]
    Model["Model\n(Eloquent ORM)"]
    View["View / JSON Response\n(Blade / API JSON)"]
    DB[("MySQL")]

    User -->|"HTTP Request"| Routes
    Routes --> Controller
    Controller --> Model
    Model -->|"SQL"| DB
    DB --> Model
    Model --> Controller
    Controller --> View
    View -->|"HTTP Response"| User
```

### REST API Endpoints

| Method | Endpoint | Auth | Fungsi |
|--------|----------|------|--------|
| POST | `/api/login` | ❌ | Login, dapat token Sanctum |
| POST | `/api/register` | ❌ | Register akun baru |
| POST | `/api/logout` | ✅ | Logout, hapus token |
| GET | `/api/me` | ✅ | Profil user |
| GET | `/api/dashboard` | ✅ | Data dashboard (role-aware) |
| GET | `/api/kelas` | ✅ | List semua kelas |
| GET | `/api/kelas-saya` | ✅ | Kelas yang sudah di-enroll |
| GET | `/api/kelas/{id}` | ✅ | Detail kelas + list materi |
| POST | `/api/kelas/{id}/enroll` | ✅ | Enroll ke kelas |
| GET | `/api/materi/{id}` | ✅ | Detail materi (konten) |
| POST | `/api/materi/{id}/complete` | ✅ | Tandai materi selesai |
| POST | `/api/materi/{id}/submit-quiz` | ✅ | Submit jawaban quiz |

---

## 🔨 Tahapan Pembuatan Proyek

### Timeline

```mermaid
gantt
    title Journey Learn LMS — Development Timeline
    dateFormat  YYYY-MM
    section Phase 1 Planning
    Requirement Analysis      :done, 2025-12, 4w
    section Phase 2 Design
    ERD & Architecture         :done, 2026-01, 2w
    UI Wireframe               :done, 2026-01, 2w
    section Phase 3 Development
    Database & Models (7)      :done, 2026-01, 2w
    Web Controllers & Views    :done, 2026-02, 4w
    REST API (12 endpoints)    :done, 2026-02, 2w
    Android App (Java)         :done, 2026-02, 2w
    section Phase 4 Testing
    Bug Fixing & API Testing   :done, 2026-02, 1w
    Whitebox Testing (74 TC)   :done, 2026-02, 1w
```

### Urutan Langkah

| No | Tahap | Deskripsi | Output |
|----|-------|-----------|--------|
| 1 | **Setup Proyek** | Install Laravel 11, konfigurasi `.env`, pasang Breeze | Project scaffold |
| 2 | **Database Design** | Buat migrasi 7 tabel dengan relasi FK | Migrasi & Schema |
| 3 | **Eloquent Models** | 7 model dengan relasi `hasMany`, `belongsTo`, `belongsToMany` | ORM layer |
| 4 | **Authentication** | Integrasi Laravel Breeze (login/register/session) | Auth system |
| 5 | **Role Middleware** | Middleware `RoleMiddleware` untuk akses dosen/mahasiswa | Access control |
| 6 | **Web Controllers** | `DashboardController`, `KelasController`, `MateriController` | Business logic |
| 7 | **Blade Views** | 12+ halaman Blade + Bootstrap 5 | UI web |
| 8 | **REST API** | Sanctum setup + 4 API controllers + 12 endpoints | Mobile backend |
| 9 | **Android App** | 10 Java files + 8 XML layouts + Retrofit | Mobile client |
| 10 | **Build APK** | `gradlew assembleDebug` menggunakan JDK 17 | app-debug.apk |
| 11 | **Whitebox Testing** | 42 Laravel tests + 32 Android tests | Test results |

---

## 🔄 Proses SDLC

Model SDLC yang digunakan: **Waterfall dengan Iterasi** (ketika ditemukan bug atau requirement baru, kembali ke fase sebelumnya).

```mermaid
graph TD
    P["📋 1. PLANNING\nAnalisis kebutuhan, scope, user stories\n1 bulan"]
    A["🔍 2. ANALYSIS\nAudit keamanan, identifikasi bug\n2 minggu"]
    D["📐 3. DESIGN\nERD, arsitektur sistem, wireframe UI\n2 minggu"]
    DEV["💻 4. DEVELOPMENT\nCoding 3700+ LOC web + Android\n8 minggu"]
    T["✅ 5. TESTING\n74 whitebox test cases\n2 minggu"]
    DEP["🚀 6. DEPLOYMENT\nXAMPP local + APK build\n1 minggu"]
    M["🔧 7. MAINTENANCE\nBug fixes ongoing"]

    P --> A --> D --> DEV --> T --> DEP --> M
    M -.->|"Iterasi jika ada bug"| A
```

| Fase | Aktivitas Utama | Deliverable |
|------|----------------|-------------|
| Planning | Analisis kebutuhan, user story dosen & mahasiswa | Dokumen requirements |
| Analysis | Bug audit, security review | Daftar issues |
| Design | ERD 7 tabel, MVC architecture, wireframe | Desain sistem |
| Development | Models, Controllers, Views, REST API, Android App | Source code |
| Testing | PHPUnit (42 TC) + JUnit Android (32 TC) = 74 total | Test report |
| Deployment | `php artisan serve`, build APK | Web running + APK |
| Maintenance | Fix bug selesai & ongoing | Updated codebase |

---

## ⚙️ Logika Aplikasi

### 1. Sequential Learning Logic

```mermaid
flowchart TD
    A["Mahasiswa akses materi ke-N"]
    B{"Urutan materi = 1?"}
    C["Cek progress materi N-1"]
    D{"Status N-1 = completed?"}
    E["Tampilkan konten materi"]
    F["Return 403 — Materi terkunci"]

    A --> B
    B -->|Ya| E
    B -->|Tidak| C
    C --> D
    D -->|Ya| E
    D -->|Tidak| F
```

### 2. Quiz Logic

```mermaid
flowchart TD
    A["Mahasiswa submit quiz"]
    B{"Sudah lulus sebelumnya?"}
    C{"Jumlah percobaan >= 3?"}
    D["Hitung jawaban benar"]
    E{"Nilai >= 70?"}
    F["Return 409 — Sudah lulus"]
    G["Return 429 — Percobaan habis"]
    H["Tandai progress = completed\nUnlock materi berikutnya"]
    I["Simpan hasil, return nilai\nBoleh coba lagi"]

    A --> B
    B -->|Ya| F
    B -->|Tidak| C
    C -->|Ya| G
    C -->|Tidak| D
    D --> E
    E -->|Ya| H
    E -->|Tidak| I
```

### 3. Dashboard Role Logic

```php
// DashboardController.php
public function dashboard(Request $request)
{
    $user = $request->user();
    if ($user->role === 'dosen') {
        return $this->dosenDashboard($user);  
        // → total kelas milik dosen, total mahasiswa terdaftar
    } else {
        return $this->mahasiswaDashboard($user); 
        // → kelas enrolled + progress per kelas
    }
}
```

### 4. Token Auth Logic (Sanctum)

```mermaid
sequenceDiagram
    participant App as Android App
    participant API as Laravel API
    participant DB as Database

    App->>API: POST /api/login {email, password}
    API->>DB: Cari user by email
    DB-->>API: User data
    API->>API: Hash::check(password, hash)
    API->>DB: Hapus token lama
    API->>DB: Buat token baru
    API-->>App: {token, user}
    Note over App: Simpan token di SharedPreferences

    App->>API: GET /api/kelas\nAuthorization: Bearer <token>
    API->>API: Sanctum verify token
    API->>DB: Query data kelas
    DB-->>API: Data
    API-->>App: JSON Response
```

---

## 📊 Sequence Diagram

### Login Flow

```mermaid
sequenceDiagram
    actor U as User
    participant V as View/Activity
    participant C as Controller/ApiService
    participant M as Model/Retrofit
    participant DB as MySQL/API

    U->>V: Isi email & password, klik Login
    V->>C: kirim credential
    C->>M: User::where('email')->first()
    M->>DB: SELECT * FROM users WHERE email=?
    DB-->>M: Row data / null
    M-->>C: User object / null
    C->>C: Hash::check(password, hash)
    alt Berhasil
        C->>DB: INSERT personal_access_tokens
        C-->>V: {status: success, token: ...}
        V-->>U: Redirect ke Dashboard
    else Gagal
        C-->>V: {status: error, message: ...}
        V-->>U: Tampilkan pesan error
    end
```

### Enroll Kelas Flow

```mermaid
sequenceDiagram
    actor M as Mahasiswa
    participant A as Android App
    participant API as Laravel API
    participant DB as Database

    M->>A: Klik tombol Enroll
    A->>API: POST /api/kelas/{id}/enroll\n+ Bearer Token
    API->>DB: Cek Enrollment (user_id, kelas_id)
    DB-->>API: exists / not exists
    alt Sudah enroll
        API-->>A: 409 Conflict
        A-->>M: "Sudah terdaftar"
    else User = Dosen
        API-->>A: 403 Forbidden
        A-->>M: "Dosen tidak bisa enroll"
    else Berhasil
        API->>DB: INSERT enrollments
        API->>DB: INSERT progress (status=locked) per materi
        API-->>A: 200 Success
        A-->>M: "Berhasil mendaftar"
    end
```

### Submit Quiz Flow

```mermaid
sequenceDiagram
    actor M as Mahasiswa
    participant A as App
    participant API as Laravel API
    participant DB as Database

    M->>A: Pilih jawaban & klik Submit
    A->>API: POST /api/materi/{id}/submit-quiz\n{jawaban: {soal_id: pilihan}}
    API->>DB: Cek already_passed (status=completed)
    API->>DB: Cek jumlah attempts (hasil_kuis)
    alt Sudah lulus
        API-->>A: 409 — "Anda sudah lulus quiz ini"
    else Percobaan habis (>=3)
        API-->>A: 429 — "Percobaan habis"
    else Proses
        API->>DB: SELECT kuis WHERE materi_id
        API->>API: Hitung jawaban benar → nilai
        API->>DB: INSERT hasil_kuis {nilai, lulus}
        alt Nilai >= 70
            API->>DB: UPDATE progress\nstatus=completed
            API-->>A: {lulus: true, nilai: X}
            A-->>M: "Selamat! Unlock materi berikutnya"
        else Nilai < 70
            API-->>A: {lulus: false, nilai: X, sisa_percobaan: N}
            A-->>M: "Belum lulus, coba lagi"
        end
    end
```

---

## 🗄️ Database & Tabel

### Entity Relationship Diagram (ERD)

```mermaid
erDiagram
    USERS {
        int id PK
        varchar nama
        varchar email
        varchar password
        enum role "mahasiswa | dosen"
        varchar foto_profil
        timestamps created_at
    }

    KELAS {
        int id PK
        int dosen_id FK
        varchar nama_kelas
        text deskripsi
        varchar kategori
        timestamps created_at
    }

    ENROLLMENT {
        int id PK
        int user_id FK
        int kelas_id FK
        datetime tanggal_daftar
    }

    MATERI {
        int id PK
        int kelas_id FK
        varchar judul
        enum tipe "video | text | quiz"
        text konten
        int urutan
        int durasi_menit
    }

    PROGRESS {
        int id PK
        int user_id FK
        int materi_id FK
        enum status "locked | in_progress | completed"
        datetime tanggal_mulai
        datetime tanggal_selesai
    }

    KUIS {
        int id PK
        int materi_id FK
        text pertanyaan
        varchar pilihan_a
        varchar pilihan_b
        varchar pilihan_c
        varchar pilihan_d
        char jawaban_benar "a|b|c|d"
        int poin
    }

    HASIL_KUIS {
        int id PK
        int user_id FK
        int materi_id FK
        int nilai
        boolean lulus
        json jawaban_detail
        datetime waktu_submit
    }

    USERS ||--o{ KELAS : "creates (dosen)"
    USERS ||--o{ ENROLLMENT : "enrolls (mahasiswa)"
    USERS ||--o{ PROGRESS : "has"
    USERS ||--o{ HASIL_KUIS : "submits"
    KELAS ||--o{ ENROLLMENT : "has"
    KELAS ||--o{ MATERI : "contains"
    MATERI ||--o{ PROGRESS : "tracked_by"
    MATERI ||--o{ KUIS : "has"
    MATERI ||--o{ HASIL_KUIS : "generates"
```

### Deskripsi Tabel

| Tabel | Jumlah Kolom | Fungsi |
|-------|-------------|--------|
| `users` | 6 kolom | Menyimpan data akun (dosen & mahasiswa) |
| `kelas` | 5 kolom | Data kelas/mata kuliah milik dosen |
| `enrollments` | 4 kolom | Relasi many-to-many user ↔ kelas |
| `materi` | 7 kolom | Konten pembelajaran (video/text/quiz) per kelas |
| `progress` | 6 kolom | Tracking status per materi per user |
| `kuis` | 9 kolom | Soal pilihan ganda untuk materi tipe quiz |
| `hasil_kuis` | 7 kolom | Riwayat pengerjaan quiz + nilai |

---

## 🚀 Cara Menjalankan

### Prasyarat
- PHP 8.2+
- Composer
- MySQL (via XAMPP)
- Node.js (untuk Vite assets)

### Langkah Setup

```bash
# 1. Clone / buka folder project
cd c:\xampp\htdocs\LMS-Laravel

# 2. Install dependencies
composer install

# 3. Copy .env dan isi konfigurasi database
copy .env.example .env
php artisan key:generate

# 4. Jalankan migrasi + seeder
php artisan migrate --seed

# 5. Jalankan server
php artisan serve --host=0.0.0.0
# → Akses: http://localhost:8000
```

### Menjalankan Test

```bash
# Laravel PHPUnit (42 test cases)
php artisan test --filter=Api

# Android JUnit (32 test cases)
cd c:\xampp\htdocs\JourneyLearnLMS-Android
$env:JAVA_HOME = "C:\Program Files\Android\Android Studio\jbr"
$env:ANDROID_HOME = "$env:LOCALAPPDATA\Android\Sdk"
.\gradlew.bat testDebugUnitTest
```

### Build Android APK

```bash
cd c:\xampp\htdocs\JourneyLearnLMS-Android
$env:JAVA_HOME = "C:\Program Files\Android\Android Studio\jbr"
$env:ANDROID_HOME = "$env:LOCALAPPDATA\Android\Sdk"
.\gradlew.bat assembleDebug
# Output: app\build\outputs\apk\debug\app-debug.apk
```

---

## 🛠️ Teknologi yang Digunakan

| Layer | Teknologi |
|-------|-----------|
| Backend | Laravel 11, PHP 8.2, Eloquent ORM |
| Auth | Laravel Breeze (Web), Laravel Sanctum (API) |
| Frontend | Blade Templates, Bootstrap 5, JavaScript |
| Database | MySQL 8.0 via XAMPP |
| API | REST API + JSON |
| Mobile | Android (Java), Retrofit 2, Gson, OkHttp |
| Testing | PHPUnit, JUnit 4, Robolectric |
| Build | Gradle 8.4, Android SDK 34 |

---

## 📊 Statistik Proyek

| Metrik | Nilai |
|--------|-------|
| Total file source code | 50+ files |
| Lines of Code (LOC) | 3700+ LOC |
| Tabel database | 7 tabel |
| API Endpoints | 12 endpoints |
| Java Activities | 5 Activities |
| Whitebox Test Cases | 74 test cases |
| Build APK | 6.4 MB |

---

*Journey Learn LMS © 2026 — Dibuat untuk keperluan tugas akhir dan pembelajaran*
