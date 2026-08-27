<?php
declare(strict_types=1);

namespace App\Controllers\Api;

class ApiController
{
    /**
     * Standard JSON Success Response
     */
    protected function jsonSuccess(mixed $data = null, string $message = 'Success', int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Standard JSON Error Response
     */
    protected function jsonError(string $message = 'An error occurred', array $errors = [], int $statusCode = 400): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Parse JSON Request Body
     */
    protected function getJsonInput(): array
    {
        $raw = file_get_contents('php://input');
        if (empty($raw)) {
            return $_POST;
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? array_merge($_POST, $decoded) : $_POST;
    }
}
