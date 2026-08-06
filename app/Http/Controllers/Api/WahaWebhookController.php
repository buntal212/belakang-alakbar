<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Master\Saldo;
use App\Models\WahaBendahara;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WahaWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $event = $request->input('event');
        $payload = $request->input('payload', []);

        $chatId = $payload['from'] ?? null;
        $body = strtolower(trim((string) ($payload['body'] ?? '')));
        $fromMe = (bool) ($payload['fromMe'] ?? false);

        if (str_ends_with($chatId, '@g.us')) {
            return response()->json([
                'status' => true,
                'message' => 'Pesan grup diabaikan',
            ]);
        }

        if ($event !== 'message') {
            return response()->json([
                'status' => true,
                'message' => 'Event diabaikan',
            ]);
        }

        if (
            !$chatId ||
            $chatId === 'status@broadcast' ||
            $fromMe ||
            $body === ''
        ) {
            return response()->json([
                'status' => true,
                'message' => 'Pesan diabaikan',
            ]);
        }

        Log::info('Pesan WhatsApp masuk', [
            'session' => $request->input('session'),
            'from' => $chatId,
            'body' => $body,
        ]);

        $bendahara = WahaBendahara::query()
            ->join(
                'm_jabatan',
                'm_jabatan.kode',
                '=',
                'waha_bendahara.pemilik'
            )
            ->where('waha_bendahara.chat_id', $chatId)
            ->where('waha_bendahara.aktif', true)
            ->select([
                'waha_bendahara.id',
                'waha_bendahara.chat_id',
                'waha_bendahara.nomor_wa',
                'waha_bendahara.aktif',
                'waha_bendahara.pemilik',
                'waha_bendahara.nama',
                'm_jabatan.kode as kode_jabatan',
                'm_jabatan.jabatan as nama_jabatan',
            ])
            ->first();

        // if (!$bendahara) {
        //     $this->kirimPesan(
        //         $chatId,
        //         'Nomor WhatsApp Anda belum terdaftar sebagai bendahara.'
        //     );

        //     return response()->json([
        //         'status' => true,
        //         'message' => 'Pengirim belum terdaftar',
        //     ]);
        // }

        if (!$bendahara) {
            Log::info('Pengirim WAHA belum terdaftar', [
                'chat_id' => $chatId,
                'body' => $body,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Pengirim belum terdaftar dan diabaikan',
            ]);
        }

        if ($body === 'cek saldo') {
            $this->balasSaldo(
                chatId: $chatId,
                pemilik: $bendahara->kode_jabatan,
                namaBendahara: $bendahara->nama,
                namaJabatan: $bendahara->nama_jabatan
            );
        }

        return response()->json([
            'status' => true,
            'message' => 'Pesan diproses',
        ]);
    }

    private function balasSaldo(
    string $chatId,
    string $pemilik,
    string $namaBendahara,
    ?string $namaJabatan = null
    ): void {
        $saldo = Saldo::query()
            ->where('pemilik', $pemilik)
            ->get()
            ->keyBy(
                fn ($item) => strtolower(trim((string) $item->jenis))
            );

        if ($saldo->isEmpty()) {
            $this->kirimPesan(
                $chatId,
                "Data saldo untuk jabatan *{$pemilik}* tidak ditemukan."
            );

            return;
        }

        $bank = (float) ($saldo->get('bank')?->nominal ?? 0);
        $tunai = (float) ($saldo->get('tunai')?->nominal ?? 0);
        $panjar = (float) ($saldo->get('panjar')?->nominal ?? 0);

        $total = $bank + $tunai + $panjar;

        $labelJabatan = $namaJabatan
            ? "Jabatan: *{$namaJabatan}*\n"
            : "Jabatan: *{$pemilik}*\n";

        $pesan = "*💰 SALDO BENDAHARA ALAKBAR*\n\n"
                . "👤 Nama Bendahara: *{$namaBendahara}*\n"
                . $labelJabatan
                . "\n"
                . "🏦 Bank\n"
                . "Rp " . number_format($bank, 0, ',', '.') . "\n\n"
                . "💵 Tunai\n"
                . "Rp " . number_format($tunai, 0, ',', '.') . "\n\n"
                . "📋 Panjar\n"
                . "Rp " . number_format($panjar, 0, ',', '.') . "\n\n"
                . "━━━━━━━━━━━━━━━\n"
                . "💵 *Total Saldo*\n"
                . "Rp " . number_format($total, 0, ',', '.');

        $this->kirimPesan($chatId, $pesan);
    }

    private function kirimPesan(
        string $chatId,
        string $text
    ): void {
        $url = rtrim(
            (string) config('services.waha.url'),
            '/'
        );

        $apiKey = config('services.waha.api_key');
        $session = config('services.waha.session');

        if (!$url || !$apiKey || !$session) {
            Log::error('Konfigurasi WAHA belum lengkap', [
                'url' => $url,
                'session' => $session,
                'api_key_tersedia' => !empty($apiKey),
            ]);

            return;
        }

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'X-Api-Key' => $apiKey,
                    'Accept' => 'application/json',
                ])
                ->post($url . '/api/sendText', [
                    'session' => $session,
                    'chatId' => $chatId,
                    'text' => $text,
                ]);

            if ($response->failed()) {
                Log::error('Gagal mengirim balasan WAHA', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'chat_id' => $chatId,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Koneksi ke WAHA gagal', [
                'message' => $e->getMessage(),
                'chat_id' => $chatId,
            ]);
        }
    }
}
