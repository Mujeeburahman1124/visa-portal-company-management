<?php
declare(strict_types=1);

namespace App\Services;

class FileStreamService
{
    /**
     * Stream a file to browser securely with HTTP Range support, complete buffer clearing,
     * accurate magic byte MIME detection, and error resilience.
     */
    public static function stream(string $filePath, string $downloadName, bool $forceDownload = false): void
    {
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }

        if (!is_file($filePath) || !is_readable($filePath)) {
            self::renderFileNotFound('File Missing on Server', 'The requested file could not be found in server storage.');
            return;
        }

        $fileSize = (int)@filesize($filePath);
        if ($fileSize <= 0) {
            self::renderFileNotFound('Empty File', 'The requested file on server has zero bytes or cannot be read.');
            return;
        }

        // Read first 32 bytes for magic byte signature detection
        $magicBytes = '';
        $fp = @fopen($filePath, 'rb');
        if ($fp) {
            $magicBytes = (string)@fread($fp, 32);
            @fclose($fp);
        }

        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        // Accurate MIME detection by magic bytes first, then extension
        if (str_starts_with($magicBytes, '%PDF')) {
            $mime = 'application/pdf';
        } elseif (str_starts_with($magicBytes, "\xFF\xD8\xFF")) {
            $mime = 'image/jpeg';
        } elseif (str_starts_with($magicBytes, "\x89PNG\r\n\x1a\n")) {
            $mime = 'image/png';
        } elseif (str_starts_with($magicBytes, "GIF87a") || str_starts_with($magicBytes, "GIF89a")) {
            $mime = 'image/gif';
        } elseif (str_starts_with($magicBytes, "RIFF") && str_contains(substr($magicBytes, 8, 8), "WEBP")) {
            $mime = 'image/webp';
        } elseif ($ext === 'pdf' && $fileSize < 300 && !str_starts_with($magicBytes, '%PDF')) {
            // A non-PDF file renamed as .pdf (e.g. plain text placeholder)
            $mime = 'text/plain';
        } else {
            $extMap = [
                'pdf'  => 'application/pdf',
                'png'  => 'image/png',
                'jpg'  => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'webp' => 'image/webp',
                'gif'  => 'image/gif',
                'txt'  => 'text/plain',
                'csv'  => 'text/csv',
                'doc'  => 'application/msword',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'xls'  => 'application/vnd.ms-excel',
                'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'zip'  => 'application/zip',
            ];
            $mime = $extMap[$ext] ?? 'application/octet-stream';
        }

        // Clean download filename
        $safeFileName = preg_replace('/[^A-Za-z0-9._-]/', '_', $downloadName ?: basename($filePath));
        $disposition = $forceDownload ? 'attachment' : 'inline';

        // HTTP Range Support (required by Chrome, Edge, and iOS Safari PDF viewers)
        $start = 0;
        $end = $fileSize - 1;

        if (isset($_SERVER['HTTP_RANGE']) && preg_match('/bytes=\h*(\d+)-(\d*)[\D.*]?/i', $_SERVER['HTTP_RANGE'], $matches)) {
            $start = (int)$matches[1];
            if (!empty($matches[2])) {
                $end = (int)$matches[2];
            }
            if ($start > $end || $start >= $fileSize) {
                http_response_code(416);
                header("Content-Range: bytes */$fileSize");
                exit;
            }
            $length = $end - $start + 1;
            http_response_code(206);
            header("Content-Range: bytes $start-$end/$fileSize");
        } else {
            http_response_code(200);
            $length = $fileSize;
        }

        header('Content-Type: ' . $mime);
        header('Content-Disposition: ' . $disposition . '; filename="' . $safeFileName . '"');
        header('Content-Length: ' . $length);
        header('Accept-Ranges: bytes');
        header('Cache-Control: private, max-age=3600, must-revalidate');
        header('Pragma: public');
        header('X-Content-Type-Options: nosniff');

        $stream = @fopen($filePath, 'rb');
        if ($stream) {
            if ($start > 0) {
                @fseek($stream, $start);
            }
            $remaining = $length;
            $chunkSize = 64 * 1024;
            while (!feof($stream) && $remaining > 0) {
                $read = min($chunkSize, $remaining);
                $buf = @fread($stream, $read);
                if ($buf === false || $buf === '') {
                    break;
                }
                echo $buf;
                $remaining -= strlen($buf);
                if (function_exists('fastcgi_finish_request')) {
                    // avoid premature close
                }
            }
            @fclose($stream);
        }
        exit;
    }

    /**
     * Display a clean, styled error page when a file is missing instead of a raw 404 or white screen.
     */
    public static function renderFileNotFound(string $title, string $details, string $backUrl = '/documents'): void
    {
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }
        http_response_code(404);
        $safeTitle = htmlspecialchars($title);
        $safeDetails = htmlspecialchars($details);
        $safeUrl = htmlspecialchars($backUrl);

        echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='utf-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <title>{$safeTitle} — MS Travel Hub</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css' rel='stylesheet'>
    <style>
        body { background: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 20px; }
        .card { max-width: 520px; width: 100%; border: none; border-radius: 18px; box-shadow: 0 10px 30px rgba(0,0,0,0.07); }
    </style>
</head>
<body>
    <div class='card p-4 p-md-5 text-center'>
        <div class='mb-3 text-warning'>
            <i class='fa-solid fa-triangle-exclamation fa-3x'></i>
        </div>
        <h5 class='fw-bold text-dark mb-2'>{$safeTitle}</h5>
        <p class='text-muted small mb-4'>{$safeDetails}</p>
        <div class='d-flex justify-content-center gap-2'>
            <a href='{$safeUrl}' class='btn btn-primary btn-sm rounded-pill px-4'>
                <i class='fa-solid fa-arrow-left me-1'></i> Return Back
            </a>
            <button onclick='window.close();' class='btn btn-outline-secondary btn-sm rounded-pill px-3'>
                Close Tab
            </button>
        </div>
    </div>
</body>
</html>";
        exit;
    }
}
