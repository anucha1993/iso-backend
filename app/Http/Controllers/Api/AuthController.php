<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Authenticate and issue a Sanctum token.
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            AuditLogger::log('failed_login', null, 'เข้าสู่ระบบไม่สำเร็จ (รหัสผ่านไม่ถูกต้อง)', [
                'email' => $credentials['email'],
            ], $user?->id);

            throw ValidationException::withMessages([
                'email' => ['อีเมลหรือรหัสผ่านไม่ถูกต้อง'],
            ]);
        }

        if (! $user->is_active) {
            AuditLogger::log('failed_login', null, 'บัญชีถูกระงับการใช้งาน', [
                'email' => $credentials['email'],
            ], $user->id);

            throw ValidationException::withMessages([
                'email' => ['บัญชีนี้ถูกระงับการใช้งาน กรุณาติดต่อผู้ดูแลระบบ'],
            ]);
        }

        $token = $user->createToken('web')->plainTextToken;

        AuditLogger::log('login', $user, 'เข้าสู่ระบบสำเร็จ', [], $user->id);

        return response()->json([
            'token' => $token,
            'user' => $user,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name')->values(),
        ]);
    }

    /**
     * Return the currently authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => $user,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name')->values(),
        ]);
    }

    /**
     * Revoke the current access token.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        AuditLogger::log('logout', $user, 'ออกจากระบบ', [], $user->id);

        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'ออกจากระบบเรียบร้อย']);
    }
}
