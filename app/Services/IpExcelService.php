<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Cache;
use PhpOffice\PhpSpreadsheet\IOFactory;

class IpExcelService
{
    protected string $excelPath;

    protected string $subnet;

    public function __construct(?string $excelPath = null, ?string $subnet = null)
    {
        $subnets = config('app.ip_subnets', ['172.16.250']);
        $this->subnet = $subnet ?? $subnets[0];

        if ($excelPath) {
            $this->excelPath = $excelPath;
        } else {
            // Prioritize local project Excel file
            $localProjectExcel = base_path('001. Data User IP.xlsx');
            if (file_exists($localProjectExcel)) {
                $this->excelPath = $localProjectExcel;
            } else {
                $this->excelPath = base_path('../001. Data User IP.xlsx');
            }
        }
    }

    /**
     * Read sheet '1st' and parse IP 1-254 rows.
     * Cached for 5 minutes to avoid reading Excel file on every request.
     */
    public function readFirstSheet(): array
    {
        return Cache::remember('excel_data', 300, function () {
            if (! file_exists($this->excelPath)) {
                throw new Exception("File Excel tidak ditemukan di: {$this->excelPath}");
            }

            $spreadsheet = IOFactory::load($this->excelPath);
            $sheetName = $this->subnet === '172.16.250' ? '1st' : $this->subnet;
            $sheet = $spreadsheet->getSheetByName($sheetName);

            if (! $sheet) {
                throw new Exception("Sheet '{$sheetName}' tidak ditemukan dalam file Excel. Harap buat sheet tersebut.");
            }

            $highestRow = $sheet->getHighestRow();
            $items = [];

            // Rows usually start at row 2 (row 1 is header: IP, Machine, USER, WINDOWS)
            for ($row = 2; $row <= $highestRow; $row++) {
                $ipNum = $sheet->getCell("A{$row}")->getValue();
                if ($ipNum === null || $ipNum === '') {
                    continue;
                }

                // Clean numerical IP suffix or full IP
                $ipSuffix = (int) filter_var($ipNum, FILTER_SANITIZE_NUMBER_INT);
                if ($ipSuffix < 1 || $ipSuffix > 254) {
                    continue;
                }

                $machine = trim((string) $sheet->getCell("B{$row}")->getValue());
                $user = trim((string) $sheet->getCell("C{$row}")->getValue());
                $windows = trim((string) $sheet->getCell("D{$row}")->getValue());

                $items[$ipSuffix] = [
                    'ip_suffix' => $ipSuffix,
                    'full_ip' => "{$this->subnet}.{$ipSuffix}",
                    'excel_machine' => $machine ?: null,
                    'excel_user' => $user ?: null,
                    'excel_windows' => $windows ?: null,
                    'row_index' => $row,
                    'is_excel_empty' => empty($machine) && empty($user),
                ];
            }

            // Fill missing IP 1..254 entries if any
            $fullList = [];
            for ($i = 1; $i <= 254; $i++) {
                if (isset($items[$i])) {
                    $fullList[] = $items[$i];
                } else {
                    $fullList[] = [
                        'ip_suffix' => $i,
                        'full_ip' => "{$this->subnet}.{$i}",
                        'excel_machine' => null,
                        'excel_user' => null,
                        'excel_windows' => null,
                        'row_index' => null,
                        'is_excel_empty' => true,
                    ];
                }
            }

            return $fullList;
        });
    }

    /**
     * Update machine/user/windows in Excel sheet '1st'
     */
    public function updateIpRecord(int $ipSuffix, ?string $machine, ?string $user, ?string $windows = null): bool
    {
        if (! file_exists($this->excelPath)) {
            return false;
        }

        $spreadsheet = IOFactory::load($this->excelPath);
        $sheetName = $this->subnet === '172.16.250' ? '1st' : $this->subnet;
        $sheet = $spreadsheet->getSheetByName($sheetName);
        if (! $sheet) {
            return false;
        }

        $highestRow = $sheet->getHighestRow();
        $targetRow = null;

        for ($row = 2; $row <= $highestRow; $row++) {
            $val = (int) filter_var($sheet->getCell("A{$row}")->getValue(), FILTER_SANITIZE_NUMBER_INT);
            if ($val === $ipSuffix) {
                $targetRow = $row;
                break;
            }
        }

        if (! $targetRow) {
            $targetRow = $highestRow + 1;
            $sheet->setCellValue("A{$targetRow}", $ipSuffix);
        }

        if ($machine !== null) {
            $sheet->setCellValue("B{$targetRow}", $machine);
        }
        if ($user !== null) {
            $sheet->setCellValue("C{$targetRow}", $user);
        }
        if ($windows !== null) {
            $sheet->setCellValue("D{$targetRow}", $windows);
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($this->excelPath);

        // Clear cache so next read gets fresh data
        Cache::forget('excel_data');

        return true;
    }
}
