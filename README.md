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
├── resources/
│   └── views/
│       ├── auth/
│       ├── dashboard/
│       ├── kelas/
│       └── materi/
├── routes/
│   ├── web.php                          ← Route web (Blade)
│   └── api.php                          ← Route REST API (12 endpoint)
└── tests/
    └── Feature/Api/
        ├── AuthControllerTest.php       ← 13 test cases
        ├── KelasControllerTest.php      ← 11 test cases
        ├── MateriControllerTest.php     ← 10 test cases
        └── DashboardControllerTest.php  ← 8 test cases
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

    subgraph APPLICATION["⚙️ Application Layer — Laravel 11"]
        Middleware["Middleware (Auth, Role, Sanctum)"]
        WebControllers["Web Controllers"]
        ApiControllers["API Controllers"]
        Models["Eloquent Models (7 Models)"]
    end

    subgraph DATA["🗄️ Data Layer"]
        MySQL[("MySQL — db_elearning\n7 Tables")]
    end

    WebBrowser --> Middleware
    AndroidApp -->|"HTTP + Bearer Token"| Middleware
    Middleware --> WebControllers
    Middleware --> ApiControllers
    WebControllers --> Models
    ApiControllers --> Models
    Models --> MySQL
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

| No | Tahap | Deskripsi | Output |
|----|-------|-----------|--------|
| 1 | **Setup Proyek** | Install Laravel 11, konfigurasi `.env`, pasang Breeze | Project scaffold |
| 2 | **Database Design** | Buat migrasi 7 tabel dengan relasi FK | Migrasi & Schema |
| 3 | **Eloquent Models** | 7 model dengan relasi hasMany, belongsTo | ORM layer |
| 4 | **Authentication** | Integrasi Laravel Breeze (login/register/session) | Auth system |
| 5 | **Role Middleware** | Middleware untuk akses dosen/mahasiswa | Access control |
| 6 | **Web Controllers** | Dashboard, Kelas, Materi Controller | Business logic |
| 7 | **Blade Views** | 12+ halaman Blade + Bootstrap 5 | UI web |
| 8 | **REST API** | Sanctum setup + 4 API controllers + 12 endpoints | Mobile backend |
| 9 | **Android App** | 10 Java files + 8 XML layouts + Retrofit | Mobile client |
| 10 | **Build APK** | `gradlew assembleDebug` menggunakan JDK 17 | app-debug.apk (6.4 MB) |
| 11 | **Whitebox Testing** | 42 Laravel tests + 32 Android tests | 74 test cases passed |

---

## 🔄 Proses SDLC

Model SDLC: **Waterfall dengan Iterasi**

```mermaid
graph TD
    P["📋 1. PLANNING\nAnalisis kebutuhan, scope, user stories"]
    A["🔍 2. ANALYSIS\nAudit keamanan, identifikasi bug"]
    D["📐 3. DESIGN\nERD, arsitektur sistem, wireframe UI"]
    DEV["💻 4. DEVELOPMENT\nCoding 3700+ LOC web + Android"]
    T["✅ 5. TESTING\n74 whitebox test cases"]
    DEP["🚀 6. DEPLOYMENT\nXAMPP local + APK build"]
    M["🔧 7. MAINTENANCE\nBug fixes ongoing"]

    P --> A --> D --> DEV --> T --> DEP --> M
    M -.->|"Iterasi jika ada bug"| A
```

| Fase | Deliverable |
|------|-------------|
| Planning | Dokumen requirements, user stories |
| Analysis | Daftar bug & security issues |
| Design | ERD 7 tabel, arsitektur MVC |
| Development | Source code + APK |
| Testing | 42 PHPUnit + 32 JUnit = 74 TC |
| Deployment | Web running + APK 6.4 MB |
| Maintenance | Bug fixes & updates |

---

## ⚙️ Logika Aplikasi

### Sequential Learning Logic

```mermaid
flowchart TD
    A["Mahasiswa akses materi ke-N"]
    B{"Urutan = 1?"}
    C["Cek progress materi N-1"]
    D{"Status N-1 = completed?"}
    E["✅ Tampilkan konten"]
    F["🔒 Return 403 — Terkunci"]

    A --> B
    B -->|Ya| E
    B -->|Tidak| C
    C --> D
    D -->|Ya| E
    D -->|Tidak| F
```

### Quiz Logic

```mermaid
flowchart TD
    A["Submit quiz"]
    B{"Sudah lulus?"}
    C{"Percobaan >= 3?"}
    D["Hitung jawaban benar"]
    E{"Nilai >= 70?"}
    F["409 — Sudah lulus"]
    G["429 — Percobaan habis"]
    H["✅ Completed + unlock materi berikutnya"]
    I["❌ Simpan nilai, boleh coba lagi"]

    A --> B --> |Ya|F
    B --> |Tidak|C --> |Ya|G
    C --> |Tidak|D --> E
    E --> |Ya|H
    E --> |Tidak|I
```

---

## 📊 Sequence Diagram

### Login Flow

```mermaid
sequenceDiagram
    actor U as User
    participant C as Controller
    participant M as Model
    participant DB as MySQL

    U->>C: POST /api/login {email, password}
    C->>M: User::where('email')->first()
    M->>DB: SELECT * FROM users WHERE email=?
    DB-->>C: User object / null
    C->>C: Hash::check(password, hash)
    alt Berhasil
        C->>DB: INSERT personal_access_tokens
        C-->>U: {status: success, token}
    else Gagal
        C-->>U: {status: error, 401}
    end
```

### Submit Quiz Flow

```mermaid
sequenceDiagram
    actor M as Mahasiswa
    participant API as Laravel API
    participant DB as Database

    M->>API: POST /api/materi/{id}/submit-quiz\n{jawaban: {soal_id: pilihan}}
    API->>DB: Cek already_passed
    API->>DB: Cek jumlah attempts
    alt Sudah lulus
        API-->>M: 409
    else Percobaan habis
        API-->>M: 429
    else Proses
        API->>DB: SELECT kuis
        API->>API: Hitung nilai
        API->>DB: INSERT hasil_kuis
        alt Nilai >= 70
            API->>DB: UPDATE progress = completed
            API-->>M: {lulus: true, nilai: X}
        else
            API-->>M: {lulus: false, nilai: X}
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
    }
    KELAS {
        int id PK
        int dosen_id FK
        varchar nama_kelas
        text deskripsi
        varchar kategori
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

| Tabel | Fungsi |
|-------|--------|
| `users` | Akun dosen & mahasiswa |
| `kelas` | Data kelas/mata kuliah milik dosen |
| `enrollments` | Relasi many-to-many user ↔ kelas |
| `materi` | Konten pembelajaran (video/text/quiz) |
| `progress` | Tracking status per materi per user |
| `kuis` | Soal pilihan ganda untuk quiz |
| `hasil_kuis` | Riwayat pengerjaan quiz + nilai |

---

## 🚀 Cara Menjalankan

```bash
# 1. Install dependencies
composer install

# 2. Setup environment
copy .env.example .env
php artisan key:generate

# 3. Migrasi database
php artisan migrate --seed

# 4. Jalankan server
php artisan serve --host=0.0.0.0
# → http://localhost:8000
```

### Menjalankan Test

```bash
# Laravel API tests (42 test cases)
php artisan test --filter=Api

# Android unit tests (32 test cases)
.\gradlew.bat testDebugUnitTest
```

### Build Android APK

```bash
$env:JAVA_HOME = "C:\Program Files\Android\Android Studio\jbr"
$env:ANDROID_HOME = "$env:LOCALAPPDATA\Android\Sdk"
.\gradlew.bat assembleDebug
# Output: app\build\outputs\apk\debug\app-debug.apk
```

---

## 🛠️ Teknologi

| Layer | Teknologi |
|-------|-----------|
| Backend | Laravel 11, PHP 8.2, Eloquent ORM |
| Auth | Laravel Breeze (Web), Laravel Sanctum (API) |
| Frontend | Blade Templates, Bootstrap 5 |
| Database | MySQL 8.0 |
| Mobile | Android Java, Retrofit 2, Gson |
| Testing | PHPUnit, JUnit 4, Robolectric |

---

## 📊 Statistik Proyek

| Metrik | Nilai |
|--------|-------|
| Lines of Code | 3700+ LOC |
| Tabel database | 7 tabel |
| API Endpoints | 12 endpoints |
| Whitebox Test Cases | 74 test cases |
| APK Size | 6.4 MB |

---

*Journey Learn LMS © 2026*
