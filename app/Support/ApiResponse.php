<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function success(mixed $data = null, array $meta = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'data' => $data,
            'meta' => $meta,
        ], $status);
    }

    public static function problem(
        string $title,
        string $detail,
        int $status = 422,
        string $type = 'about:blank',
        array $errors = []
    ): JsonResponse {
        return response()->json([
            'type' => $type,
            'title' => $title,
            'status' => $status,
            'detail' => $detail,
            'errors' => $errors,
        ], $status, ['Content-Type' => 'application/problem+json']);
    }
}

