<?php

namespace App\Http\Controllers;

use App\Services\IpExcelService;
use App\Services\NetworkAiAgentService;
use App\Services\NetworkScannerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class IpAiController extends Controller
{
    /**
     * Diagnose a single IP with the AI Network Agent.
     */
    public function diagnoseSingleIp(Request $request): JsonResponse
    {
        $request->validate([
            'ip' => 'required|string',
            'subnet' => 'nullable|string',
        ]);

        $ip = trim($request->input('ip'));
        $subnet = $request->input('subnet', config('app.ip_subnets.0', '172.16.250'));

        try {
            $scannerService = new NetworkScannerService($subnet);
            $excelService = app(IpExcelService::class, ['subnet' => $subnet]);
            $aiService = new NetworkAiAgentService($scannerService, $excelService, $subnet);

            $prompt = "Tolong lakukan diagnosa mendalam untuk IP {$ip}. Periksa status hidup/matinya (ping), hostname, MAC address & vendor hardware, port-port yang terbuka, serta bandingkan dengan data kepemilikan di file Excel. Berikan ringkasan temuan dan analisa kondisi perangkat tersebut.";

            $result = $aiService->runAgent($prompt);

            return response()->json($result);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kendala saat menjalankan diagnosa AI: '.$e->getMessage(),
                'steps' => [],
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Interactive Chat / Command with AI Network Copilot.
     */
    public function chat(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string',
            'history' => 'nullable|array',
            'subnet' => 'nullable|string',
        ]);

        $message = trim($request->input('message'));
        $history = $request->input('history', []);
        $subnet = $request->input('subnet', config('app.ip_subnets.0', '172.16.250'));

        try {
            $scannerService = new NetworkScannerService($subnet);
            $excelService = app(IpExcelService::class, ['subnet' => $subnet]);
            $aiService = new NetworkAiAgentService($scannerService, $excelService, $subnet);

            $result = $aiService->runAgent($message, $history);

            return response()->json($result);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kendala pada AI Copilot: '.$e->getMessage(),
                'steps' => [],
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
