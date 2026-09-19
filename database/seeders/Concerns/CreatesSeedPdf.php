<?php

namespace Database\Seeders\Concerns;

trait CreatesSeedPdf
{
    /**
     * @param  array<int, string>  $lines
     */
    private function seedPdf(array $lines): string
    {
        $content = "BT\n/F1 12 Tf\n50 760 Td\n";

        foreach ($lines as $index => $line) {
            if ($index > 0) {
                $content .= "0 -22 Td\n";
            }

            $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $line);
            $content .= "({$escaped}) Tj\n";
        }

        $content .= "ET\n";
        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            3 => '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>',
            4 => '<< /Length '.strlen($content).">>\nstream\n{$content}endstream",
            5 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $number => $object) {
            $offsets[$number] = strlen($pdf);
            $pdf .= "{$number} 0 obj\n{$object}\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 6\n0000000000 65535 f \n";

        for ($number = 1; $number <= 5; $number++) {
            $pdf .= sprintf('%010d 00000 n ', $offsets[$number])."\n";
        }

        return $pdf."trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF\n";
    }
}
