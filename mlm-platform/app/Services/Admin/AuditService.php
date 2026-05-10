<?php

namespace App\Services\Admin;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class AuditService
{
    /**
     * Log an action to the immutable audit trail.
     */
    public function log(
        string $action,
        ?Model $target = null,
        ?array $oldValue = null,
        ?array $newValue = null,
        ?Model $actor = null
    ): void {
        $actorId = $actor ? $actor->id : (auth()->id() ?? null);
        $actorRole = $actor && method_exists($actor, 'getRoleNames') 
            ? $actor->getRoleNames()->first() 
            : (auth()->check() ? auth()->user()->getRoleNames()->first() : null);

        AuditLog::create([
            'actor_id' => $actorId,
            'actor_role' => $actorRole,
            'target_type' => $target ? get_class($target) : null,
            'target_id' => $target ? $target->id : null,
            'action' => $action,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
