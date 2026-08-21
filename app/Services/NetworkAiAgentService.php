<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NetworkAiAgentService
{
    protected string $baseUrl;

    protected ?string $apiKey;

    protected string $model;

    protected int $timeout;

    protected NetworkScannerService $scannerService;

    protected IpExcelService $excelService;

    protected string $subnet;

    public function __construct(
        NetworkScannerService $scannerService,
        IpExcelService $excelService,
        ?string $subnet = null
    ) {
        $this->baseUrl = rtrim(config('services.ai_agent.base_url', 'https://api.openai.com/v1'), '/');
        $this->apiKey = config('services.ai_agent.api_key');
        $this->model = config('services.ai_agent.model', 'gpt-4o-mini');
        $this->timeout = (int) config('services.ai_agent.timeout', 60);

        $subnets = config('app.ip_subnets', ['172.16.250']);
        $this->subnet = $subnet ?? $subnets[0];

        $this->scannerService = $scannerService;
        $this->excelService = $excelService;
    }

    /**
     * Run full AI diagnostic session with Tool Calling loop.
     *
     * @param  string  $prompt  User message or system prompt
     * @param  array  $conversationHistory  Array of previous messages ['role' => 'user'|'assistant', 'content' => '...']
     * @return array ['success' => bool, 'message' => string, 'steps' => array, 'error' => ?string]
     */
    public function runAgent(string $prompt, array $conversationHistory = []): array
    {
        if (empty($this->apiKey)) {
            return [
                'success' => false,
                'message' => 'AI API Key belum dikonfigurasi. Silakan isi `AI_API_KEY`, `AI_API_BASE_URL`, dan `AI_MODEL` di file `.env`.',
                'steps' => [],
                'error' => 'API_KEY_MISSING',
            ];
        }

        $systemPrompt = $this->getSystemPrompt();

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        // Append past conversation history if available
        foreach ($conversationHistory as $msg) {
            if (isset($msg['role'], $msg['content']) && in_array($msg['role'], ['user', 'assistant', 'system'])) {
                $messages[] = [
                    'role' => $msg['role'],
                    'content' => (string) $msg['content'],
                ];
            }
        }

        // Add current user prompt
        $messages[] = ['role' => 'user', 'content' => $prompt];

        $tools = $this->getToolDefinitions();
        $executedSteps = [];
        $maxIterations = 6;
        $iteration = 0;

        try {
            while ($iteration < $maxIterations) {
                $iteration++;

                $payload = [
                    'model' => $this->model,
                    'messages' => $messages,
                    'tools' => $tools,
                    'tool_choice' => 'auto',
                    'stream' => false,
                ];

                $response = Http::withHeaders([
                    'Authorization' => 'Bearer '.$this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                    ->timeout($this->timeout)
                    ->post($this->baseUrl.'/chat/completions', $payload);

                if (! $response->successful()) {
                    $errorBody = $response->json() ?? $response->body();
                    $errorMsg = is_array($errorBody) ? ($errorBody['error']['message'] ?? json_encode($errorBody)) : (string) $errorBody;
                    Log::error('AI Agent API Error', ['status' => $response->status(), 'error' => $errorMsg]);

                    return [
                        'success' => false,
                        'message' => "Terjadi kesalahan saat menghubungi server AI (Status {$response->status()}): {$errorMsg}",
                        'steps' => $executedSteps,
                        'error' => 'API_REQUEST_FAILED',
                    ];
                }

                $data = $response->json();
                $choice = $data['choices'][0]['message'] ?? null;

                if (! $choice) {
                    return [
                        'success' => false,
                        'message' => 'Respon AI tidak valid atau kosong.',
                        'steps' => $executedSteps,
                        'error' => 'INVALID_RESPONSE',
                    ];
                }

                // If model wants to call tools
                if (! empty($choice['tool_calls'])) {
                    // Append assistant's tool_calls message
                    $messages[] = $choice;

                    foreach ($choice['tool_calls'] as $toolCall) {
                        $toolName = $toolCall['function']['name'] ?? '';
                        $toolArgsRaw = $toolCall['function']['arguments'] ?? '{}';
                        $toolArgs = json_decode($toolArgsRaw, true) ?? [];
                        $toolCallId = $toolCall['id'] ?? ('call_'.uniqid());

                        // Execute the local tool
                        $stepResult = $this->executeTool($toolName, $toolArgs);

                        $executedSteps[] = [
                            'tool' => $toolName,
                            'arguments' => $toolArgs,
                            'summary' => $stepResult['summary'] ?? 'Selesai',
                            'details' => $stepResult['data'] ?? null,
                        ];

                        // Append tool execution result back to messages
                        $messages[] = [
                            'role' => 'tool',
                            'tool_call_id' => $toolCallId,
                            'name' => $toolName,
                            'content' => json_encode($stepResult['data'] ?? $stepResult, JSON_UNESCAPED_UNICODE),
                        ];
                    }

                    // Loop back to let the model review tool results
                    continue;
                }

                // If model returned a final text answer
                $finalContent = $choice['content'] ?? 'Analisis selesai tanpa pesan tambahan.';

                return [
                    'success' => true,
                    'message' => $finalContent,
                    'steps' => $executedSteps,
                    'error' => null,
                ];
            }

            return [
                'success' => true,
                'message' => 'Analisa telah mencapai batas langkah maksimum.',
                'steps' => $executedSteps,
                'error' => null,
            ];
        } catch (Exception $e) {
            Log::error('NetworkAiAgentService exception: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat memproses perintah: '.$e->getMessage(),
                'steps' => $executedSteps,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Dispatch and execute local tools.
     */
    protected function executeTool(string $toolName, array $args): array
    {
        switch ($toolName) {
            case 'ping_ip':
                $ip = $this->sanitizeIp($args['ip'] ?? '');
                $res = $this->scannerService->pingOnly($ip);

                return [
                    'summary' => "Ping {$ip}: ".($res['is_active'] ? "ONLINE ({$res['response_time_ms']}ms)" : 'OFFLINE (RTO)'),
                    'data' => $res,
                ];

            case 'resolve_hostname':
                $ip = $this->sanitizeIp($args['ip'] ?? '');
                $res = $this->scannerService->hostnameOnly($ip);

                return [
                    'summary' => "Hostname {$ip}: ".($res['hostname'] ?: 'Tidak terdeteksi')." ({$res['device_type']})",
                    'data' => $res,
                ];

            case 'check_mac_and_vendor':
                $ip = $this->sanitizeIp($args['ip'] ?? '');
                $macRes = $this->scannerService->macOnly($ip);
                $mac = $macRes['mac_address'] ?? null;
                $vendorDetails = MacVendorLookupService::resolveDetails($mac);

                $data = [
                    'ip' => $ip,
                    'mac_address' => $mac,
                    'vendor' => $vendorDetails['vendor'] ?? 'Unknown',
                    'probable_device' => $vendorDetails['probable_device'] ?? 'Unknown Device',
                ];

                return [
                    'summary' => "MAC {$ip}: ".($mac ?: 'N/A').' | Vendor: '.($vendorDetails['vendor'] ?? 'Unknown'),
                    'data' => $data,
                ];

            case 'scan_open_ports':
                $ip = $this->sanitizeIp($args['ip'] ?? '');
                $ports = ! empty($args['ports']) && is_array($args['ports'])
                    ? array_map('intval', $args['ports'])
                    : [80, 443, 22, 135, 445, 3389, 8080, 9100];

                $open = $this->scannerService->asyncPortScan($ip, $ports, 0.4);

                return [
                    'summary' => "Port Scan {$ip}: ".(empty($open) ? 'Semua port tertutup' : 'Port terbuka: '.implode(', ', $open)),
                    'data' => [
                        'ip' => $ip,
                        'scanned_ports' => $ports,
                        'open_ports' => $open,
                    ],
                ];

            case 'lookup_excel_user':
                $query = trim((string) ($args['query'] ?? ''));
                $excelData = $this->excelService->readFirstSheet();
                $matched = [];

                foreach ($excelData as $row) {
                    $match = false;
                    $suffix = (string) $row['ip_suffix'];
                    $fullIp = $row['full_ip'];
                    $user = (string) ($row['excel_user'] ?? '');
                    $machine = (string) ($row['excel_machine'] ?? '');
                    $win = (string) ($row['excel_windows'] ?? '');

                    if ($query === $suffix || $query === $fullIp) {
                        $match = true;
                    } elseif ($query !== '' && (
                        stripos($user, $query) !== false ||
                        stripos($machine, $query) !== false ||
                        stripos($win, $query) !== false
                    )) {
                        $match = true;
                    }

                    if ($match) {
                        $matched[] = $row;
                    }
                }

                return [
                    'summary' => "Excel Query '{$query}': Ditemukan ".count($matched).' data',
                    'data' => [
                        'query' => $query,
                        'total_matches' => count($matched),
                        'results' => array_slice($matched, 0, 10), // Limit top 10
                    ],
                ];

            case 'get_subnet_overview':
                $excelData = $this->excelService->readFirstSheet();
                $cachedScan = Cache::get("ip_scan_results_{$this->subnet}", []);

                $totalIps = count($excelData);
                $onlineCount = 0;
                $activeUnmapped = 0;
                $offlineMapped = 0;
                $freeIps = 0;

                foreach ($excelData as $row) {
                    $suffix = $row['ip_suffix'];
                    $scan = $cachedScan[$suffix] ?? [];
                    $isOnline = $scan['is_active'] ?? false;
                    $excelEmpty = $row['is_excel_empty'];

                    if ($isOnline) {
                        $onlineCount++;
                    }

                    if ($isOnline && $excelEmpty) {
                        $activeUnmapped++;
                    } elseif (! $isOnline && ! $excelEmpty) {
                        $offlineMapped++;
                    } elseif (! $isOnline && $excelEmpty) {
                        $freeIps++;
                    }
                }

                $overview = [
                    'subnet' => $this->subnet,
                    'total_addresses' => $totalIps,
                    'online_count' => $onlineCount,
                    'active_unmapped_in_excel' => $activeUnmapped,
                    'offline_mapped_in_excel' => $offlineMapped,
                    'free_available_ips' => $freeIps,
                ];

                return [
                    'summary' => "Subnet {$this->subnet}: {$onlineCount} Online, {$activeUnmapped} Anomali Tanpa Excel, {$freeIps} IP Kosong",
                    'data' => $overview,
                ];

            case 'find_available_ips':
                $limit = isset($args['limit']) ? min((int) $args['limit'], 25) : 10;
                $excelData = $this->excelService->readFirstSheet();
                $cachedScan = Cache::get("ip_scan_results_{$this->subnet}", []);
                $available = [];

                foreach ($excelData as $row) {
                    $suffix = $row['ip_suffix'];
                    $scan = $cachedScan[$suffix] ?? [];
                    $isOnline = $scan['is_active'] ?? false;
                    $excelEmpty = $row['is_excel_empty'];

                    if (! $isOnline && $excelEmpty) {
                        $available[] = [
                            'ip_suffix' => $suffix,
                            'full_ip' => $row['full_ip'],
                            'status' => 'Bebas / Kosong (Offline & Kosong di Excel)',
                        ];
                        if (count($available) >= $limit) {
                            break;
                        }
                    }
                }

                return [
                    'summary' => 'Ditemukan '.count($available)." IP kosong siap pakai di subnet {$this->subnet}",
                    'data' => [
                        'subnet' => $this->subnet,
                        'total_found' => count($available),
                        'available_ips' => $available,
                    ],
                ];

            case 'full_ip_diagnose':
                $ip = $this->sanitizeIp($args['ip'] ?? '');
                $suffix = (int) substr(strrchr($ip, '.') ?: '', 1);

                // 1. Scan network
                $scanRes = $this->scannerService->pingIp($ip);
                $mac = $scanRes['mac_address'] ?? null;
                $vendorDetails = MacVendorLookupService::resolveDetails($mac);

                // 2. Lookup Excel
                $excelData = $this->excelService->readFirstSheet();
                $excelRow = null;
                foreach ($excelData as $row) {
                    if ($row['ip_suffix'] === $suffix) {
                        $excelRow = $row;
                        break;
                    }
                }

                $fullReport = [
                    'ip' => $ip,
                    'is_online' => $scanRes['is_active'] ?? false,
                    'response_time_ms' => $scanRes['response_time_ms'] ?? null,
                    'hostname' => $scanRes['hostname'] ?? null,
                    'mac_address' => $mac,
                    'vendor' => $vendorDetails['vendor'] ?? null,
                    'probable_device' => $vendorDetails['probable_device'] ?? null,
                    'open_ports' => $scanRes['open_ports'] ?? [],
                    'excel_user' => $excelRow['excel_user'] ?? null,
                    'excel_machine' => $excelRow['excel_machine'] ?? null,
                    'excel_windows' => $excelRow['excel_windows'] ?? null,
                    'is_excel_empty' => $excelRow['is_excel_empty'] ?? true,
                ];

                $statusStr = $fullReport['is_online'] ? 'ONLINE' : 'OFFLINE';

                return [
                    'summary' => "Full Diagnose {$ip}: Status {$statusStr} | User: ".($fullReport['excel_user'] ?? 'Kosong di Excel'),
                    'data' => $fullReport,
                ];

            default:
                return [
                    'summary' => "Tool {$toolName} tidak dikenal",
                    'data' => ['error' => 'Unknown tool'],
                ];
        }
    }

    /**
     * System prompt defining AI agent behavior & knowledge of the environment.
     */
    protected function getSystemPrompt(): string
    {
        return <<<PROMPT
Anda adalah **AI Network Copilot & Diagnostic Agent** untuk aplikasi IP Tracer di jaringan lokal perusahaan (Subnet aktif: {$this->subnet}.0/24).

Peran & Karakter Anda:
- Ahli Jaringan Komputer, Troubleshooting IT Support, dan Keamanan Jaringan LAN yang ramah, solutif, dan profesional.
- Anda memiliki akses langsung ke "Peralatan/Tools" teknis (Ping, Hostname Resolver, MAC & Vendor Lookup, Port Scanner, Excel Database Reader, dan Subnet Summary).
- Gunakan bahasa Indonesia yang natural, jelas, ringkas, dan terstruktur.
- Ketika user bertanya tentang status IP, perangkat bermasalah, atau data user, **SELALU panggil tools yang relevan terlebih dahulu** sebelum memberikan kesimpulan. Jangan menebak atau berhalusinasi.
- Jika ada IP online tapi data di Excel kosong, tandai sebagai potensi perangkat baru / anomali.
- Format respon Anda dengan Markdown yang cantik: gunakan bullet points, bolding pada entitas penting (IP, Nama User, Port), dan berikan rekomendasi tindakan jika ditemukan masalah teknis.
PROMPT;
    }

    /**
     * OpenAI-compatible tools definitions.
     */
    protected function getToolDefinitions(): array
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'ping_ip',
                    'description' => 'Mengecek apakah IP target aktif/hidup (online) atau mati (offline/RTO) beserta waktu latensi (ms).',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'ip' => [
                                'type' => 'string',
                                'description' => "Alamat IP lengkap (contoh: '{$this->subnet}.25') atau nomor suffix (contoh: '25').",
                            ],
                        ],
                        'required' => ['ip'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'resolve_hostname',
                    'description' => 'Mencari tahu hostname komputer/server di jaringan menggunakan NetBIOS, DNS PTR, dan reverse probe.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'ip' => [
                                'type' => 'string',
                                'description' => "Alamat IP lengkap (contoh: '{$this->subnet}.10').",
                            ],
                        ],
                        'required' => ['ip'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'check_mac_and_vendor',
                    'description' => 'Mendapatkan MAC Address perangkat dari tabel ARP serta mengidentifikasi merk/vendor hardware (misal: HP, Epson, Intel, Lenovo).',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'ip' => [
                                'type' => 'string',
                                'description' => "Alamat IP lengkap (contoh: '{$this->subnet}.50').",
                            ],
                        ],
                        'required' => ['ip'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'scan_open_ports',
                    'description' => 'Memindai port TCP tertentu yang terbuka pada IP target untuk mengetahui service aktif (seperti 80/443 HTTP, 3389 RDP, 445 SMB, 9100 Printer).',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'ip' => [
                                'type' => 'string',
                                'description' => 'Alamat IP target.',
                            ],
                            'ports' => [
                                'type' => 'array',
                                'items' => ['type' => 'integer'],
                                'description' => 'Daftar port yang ingin dicek (opsional, default: [80, 443, 22, 135, 445, 3389, 8080, 9100]).',
                            ],
                        ],
                        'required' => ['ip'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'lookup_excel_user',
                    'description' => 'Mencari data pendaftaran IP di file Excel (001. Data User IP.xlsx) berdasarkan nama user, nama mesin, versi OS, atau nomor IP.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'query' => [
                                'type' => 'string',
                                'description' => "Kata kunci pencarian, misalnya: nomor IP '25', nama user 'Budi', atau divisi.",
                            ],
                        ],
                        'required' => ['query'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_subnet_overview',
                    'description' => 'Mengambil ringkasan statistik jaringan subnet saat ini (jumlah IP online, IP offline terdaftar, IP bebas/kosong, anomali).',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => new \stdClass,
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'find_available_ips',
                    'description' => 'Mencari daftar alamat IP yang kosong / belum terpakai (bebas di Excel dan offline di jaringan) untuk dialokasikan ke perangkat atau user baru.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'limit' => [
                                'type' => 'integer',
                                'description' => 'Jumlah IP kosong yang ingin ditampilkan (opsional, default: 10).',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'full_ip_diagnose',
                    'description' => 'Menjalankan diagnosa mendalam 1 IP sekaligus (Ping, MAC, Vendor, Hostname, Port Scan, dan Data Excel) dalam satu panggilan.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'ip' => [
                                'type' => 'string',
                                'description' => "Alamat IP lengkap (contoh: '{$this->subnet}.30').",
                            ],
                        ],
                        'required' => ['ip'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Normalize IP input to full IPv4 address with current subnet.
     */
    protected function sanitizeIp(string $ip): string
    {
        $ip = trim($ip);
        if (is_numeric($ip)) {
            return "{$this->subnet}.{$ip}";
        }
        if (! str_contains($ip, '.')) {
            return "{$this->subnet}.{$ip}";
        }

        return $ip;
    }
}
