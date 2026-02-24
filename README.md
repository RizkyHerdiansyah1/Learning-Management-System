# 🎓 Journey Learn LMS

**Sistem Learning Management System (LMS) berbasis Laravel 11**

Platform e-learning modern untuk mengelola kelas, materi, dan kuis secara online. Dibangun dengan Laravel 11 dan Tailwind CSS.

![Laravel](https://img.shields.io/badge/Laravel-11-red?logo=laravel)
![PHP](https://img.shields.io/badge/PHP-8.2+-blue?logo=php)
![License](https://img.shields.io/badge/License-MIT-green)

---

## ✨ Fitur Utama

### 👨‍🏫 Dosen
- Membuat dan mengelola kelas
- Upload materi (Video YouTube, Teks, Quiz)
- Preview video YouTube secara langsung
- Membuat soal quiz pilihan ganda
- Melihat nilai mahasiswa

### 👨‍🎓 Mahasiswa
- Mengikuti kelas yang tersedia
- Menonton video materi
- Sequential learning (materi harus diselesaikan berurutan)
- Mengerjakan quiz dengan batas waktu
- Melihat hasil dan nilai

---

## 🛠️ Teknologi

| Komponen | Teknologi |
|----------|-----------|
| Backend | Laravel 11, PHP 8.2+ |
| Frontend | Blade Templates, Tailwind CSS |
| Database | MySQL 5.7+ / MariaDB |
| Authentication | Laravel Breeze |

---

## 📦 Instalasi

### Prasyarat
- PHP 8.2 atau lebih tinggi
- Composer
- MySQL / MariaDB
- Node.js & NPM (opsional, untuk asset)

### Langkah Instalasi

```bash
# 1. Clone repository
git clone https://github.com/USERNAME/journey-learn-lms.git
cd journey-learn-lms

# 2. Install dependencies PHP
composer install

# 3. Copy file environment
cp .env.example .env

# 4. Generate application key
php artisan key:generate

# 5. Konfigurasi database di file .env
# Ubah DB_DATABASE, DB_USERNAME, DB_PASSWORD sesuai setting Anda

# 6. Jalankan migration (opsional jika database sudah ada)
php artisan migrate

# 7. Jalankan server development
php artisan serve
```

### Akses Aplikasi
Buka browser di `http://localhost:8000`

---

## 📁 Struktur Project

```
journey-learn-lms/
├── app/
│   ├── Http/Controllers/    # Controller (Dashboard, Kelas, Materi)
│   ├── Models/              # Eloquent Models
│   └── Http/Middleware/     # Role Middleware
├── database/
│   └── migrations/          # Database migrations
├── resources/views/
│   ├── auth/                # Login, Register
│   ├── dashboard/           # Dashboard Dosen & Mahasiswa
│   ├── kelas/               # Manajemen Kelas
│   └── materi/              # Manajemen Materi & Quiz
├── routes/
│   └── web.php              # Route definitions
└── docs/                    # Dokumentasi tambahan
```

---

## 🗄️ Database Schema

| Tabel | Deskripsi |
|-------|-----------|
| `users` | Data pengguna (dosen/mahasiswa) |
| `kelas` | Daftar kelas pembelajaran |
| `enrollment` | Pendaftaran mahasiswa ke kelas |
| `materi` | Materi pembelajaran |
| `progress` | Progress mahasiswa per materi |
| `kuis` | Soal-soal quiz |
| `hasil_kuis` | Hasil pengerjaan quiz |

---

## 👥 Role & Akses

| Fitur | Dosen | Mahasiswa |
|-------|:-----:|:---------:|
| Dashboard | ✅ | ✅ |
| Buat Kelas | ✅ | ❌ |
| Upload Materi | ✅ | ❌ |
| Lihat Materi | ✅ | ✅ |
| Kerjakan Quiz | ❌ | ✅ |
| Lihat Nilai | ✅ | ✅ |

---

## 🤝 Kontribusi

Kontribusi sangat diterima! Silakan:
1. Fork repository ini
2. Buat branch fitur (`git checkout -b fitur/AmazingFeature`)
3. Commit perubahan (`git commit -m 'Menambah fitur amazing'`)
4. Push ke branch (`git push origin fitur/AmazingFeature`)
5. Buat Pull Request

---

## 📄 Lisensi

Didistribusikan di bawah Lisensi MIT. Lihat `LICENSE` untuk informasi lebih lanjut.

---

## 📞 Kontak

Dibuat dengan ❤️ untuk keperluan pembelajaran

