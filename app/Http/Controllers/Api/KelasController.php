<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Kelas;
use Illuminate\Http\Request;

class KelasController extends Controller
{
    /**
     * LIST SEMUA KELAS
     * GET /api/kelas
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // ID kelas yang sudah di-enroll
        $enrolledIds = Enrollment::where('user_id', $user->id)
            ->pluck('kelas_id')
            ->toArray();

        $kelas = Kelas::with('dosen:id,nama')
            ->withCount('materi')
            ->get()
            ->map(function ($k) use ($enrolledIds) {
                return [
                    'id' => $k->id,
                    'nama_kelas' => $k->nama_kelas,
                    'deskripsi' => $k->deskripsi,
                    'kategori' => $k->kategori,
                    'dosen' => $k->dosen ? $k->dosen->nama : '-',
                    'jumlah_materi' => $k->materi_count,
                    'sudah_enroll' => in_array($k->id, $enrolledIds),
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $kelas,
        ]);
    }

    /**
     * DETAIL KELAS + LIST MATERI
     * GET /api/kelas/{id}
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $kelas = Kelas::with([
            'dosen:id,nama',
            'materi' => function ($q) {
                $q->orderBy('urutan');
            }
        ])->findOrFail($id);

        // Cek enrollment
        $enrolled = Enrollment::where('user_id', $user->id)
            ->where('kelas_id', $id)
            ->exists();

        // Hitung progress jika enrolled
        $totalMateri = $kelas->materi->count();
        $completedMateri = 0;

        $materiList = $kelas->materi->map(function ($m) use ($user, &$completedMateri) {
            $progress = \App\Models\Progress::where('user_id', $user->id)
                ->where('materi_id', $m->id)
                ->first();

            $selesai = $progress && $progress->status === 'completed';
            if ($selesai)
                $completedMateri++;

            return [
                'id' => $m->id,
                'judul' => $m->judul,
                'tipe' => $m->tipe,
                'urutan' => $m->urutan,
                'durasi_menit' => $m->durasi_menit,
                'selesai' => $selesai,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $kelas->id,
                'nama_kelas' => $kelas->nama_kelas,
                'deskripsi' => $kelas->deskripsi,
                'kategori' => $kelas->kategori,
                'dosen' => $kelas->dosen ? $kelas->dosen->nama : '-',
                'sudah_enroll' => $enrolled,
                'total_materi' => $totalMateri,
                'completed_materi' => $completedMateri,
                'materi' => $materiList,
            ],
        ]);
    }

    /**
     * ENROLL KELAS
     * POST /api/kelas/{id}/enroll
     */
    public function enroll(Request $request, $id)
    {
        $user = $request->user();
        $kelas = Kelas::findOrFail($id);

        // Cek sudah enroll
        $existing = Enrollment::where('user_id', $user->id)
            ->where('kelas_id', $id)
            ->first();

        if ($existing) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda sudah terdaftar di kelas ini.',
            ], 409);
        }

        // Cek role
        if ($user->isDosen()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dosen tidak bisa enroll kelas.',
            ], 403);
        }

        Enrollment::create([
            'user_id' => $user->id,
            'kelas_id' => $id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil mendaftar ke kelas ' . $kelas->nama_kelas,
        ]);
    }

    /**
     * KELAS YANG SUDAH DI-ENROLL
     * GET /api/kelas-saya
     */
    public function kelasSaya(Request $request)
    {
        $user = $request->user();

        $enrolledKelasIds = Enrollment::where('user_id', $user->id)
            ->pluck('kelas_id');

        $kelasList = Kelas::with('dosen:id,nama')
            ->withCount('materi')
            ->whereIn('id', $enrolledKelasIds)
            ->get()
            ->map(function ($k) use ($user) {
                $totalMateri = $k->materi_count;
                $done = \App\Models\Progress::where('user_id', $user->id)
                    ->whereIn('materi_id', $k->materi->pluck('id'))
                    ->where('status', 'completed')
                    ->count();

                return [
                    'id' => $k->id,
                    'nama_kelas' => $k->nama_kelas,
                    'deskripsi' => $k->deskripsi,
                    'dosen' => $k->dosen ? $k->dosen->nama : '-',
                    'total_materi' => $totalMateri,
                    'completed_materi' => $done,
                    'progress_persen' => $totalMateri > 0 ? round(($done / $totalMateri) * 100) : 0,
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $kelasList,
        ]);
    }
}
