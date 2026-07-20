<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CompanyDocument;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class CompanyDocumentSeeder extends Seeder
{
    public function run(): void
    {
        $verifier = User::role(['admin', 'terminal manager'])->first()
            ?? User::query()->first();

        Company::query()
            ->whereIn('company_code', ['NOR', 'SOU'])
            ->get()
            ->each(function (Company $company) use ($verifier) {
                $operator = User::query()
                    ->where('company_id', $company->id)
                    ->role('operator')
                    ->first();

                $uploadedBy = $operator?->id ?? $verifier?->id;
                $requiredDocs = $company->business_type === 'corporate'
                    ? ['SEC_CERT', 'MAYORS_PERMIT', 'BIR_2303']
                    : ['DTI_CERT', 'MAYORS_PERMIT', 'BIR_2303'];

                foreach ($requiredDocs as $docType) {
                    $originalName = strtolower($company->company_code . '-' . $docType) . '.pdf';
                    $filePath = "company-documents/{$company->id}/{$docType}/{$originalName}";
                    $issuedAt = now()->subMonths(6)->toDateString();
                    $expiresAt = now()->addYear()->toDateString();
                    $pdf = $this->samplePdf($company, $docType, $issuedAt, $expiresAt);

                    Storage::disk('public')->put($filePath, $pdf);

                    CompanyDocument::query()->updateOrCreate(
                        [
                            'company_id' => $company->id,
                            'doc_type' => $docType,
                        ],
                        [
                            'file_path' => $filePath,
                            'original_name' => $originalName,
                            'mime_type' => 'application/pdf',
                            'file_size' => strlen($pdf),
                            'issued_at' => $issuedAt,
                            'expires_at' => $expiresAt,
                            'status' => 'verified',
                            'uploaded_by' => $uploadedBy,
                            'verified_by' => $verifier?->id,
                            'verified_at' => now()->subDay(),
                        ],
                    );
                }
            });
    }

    private function samplePdf(
        Company $company,
        string $docType,
        string $issuedAt,
        string $expiresAt,
    ): string {
        $lines = [
            'SAMPLE / SEED DATA - NOT AN OFFICIAL DOCUMENT',
            str_replace('_', ' ', $docType),
            'Company: ' . $company->company_name,
            'Company Code: ' . $company->company_code,
            'Registration Number: ' . $company->registration_number,
            'Issued: ' . $issuedAt,
            'Expires: ' . $expiresAt,
        ];

        $content = "BT\n/F1 16 Tf\n50 760 Td\n";
        foreach ($lines as $index => $line) {
            if ($index > 0) {
                $content .= "0 -28 Td\n";
            }

            $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $line);
            $content .= "({$escaped}) Tj\n";
        }
        $content .= "ET\n";

        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            3 => '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>',
            4 => "<< /Length " . strlen($content) . ">>\nstream\n{$content}endstream",
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
            $pdf .= sprintf('%010d 00000 n ', $offsets[$number]) . "\n";
        }

        return $pdf
            . "trailer\n<< /Size 6 /Root 1 0 R >>\n"
            . "startxref\n{$xrefOffset}\n%%EOF\n";
    }
}
