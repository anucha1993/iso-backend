<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChecklistItem;
use App\Models\ChecklistStandard;
use Illuminate\Http\JsonResponse;

class ChecklistController extends Controller
{
    /**
     * The 13 master checklist tasks (FM-IT-02).
     */
    public function items(): JsonResponse
    {
        return response()->json([
            'data' => ChecklistItem::where('is_active', true)->orderBy('order')->get(),
        ]);
    }

    /**
     * CPU / Memory / Disk threshold standards.
     */
    public function standards(): JsonResponse
    {
        return response()->json([
            'data' => ChecklistStandard::where('is_active', true)->orderBy('order')->get(),
        ]);
    }
}
