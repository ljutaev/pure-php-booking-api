<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Presentation\Http\JsonResponse;
use App\Presentation\Http\Request;

final class HealthController
{
    public function health(Request $request): JsonResponse
    {
        return JsonResponse::ok([
            'status' => 'ok',
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ]);
    }

    public function ready(Request $request): JsonResponse
    {
        return JsonResponse::ok(['status' => 'ok']);
    }
}
