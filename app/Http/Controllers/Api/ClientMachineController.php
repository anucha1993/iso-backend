<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClientMachine;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientMachineController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ClientMachine::query()->orderBy('name');
        if ($request->boolean('active_only')) {
            $query->where('is_active', true);
        }

        return response()->json(['data' => $query->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $machine = ClientMachine::create($this->validated($request));
        AuditLogger::log('created', $machine, 'เพิ่มเครื่อง Client: '.$machine->name);

        return response()->json(['data' => $machine], 201);
    }

    public function update(Request $request, ClientMachine $machine): JsonResponse
    {
        $machine->update($this->validated($request));
        AuditLogger::log('updated', $machine, 'แก้ไขเครื่อง Client: '.$machine->name);

        return response()->json(['data' => $machine]);
    }

    /**
     * Bulk import machines from pasted lines: name[,floor[,owner[,department[,asset_tag]]]]
     */
    public function bulkImport(Request $request): JsonResponse
    {
        $data = $request->validate(['text' => ['required', 'string', 'max:100000']]);

        $created = 0;
        $skipped = 0;
        foreach (preg_split('/\r\n|\r|\n/', $data['text']) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $cols = array_map('trim', explode(',', $line));
            $name = $cols[0] ?? '';
            if ($name === '' || ClientMachine::where('name', $name)->exists()) {
                $skipped++;
                continue;
            }
            ClientMachine::create([
                'name' => $name,
                'floor' => ($cols[1] ?? '') !== '' ? $cols[1] : null,
                'owner' => ($cols[2] ?? '') !== '' ? $cols[2] : null,
                'department' => ($cols[3] ?? '') !== '' ? $cols[3] : null,
                'asset_tag' => ($cols[4] ?? '') !== '' ? $cols[4] : null,
                'is_active' => true,
            ]);
            $created++;
        }

        AuditLogger::log('created', null, "นำเข้าเครื่อง Client {$created} เครื่อง (ข้าม {$skipped})");

        return response()->json(['created' => $created, 'skipped' => $skipped]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'asset_tag' => ['nullable', 'string', 'max:255'],
            'owner' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'floor' => ['nullable', 'string', 'max:100'],
            'os' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ]);
    }
}
