<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Central helper for writing ISO 27001 audit-trail entries.
 */
class AuditLogger
{
    /**
     * Record an auditable event.
     *
     * @param  array<string,mixed>  $properties
     */
    public static function log(
        string $event,
        ?Model $auditable = null,
        ?string $description = null,
        array $properties = [],
        ?int $userId = null,
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $userId ?? Auth::id(),
            'event' => $event,
            'auditable_type' => $auditable ? $auditable::class : null,
            'auditable_id' => $auditable?->getKey(),
            'description' => $description,
            'properties' => $properties !== [] ? $properties : null,
            'ip_address' => Request::ip(),
            'user_agent' => substr((string) Request::userAgent(), 0, 1000),
            'created_at' => now(),
        ]);
    }
}
