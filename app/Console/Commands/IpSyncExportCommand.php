<?php

namespace App\Console\Commands;

use App\Services\IpExcelService;
use App\Services\MacVendorLookupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class IpSyncExportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ip:sync-github {--subnet= : Subnet yang diekspor (default: 172.16.250)} {--token= : GitHub Personal Access Token} {--repo= : GitHub Repository (owner/repo)} {--local-only : Hanya simpan ke file lokal tanpa upload ke GitHub}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ekspor snapshot data IP (Excel + Scan) dan unggah ke GitHub repository via REST API';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $subnet = $this->option('subnet') ?: config('app.ip_subnets.0', '172.16.250');
        $repo = $this->option('repo') ?: env('GITHUB_REPO', 'efootbal1diat-collab/ip-tracer-bot');
        $token = $this->option('token') ?: env('GITHUB_TOKEN');
        $localOnly = (bool) $this->option('local-only');

        $this->info("🔄 Mengumpulkan data IP untuk subnet: {$subnet}...");

        try {
            $excelService = app(IpExcelService::class, ['subnet' => $subnet]);
            $excelData = $excelService->readFirstSheet();
            $cachedScan = Cache::get("ip_scan_results_{$subnet}", []);

            $onlineCount = 0;
            $freeCount = 0;
            $anomalyCount = 0;
            $items = [];

            foreach ($excelData as $row) {
                $suffix = $row['ip_suffix'];
                $scanInfo = $cachedScan[$suffix] ?? [
                    'status' => 'unknown',
                    'is_active' => false,
                    'hostname' => null,
                    'device_type' => 'Unknown',
                    'response_time_ms' => null,
                    'open_ports' => [],
                    'mac_address' => null,
                ];

                $mac = $scanInfo['mac_address'] ?? null;
                $macDetails = MacVendorLookupService::resolveDetails($mac);
                $isOnline = $scanInfo['is_active'] ?? false;
                $isExcelEmpty = $row['is_excel_empty'];

                if ($isOnline) {
                    $onlineCount++;
                }

                if ($isOnline && $isExcelEmpty) {
                    $statusCat = 'active_unmapped';
                    $anomalyCount++;
                } elseif (! $isOnline && ! $isExcelEmpty) {
                    $statusCat = 'offline_mapped';
                } elseif ($isOnline && ! $isExcelEmpty) {
                    $statusCat = 'active_matched';
                } else {
                    $statusCat = 'free_ip';
                    $freeCount++;
                }

                $items[] = [
                    'ip_suffix' => $suffix,
                    'full_ip' => $row['full_ip'],
                    'is_online' => $isOnline,
                    'response_time_ms' => $scanInfo['response_time_ms'] ?? null,
                    'hostname' => $scanInfo['hostname'] ?? null,
                    'device_type' => $row['excel_windows'] ?: ($scanInfo['device_type'] ?? 'Unknown'),
                    'mac_address' => $mac,
                    'vendor' => $macDetails['vendor'] ?? null,
                    'probable_device' => $macDetails['probable_device'] ?? null,
                    'open_ports' => $scanInfo['open_ports'] ?? [],
                    'excel_machine' => $row['excel_machine'] ?? null,
                    'excel_user' => $row['excel_user'] ?? null,
                    'excel_windows' => $row['excel_windows'] ?? null,
                    'is_free_ip' => ($statusCat === 'free_ip'),
                    'status_category' => $statusCat,
                ];
            }

            $payload = [
                'subnet' => $subnet,
                'last_updated' => now()->toIso8601String(),
                'last_updated_human' => now()->translatedFormat('d F Y, H:i:s').' WIB',
                'summary' => [
                    'total_ips' => count($items),
                    'online_count' => $onlineCount,
                    'free_available_ips' => $freeCount,
                    'active_unmapped_anomalies' => $anomalyCount,
                ],
                'ip_records' => $items,
            ];

            $jsonContent = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            // Save to local file in project & wa-bot
            $localExportPath = base_path('ip_data.json');
            file_put_contents($localExportPath, $jsonContent);

            $waBotDataPath = base_path('cloud-wa-bot/ip_data.json');
            if (is_dir(base_path('cloud-wa-bot'))) {
                @file_put_contents($waBotDataPath, $jsonContent);
            }

            $this->info("✅ Berhasil ekspor data lokal ke: {$localExportPath}");
            $this->info("   📊 Summary: {$onlineCount} Online | {$freeCount} IP Kosong | {$anomalyCount} Anomali");

            if ($localOnly) {
                return 0;
            }

            // Upload to GitHub via GitHub REST API
            if (empty($token)) {
                $this->warn('⚠️ GITHUB_TOKEN belum diisi di .env. File lokal sudah siap di ip_data.json.');
                $this->line('   Untuk upload otomatis, isi GITHUB_TOKEN=ghp_xxx dan GITHUB_REPO='.$repo.' di file .env');

                return 0;
            }

            $this->info("🚀 Mengunggah ip_data.json ke GitHub repository: {$repo}...");

            $filePath = 'ip_data.json';
            $apiUrl = "https://api.github.com/repos/{$repo}/contents/{$filePath}";

            // Check if file already exists on GitHub to get SHA (required for update)
            $getRes = Http::withoutVerifying()->withHeaders([
                'Authorization' => "token {$token}",
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'Laravel-IpTracer-Sync',
            ])->get($apiUrl);

            $sha = null;
            if ($getRes->successful()) {
                $sha = $getRes->json()['sha'] ?? null;
            }

            $body = [
                'message' => 'Update snapshot data IP: '.now()->format('Y-m-d H:i:s'),
                'content' => base64_encode($jsonContent),
            ];

            if ($sha) {
                $body['sha'] = $sha;
            }

            $putRes = Http::withoutVerifying()->withHeaders([
                'Authorization' => "token {$token}",
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'Laravel-IpTracer-Sync',
            ])->put($apiUrl, $body);

            if ($putRes->successful()) {
                $this->info("🎉 SUKSES! Data IP berhasil di-update di GitHub: https://github.com/{$repo}");
            } else {
                $this->error('❌ Gagal upload ke GitHub: '.($putRes->json()['message'] ?? $putRes->body()));

                return 1;
            }

            return 0;
        } catch (Throwable $e) {
            $this->error('Error: '.$e->getMessage());

            return 1;
        }
    }
}
