<?php

declare(strict_types=1);

class Response
{
    public static function json(array $data, int $code = 200): never
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function jsonError(string $message, int $code = 400): never
    {
        self::json(['success' => false, 'message' => $message], $code);
    }

    public static function jsonSuccess(array $data = [], string $message = 'OK'): never
    {
        self::json(array_merge(['success' => true, 'message' => $message], $data));
    }
}
