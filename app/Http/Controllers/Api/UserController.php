<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $users = User::with('roles:id,name')->orderBy('name')->get();

        return response()->json([
            'data' => $users,
            'roles' => Role::orderBy('name')->pluck('name'),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::min(10)->mixedCase()->numbers()->symbols()],
            'position' => ['nullable', 'string', 'max:255'],
            'employee_code' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'roles' => ['array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'position' => $data['position'] ?? null,
            'employee_code' => $data['employee_code'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        $user->syncRoles($data['roles'] ?? []);
        AuditLogger::log('created', $user, "สร้างผู้ใช้ {$user->email}", ['roles' => $data['roles'] ?? []]);

        return response()->json(['data' => $user->load('roles:id,name')], 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', Password::min(10)->mixedCase()->numbers()->symbols()],
            'position' => ['nullable', 'string', 'max:255'],
            'employee_code' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'roles' => ['array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ]);

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'position' => $data['position'] ?? null,
            'employee_code' => $data['employee_code'] ?? null,
            'is_active' => $data['is_active'] ?? $user->is_active,
        ]);

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        if (array_key_exists('roles', $data)) {
            $user->syncRoles($data['roles']);
        }

        AuditLogger::log('updated', $user, "แก้ไขผู้ใช้ {$user->email}", ['roles' => $data['roles'] ?? null]);

        return response()->json(['data' => $user->fresh()->load('roles:id,name')]);
    }
}
