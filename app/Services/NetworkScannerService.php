<?php

namespace App\Services;

/**
 * NetworkScannerService — Enterprise-grade IP scanning & hostname resolution
 *
 * Online Detection Pipeline (optimized for accuracy):
 *   1. Parallel ICMP Ping sweep  — most reliable LAN detection
 *   2. ARP table refresh         — fresh MAC after ping sweep
 *   3. TCP port fallback         — catches devices blocking ICMP
 *
 * Hostname Detection Pipeline (Corporate LAN Optimized):
 *   1. NetBIOS (nbtstat -A)  — fastest & most reliable for Windows PCs in domain/LAN
 *   2. DNS Reverse (nslookup) — reliable for servers with PTR records, with process timeout
 *   3. Ping -a Reverse DNS   — fallback using Windows ping reverse resolution
 *
 * All external commands use process-level timeout to prevent PHP from hanging.
 */
class NetworkScannerService
{
    protected string $subnet;

    /** Process-level timeout for external commands (seconds) */
    protected const CMD_TIMEOUT = 2;

    /** How many concurrent ping processes to run in parallel */
    protected const PING_CONCURRENCY = 50;

    /** How many concurrent hostname resolution processes to run */
    protected const HOSTNAME_CONCURRENCY = 30;

    public function __construct(?string $subnet = null)
    {
        $subnets = config('app.ip_subnets', ['172.16.250']);
        $this->subnet = $subnet ?? $subnets[0];
    }

    // ──────────────────────────────────────────────
    //  ARP TABLE
    // ──────────────────────────────────────────────

    /**
     * Get system ARP table (IP => MAC) from Windows ARP cache for the current subnet
     */
    public function getArpTable(): array
    {
        $arpTable = [];
        $output = [];
        exec('arp -a', $output);

        $escapedSubnet = preg_quote($this->subnet, '/');

        foreach ($output as $line) {
            if (preg_match('/('.$escapedSubnet.'\.\d+)\s+([0-9a-fA-F-]{17})/i', $line, $matches) ||
                preg_match('/(\d+\.\d+\.\d+\.\d+)\s+([0-9a-fA-F-]{17})/i', $line, $matches)) {
                $ip = $matches[1];
                if (! str_starts_with($ip, $this->subnet.'.')) {
                    continue;
                }
                $mac = strtoupper(str_replace('-', ':', $matches[2]));
                // Skip broadcast/invalid MACs
                if ($mac !== 'FF:FF:FF:FF:FF:FF' && $mac !== '00:00:00:00:00:00') {
                    $arpTable[$ip] = $mac;
                }
            }
        }

        return $arpTable;
    }

    // ──────────────────────────────────────────────
    //  EXEC WITH TIMEOUT (Process-Level)
    // ──────────────────────────────────────────────

    /**
     * Run a command with process-level timeout.
     * Uses proc_open + non-blocking reads with hard timeout enforcement.
     * On timeout, force-kills the process tree via taskkill.
     *
     * @param  string  $command  Shell command to execute
     * @param  int  $timeout  Max execution time in seconds
     * @return array ['output' => string, 'exitCode' => int]
     */
    private function execWithTimeout(string $command, int $timeout = self::CMD_TIMEOUT): array
    {
        $wrappedCmd = "cmd /c \"{$command}\"";

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($wrappedCmd, $descriptors, $pipes);
        if (! is_resource($process)) {
            return ['output' => '', 'exitCode' => -1];
        }

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $output = '';
        $startTime = microtime(true);

        // Non-blocking poll loop with hard timeout
        while (true) {
            $elapsed = microtime(true) - $startTime;
            if ($elapsed >= $timeout) {
                break;
            }

            // Check if process already exited
            $status = proc_get_status($process);
            if (! $status['running']) {
                stream_set_blocking($pipes[1], true);
                stream_set_blocking($pipes[2], true);
                $output .= @stream_get_contents($pipes[1]);
                $output .= @stream_get_contents($pipes[2]);
                break;
            }

            // Try non-blocking read
            $chunk1 = @fread($pipes[1], 8192);
            $chunk2 = @fread($pipes[2], 8192);
            if ($chunk1 !== false && $chunk1 !== '') {
                $output .= $chunk1;
            }
            if ($chunk2 !== false && $chunk2 !== '') {
                $output .= $chunk2;
            }

            usleep(3000); // 3ms between reads (faster response)
        }

        // ── Force kill if still running after timeout ──
        $status = proc_get_status($process);
        if ($status['running']) {
            $pid = $status['pid'];
            // Kill process tree forcefully (cmd.exe + child processes)
            exec("taskkill /PID {$pid} /T /F 2>NUL");
            usleep(50000); // Wait for process to be killed
        }

        @fclose($pipes[0]);
        @fclose($pipes[1]);
        @fclose($pipes[2]);
        $exitCode = proc_close($process);

        return [
            'output' => $output,
            'exitCode' => $exitCode,
        ];
    }

    // ──────────────────────────────────────────────
    //  HOSTNAME RESOLUTION — Central Pipeline
    // ──────────────────────────────────────────────

    /**
     * Resolve hostname for a single IP using multi-method pipeline.
     *
     * Methods are tried in order of speed & reliability for corporate LAN:
     *   1. NetBIOS  — fastest for Windows PCs (broadcast-based, no DNS needed)
     *   2. DNS      — nslookup reverse lookup, reliable for servers with PTR records
     *   3. Ping -a  — Windows ping reverse resolution (uses DNS + NetBIOS cache)
     *
     * @param  string  $ip  Full IP address (e.g. 172.16.250.1)
     * @return string|null Hostname or null if unresolved
     */
    public function resolveHostname(string $ip): ?string
    {
        // Launch ALL 3 methods for the single IP concurrently for speed
        $methods = ['nbtstat', 'nslookup', 'pinga'];
        $processes = [];

        foreach ($methods as $method) {
            $cmd = match ($method) {
                'nbtstat' => "cmd /c \"nbtstat -A {$ip}\"",
                'nslookup' => "cmd /c \"nslookup {$ip}\"",
                'pinga' => "cmd /c \"ping -a -n 1 -w 800 {$ip}\"",
            };

            $descriptors = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ];

            $proc = proc_open($cmd, $descriptors, $pipes);
            if (is_resource($proc)) {
                stream_set_blocking($pipes[1], false);
                stream_set_blocking($pipes[2], false);
                $processes[$method] = [
                    'proc' => $proc,
                    'pipes' => $pipes,
                    'output' => '',
                ];
            }
        }

        $batchStart = microtime(true);
        $batchTimeout = self::CMD_TIMEOUT + 0.5;

        while (! empty($processes)) {
            if ((microtime(true) - $batchStart) >= $batchTimeout) {
                break;
            }

            foreach ($processes as $method => &$pData) {
                // Read available output
                $chunk = @fread($pData['pipes'][1], 8192);
                if ($chunk !== false && $chunk !== '') {
                    $pData['output'] .= $chunk;
                }

                $status = proc_get_status($pData['proc']);
                if (! $status['running']) {
                    stream_set_blocking($pData['pipes'][1], true);
                    $pData['output'] .= @stream_get_contents($pData['pipes'][1]);

                    $hostname = match ($method) {
                        'nbtstat' => $this->parseNbtstatOutput($pData['output']),
                        'nslookup' => $this->parseNslookupOutput($pData['output'], $ip),
                        'pinga' => $this->parsePingAOutput($pData['output'], $ip),
                    };

                    if ($hostname) {
                        // Cleanup all other processes
                        foreach ($processes as $key => $p) {
                            if ($key !== $method) {
                                $s = proc_get_status($p['proc']);
                                if ($s['running']) {
                                    exec("taskkill /PID {$s['pid']} /T /F 2>NUL");
                                }
                                @fclose($p['pipes'][0]);
                                @fclose($p['pipes'][1]);
                                @fclose($p['pipes'][2]);
                                proc_close($p['proc']);
                            }
                        }

                        return $hostname;
                    }

                    @fclose($pData['pipes'][0]);
                    @fclose($pData['pipes'][1]);
                    @fclose($pData['pipes'][2]);
                    proc_close($pData['proc']);
                    unset($processes[$method]);
                }
            }
            unset($pData);
            usleep(5000);
        }

        // Final cleanup
        foreach ($processes as $pData) {
            $status = proc_get_status($pData['proc']);
            if ($status['running']) {
                exec("taskkill /PID {$status['pid']} /T /F 2>NUL");
            }
            @fclose($pData['pipes'][0]);
            @fclose($pData['pipes'][1]);
            @fclose($pData['pipes'][2]);
            proc_close($pData['proc']);
        }

        return null;
    }

    // ──────────────────────────────────────────────
    //  PARALLEL HOSTNAME RESOLUTION
    // ──────────────────────────────────────────────

    /**
     * Resolve hostnames for multiple IPs in parallel using waterfall approach.
     * Phase 1: NetBIOS (fastest for Windows PCs)
     * Phase 2: DNS (for servers with PTR records) — only for unresolved IPs
     *
     * This is 3x faster than running all methods for every IP.
     *
     * @param  array  $ips  Array of full IP addresses
     * @return array keyed by IP => hostname|null
     */
    private function parallelResolveHostnames(array $ips): array
    {
        if (empty($ips)) {
            return [];
        }

        $resolved = [];

        // ── Phase 1: NetBIOS (fastest, ~0.5-1s per batch) ──
        $unresolvedAfterNbt = $this->resolveHostnamesByMethod($ips, 'nbtstat', $resolved);

        // ── Phase 2: DNS fallback (only for unresolved IPs) ──
        if (! empty($unresolvedAfterNbt)) {
            $this->resolveHostnamesByMethod($unresolvedAfterNbt, 'nslookup', $resolved);
        }

        // Ensure all IPs have an entry
        foreach ($ips as $ip) {
            if (! isset($resolved[$ip])) {
                $resolved[$ip] = null;
            }
        }

        return $resolved;
    }

    /**
     * Resolve hostnames using a specific method (nbtstat or nslookup).
     * Returns array of IPs that were NOT resolved.
     *
     * @param  array  $ips  IPs to resolve
     * @param  string  $method  'nbtstat' or 'nslookup'
     * @param  array  &$resolved  Reference to resolved results array
     * @return array Unresolved IPs
     */
    private function resolveHostnamesByMethod(array $ips, string $method, array &$resolved): array
    {
        $unresolved = [];
        $batches = array_chunk($ips, self::HOSTNAME_CONCURRENCY);

        foreach ($batches as $batch) {
            $processes = [];

            foreach ($batch as $ip) {
                // Skip if already resolved
                if (isset($resolved[$ip])) {
                    continue;
                }

                $cmd = match ($method) {
                    'nbtstat' => "cmd /c \"nbtstat -A {$ip}\"",
                    'nslookup' => "cmd /c \"nslookup {$ip}\"",
                };

                $descriptors = [
                    0 => ['pipe', 'r'],
                    1 => ['pipe', 'w'],
                    2 => ['pipe', 'w'],
                ];

                $proc = proc_open($cmd, $descriptors, $pipes);
                if (is_resource($proc)) {
                    stream_set_blocking($pipes[1], false);
                    stream_set_blocking($pipes[2], false);
                    $processes[$ip] = [
                        'ip' => $ip,
                        'proc' => $proc,
                        'pipes' => $pipes,
                        'output' => '',
                    ];
                }
            }

            $batchStart = microtime(true);
            $batchTimeout = 2.0; // 2s timeout per batch (faster than 3s)

            while (! empty($processes)) {
                if ((microtime(true) - $batchStart) >= $batchTimeout) {
                    break;
                }

                foreach ($processes as $ip => &$pData) {
                    // Read available output
                    $chunk = @fread($pData['pipes'][1], 8192);
                    if ($chunk !== false && $chunk !== '') {
                        $pData['output'] .= $chunk;
                    }

                    $status = proc_get_status($pData['proc']);
                    if (! $status['running']) {
                        stream_set_blocking($pData['pipes'][1], true);
                        $pData['output'] .= @stream_get_contents($pData['pipes'][1]);

                        $hostname = match ($method) {
                            'nbtstat' => $this->parseNbtstatOutput($pData['output']),
                            'nslookup' => $this->parseNslookupOutput($pData['output'], $ip),
                        };

                        if ($hostname) {
                            $resolved[$ip] = $hostname;
                        }

                        @fclose($pData['pipes'][0]);
                        @fclose($pData['pipes'][1]);
                        @fclose($pData['pipes'][2]);
                        proc_close($pData['proc']);
                        unset($processes[$ip]);
                    }
                }
                unset($pData);

                usleep(5000); // 5ms poll interval
            }

            // Final cleanup
            foreach ($processes as $ip => $pData) {
                $status = proc_get_status($pData['proc']);
                if ($status['running']) {
                    exec("taskkill /PID {$status['pid']} /T /F 2>NUL");
                }
                @fclose($pData['pipes'][0]);
                @fclose($pData['pipes'][1]);
                @fclose($pData['pipes'][2]);
                proc_close($pData['proc']);
                $unresolved[] = $ip;
            }
        }

        // Add unresolved IPs from this batch
        foreach ($ips as $ip) {
            if (! isset($resolved[$ip]) && ! in_array($ip, $unresolved)) {
                $unresolved[] = $ip;
            }
        }

        return $unresolved;
    }

    /**
     * Parse nbtstat -A output for hostname.
     */
    private function parseNbtstatOutput(string $output): ?string
    {
        foreach (explode("\n", $output) as $line) {
            if (str_contains($line, '<00>') && stripos($line, 'UNIQUE') !== false) {
                $parts = preg_split('/\s+/', trim($line));
                if (! empty($parts[0])) {
                    return strtoupper(trim($parts[0]));
                }
            }
        }

        return null;
    }

    /**
     * Parse nslookup output for hostname.
     */
    private function parseNslookupOutput(string $output, string $ip): ?string
    {
        foreach (explode("\n", $output) as $line) {
            $line = trim($line);
            if (preg_match('/^(?:Name|name)\s*[:=]\s*(.+)$/i', $line, $matches)) {
                $hostname = trim($matches[1]);
                if ($hostname !== $ip && ! preg_match('/^\d+\.\d+\.\d+\.\d+$/', $hostname)) {
                    $shortName = explode('.', $hostname)[0];
                    if (! empty($shortName)) {
                        return strtoupper($shortName);
                    }
                }
            }
        }

        return null;
    }

    /**
     * Parse ping -a output for hostname.
     */
    private function parsePingAOutput(string $output, string $ip): ?string
    {
        foreach (explode("\n", $output) as $line) {
            if (preg_match('/Pinging\s+([^\s\[<]+)\s*\[/i', $line, $matches)) {
                $candidate = trim($matches[1]);
                if ($candidate !== $ip && ! preg_match('/^\d+\.\d+\.\d+\.\d+$/', $candidate)) {
                    return strtoupper($candidate);
                }
            }
        }

        return null;
    }

    // ──────────────────────────────────────────────
    //  PARALLEL PING SWEEP (Core of accurate detection)
    // ──────────────────────────────────────────────

    /**
     * Parse ping output and determine if the target is online.
     * Handles both English and Indonesian locale Windows output.
     *
     * @param  string  $output  Raw ping command output
     * @return array ['online' => bool, 'response_time_ms' => float|null]
     */
    private function parsePingOutput(string $output): array
    {
        $online = false;
        $responseTime = null;

        foreach (explode("\n", $output) as $line) {
            $lower = strtolower($line);

            // Check for positive reply indicators
            if (str_contains($lower, 'ttl=') ||
                (str_contains($lower, 'reply from') && ! str_contains($lower, 'unreachable')) ||
                (str_contains($lower, 'balasan dari') && ! str_contains($lower, 'unreachable'))) {
                $online = true;

                // Extract response time: "time=1ms" or "time<1ms" or "waktu=1md" or "waktu<1md"
                if (preg_match('/(?:time|waktu)\s*[=<]\s*(\d+)/i', $line, $m)) {
                    $responseTime = (float) $m[1];
                }
                break;
            }
        }

        return ['online' => $online, 'response_time_ms' => $responseTime];
    }

    /**
     * Run parallel ICMP ping sweep across a range of IPs.
     * Launches multiple ping processes concurrently for speed.
     *
     * @param  int  $start  Starting IP suffix
     * @param  int  $end  Ending IP suffix
     * @return array keyed by suffix => ['online' => bool, 'response_time_ms' => float|null]
     */
    private function parallelPingSweep(int $start, int $end): array
    {
        $results = [];
        $ipsToScan = range($start, $end);

        // Reduce concurrency for better stability
        $concurrency = 30;
        $batches = array_chunk($ipsToScan, $concurrency);

        foreach ($batches as $batch) {
            $processes = [];

            // Launch all pings in this batch concurrently
            foreach ($batch as $suffix) {
                $ip = "{$this->subnet}.{$suffix}";
                $cmd = "cmd /c \"ping -n 1 -w 800 {$ip}\"";

                $descriptors = [
                    0 => ['pipe', 'r'],
                    1 => ['pipe', 'w'],
                    2 => ['pipe', 'w'],
                ];

                $proc = proc_open($cmd, $descriptors, $pipes);
                if (is_resource($proc)) {
                    stream_set_blocking($pipes[1], false);
                    stream_set_blocking($pipes[2], false);
                    $processes[$suffix] = [
                        'proc' => $proc,
                        'pipes' => $pipes,
                        'output' => '',
                        'start' => microtime(true),
                    ];
                } else {
                    $results[$suffix] = ['online' => false, 'response_time_ms' => null];
                }
            }

            // Wait for all processes with timeout (max 4 seconds per batch)
            $batchTimeout = 4.0;
            $batchStart = microtime(true);

            while (! empty($processes)) {
                $elapsed = microtime(true) - $batchStart;
                if ($elapsed >= $batchTimeout) {
                    break;
                }

                foreach ($processes as $suffix => &$pData) {
                    // Read available output
                    $chunk = @fread($pData['pipes'][1], 8192);
                    if ($chunk !== false && $chunk !== '') {
                        $pData['output'] .= $chunk;
                    }
                    $chunk2 = @fread($pData['pipes'][2], 8192);
                    if ($chunk2 !== false && $chunk2 !== '') {
                        $pData['output'] .= $chunk2;
                    }

                    // Check if finished
                    $status = proc_get_status($pData['proc']);
                    if (! $status['running']) {
                        // Read remaining output
                        stream_set_blocking($pData['pipes'][1], true);
                        stream_set_blocking($pData['pipes'][2], true);
                        $pData['output'] .= @stream_get_contents($pData['pipes'][1]);
                        $pData['output'] .= @stream_get_contents($pData['pipes'][2]);

                        $parsed = $this->parsePingOutput($pData['output']);
                        $results[$suffix] = $parsed;

                        // If ping returned time, use it; otherwise calculate from elapsed
                        if ($parsed['online'] && $parsed['response_time_ms'] === null) {
                            $results[$suffix]['response_time_ms'] = round((microtime(true) - $pData['start']) * 1000, 1);
                        }

                        @fclose($pData['pipes'][0]);
                        @fclose($pData['pipes'][1]);
                        @fclose($pData['pipes'][2]);
                        proc_close($pData['proc']);
                        unset($processes[$suffix]);
                    }
                }
                unset($pData);

                usleep(5000); // 5ms poll interval — faster detection
            }

            // Kill remaining processes that didn't finish in time
            foreach ($processes as $suffix => $pData) {
                $status = proc_get_status($pData['proc']);
                if ($status['running']) {
                    exec("taskkill /PID {$status['pid']} /T /F 2>NUL");
                }

                // Try to get any output that was collected
                $parsed = $this->parsePingOutput($pData['output']);
                $results[$suffix] = $parsed;

                @fclose($pData['pipes'][0]);
                @fclose($pData['pipes'][1]);
                @fclose($pData['pipes'][2]);
                proc_close($pData['proc']);
            }
        }

        return $results;
    }

    // ──────────────────────────────────────────────
    //  TCP PORT PROBE (Fallback for ICMP-blocked devices)
    // ──────────────────────────────────────────────

    /**
     * TCP port probe for a list of IPs that did not respond to ping.
     * Uses stream_socket_client with async connect + stream_select.
     *
     * @param  array  $suffixes  IP suffixes to probe
     * @return array keyed by suffix => ['online' => bool, 'response_time_ms' => float|null, 'port' => int|null]
     */
    private function tcpPortProbe(array $suffixes): array
    {
        if (empty($suffixes)) {
            return [];
        }

        $results = [];
        $ports = [445, 135, 80, 443, 3389, 9100];

        foreach ($ports as $port) {
            $sockets = [];
            $startTimes = [];
            $remaining = [];

            // Only probe suffixes not yet found online
            foreach ($suffixes as $suffix) {
                if (! empty($results[$suffix]['online'])) {
                    continue;
                }
                $remaining[] = $suffix;
            }

            if (empty($remaining)) {
                break;
            }

            // Open async connections
            foreach ($remaining as $suffix) {
                $ip = "{$this->subnet}.{$suffix}";
                $s = @stream_socket_client(
                    "tcp://{$ip}:{$port}",
                    $errno,
                    $errstr,
                    0,
                    STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT
                );

                if ($s) {
                    stream_set_blocking($s, false);
                    $sockets[$suffix] = $s;
                    $startTimes[$suffix] = microtime(true);
                }
            }

            if (empty($sockets)) {
                continue;
            }

            // Wait up to 500ms with stream_select
            $deadline = microtime(true) + 0.5;
            while (! empty($sockets) && microtime(true) < $deadline) {
                $read = null;
                $write = array_values($sockets);
                $except = null;
                $remainingTime = max(0, $deadline - microtime(true));
                $tvSec = (int) floor($remainingTime);
                $tvUsec = (int) (($remainingTime - $tvSec) * 1000000);

                $changed = @stream_select($read, $write, $except, $tvSec, $tvUsec);
                if ($changed === false || $changed === 0) {
                    break;
                }

                foreach ($write as $wSocket) {
                    $foundKey = array_search($wSocket, $sockets, true);
                    if ($foundKey === false) {
                        continue;
                    }

                    // Verify connection actually succeeded (critical on Windows!)
                    // Try to get remote peer name - fails if connection was refused
                    $peerName = @stream_socket_get_name($wSocket, true);
                    if ($peerName !== false && $peerName !== '') {
                        $elapsed = round((microtime(true) - $startTimes[$foundKey]) * 1000, 1);
                        $results[$foundKey] = [
                            'online' => true,
                            'response_time_ms' => $elapsed,
                            'port' => $port,
                        ];
                    }

                    @fclose($wSocket);
                    unset($sockets[$foundKey]);
                }
            }

            // Close remaining sockets
            foreach ($sockets as $s) {
                @fclose($s);
            }
        }

        // Fill in missing results
        foreach ($suffixes as $suffix) {
            if (! isset($results[$suffix])) {
                $results[$suffix] = ['online' => false, 'response_time_ms' => null, 'port' => null];
            }
        }

        return $results;
    }

    // ──────────────────────────────────────────────
    //  BATCH RANGE SCAN (Accurate & Fast)
    // ──────────────────────────────────────────────

    /**
     * Scan a range of IPs using multi-phase detection for accuracy.
     *
     * Phase 1: Parallel ICMP Ping sweep (primary — most reliable)
     * Phase 2: Refresh ARP table (pings warm the ARP cache)
     * Phase 3: TCP port probe for non-responders (catches ICMP-blocked devices)
     * Phase 4: Hostname resolution for active devices
     *
     * @param  int  $start  Starting IP suffix (1-254)
     * @param  int  $end  Ending IP suffix (1-254)
     * @return array Results keyed by IP suffix
     */
    public function scanSubnetRange(int $start = 1, int $end = 254): array
    {
        $results = [];

        try {
            // Initialize all IPs as offline
            for ($i = $start; $i <= $end; $i++) {
                $results[$i] = [
                    'ip' => "{$this->subnet}.{$i}",
                    'status' => 'offline',
                    'is_active' => false,
                    'response_time_ms' => null,
                    'hostname' => null,
                    'device_type' => 'Unknown',
                    'open_ports' => [],
                    'mac_address' => null,
                ];
            }

            // ── Phase 1: Parallel ICMP Ping Sweep ──
            $pingResults = $this->parallelPingSweep($start, $end);

            $onlineSuffixes = [];
            $offlineSuffixes = [];

            foreach ($pingResults as $suffix => $pingData) {
                if ($pingData['online']) {
                    $results[$suffix]['status'] = 'online';
                    $results[$suffix]['is_active'] = true;
                    $results[$suffix]['response_time_ms'] = $pingData['response_time_ms'];
                    $results[$suffix]['device_type'] = 'Active ICMP';
                    $onlineSuffixes[] = $suffix;
                } else {
                    $offlineSuffixes[] = $suffix;
                }
            }

            // ── Phase 2: Refresh ARP table (pings warmed the cache) ──
            $arpTable = $this->getArpTable();

            foreach ($results as $suffix => &$result) {
                $ip = "{$this->subnet}.{$suffix}";
                if (isset($arpTable[$ip])) {
                    $result['mac_address'] = $arpTable[$ip];
                }
            }
            unset($result);

            // ── Phase 3: TCP port probe for offline IPs (ICMP might be blocked) ──
            if (! empty($offlineSuffixes)) {
                $tcpResults = $this->tcpPortProbe($offlineSuffixes);

                foreach ($tcpResults as $suffix => $tcpData) {
                    if ($tcpData['online']) {
                        $results[$suffix]['status'] = 'online';
                        $results[$suffix]['is_active'] = true;
                        $results[$suffix]['response_time_ms'] = $tcpData['response_time_ms'];
                        $results[$suffix]['open_ports'] = $tcpData['port'] ? [$tcpData['port']] : [];

                        // Determine device type by port
                        if ($tcpData['port'] === 9100) {
                            $results[$suffix]['device_type'] = 'Network Printer (JetDirect)';
                        } elseif (in_array($tcpData['port'], [445, 135], true)) {
                            $results[$suffix]['device_type'] = 'Windows PC/Server';
                        } elseif ($tcpData['port'] === 3389) {
                            $results[$suffix]['device_type'] = 'Windows PC (RDP)';
                        } else {
                            $results[$suffix]['device_type'] = 'Active Device (Port Open)';
                        }

                        $onlineSuffixes[] = $suffix;
                    }
                }
            }

            // ── Phase 4: Parallel hostname resolution for active IPs ──
            $activeIps = [];
            foreach ($results as $suffix => $result) {
                if ($result['is_active']) {
                    $activeIps[] = $result['ip'];
                }
            }

            $hostnames = $this->parallelResolveHostnames($activeIps);

            foreach ($results as $suffix => &$result) {
                if ($result['is_active']) {
                    $result['hostname'] = $hostnames[$result['ip']] ?? null;
                }
            }
            unset($result);

            return $results;

        } catch (\Throwable $e) {
            // Log error dan return partial results
            error_log('ScanSubnetRange error: '.$e->getMessage());

            return $results;
        }
    }

    // ──────────────────────────────────────────────
    //  SINGLE IP SCAN (Accurate with retry)
    // ──────────────────────────────────────────────

    /**
     * Fast single-IP scan — optimized for per-row button clicks.
     *
     * Detection: ICMP Ping (1 attempt) → TCP Port probe (async parallel).
     * ARP cache is only used for MAC address lookup — NOT for online detection.
     * Hostname is resolved only if the device is confirmed online.
     *
     * @param  string  $ip  Full IP address (e.g. 172.16.250.1)
     * @return array Scan result
     */
    public function pingIp(string $ip): array
    {
        $responseTime = null;
        $openPorts = [];

        // ── Step 1: ICMP Ping (1 packet, 400ms timeout) ──
        $pingResult = $this->execWithTimeout("ping -n 1 -w 400 {$ip}", self::CMD_TIMEOUT);
        $parsed = $this->parsePingOutput($pingResult['output']);
        $isPingActive = $parsed['online'];

        if ($isPingActive) {
            $responseTime = $parsed['response_time_ms'];
        }

        // ── Step 2: TCP Port Probe — parallel async (only if ping fails) ──
        $isPortActive = false;
        $portsToCheck = [445, 135, 80, 443, 3389, 9100];

        if (! $isPingActive) {
            $portResult = $this->asyncPortScan($ip, $portsToCheck, 0.3);
            if (! empty($portResult)) {
                $isPortActive = true;
                $openPorts = $portResult;
                $responseTime = round(0.3, 1);
            }
        }

        $isActive = $isPingActive || $isPortActive;

        // ── Step 3: Refresh ARP table for MAC lookup ──
        $arpTable = $this->getArpTable();
        $macAddress = $arpTable[$ip] ?? null;

        // ── Step 4: Resolve hostname ONLY if device is confirmed online ──
        // Use faster single-method resolution instead of running all 3 methods
        $hostname = $isActive ? $this->resolveHostnameFast($ip) : null;

        // ── Step 5: Quick port scan for classification (if ping active and no ports found) ──
        if ($isPingActive && empty($openPorts)) {
            $openPorts = $this->asyncPortScan($ip, [445, 80, 3389, 9100], 0.2);
        }

        // ── Determine device type ──
        $firstPort = $openPorts[0] ?? null;
        $deviceType = match (true) {
            $firstPort === 9100 => 'Network Printer (JetDirect)',
            in_array((int) $firstPort, [445, 135], true) => 'Windows PC/Server',
            $firstPort === 3389 => 'Windows PC (RDP)',
            $firstPort === 80 => 'Web Service Device',
            $firstPort !== null => 'Active Device (Port Open)',
            $isActive => 'Active ICMP',
            default => 'Unknown',
        };

        return [
            'ip' => $ip,
            'status' => $isActive ? 'online' : 'offline',
            'is_active' => $isActive,
            'response_time_ms' => $responseTime,
            'hostname' => $hostname,
            'device_type' => $deviceType,
            'open_ports' => $openPorts,
            'mac_address' => $macAddress,
        ];
    }

    // ──────────────────────────────────────────────
    //  SEPARATE SCAN METHODS (Focused & Fast)
    // ──────────────────────────────────────────────

    /**
     * Ping Only — Fast & Smart online check via ICMP with TCP port fallback.
     * Also instantly captures fresh MAC address from ARP table warmed by ping.
     *
     * @param  string  $ip  Full IP address
     * @return array ['ip' => string, 'is_active' => bool, 'status' => string, 'response_time_ms' => float|null, 'mac_address' => string|null]
     */
    public function pingOnly(string $ip): array
    {
        // ── Step 1: Fast ICMP Ping (800ms ping timeout, 2s process timeout) ──
        $result = $this->execWithTimeout("ping -n 1 -w 800 {$ip}", 2);
        $parsed = $this->parsePingOutput($result['output']);
        $isActive = $parsed['online'];
        $responseTime = $parsed['response_time_ms'] ?? null;

        // ── Step 2: Fallback TCP port probe if ping is offline (firewall bypass) ──
        if (! $isActive) {
            $ports = $this->asyncPortScan($ip, [445, 135, 80, 9100, 3389], 0.3);
            if (! empty($ports)) {
                $isActive = true;
                $responseTime = 1.0;
            }
        }

        // ── Step 3: Immediate ARP table lookup for the IP (warmed by ping/TCP) ──
        $arpTable = $this->getArpTable();
        $macAddress = $arpTable[$ip] ?? null;

        // ── Step 4: Triple-Check — If MAC exists in ARP cache, the device IS ONLINE ──
        if (! $isActive && ! empty($macAddress)) {
            $isActive = true;
            $responseTime = $responseTime ?? 1.0;
        }

        return [
            'ip' => $ip,
            'status' => $isActive ? 'online' : 'offline',
            'is_active' => $isActive,
            'response_time_ms' => $responseTime,
            'mac_address' => $macAddress,
        ];
    }

    /**
     * Hostname Only — Resolve hostname via NetBIOS/DNS/ping -a + determine OS from ports.
     * Medium speed (~1-2s per IP).
     *
     * @param  string  $ip  Full IP address
     * @return array ['hostname' => string|null, 'device_type' => string]
     */
    public function hostnameOnly(string $ip): array
    {
        // Resolve hostname
        $hostname = $this->resolveHostnameFast($ip);

        // Quick port scan for OS detection
        $openPorts = $this->asyncPortScan($ip, [445, 135, 80, 3389, 9100], 0.3);
        $firstPort = $openPorts[0] ?? null;

        $deviceType = match ($firstPort) {
            9100 => 'Network Printer (JetDirect)',
            445, 135 => 'Windows PC/Server',
            3389 => 'Windows PC (RDP)',
            80 => 'Web Service Device',
            default => 'Unknown',
        };

        return [
            'ip' => $ip,
            'hostname' => $hostname,
            'device_type' => $deviceType,
            'open_ports' => $openPorts,
        ];
    }

    /**
     * MAC Only — Get MAC address from ARP table + lookup vendor.
     * If not in ARP, fires a rapid ping (200ms) to warm the ARP cache.
     *
     * @param  string  $ip  Full IP address
     * @return array ['mac_address' => string|null]
     */
    public function macOnly(string $ip): array
    {
        $arpTable = $this->getArpTable();
        $mac = $arpTable[$ip] ?? null;

        if (! $mac) {
            // Rapid ping to trigger kernel ARP resolution
            $this->execWithTimeout("ping -n 1 -w 200 {$ip}", 1);
            $arpTable = $this->getArpTable();
            $mac = $arpTable[$ip] ?? null;
        }

        return [
            'ip' => $ip,
            'mac_address' => $mac,
        ];
    }

    /**
     * Fast hostname resolution for single IP — NetBIOS first, DNS second, Ping -a fallback.
     * Balanced speed vs reliability for corporate LAN.
     *
     * @param  string  $ip  Full IP address
     * @return string|null Hostname or null
     */
    private function resolveHostnameFast(string $ip): ?string
    {
        // Try NetBIOS first (fastest for Windows PCs on LAN)
        $result = $this->execWithTimeout("nbtstat -A {$ip}", 1);
        if (! empty($result['output'])) {
            $hostname = $this->parseNbtstatOutput($result['output']);
            if ($hostname) {
                return $hostname;
            }
        }

        // Fallback to DNS reverse lookup (for servers with PTR records)
        $result = $this->execWithTimeout("nslookup {$ip}", 1);
        if (! empty($result['output'])) {
            $hostname = $this->parseNslookupOutput($result['output'], $ip);
            if ($hostname) {
                return $hostname;
            }
        }

        // Fallback to Ping -a
        $result = $this->execWithTimeout("ping -a -n 1 -w 400 {$ip}", 1);
        if (! empty($result['output'])) {
            $hostname = $this->parsePingAOutput($result['output'], $ip);
            if ($hostname) {
                return $hostname;
            }
        }

        return null;
    }

    /**
     * Async parallel TCP port scan using stream_select.
     * Opens all connections simultaneously and waits for results.
     *
     * @param  string  $ip  Target IP
     * @param  array  $ports  Ports to scan
     * @param  float  $timeout  Timeout in seconds
     * @return array List of open ports
     */
    private function asyncPortScan(string $ip, array $ports, float $timeout = 0.5): array
    {
        $sockets = [];
        $openPorts = [];

        foreach ($ports as $port) {
            $s = @stream_socket_client(
                "tcp://{$ip}:{$port}",
                $errno,
                $errstr,
                0,
                STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT
            );

            if ($s) {
                stream_set_blocking($s, false);
                $sockets[$port] = $s;
            }
        }

        if (empty($sockets)) {
            return [];
        }

        $deadline = microtime(true) + $timeout;
        while (! empty($sockets) && microtime(true) < $deadline) {
            $read = null;
            $write = array_values($sockets);
            $except = null;
            $remainingTime = max(0, $deadline - microtime(true));
            $tvSec = (int) floor($remainingTime);
            $tvUsec = (int) (($remainingTime - $tvSec) * 1000000);

            $changed = @stream_select($read, $write, $except, $tvSec, $tvUsec);
            if ($changed === false || $changed === 0) {
                break;
            }

            foreach ($write as $wSocket) {
                $foundPort = array_search($wSocket, $sockets, true);
                if ($foundPort === false) {
                    continue;
                }

                $peerName = @stream_socket_get_name($wSocket, true);
                if ($peerName !== false && $peerName !== '') {
                    $openPorts[] = $foundPort;
                }

                @fclose($wSocket);
                unset($sockets[$foundPort]);
            }
        }

        foreach ($sockets as $s) {
            @fclose($s);
        }

        return $openPorts;
    }
}
