<?php
declare(strict_types=1);

namespace App\Validators;

class DocumentValidator extends Validator
{
    public const ALLOWED_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'docx'];
    public const MAX_FILE_SIZE_BYTES = 10485760; // 10 MB

    public function validateUpload(array $fileData, array $metaData): bool
    {
        $this->validateRequired($metaData, [
            'application_id' => 'Application ID',
            'document_type_id' => 'Document Type'
        ]);

        if (empty($fileData) || ($fileData['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            $this->addError('document_file', 'Please select a document file to upload.');
            return false;
        }

        if (($fileData['error'] ?? 0) !== UPLOAD_ERR_OK) {
            $this->addError('document_file', 'File upload error occurred. Please try again.');
            return false;
        }

        if (($fileData['size'] ?? 0) > self::MAX_FILE_SIZE_BYTES) {
            $this->addError('document_file', 'File size exceeds maximum allowed limit of 10MB.');
        }

        $ext = strtolower(pathinfo($fileData['name'] ?? '', PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            $this->addError('document_file', 'Invalid file type. Allowed formats: PDF, JPG, PNG, WEBP, DOCX.');
        }

        if (!empty($metaData['expiry_date'])) {
            $this->validateDate($metaData['expiry_date'], 'expiry_date', 'Document Expiry Date');
        }

        return $this->isValid();
    }
}
