<?php

// Quick test: scan 172.16.250.7
$ip = '172.16.250.7';

echo "=== Testing IP: {$ip} ===\n\n";

// 1. ARP check
$output = [];
exec('arp -a', $output);
$arpFound = false;
foreach ($output as $line) {
    if (strpos($line, $ip) !== false) {
        echo "ARP: FOUND - {$line}\n";
        $arpFound = true;
    }
}
if (! $arpFound) {
    echo "ARP: NOT FOUND\n";
}

// 2. ICMP Ping
echo "\n--- ICMP Ping ---\n";
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
echo 'Ping active: '.($isPingActive ? 'YES' : 'NO')."\n";

// 3. TCP Port Probe
echo "\n--- TCP Port Probe ---\n";
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
        echo "Port {$port}: closed ({$elapsed}ms) - {$errstr}\n";
    }
}

// 4. Result
echo "\n=== RESULT ===\n";
$isActive = $isPingActive || $isPortActive || $arpFound;
echo 'is_active: '.($isActive ? 'TRUE' : 'FALSE')."\n";
echo 'Status: '.($isActive ? 'ONLINE' : 'OFFLINE')."\n";
