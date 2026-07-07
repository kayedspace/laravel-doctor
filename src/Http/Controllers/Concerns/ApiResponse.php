<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    protected function success(mixed $data, int $status = 200, array $headers = [], int $options = 0): JsonResponse
    {
        return response()->json($data, $status, $headers, $options);
    }

    protected function error(string $message, int $status): JsonResponse
    {
        return response()->json([
            'error' => [
                'message' => $message,
            ],
        ], $status);
    }
}
