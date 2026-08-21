<?php

namespace App\Console\Commands;

use App\Services\IpExcelService;
use App\Services\NetworkAiAgentService;
use App\Services\NetworkScannerService;
use Illuminate\Console\Command;
use Throwable;

class AiAskCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:ask {question : Pertanyaan atau perintah jaringan} {--subnet= : Subnet yang digunakan (opsional)} {--raw : Tampilkan format mentah tanpa konversi WA}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Eksekusi perintah AI Network Agent dari CLI atau WhatsApp bridge';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $question = trim((string) $this->argument('question'));
        $subnet = $this->option('subnet') ?: config('app.ip_subnets.0', '172.16.250');
        $raw = (bool) $this->option('raw');

        if (empty($question)) {
            $this->error('Pertanyaan tidak boleh kosong.');

            return 1;
        }

        try {
            $scannerService = new NetworkScannerService($subnet);
            $excelService = app(IpExcelService::class, ['subnet' => $subnet]);
            $aiService = new NetworkAiAgentService($scannerService, $excelService, $subnet);

            $result = $aiService->runAgent($question);

            if (! $result['success']) {
                $this->error($result['message'] ?? 'Gagal memproses pertanyaan.');

                return 1;
            }

            $message = $result['message'] ?? 'Analisis selesai.';

            if (! $raw) {
                $message = $this->formatForWhatsApp($message);
            }

            $this->line($message);

            return 0;
        } catch (Throwable $e) {
            $this->error('Error: '.$e->getMessage());

            return 1;
        }
    }

    /**
     * Format standard Markdown into WhatsApp-friendly text formatting.
     */
    protected function formatForWhatsApp(string $text): string
    {
        // Convert headers ### Title to *Title*
        $text = preg_replace('/^#{1,6}\s*(.+)$/m', '*$1*', $text);

        // Convert standard markdown bold **text** to WA bold *text*
        $text = preg_replace('/\*\*(.+?)\*\*/s', '*$1*', $text);

        // Convert markdown table rows into clean list format for mobile screen
        $lines = explode("\n", $text);
        $formattedLines = [];
        $inTable = false;

        foreach ($lines as $line) {
            $trimmed = trim($line);

            // Skip table separator line |:---|:---|
            if (preg_match('/^\|[\s\-:|]+\|$/', $trimmed)) {
                $inTable = true;

                continue;
            }

            // If it's a table row | col1 | col2 | col3 |
            if (preg_match('/^\|(.+)\|$/', $trimmed)) {
                $cols = array_map('trim', explode('|', trim($trimmed, '|')));

                // Check if it's table header
                if (in_array(strtolower($cols[0] ?? ''), ['no', 'no.', '#', 'alamat ip', 'ip'])) {
                    continue;
                }

                // Format row as clean bullet point: • 172.16.250.7 (Status: Bebas / Kosong)
                if (count($cols) >= 3) {
                    $ip = $cols[1] ?? '';
                    $status = $cols[2] ?? '';
                    $desc = $cols[3] ?? '';
                    $formattedLines[] = "• *{$ip}* — {$status}".($desc ? " ({$desc})" : '');
                } else {
                    $formattedLines[] = '• '.implode(' — ', $cols);
                }

                continue;
            }

            $inTable = false;
            $formattedLines[] = $line;
        }

        return implode("\n", $formattedLines);
    }
}
