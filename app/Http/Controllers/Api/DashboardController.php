<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\HasilKuis;
use App\Models\Kelas;
use App\Models\Progress;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * DASHBOARD DATA
     * GET /api/dashboard
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->isDosen()) {
            return $this->dosenDashboard($user);
        }

        return $this->mahasiswaDashboard($user);
    }

    private function dosenDashboard($user)
    {
        $totalKelas = Kelas::where('dosen_id', $user->id)->count();

        $kelasIds = Kelas::where('dosen_id', $user->id)->pluck('id');
        $totalMahasiswa = Enrollment::whereIn('kelas_id', $kelasIds)
            ->distinct('user_id')
            ->count('user_id');

        $kelasList = Kelas::where('dosen_id', $user->id)
            ->withCount(['materi', 'enrollments'])
            ->get()
            ->map(function ($k) {
                return [
                    'id' => $k->id,
                    'nama_kelas' => $k->nama_kelas,
                    'kategori' => $k->kategori,
                    'jumlah_materi' => $k->materi_count,
                    'jumlah_mahasiswa' => $k->enrollments_count,
                ];
            });

        return response()->json([
            'status' => 'success',
            'role' => 'dosen',
            'data' => [
                'nama' => $user->nama,
                'total_kelas' => $totalKelas,
                'total_mahasiswa' => $totalMahasiswa,
                'kelas' => $kelasList,
            ],
        ]);
    }

    private function mahasiswaDashboard($user)
    {
        $enrolledIds = Enrollment::where('user_id', $user->id)->pluck('kelas_id');
        $totalEnrolled = $enrolledIds->count();

        $completedKelas = 0;
        $kelasList = Kelas::with('dosen:id,nama')
            ->withCount('materi')
            ->whereIn('id', $enrolledIds)
            ->get()
            ->map(function ($k) use ($user, &$completedKelas) {
                $materiIds = $k->materi->pluck('id');
                $totalMateri = $k->materi_count;
                $done = Progress::where('user_id', $user->id)
                    ->whereIn('materi_id', $materiIds)
                    ->where('status', 'complete')
                    ->count();

                $persen = $totalMateri > 0 ? round(($done / $totalMateri) * 100) : 0;
                if ($persen === 100)
                    $completedKelas++;

                return [
                    'id' => $k->id,
                    'nama_kelas' => $k->nama_kelas,
                    'dosen' => $k->dosen ? $k->dosen->nama : '-',
                    'total_materi' => $totalMateri,
                    'completed_materi' => $done,
                    'progress_persen' => $persen,
                ];
            });

        $totalQuizDone = HasilKuis::where('user_id', $user->id)->count();

        return response()->json([
            'status' => 'success',
            'role' => 'mahasiswa',
            'data' => [
                'nama' => $user->nama,
                'total_enrolled' => $totalEnrolled,
                'completed_kelas' => $completedKelas,
                'total_quiz_done' => $totalQuizDone,
                'kelas' => $kelasList,
            ],
        ]);
    }
}
