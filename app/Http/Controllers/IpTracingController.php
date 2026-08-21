<?php

namespace App\Http\Controllers;

use App\Services\IpExcelService;
use App\Services\MacVendorLookupService;
use App\Services\NetworkScannerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class IpTracingController extends Controller
{
    protected IpExcelService $excelService;

    protected NetworkScannerService $scannerService;

    public function __construct(IpExcelService $excelService, NetworkScannerService $scannerService)
    {
        $this->excelService = $excelService;
        $this->scannerService = $scannerService;
    }

    /**
     * Preserve hostname, MAC, device type & ports from previous cache, then merge new scan data.
     */
    private function mergeScanResult(array &$cachedScan, int $suffix, array $newData): void
    {
        $existing = $cachedScan[$suffix] ?? [];

        if (empty($newData['hostname']) && ! empty($existing['hostname'])) {
            $newData['hostname'] = $existing['hostname'];
        }
        if (empty($newData['mac_address']) && ! empty($existing['mac_address'])) {
            $newData['mac_address'] = $existing['mac_address'];
        }
        if ((empty($newData['device_type']) || $newData['device_type'] === 'Unknown') && ! empty($existing['device_type']) && $existing['device_type'] !== 'Unknown') {
            $newData['device_type'] = $existing['device_type'];
        }
        if (empty($newData['open_ports']) && ! empty($existing['open_ports'])) {
            $newData['open_ports'] = $existing['open_ports'];
        }

        $cachedScan[$suffix] = array_merge($existing, $newData);
    }

    /**
     * Display Main Dashboard View
     */
    public function index(Request $request)
    {
        $subnets = config('app.ip_subnets', ['172.16.250']);
        $activeSubnet = $request->query('subnet', $subnets[0]);
        if (! in_array($activeSubnet, $subnets)) {
            $activeSubnet = $subnets[0];
        }

        $this->excelService = app(IpExcelService::class, ['subnet' => $activeSubnet]);

        $excelData = $this->excelService->readFirstSheet();
        $cachedScan = Cache::get("ip_scan_results_{$activeSubnet}", []);

        // Merge Excel data with cached scan results
        $combinedData = array_map(function ($excelRow) use ($cachedScan) {
            $suffix = $excelRow['ip_suffix'];
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

            $excelEmpty = $excelRow['is_excel_empty'];

            // Online status from last scan cache only (no ARP to avoid stale data)
            $isOnline = $scanInfo['is_active'] ?? false;

            if ($isOnline && ! $excelEmpty) {
                $statusCategory = 'active_matched'; // Hijau: Online & Terdaftar di Excel
            } elseif ($isOnline && $excelEmpty) {
                $statusCategory = 'active_unmapped'; // Kuning: Online di Jaringan, tapi Excel Masih Kosong!
            } elseif (! $isOnline && ! $excelEmpty) {
                $statusCategory = 'offline_mapped'; // Abu: Terdaftar di Excel, tapi perangkat sedang Offline
            } else {
                $statusCategory = 'free_ip'; // Abu/Putih: IP Kosong (Tidak ada di Excel & Offline)
            }

            return array_merge($excelRow, [
                'scan_status' => $isOnline ? 'online' : 'offline',
                'is_active' => $isOnline,
                'hostname' => $scanInfo['hostname'] ?? null,
                'device_type' => $excelRow['excel_windows'] ?: ($scanInfo['device_type'] ?? 'Unknown'),
                'vendor' => $macDetails['vendor'] ?? null,
                'probable_device' => $macDetails['probable_device'] ?? null,
                'response_time_ms' => $scanInfo['response_time_ms'] ?? null,
                'mac_address' => $mac,
                'status_category' => $statusCategory,
            ]);
        }, $excelData);

        return view('ip_tracing.dashboard', [
            'ipList' => $combinedData,
            'lastScanTime' => Cache::get("last_scan_timestamp_{$activeSubnet}", 'Belum pernah dipindai'),
            'summaryStats' => [
                'total' => 254,
                'active_count' => count(array_filter($combinedData, fn ($i) => $i['is_active'])),
                'unmapped_active' => count(array_filter($combinedData, fn ($i) => $i['status_category'] === 'active_unmapped')),
                'free_count' => count(array_filter($combinedData, fn ($i) => $i['status_category'] === 'free_ip')),
                'excel_mapped' => count(array_filter($combinedData, fn ($i) => ! $i['is_excel_empty'])),
            ],
            'subnets' => $subnets,
            'activeSubnet' => $activeSubnet,
        ]);
    }

    /**
     * Batch Range Scan API (with hostname resolution via NetBIOS + DNS)
     */
    public function scanRange(Request $request)
    {
        // Extend timeout — parallel ping + hostname resolution can take time
        // Using 0 disables PHP timeout limit (not recommended for production, but needed for long scans)
        set_time_limit(0);

        $start = (int) $request->input('start', 1);
        $end = (int) $request->input('end', 254);

        $subnets = config('app.ip_subnets', ['172.16.250']);
        $activeSubnet = $request->input('subnet', $subnets[0]);
        $scannerService = app(NetworkScannerService::class, ['subnet' => $activeSubnet]);

        $results = $scannerService->scanSubnetRange($start, $end);

        $cacheKey = "ip_scan_results_{$activeSubnet}";
        $cachedScan = Cache::get($cacheKey, []);
        foreach ($results as $suffix => $scanData) {
            $this->mergeScanResult($cachedScan, $suffix, $scanData);
        }

        Cache::put($cacheKey, $cachedScan, now()->addDays(7));
        Cache::put("last_scan_timestamp_{$activeSubnet}", now()->format('d M Y H:i:s'));

        $activeCount = count(array_filter($results, fn ($r) => $r['is_active']));

        return response()->json([
            'success' => true,
            'message' => "Pemindaian IP range {$start} - {$end} selesai. {$activeCount} perangkat aktif terdeteksi.",
            'scanned_count' => count($results),
            'active_count' => $activeCount,
            'hostname_stats' => [
                'resolved' => count(array_filter($results, fn ($r) => ! empty($r['hostname']))),
                'unresolved' => count(array_filter($results, fn ($r) => $r['is_active'] && empty($r['hostname']))),
            ],
        ]);
    }

    /**
     * Single IP Scan API — Scan satu IP dan kembalikan hasil detail
     */
    public function scanSingleIp(Request $request)
    {
        // Extend timeout for network operations (ping, NetBIOS, DNS)
        set_time_limit(30);

        $request->validate([
            'ip_suffix' => 'required|integer|min:1|max:254',
            'subnet' => 'nullable|string',
        ]);

        $ipSuffix = (int) $request->ip_suffix;

        $subnets = config('app.ip_subnets', ['172.16.250']);
        $subnet = $request->input('subnet', $subnets[0]);
        $ip = "{$subnet}.{$ipSuffix}";

        $scannerService = app(NetworkScannerService::class, ['subnet' => $subnet]);

        try {
            Log::info("Scanning IP: {$ip}");
            $result = $scannerService->pingIp($ip);
        } catch (\Throwable $e) {
            Log::error("Scan error for {$ip}: ".$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error scanning IP: '.$e->getMessage(),
            ], 500);
        }

        // Resolve MAC vendor
        $macDetails = MacVendorLookupService::resolveDetails($result['mac_address'] ?? null);

        // Update cache
        $cacheKey = "ip_scan_results_{$subnet}";
        $cachedScan = Cache::get($cacheKey, []);
        $this->mergeScanResult($cachedScan, $ipSuffix, [
            'status' => $result['status'],
            'is_active' => $result['is_active'],
            'hostname' => $result['hostname'],
            'device_type' => $result['device_type'],
            'response_time_ms' => $result['response_time_ms'],
            'mac_address' => $result['mac_address'],
        ]);
        Cache::put($cacheKey, $cachedScan, now()->addDays(7));

        return response()->json([
            'success' => true,
            'ip' => $ip,
            'ip_suffix' => $ipSuffix,
            'status' => $result['status'],
            'is_active' => $result['is_active'],
            'hostname' => $result['hostname'],
            'device_type' => $result['device_type'],
            'response_time' => $result['response_time_ms'] ? "{$result['response_time_ms']}ms" : null,
            'open_ports' => $result['open_ports'] ?? [],
            'mac_address' => $result['mac_address'],
            'vendor' => $macDetails['vendor'] ?? null,
            'probable_device' => $macDetails['probable_device'] ?? null,
        ]);
    }

    // ──────────────────────────────────────────────
    //  SEPARATE SCAN ENDPOINTS (Focused & Fast)
    // ──────────────────────────────────────────────

    /**
     * Ping Only — Fast online/offline check + automatic instant MAC & Vendor capture from ARP
     */
    public function pingSingleIp(Request $request)
    {
        set_time_limit(5);

        $request->validate([
            'ip_suffix' => 'required|integer|min:1|max:254',
            'subnet' => 'nullable|string',
        ]);

        $ipSuffix = (int) $request->ip_suffix;
        $subnets = config('app.ip_subnets', ['172.16.250']);
        $subnet = $request->input('subnet', $subnets[0]);
        $ip = "{$subnet}.{$ipSuffix}";

        $scannerService = app(NetworkScannerService::class, ['subnet' => $subnet]);

        try {
            $result = $scannerService->pingOnly($ip);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: '.$e->getMessage(),
            ], 500);
        }

        $macAddress = $result['mac_address'] ?? null;
        $macDetails = MacVendorLookupService::resolveDetails($macAddress);

        // Update cache
        $cacheKey = "ip_scan_results_{$subnet}";
        $cachedScan = Cache::get($cacheKey, []);
        $this->mergeScanResult($cachedScan, $ipSuffix, [
            'status' => $result['is_active'] ? 'online' : 'offline',
            'is_active' => $result['is_active'],
            'response_time_ms' => $result['response_time_ms'],
            'mac_address' => $macAddress,
        ]);
        Cache::put($cacheKey, $cachedScan, now()->addDays(7));

        $merged = $cachedScan[$ipSuffix] ?? [];
        $effectiveMac = $merged['mac_address'] ?? $macAddress;
        $effectiveVendor = MacVendorLookupService::resolveDetails($effectiveMac);

        return response()->json([
            'success' => true,
            'ip' => $ip,
            'ip_suffix' => $ipSuffix,
            'is_active' => $result['is_active'],
            'status' => $result['is_active'] ? 'online' : 'offline',
            'response_time' => $result['response_time_ms'] ? "{$result['response_time_ms']}ms" : null,
            'mac_address' => $effectiveMac,
            'vendor' => $effectiveVendor['vendor'] ?? null,
            'probable_device' => $effectiveVendor['probable_device'] ?? null,
        ]);
    }

    /**
     * Hostname Only — Resolve hostname + OS (medium speed)
     */
    public function hostnameSingleIp(Request $request)
    {
        set_time_limit(10);

        $request->validate([
            'ip_suffix' => 'required|integer|min:1|max:254',
            'subnet' => 'nullable|string',
        ]);

        $ipSuffix = (int) $request->ip_suffix;
        $subnets = config('app.ip_subnets', ['172.16.250']);
        $subnet = $request->input('subnet', $subnets[0]);
        $ip = "{$subnet}.{$ipSuffix}";

        $scannerService = app(NetworkScannerService::class, ['subnet' => $subnet]);

        try {
            $result = $scannerService->hostnameOnly($ip);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: '.$e->getMessage(),
            ], 500);
        }

        // Update cache (merge preserves existing hostname if new result is null)
        $cacheKey = "ip_scan_results_{$subnet}";
        $cachedScan = Cache::get($cacheKey, []);
        $this->mergeScanResult($cachedScan, $ipSuffix, [
            'hostname' => $result['hostname'],
            'device_type' => $result['device_type'],
            'open_ports' => $result['open_ports'],
        ]);
        Cache::put($cacheKey, $cachedScan, now()->addDays(7));

        // Return merged result (uses cached hostname if scan returned null)
        $merged = $cachedScan[$ipSuffix] ?? $result;

        return response()->json([
            'success' => true,
            'ip' => $ip,
            'hostname' => $merged['hostname'] ?? $result['hostname'],
            'device_type' => $merged['device_type'] ?? $result['device_type'],
            'open_ports' => $merged['open_ports'] ?? $result['open_ports'],
        ]);
    }

    /**
     * MAC Only — Get MAC address + vendor info
     */
    public function macSingleIp(Request $request)
    {
        set_time_limit(5);

        $request->validate([
            'ip_suffix' => 'required|integer|min:1|max:254',
            'subnet' => 'nullable|string',
        ]);

        $ipSuffix = (int) $request->ip_suffix;
        $subnets = config('app.ip_subnets', ['172.16.250']);
        $subnet = $request->input('subnet', $subnets[0]);
        $ip = "{$subnet}.{$ipSuffix}";

        $scannerService = app(NetworkScannerService::class, ['subnet' => $subnet]);

        try {
            $result = $scannerService->macOnly($ip);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: '.$e->getMessage(),
            ], 500);
        }

        // Resolve MAC vendor
        $macDetails = MacVendorLookupService::resolveDetails($result['mac_address'] ?? null);

        // Update cache
        $cacheKey = "ip_scan_results_{$subnet}";
        $cachedScan = Cache::get($cacheKey, []);
        $this->mergeScanResult($cachedScan, $ipSuffix, [
            'mac_address' => $result['mac_address'],
        ]);
        Cache::put($cacheKey, $cachedScan, now()->addDays(7));

        return response()->json([
            'success' => true,
            'ip' => $ip,
            'mac_address' => $result['mac_address'],
            'vendor' => $macDetails['vendor'] ?? null,
            'probable_device' => $macDetails['probable_device'] ?? null,
        ]);
    }

    /**
     * Update Excel Entry from Web UI
     */
    public function updateExcel(Request $request)
    {
        $request->validate([
            'ip_suffix' => 'required|integer|min:1|max:254',
            'machine' => 'nullable|string',
            'user' => 'nullable|string',
            'windows' => 'nullable|string',
            'subnet' => 'nullable|string',
        ]);

        $subnets = config('app.ip_subnets', ['172.16.250']);
        $subnet = $request->input('subnet', $subnets[0]);
        $excelService = app(IpExcelService::class, ['subnet' => $subnet]);

        $success = $excelService->updateIpRecord(
            (int) $request->ip_suffix,
            $request->machine,
            $request->user,
            $request->windows
        );

        return response()->json([
            'success' => $success,
            'message' => $success ? "Data Excel untuk IP {$subnet}.{$request->ip_suffix} berhasil diperbarui!" : 'Gagal mengupdate Excel.',
        ]);
    }
}
