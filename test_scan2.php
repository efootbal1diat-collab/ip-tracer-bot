<?php

// Test with actual code logic from NetworkScannerService
$ip = '172.16.250.7';

echo "=== Testing IP: {$ip} ===\n\n";

// 1. ARP check (actual regex from code)
$arpTable = [];
$output = [];
exec('arp -a', $output);
foreach ($output as $line) {
    if (preg_match('/(172\.16\.250\.\d+)\s+([0-9a-fA-F-]{17})/i', $line, $matches)) {
        $arpTable[$matches[1]] = strtoupper(str_replace('-', ':', $matches[2]));
    }
}
$isArp = isset($arpTable[$ip]);
echo 'ARP table entries: '.count($arpTable)."\n";
echo "ARP for {$ip}: ".($isArp ? "FOUND ({$arpTable[$ip]})" : 'NOT FOUND')."\n";
echo 'All ARP IPs: '.implode(', ', array_keys($arpTable))."\n\n";

// 2. ICMP Ping
echo "--- ICMP Ping ---\n";
$start = microtime(true);
$pingOutput = [];
exec("ping -n 1 -w 300 {$ip}", $pingOutput, $exitCode);
$elapsed = round((microtime(true) - $start) * 1000);
echo "Ping exit code: {$exitCode} ({$elapsed}ms)\n";
foreach ($pingOutput as $line) {
    echo "  {$line}\n";
}
$isPingActive = false;
if ($exitCode === 0) {
    foreach ($pingOutput as $line) {
        $lower = strtolower($line);
        if (strpos($lower, 'ttl=') !== false || strpos($lower, 'reply from') !== false || strpos($lower, 'balasan dari') !== false) {
            $isPingActive = true;
            break;
        }
    }
}
echo 'Ping active: '.($isPingActive ? 'YES' : 'NO')."\n\n";

// 3. TCP Port Probe
echo "--- TCP Port Probe ---\n";
$ports = [445, 135, 80, 443, 9100];
$isPortActive = false;
$detectedPort = null;
foreach ($ports as $port) {
    $start = microtime(true);
    $conn = @fsockopen($ip, $port, $errno, $errstr, 0.15);
    $elapsed = round((microtime(true) - $start) * 1000);
    if (is_resource($conn)) {
        echo "Port {$port}: OPEN ({$elapsed}ms)\n";
        fclose($conn);
        $isPortActive = true;
        $detectedPort = $port;
        break;
    } else {
        echo "Port {$port}: closed ({$elapsed}ms)\n";
    }
}

// 4. Final result (same logic as pingIp)
$isActive = $isPingActive || $isPortActive || $isArp;
echo "\n=== RESULT ===\n";
echo 'isArp: '.($isArp ? 'true' : 'false')."\n";
echo 'isPingActive: '.($isPingActive ? 'true' : 'false')."\n";
echo 'isPortActive: '.($isPortActive ? 'true' : 'false')."\n";
echo 'isActive: '.($isActive ? 'true' : 'false')."\n";
echo 'Status: '.($isActive ? 'ONLINE' : 'OFFLINE')."\n";
