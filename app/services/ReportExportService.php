<?php

declare(strict_types=1);

class ReportExportService
{
    public function sendExcel(array $headers, array $rows, string $filename): void
    {
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        echo "\xEF\xBB\xBF";
        echo '<html><head><meta charset="UTF-8"></head><body><table border="1">';
        echo '<tr>';
        foreach ($headers as $header) {
            echo '<th style="background:#1e3a5f;color:#fff;font-weight:bold;">' . htmlspecialchars($header) . '</th>';
        }
        echo '</tr>';
        foreach ($rows as $row) {
            echo '<tr>';
            foreach ($row as $cell) {
                echo '<td>' . htmlspecialchars((string) $cell) . '</td>';
            }
            echo '</tr>';
        }
        echo '</table></body></html>';
        exit;
    }

    public function sendPdf(string $title, array $headers, array $rows, string $filename): void
    {
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        echo $this->buildSimplePdf($title, $headers, $rows);
        exit;
    }

    private function buildSimplePdf(string $title, array $headers, array $rows): string
    {
        $lines = [];
        $lines[] = $title;
        $lines[] = 'Generated: ' . date('d/m/Y H:i');
        $lines[] = str_repeat('-', 72);
        $lines[] = implode(' | ', $headers);
        $lines[] = str_repeat('-', 72);
        foreach ($rows as $row) {
            $lines[] = implode(' | ', array_map(fn ($v) => (string) $v, $row));
        }

        $y = 800;
        $content = "BT\n/F1 12 Tf\n";
        $content .= "50 $y Td\n(" . $this->pdfEscape($title) . ") Tj\n";
        $content .= "/F1 9 Tf\n";

        foreach ($lines as $line) {
            if ($y < 50) {
                break;
            }
            $content .= "0 -14 Td\n(" . $this->pdfEscape(substr($line, 0, 90)) . ") Tj\n";
            $y -= 14;
        }
        $content .= "ET";

        $objects = [];
        $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[] = '<< /Type /Pages /Kids [3 0 R] /Count 1 >>';
        $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>';
        $objects[] = '<< /Length ' . strlen($content) . " >>\nstream\n" . $content . "\nendstream";
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $i => $obj) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1) . " 0 obj\n" . $obj . "\nendobj\n";
        }

        $xrefPos = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xrefPos . "\n%%EOF";

        return $pdf;
    }

    private function pdfEscape(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }
}
