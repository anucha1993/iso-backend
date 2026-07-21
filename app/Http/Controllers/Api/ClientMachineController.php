<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClientMachine;
use App\Models\ClientMaEntry;
use App\Models\ClientMachineImport;
use App\Models\CorrectiveAction;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

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

    /**
     * Full maintenance history of one machine across every monthly record.
     */
    public function history(ClientMachine $machine): JsonResponse
    {
        $history = ClientMaEntry::where('client_machine_id', $machine->id)
            ->with('record:id,year,month,status')
            ->get()
            ->filter(fn ($e) => $e->record !== null)
            ->map(fn ($e) => [
                'record_id' => $e->client_ma_record_id,
                'year' => $e->record->year,
                'month' => $e->record->month,
                'record_status' => $e->record->status,
                'status' => $e->status,
                'tasks' => $e->tasks ?? [],
                'note' => $e->note,
                'snap_owner' => $e->snap_owner,
                'snap_floor' => $e->snap_floor,
                'snap_department' => $e->snap_department,
                'metrics' => $e->metrics ?? [],
            ])
            ->sortByDesc(fn ($r) => $r['year'] * 100 + $r['month'])
            ->values();

        $clientFormId = \App\Models\FormTemplate::where('module_key', 'client_maintenance')->value('id');

        return response()->json(['data' => [
            'machine' => $machine,
            'history' => $history,
            'standards' => \App\Models\ChecklistStandard::effectiveFor($clientFormId),
            'tasks_def' => \App\Models\ChecklistItem::where('form_template_id', $clientFormId)->where('is_active', true)->whereNotNull('key')->orderBy('order')->get(['id', 'key', 'name', 'description', 'check_method', 'order']),
        ]]);
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

    public function destroy(ClientMachine $machine): JsonResponse
    {
        // Keep ISO history intact: if the machine appears in any monthly MA
        // record, deactivate instead of hard-deleting (same pattern as checklist items).
        if (ClientMaEntry::where('client_machine_id', $machine->id)->exists()) {
            $machine->update(['is_active' => false]);
            AuditLogger::log('updated', $machine, 'ปิดใช้งานเครื่อง Client (มีประวัติบำรุงรักษา): '.$machine->name);

            return response()->json(['data' => $machine->fresh(), 'soft' => true]);
        }

        // No history: safe to hard-delete. Remove any per-asset corrective
        // actions first (polymorphic, no FK cascade) to avoid orphaned rows.
        CorrectiveAction::where('subject_type', ClientMachine::class)
            ->where('subject_id', $machine->id)
            ->delete();

        $name = $machine->name;
        $machine->delete();
        AuditLogger::log('deleted', $machine, 'ลบเครื่อง Client: '.$name);

        return response()->json(['ok' => true]);
    }

    /**
     * Import machines from an uploaded CSV file (or pasted text).
     * Matches existing machines by name (hostname): update if found, else create.
     * Records a history row with field-level diffs and stores the source file.
     * Columns: name, floor, owner, department, asset_tag, os
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['nullable', 'file', 'mimes:csv,txt', 'max:5120'],
            'text' => ['nullable', 'string', 'max:200000'],
        ]);

        $file = $request->file('file');
        $filename = $file ? $file->getClientOriginalName() : ('paste-'.now()->format('Ymd-His').'.csv');
        $content = $file ? file_get_contents($file->getRealPath()) : (string) $request->input('text', '');
        $content = preg_replace('/^\xEF\xBB\xBF/', '', (string) $content); // strip UTF-8 BOM

        if (trim((string) $content) === '') {
            throw ValidationException::withMessages(['file' => ['ไม่พบข้อมูลสำหรับนำเข้า']]);
        }

        $fields = ['floor', 'owner', 'department', 'asset_tag', 'os'];
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $changes = [];

        foreach (preg_split('/\r\n|\r|\n/', $content) as $i => $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $cols = array_map('trim', str_getcsv($line));
            $name = $cols[0] ?? '';
            if ($i === 0 && in_array(mb_strtolower($name), ['name', 'hostname', 'ชื่อเครื่อง'], true)) {
                continue; // header row
            }
            if ($name === '') {
                $skipped++;
                continue;
            }

            $incoming = [
                'floor' => ($cols[1] ?? '') !== '' ? $cols[1] : null,
                'owner' => ($cols[2] ?? '') !== '' ? $cols[2] : null,
                'department' => ($cols[3] ?? '') !== '' ? $cols[3] : null,
                'asset_tag' => ($cols[4] ?? '') !== '' ? $cols[4] : null,
                'os' => ($cols[5] ?? '') !== '' ? $cols[5] : null,
            ];

            $machine = ClientMachine::where('name', $name)->first();
            if ($machine) {
                $diff = [];
                $apply = [];
                foreach ($fields as $f) {
                    if ($incoming[$f] !== null && (string) $incoming[$f] !== (string) $machine->$f) {
                        $diff[$f] = ['from' => $machine->$f, 'to' => $incoming[$f]];
                        $apply[$f] = $incoming[$f];
                    }
                }
                if ($apply) {
                    $machine->update($apply);
                    $updated++;
                    $changes[] = ['machine_id' => $machine->id, 'name' => $name, 'action' => 'update', 'diff' => $diff];
                } else {
                    $skipped++;
                }
            } else {
                $values = array_filter($incoming, fn ($v) => $v !== null);
                $machine = ClientMachine::create(array_merge(['name' => $name, 'is_active' => true], $values));
                $created++;
                $changes[] = ['machine_id' => $machine->id, 'name' => $name, 'action' => 'create', 'values' => $values];
            }
        }

        $disk = config('filesystems.attachment', 'public');
        if ($file) {
            $path = $file->store('client-inventory-imports', $disk);
        } else {
            $path = 'client-inventory-imports/'.$filename;
            Storage::disk($disk)->put($path, $content);
        }

        $import = ClientMachineImport::create([
            'filename' => $filename,
            'path' => $path,
            'uploaded_by' => $request->user()->id,
            'uploaded_by_name' => $request->user()->name,
            'created_count' => $created,
            'updated_count' => $updated,
            'skipped_count' => $skipped,
            'changes' => $changes,
        ]);

        AuditLogger::log('created', null, "นำเข้า inventory Client: สร้าง {$created}, แก้ไข {$updated}, ข้าม {$skipped} ({$filename})");

        return response()->json(['data' => [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'import_id' => $import->id,
        ]]);
    }

    /**
     * History of inventory import batches (newest first).
     */
    public function imports(): JsonResponse
    {
        $disk = config('filesystems.attachment', 'public');
        $items = ClientMachineImport::latest()
            ->limit(100)
            ->get(['id', 'filename', 'path', 'uploaded_by_name', 'created_count', 'updated_count', 'skipped_count', 'created_at'])
            ->map(fn ($im) => array_merge($im->toArray(), [
                'file_url' => $im->path ? Storage::disk($disk)->url($im->path) : null,
            ]));

        return response()->json(['data' => $items]);
    }

    public function importShow(ClientMachineImport $import): JsonResponse
    {
        $disk = config('filesystems.attachment', 'public');

        return response()->json(['data' => array_merge($import->toArray(), [
            'file_url' => $import->path ? Storage::disk($disk)->url($import->path) : null,
        ])]);
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
