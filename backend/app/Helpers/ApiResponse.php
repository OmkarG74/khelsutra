<?php

namespace App\Helpers;

class ApiResponse
{
    /**
     * Build standardized success response.
     *
     * @param mixed $data
     * @param string|null $message
     * @param int $code
     * @return array
     */
    public static function success(mixed $data = null, ?string $message = 'Operation successful', int $code = 200): array
    {
        return [
            'success' => true,
            'message' => $message,
            'data' => $data ?? new \stdClass(),
            '_status_code' => $code
        ];
    }

    /**
     * Build standardized error response.
     *
     * @param string $message
     * @param mixed $errors
     * @param int $code
     * @return array
     */
    public static function error(string $message = 'An error occurred', mixed $errors = null, int $code = 400): array
    {
        return [
            'success' => false,
            'message' => $message,
            'errors' => $errors ?? new \stdClass(),
            '_status_code' => $code
        ];
    }

    /**
     * Output JSON directly to HTTP client with header.
     *
     * @param array $payload
     * @return void
     */
    public static function send(array $payload): void
    {
        $code = $payload['_status_code'] ?? 200;
        unset($payload['_status_code']);
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit;
    }
}
