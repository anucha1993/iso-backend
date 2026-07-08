<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * Paginated, filterable view of the audit trail (admin only).
     */
    public function index(Request $request): JsonResponse
    {
        $query = AuditLog::query()->with('user:id,name,email')->latest('created_at');

        if ($event = $request->string('event')->toString()) {
            $query->where('event', $event);
        }

        if ($userId = $request->integer('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($from = $request->date('from')) {
            $query->where('created_at', '>=', $from);
        }

        if ($to = $request->date('to')) {
            $query->where('created_at', '<=', $to);
        }

        return response()->json(
            $query->paginate($request->integer('per_page', 25))
        );
    }
}
