<?php

namespace App\Http\Controllers;

use App\Services\IpExcelService;
use App\Services\NetworkAiAgentService;
use App\Services\NetworkScannerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class TeamsWebhookController extends Controller
{
    /**
     * Handle incoming webhook requests from Microsoft Teams Outgoing Webhook.
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();
        Log::info('Teams Webhook Received', ['payload' => $payload]);

        // Extract message text
        $rawText = (string) ($request->input('text') ?? '');

        // Remove XML/HTML mention tags like <at>IP Bot</at> or &nbsp;
        $cleanText = preg_replace('/<at>.*?<\/at>/is', '', $rawText);
        $cleanText = html_entity_decode(strip_tags($cleanText), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $cleanText = trim(preg_replace('/\s+/', ' ', $cleanText));

        if (empty($cleanText)) {
            $cleanText = 'Halo! Saya AI Network Copilot. Ada yang bisa saya bantu terkait jaringan IP?';
        }

        $senderName = $request->input('from.name') ?? 'User Teams';
        $subnet = config('app.ip_subnets.0', '172.16.250');

        try {
            $scannerService = new NetworkScannerService($subnet);
            $excelService = app(IpExcelService::class, ['subnet' => $subnet]);
            $aiService = new NetworkAiAgentService($scannerService, $excelService, $subnet);

            // Execute AI query
            $result = $aiService->runAgent($cleanText);
            $message = $result['message'] ?? 'Analisis selesai.';

            // Format message for MS Teams
            $teamsResponse = [
                'type' => 'message',
                'text' => $message,
            ];

            return response()->json($teamsResponse);
        } catch (Throwable $e) {
            Log::error('Teams webhook error: '.$e->getMessage());

            return response()->json([
                'type' => 'message',
                'text' => "⚠️ *Terjadi kendala teknis saat memproses:* {$e->getMessage()}",
            ]);
        }
    }
}
