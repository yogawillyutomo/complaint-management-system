<?php

namespace App\Services;

use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class MutationRateLimiter
{
    public function consume(
        string $action,
        int $maxAttempts,
        int $decaySeconds = 60
    ): void {
        $userId = auth()->id() ?? 'guest';
        $ip = request()->ip() ?? 'unknown';

        $key = implode(':', [
            'mutation',
            $action,
            $userId,
            $ip,
        ]);

        if (! RateLimiter::attempt(
            $key,
            $maxAttempts,
            static fn (): bool => true,
            $decaySeconds
        )) {
            throw new TooManyRequestsHttpException(
                RateLimiter::availableIn($key),
                'Too many requests. Please try again shortly.'
            );
        }
    }
}
