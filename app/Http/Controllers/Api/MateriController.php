<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HasilKuis;
use App\Models\Kuis;
use App\Models\Materi;
use App\Models\Progress;
use Illuminate\Http\Request;

class MateriController extends Controller
{
    /**
     * DETAIL MATERI
     * GET /api/materi/{id}
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $materi = Materi::with('kelas')->findOrFail($id);

        // Cek enrollment
        $enrolled = \App\Models\Enrollment::where('user_id', $user->id)
            ->where('kelas_id', $materi->kelas_id)
            ->exists();

        if (!$enrolled) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda belum enroll kelas ini.',
            ], 403);
        }

        // Cek sequential learning (materi sebelumnya harus selesai)
        if ($materi->urutan > 1) {
            $prevMateri = Materi::where('kelas_id', $materi->kelas_id)
                ->where('urutan', $materi->urutan - 1)
                ->first();

            if ($prevMateri) {
                $prevProgress = Progress::where('user_id', $user->id)
                    ->where('materi_id', $prevMateri->id)
                    ->where('status', 'completed')
                    ->exists();

                if (!$prevProgress) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Selesaikan materi sebelumnya terlebih dahulu.',
                        'locked' => true,
                    ], 403);
                }
            }
        }

        // Progress user untuk materi ini
        $progress = Progress::where('user_id', $user->id)
            ->where('materi_id', $id)
            ->first();

        // Data response
        $data = [
            'id' => $materi->id,
            'judul' => $materi->judul,
            'tipe' => $materi->tipe,
            'urutan' => $materi->urutan,
            'durasi_menit' => $materi->durasi_menit,
            'kelas_id' => $materi->kelas_id,
            'kelas_nama' => $materi->kelas->nama_kelas ?? '-',
            'selesai' => $progress ? ($progress->status === 'completed') : false,
        ];

        // Tambah konten sesuai tipe
        if ($materi->tipe === 'video') {
            $data['video_url'] = $this->convertToEmbedUrl($materi->konten);
        } elseif ($materi->tipe === 'text') {
            $data['konten'] = $materi->konten;
        } elseif ($materi->tipe === 'quiz') {
            // Cek attempt
            $attempts = HasilKuis::where('user_id', $user->id)
                ->where('materi_id', $id)
                ->count();

            $lastHasil = HasilKuis::where('user_id', $user->id)
                ->where('materi_id', $id)
                ->latest('id')
                ->first();

            $soalList = Kuis::where('materi_id', $id)->get()->map(function ($s) {
                return [
                    'id' => $s->id,
                    'pertanyaan' => $s->pertanyaan,
                    'pilihan_a' => $s->pilihan_a,
                    'pilihan_b' => $s->pilihan_b,
                    'pilihan_c' => $s->pilihan_c,
                    'pilihan_d' => $s->pilihan_d,
                    // TIDAK kirim jawaban_benar ke client!
                ];
            });

            $data['soal'] = $soalList;
            $data['attempts'] = $attempts;
            $data['max_attempts'] = 3;
            $data['bisa_kerjakan'] = $attempts < 3 && !($progress && $progress->status === 'completed');
            $data['last_nilai'] = $lastHasil ? $lastHasil->nilai : null;
        }

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    /**
     * MARK MATERI COMPLETE (untuk video & text)
     * POST /api/materi/{id}/complete
     */
    public function complete(Request $request, $id)
    {
        $user = $request->user();
        $materi = Materi::findOrFail($id);

        $progress = Progress::firstOrCreate(
            ['user_id' => $user->id, 'materi_id' => $id],
            ['status' => 'in_progress', 'tanggal_mulai' => now()]
        );

        $progress->update([
            'status' => 'completed',
            'tanggal_selesai' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Materi ' . $materi->judul . ' berhasil diselesaikan!',
        ]);
    }

    /**
     * SUBMIT QUIZ
     * POST /api/materi/{id}/submit-quiz
     * Body: { "jawaban": {"1": "a", "2": "b", ...} }
     */
    public function submitQuiz(Request $request, $id)
    {
        $user = $request->user();
        $materi = Materi::findOrFail($id);

        if ($materi->tipe !== 'quiz') {
            return response()->json(['status' => 'error', 'message' => 'Materi ini bukan quiz.'], 400);
        }

        // Cek max attempts
        $attempts = HasilKuis::where('user_id', $user->id)
            ->where('materi_id', $id)
            ->count();

        if ($attempts >= 3) {
            return response()->json([
                'status' => 'error',
                'message' => 'Batas percobaan quiz (3x) sudah habis.',
            ], 403);
        }

        // Cek sudah lulus (selesai)
        $alreadyPassed = Progress::where('user_id', $user->id)
            ->where('materi_id', $id)
            ->where('status', 'completed')
            ->exists();

        if ($alreadyPassed) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda sudah lulus quiz ini.',
            ], 409);
        }

        $request->validate([
            'jawaban' => 'required|array',
        ]);

        $jawabanUser = $request->jawaban; // ['soal_id' => 'a', ...]
        $soalList = Kuis::where('materi_id', $id)->get();

        $benar = 0;
        $detail = [];

        foreach ($soalList as $soal) {
            $jawabanUser_key = (string) $soal->id;
            $userJawab = strtolower($jawabanUser[$jawabanUser_key] ?? '');
            $correct = strtolower($soal->jawaban_benar);
            $isBenar = ($userJawab === $correct);

            if ($isBenar)
                $benar++;

            $detail[] = [
                'soal_id' => $soal->id,
                'pertanyaan' => $soal->pertanyaan,
                'jawaban_user' => $userJawab,
                'jawaban_benar' => $correct,
                'benar' => $isBenar,
            ];
        }

        $totalSoal = $soalList->count();
        $nilai = $totalSoal > 0 ? round(($benar / $totalSoal) * 100) : 0;
        $lulus = $nilai >= 70;

        // Simpan hasil
        HasilKuis::create([
            'user_id' => $user->id,
            'materi_id' => $id,
            'nilai' => $nilai,
            'total_soal' => $totalSoal,
            'jawaban_detail' => json_encode($detail),
        ]);

        // Jika lulus, mark progress selesai
        if ($lulus) {
            Progress::updateOrCreate(
                ['user_id' => $user->id, 'materi_id' => $id],
                ['status' => 'completed', 'tanggal_selesai' => now()]
            );
        }

        return response()->json([
            'status' => 'success',
            'nilai' => $nilai,
            'benar' => $benar,
            'total_soal' => $totalSoal,
            'lulus' => $lulus,
            'percobaan' => $attempts + 1,
            'sisa_coba' => max(0, 2 - $attempts),
            'detail' => $detail,
            'message' => $lulus
                ? '🎉 Selamat! Anda lulus dengan nilai ' . $nilai
                : '❌ Nilai ' . $nilai . '. Minimal 70 untuk lulus.',
        ]);
    }

    /**
     * Convert YouTube URL to embed URL
     */
    private function convertToEmbedUrl(string $url): string
    {
        if (str_contains($url, 'youtube.com/watch')) {
            parse_str(parse_url($url, PHP_URL_QUERY), $params);
            if (isset($params['v'])) {
                return 'https://www.youtube.com/embed/' . $params['v'];
            }
        }
        if (str_contains($url, 'youtu.be/')) {
            $videoId = explode('youtu.be/', $url)[1];
            $videoId = explode('?', $videoId)[0];
            return 'https://www.youtube.com/embed/' . $videoId;
        }
        return $url;
    }
}
