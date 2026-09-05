<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WahaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class WahaController extends Controller
{
    public function __construct(private readonly WahaService $waha) {}

    public function status(): JsonResponse
    {
        try {
            $session = $this->waha->session();
            $account = ($session['status'] ?? null) === 'WORKING' ? $this->waha->account() : null;

            return response()->json([
                'server_online' => true,
                'session' => $this->waha->sessionName(),
                'status' => $session['status'] ?? 'NOT_CREATED',
                'phone' => $account['id'] ?? null,
                'name' => $account['pushName'] ?? $account['name'] ?? null,
            ]);
        } catch (Throwable $exception) {
            return $this->gatewayError($exception);
        }
    }

    public function createSession(): JsonResponse
    {
        try {
            $session = $this->waha->session();
            if (!$session) {
                $this->waha->createSession();
                $session = $this->waha->session();
            }

            if (in_array($session['status'] ?? 'STOPPED', ['STOPPED', 'FAILED'], true)) {
                $this->waha->start();
            }

            return response()->json(['message' => 'Sesi WhatsApp siap dihubungkan.']);
        } catch (Throwable $exception) {
            return $this->gatewayError($exception);
        }
    }

    public function start(): JsonResponse
    {
        try {
            return response()->json($this->waha->start());
        } catch (Throwable $exception) {
            return $this->gatewayError($exception);
        }
    }

    public function qr(): JsonResponse
    {
        try {
            $qr = $this->waha->qr();
            $image = $qr['data'] ?? $qr['image'] ?? $qr['qr'] ?? $qr['binary'] ?? null;

            if (!is_string($image) || $image === '') {
                Log::info('WAHA belum mengirim gambar QR.', ['fields' => array_keys($qr)]);
                return response()->json(['message' => 'QR WhatsApp belum siap.'], 422);
            }

            if (str_starts_with($image, 'data:image/')) {
                return response()->json(['image' => $image]);
            }

            return response()->json([
                'image' => 'data:' . ($qr['mimetype'] ?? 'image/png') . ';base64,' . $image,
            ]);
        } catch (Throwable $exception) {
            return $this->gatewayError($exception);
        }
    }

    public function restart(): JsonResponse
    {
        try {
            $this->waha->restart();
            return response()->json(['message' => 'Sesi WhatsApp berhasil dimulai ulang.']);
        } catch (Throwable $exception) {
            return $this->gatewayError($exception);
        }
    }

    public function logout(): JsonResponse
    {
        try {
            $this->waha->logout();
            return response()->json(['message' => 'WhatsApp berhasil logout.']);
        } catch (Throwable $exception) {
            return $this->gatewayError($exception);
        }
    }

    public function destroy(): JsonResponse
    {
        try {
            $this->waha->deleteSession();
            return response()->json(['message' => 'Sesi WhatsApp berhasil dihapus.']);
        } catch (Throwable $exception) {
            return $this->gatewayError($exception);
        }
    }

    public function sendTest(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string'],
            'message' => ['required', 'string', 'max:4096'],
        ]);
        $phone = preg_replace('/\D+/', '', $data['phone']);
        $phone = str_starts_with($phone, '0') ? '62' . substr($phone, 1) : ltrim($phone, '0');

        if (!str_starts_with($phone, '62') || strlen($phone) < 10) {
            return response()->json(['message' => 'Nomor WhatsApp tidak valid.'], 422);
        }

        try {
            $this->waha->sendText($phone, $data['message']);
            return response()->json(['message' => 'Pesan WhatsApp berhasil dikirim.']);
        } catch (Throwable $exception) {
            return $this->gatewayError($exception);
        }
    }

    private function gatewayError(Throwable $exception): JsonResponse
    {
        Log::warning('Permintaan WhatsApp Gateway gagal.', [
            'exception' => $exception->getMessage(),
            'http_status' => $exception->getCode() ?: null,
        ]);
        return response()->json(['message' => 'Server WhatsApp Gateway tidak dapat dihubungi.'], 503);
    }
}
