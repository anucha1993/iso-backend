<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Server::query()->orderBy('name');

        if ($request->boolean('active_only', false)) {
            $query->where('is_active', true);
        }

        return response()->json(['data' => $query->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:servers,name'],
            'server_type' => ['nullable', 'string', 'max:100'],
            'responsible' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ]);

        $server = Server::create($data);
        AuditLogger::log('created', $server, "สร้างเซิร์ฟเวอร์ {$server->name}");

        return response()->json(['data' => $server], 201);
    }

    public function update(Request $request, Server $server): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:servers,name,'.$server->id],
            'server_type' => ['nullable', 'string', 'max:100'],
            'responsible' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ]);

        $server->update($data);
        AuditLogger::log('updated', $server, "แก้ไขเซิร์ฟเวอร์ {$server->name}");

        return response()->json(['data' => $server]);
    }
}
