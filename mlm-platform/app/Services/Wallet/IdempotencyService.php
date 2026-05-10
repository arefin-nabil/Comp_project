<?php

namespace App\Services\Wallet;

use App\Models\IdempotencyKey;
use Carbon\Carbon;

class IdempotencyService
{
    /**
     * Store or check an idempotency key.
     * Returns true if newly created, false if already exists (duplicate).
     *
     * @param string $key
     * @param string $action
     * @param int|null $actorId
     * @param array|null $payload
     * @param Carbon|null $expiresAt
     * @return bool
     */
    public function lock(string $key, string $action, ?int $actorId = null, ?array $payload = null, ?Carbon $expiresAt = null): bool
    {
        $existing = IdempotencyKey::where('key', $key)->first();
        if ($existing) {
            return false;
        }

        IdempotencyKey::create([
            'key' => $key,
            'action' => $action,
            'actor_id' => $actorId,
            'request_payload' => $payload,
            'status' => 'pending',
            'expires_at' => $expiresAt,
        ]);

        return true;
    }

    /**
     * Mark idempotency key as completed with optional response.
     */
    public function complete(string $key, ?array $responsePayload = null, ?string $resultReferenceType = null, ?int $resultReferenceId = null): void
    {
        IdempotencyKey::where('key', $key)->update([
            'status' => 'completed',
            'response_payload' => $responsePayload,
            'result_reference_type' => $resultReferenceType,
            'result_reference_id' => $resultReferenceId,
        ]);
    }

    /**
     * Mark idempotency key as failed so it can be retried if needed,
     * or keep it failed depending on business logic.
     */
    public function fail(string $key, ?array $errorPayload = null): void
    {
        IdempotencyKey::where('key', $key)->update([
            'status' => 'failed',
            'response_payload' => $errorPayload,
        ]);
    }
}
