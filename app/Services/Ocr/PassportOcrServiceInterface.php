<?php
declare(strict_types=1);

namespace App\Services\Ocr;

interface PassportOcrServiceInterface
{
    /**
     * Extract structured passport data from a document image or PDF file.
     *
     * @param string $filePath Absolute path to the passport file
     * @param array $options Configuration and extraction hints
     * @return array Extracted structured passport data and confidence scores
     */
    public function extract(string $filePath, array $options = []): array;
}
