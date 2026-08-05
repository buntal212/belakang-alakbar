<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Master\Saldo;
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

        // Abaikan event selain pesan
        if ($event !== 'message') {
            return response()->json(['status' => true]);
        }

        // Abaikan status WA, pesan sendiri, dan pesan kosong
        if (
            !$chatId ||
            $chatId === 'status@broadcast' ||
            $fromMe ||
            $body === ''
        ) {
            return response()->json(['status' => true]);
        }

        Log::info('Pesan WhatsApp masuk', [
            'session' => $request->input('session'),
            'from' => $chatId,
            'body' => $body,
        ]);

        if ($body === 'cek saldo') {
            $this->balasSaldo($chatId);
        }

        return response()->json([
            'status' => true,
            'message' => 'Pesan diproses',
        ]);
    }

    private function balasSaldo(string $chatId): void
    {
        /*
         * Ganti dengan nilai persis yang ada
         * pada kolom saldo.pemilik.
         */
        $pemilik = 'J000004';

        $saldo = Saldo::query()
            ->where('pemilik', $pemilik)
            ->get()
            ->keyBy(fn ($item) => strtolower(trim($item->jenis)));

        $bank = (float) ($saldo->get('bank')?->nominal ?? 0);
        $tunai = (float) ($saldo->get('tunai')?->nominal ?? 0);
        $panjar = (float) ($saldo->get('panjar')?->nominal ?? 0);

        if ($saldo->isEmpty()) {
            $this->kirimPesan(
                $chatId,
                "Data saldo untuk *{$pemilik}* tidak ditemukan."
            );

            return;
        }

        $total = $bank + $tunai + $panjar;

        $pesan = "*SALDO BENDAHARA ALAKBAR*\n\n"
            . "Pemilik: *{$pemilik}*\n\n"
            . "🏦 Bank\n"
            . "Rp " . number_format($bank, 0, ',', '.') . "\n\n"
            . "💵 Tunai\n"
            . "Rp " . number_format($tunai, 0, ',', '.') . "\n\n"
            . "📋 Panjar\n"
            . "Rp " . number_format($panjar, 0, ',', '.') . "\n\n"
            . "*Total*\n"
            . "Rp " . number_format($total, 0, ',', '.');

        $this->kirimPesan($chatId, $pesan);
    }

    private function kirimPesan(string $chatId, string $text): void
    {
        $response = Http::timeout(30)
            ->withHeaders([
                'X-Api-Key' => config('services.waha.api_key'),
            ])
            ->post(config('services.waha.url') . '/api/sendText', [
                'session' => config('services.waha.session'),
                'chatId' => $chatId,
                'text' => $text,
            ]);

        if ($response->failed()) {
            Log::error('Gagal mengirim balasan WAHA', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);
        }
    }
}
