<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\AuditLogger;
use App\Support\SignatureStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * Save / replace the authenticated user's signature (stored in the user profile).
     */
    public function updateSignature(Request $request): JsonResponse
    {
        $data = $request->validate([
            'signature' => ['required', 'string'],
        ]);

        $user = $request->user();

        // Remove previous signature file to avoid orphans.
        if ($user->signature_path && Storage::disk('public')->exists($user->signature_path)) {
            Storage::disk('public')->delete($user->signature_path);
        }

        $path = SignatureStorage::storeDataUrl($data['signature'], 'user'.$user->id);
        $user->update(['signature_path' => $path]);

        AuditLogger::log('updated', $user, 'บันทึกลายเซ็นในโปรไฟล์', [], $user->id);

        return response()->json([
            'user' => $user->fresh(),
        ]);
    }

    /**
     * Remove the authenticated user's stored signature.
     */
    public function deleteSignature(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->signature_path && Storage::disk('public')->exists($user->signature_path)) {
            Storage::disk('public')->delete($user->signature_path);
        }

        $user->update(['signature_path' => null]);
        AuditLogger::log('updated', $user, 'ลบลายเซ็นในโปรไฟล์', [], $user->id);

        return response()->json(['user' => $user->fresh()]);
    }
}
