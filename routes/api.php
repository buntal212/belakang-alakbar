<?php

use App\Helpers\Routes\RouteHelper;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

Route::prefix('v1')->group(function () {

    RouteHelper::includeRouteFiles(base_path('routes/v1'));

});

Route::prefix('waha')->group(function () {
    RouteHelper::includeRouteFiles(base_path('routes/waha'));
});

// Route::post('/webhook/waha', function (Request $request) {
//     $event = $request->input('event');
//     $payload = $request->input('payload', []);

//     $from = $payload['from'] ?? null;
//     $body = strtolower(trim((string) ($payload['body'] ?? '')));
//     $fromMe = (bool) ($payload['fromMe'] ?? false);

//     if ($event !== 'message') {
//         return response()->json(['status' => true]);
//     }

//     if ($from === 'status@broadcast' || $fromMe || $body === '') {
//         return response()->json(['status' => true]);
//     }

//     Log::info('Pesan WhatsApp masuk', [
//         'session' => $request->input('session'),
//         'from' => $from,
//         'body' => $body,
//     ]);

//     if ($body === 'cek saldo') {
//         $balasan = "*SALDO BENDAHARA ALAKBAR*\n\n"
//             . "Saldo Bank: Rp 10.000.000\n"
//             . "Saldo Tunai: Rp 2.500.000\n"
//             . "Total: Rp 12.500.000";

//         $response = Http::withHeaders([
//             'X-Api-Key' => 'rahasia123',
//         ])->post('http://192.168.33.106:3000/api/sendText', [
//             'session' => 'alakbar',
//             'chatId' => $from,
//             'text' => $balasan,
//         ]);

//         Log::info('Balasan WAHA', [
//             'status' => $response->status(),
//             'body' => $response->body(),
//         ]);
//     }

//     return response()->json([
//         'status' => true,
//         'message' => 'Pesan diproses',
//     ]);
// });
