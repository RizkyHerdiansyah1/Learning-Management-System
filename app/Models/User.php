<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Disable timestamps for existing database
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'nama',
        'email',
        'password',
        'role',
        'foto_profil',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Check if user is dosen
     */
    public function isDosen(): bool
    {
        return $this->role === 'dosen';
    }

    /**
     * Check if user is mahasiswa
     */
    public function isMahasiswa(): bool
    {
        return $this->role === 'mahasiswa';
    }

    /**
     * Get kelas created by this dosen
     */
    public function kelasAsDosen()
    {
        return $this->hasMany(Kelas::class, 'dosen_id');
    }

    /**
     * Get enrollments for this mahasiswa
     */
    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * Get progress for this mahasiswa
     */
    public function progress()
    {
        return $this->hasMany(Progress::class);
    }

    /**
     * Get hasil kuis for this mahasiswa
     */
    public function hasilKuis()
    {
        return $this->hasMany(HasilKuis::class);
    }

    /**
     * Get enrolled kelas
     */
    public function enrolledKelas()
    {
        return $this->belongsToMany(Kelas::class, 'enrollment')
            ->withTimestamps();
    }
}
