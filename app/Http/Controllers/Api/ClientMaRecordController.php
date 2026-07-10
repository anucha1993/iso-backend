<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClientMachine;
use App\Models\ClientMaEntry;
use App\Models\ClientMaRecord;
use App\Models\FormTemplate;
use App\Models\User;
use App\Notifications\WorkflowNotification;
use App\Support\AuditLogger;
use App\Support\SignatureStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ClientMaRecordController extends Controller
{
    private const MONTHS = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

    public function index(Request $request): JsonResponse
    {
        $query = ClientMaRecord::query()->withCount('entries')->latest();
        if ($year = $request->integer('year')) {
            $query->where('year', $year);
        }
        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        return response()->json($query->paginate($request->integer('per_page', 50)));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'year' => ['required', 'integer', 'min:2400', 'max:2700'],
            'month' => ['required', 'integer', 'between:1,12'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        if (ClientMaRecord::where('year', $data['year'])->where('month', $data['month'])->exists()) {
            throw ValidationException::withMessages([
                'month' => ['มีบันทึกบำรุงรักษา Client ของเดือน/ปีนี้อยู่แล้ว'],
            ]);
        }

        $template = FormTemplate::where('module_key', 'client_maintenance')->first();

        $record = DB::transaction(function () use ($data, $template, $request) {
            $record = ClientMaRecord::create([
                'form_template_id' => $template?->id,
                'year' => $data['year'],
                'month' => $data['month'],
                'responsible' => $request->user()->name,
                'status' => ClientMaRecord::STATUS_DRAFT,
                'tasks' => $this->defaultTasks(),
                'report_files' => [],
                'note' => $data['note'] ?? null,
            ]);

            $rows = ClientMachine::where('is_active', true)->pluck('id')->map(fn ($id) => [
                'client_ma_record_id' => $record->id,
                'client_machine_id' => $id,
                'status' => 'na',
                'tasks' => json_encode($this->defaultTasks()),
                'created_at' => now(),
                'updated_at' => now(),
            ])->all();
            if ($rows) {
                ClientMaEntry::insert($rows);
            }

            return $record;
        });

        AuditLogger::log('created', $record, "สร้างบันทึกบำรุงรักษา Client {$this->monthLabel($record->month)} ปี {$record->year}");

        return response()->json(['data' => $this->full($record)], 201);
    }

    public function show(ClientMaRecord $record): JsonResponse
    {
        return response()->json(['data' => $this->full($record)]);
    }

    public function update(Request $request, ClientMaRecord $record): JsonResponse
    {
        $this->assertEditable($record);

        $data = $request->validate([
            'responsible' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:2000'],
            'tasks' => ['nullable', 'array'],
            'entries' => ['nullable', 'array'],
            'entries.*.id' => ['required', 'integer'],
            'entries.*.status' => ['required', Rule::in(['done', 'issue', 'na'])],
            'entries.*.tasks' => ['nullable', 'array'],
            'entries.*.note' => ['nullable', 'string', 'max:500'],
        ]);

        $record->update([
            'responsible' => $data['responsible'] ?? $record->responsible,
            'note' => $data['note'] ?? null,
            'tasks' => $data['tasks'] ?? $record->tasks,
        ]);

        foreach ($data['entries'] ?? [] as $e) {
            ClientMaEntry::where('client_ma_record_id', $record->id)->whereKey($e['id'])
                ->update([
                    'status' => $e['status'],
                    'tasks' => isset($e['tasks']) ? json_encode($e['tasks']) : null,
                    'note' => $e['note'] ?? null,
                ]);
        }

        AuditLogger::log('updated', $record, "บันทึกข้อมูลบำรุงรักษา Client {$this->monthLabel($record->month)} ปี {$record->year}");

        return response()->json(['data' => $this->full($record->fresh())]);
    }

    public function submit(Request $request, ClientMaRecord $record): JsonResponse
    {
        $this->assertEditable($record);
        $request->validate(['signature' => ['nullable', 'string']]);
        $user = $request->user();
        $path = $this->resolveSignature($request, $user);

        $record->update([
            'status' => ClientMaRecord::STATUS_SUBMITTED,
            'prepared_by' => $user->id,
            'prepared_name' => $user->name,
            'prepared_position' => $user->position,
            'prepared_signature_path' => $path,
            'prepared_signed_at' => now(),
            'rejected_reason' => null,
        ]);

        AuditLogger::log('submitted', $record, "ส่งอนุมัติบำรุงรักษา Client {$this->monthLabel($record->month)} ปี {$record->year}");

        $approvers = User::permission('records.approve')->where('is_active', true)->where('id', '!=', $user->id)->get();
        Notification::send($approvers, new WorkflowNotification(
            'submitted',
            'มีคำขออนุมัติใหม่ (บำรุงรักษา Client)',
            "บำรุงรักษาเครื่อง Client · {$this->monthLabel($record->month)} {$record->year} — รอการอนุมัติ (ส่งโดย {$user->name})",
            "/client-maintenance/{$record->id}",
        ));

        return response()->json(['data' => $this->full($record->fresh())]);
    }

    public function approve(Request $request, ClientMaRecord $record): JsonResponse
    {
        if ($record->status !== ClientMaRecord::STATUS_SUBMITTED) {
            throw ValidationException::withMessages(['status' => ['ต้องเป็นรายการที่ส่งแล้วเท่านั้นจึงจะอนุมัติได้']]);
        }
        $request->validate(['signature' => ['nullable', 'string']]);
        $user = $request->user();
        $path = $this->resolveSignature($request, $user);

        $record->update([
            'status' => ClientMaRecord::STATUS_APPROVED,
            'approved_by' => $user->id,
            'approved_name' => $user->name,
            'approved_position' => $user->position,
            'approved_signature_path' => $path,
            'approved_signed_at' => now(),
        ]);

        AuditLogger::log('approved', $record, "อนุมัติบำรุงรักษา Client {$this->monthLabel($record->month)} ปี {$record->year}");

        if ($record->prepared_by && $record->prepared_by !== $user->id) {
            User::find($record->prepared_by)?->notify(new WorkflowNotification(
                'approved',
                'อนุมัติแล้ว ✓ (บำรุงรักษา Client)',
                "บำรุงรักษาเครื่อง Client · {$this->monthLabel($record->month)} {$record->year} — อนุมัติโดย {$user->name}",
                "/client-maintenance/{$record->id}",
            ));
        }

        return response()->json(['data' => $this->full($record->fresh())]);
    }

    public function reject(Request $request, ClientMaRecord $record): JsonResponse
    {
        if (! in_array($record->status, [ClientMaRecord::STATUS_SUBMITTED, ClientMaRecord::STATUS_APPROVED], true)) {
            throw ValidationException::withMessages(['status' => ['ต้องเป็นรายการที่ส่ง/อนุมัติแล้วเท่านั้นจึงจะตีกลับได้']]);
        }
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $user = $request->user();

        $record->update([
            'status' => ClientMaRecord::STATUS_REJECTED,
            'rejected_reason' => $data['reason'],
            'approved_by' => null,
            'approved_name' => null,
            'approved_position' => null,
            'approved_signature_path' => null,
            'approved_signed_at' => null,
        ]);

        AuditLogger::log('rejected', $record, "ตีกลับบำรุงรักษา Client {$this->monthLabel($record->month)} ปี {$record->year}", ['reason' => $data['reason']]);

        if ($record->prepared_by && $record->prepared_by !== $user->id) {
            User::find($record->prepared_by)?->notify(new WorkflowNotification(
                'rejected',
                'ถูกตีกลับ — ต้องแก้ไข (บำรุงรักษา Client)',
                "บำรุงรักษาเครื่อง Client · {$this->monthLabel($record->month)} {$record->year} — เหตุผล: {$data['reason']}",
                "/client-maintenance/{$record->id}",
            ));
        }

        return response()->json(['data' => $this->full($record->fresh())]);
    }

    public function uploadReport(Request $request, ClientMaRecord $record): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,csv,xlsx,xls,txt,png,jpg,jpeg', 'max:20480'],
        ]);

        $file = $request->file('file');
        $disk = config('filesystems.attachment', 'public');
        $path = $file->store('client-ma/'.$record->id, $disk);

        $files = $record->report_files ?? [];
        $files[] = [
            'path' => $path,
            'name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'url' => Storage::disk($disk)->url($path),
        ];
        $record->update(['report_files' => $files]);

        AuditLogger::log('updated', $record, 'แนบรายงาน Action1: '.$file->getClientOriginalName());

        return response()->json(['data' => $this->full($record->fresh())]);
    }

    public function deleteReport(Request $request, ClientMaRecord $record): JsonResponse
    {
        $data = $request->validate(['path' => ['required', 'string']]);
        $disk = config('filesystems.attachment', 'public');

        $files = collect($record->report_files ?? [])->reject(fn ($f) => ($f['path'] ?? '') === $data['path'])->values()->all();
        if (Storage::disk($disk)->exists($data['path'])) {
            Storage::disk($disk)->delete($data['path']);
        }
        $record->update(['report_files' => $files]);

        return response()->json(['data' => $this->full($record->fresh())]);
    }

    public function destroy(ClientMaRecord $record): JsonResponse
    {
        $disk = config('filesystems.attachment', 'public');
        foreach ($record->report_files ?? [] as $f) {
            if (! empty($f['path']) && Storage::disk($disk)->exists($f['path'])) {
                Storage::disk($disk)->delete($f['path']);
            }
        }

        AuditLogger::log('deleted', $record, "ลบบันทึกบำรุงรักษา Client {$this->monthLabel($record->month)} ปี {$record->year}");
        $record->delete();

        return response()->json(['ok' => true]);
    }

    private function assertEditable(ClientMaRecord $record): void
    {
        if (! $record->isEditable()) {
            throw ValidationException::withMessages(['status' => ['รายการถูกส่ง/อนุมัติแล้ว ไม่สามารถแก้ไขได้']]);
        }
    }

    private function resolveSignature(Request $request, User $user): string
    {
        if ($request->filled('signature')) {
            $path = SignatureStorage::storeDataUrl($request->string('signature')->toString(), 'user'.$user->id);
            $user->update(['signature_path' => $path]);

            return $path;
        }
        if ($user->signature_path) {
            return $user->signature_path;
        }
        throw ValidationException::withMessages(['signature' => ['กรุณาวาด/บันทึกลายเซ็นก่อนลงชื่อ']]);
    }

    /**
     * @return array<string, bool>
     */
    private function defaultTasks(): array
    {
        return [
            'patch' => false,
            'antivirus' => false,
            'disk_cleanup' => false,
            'disk_space' => false,
            'software' => false,
            'reboot' => false,
            'agent' => false,
        ];
    }

    private function monthLabel(int $m): string
    {
        return self::MONTHS[$m] ?? (string) $m;
    }

    /**
     * @return array<string, mixed>
     */
    private function full(ClientMaRecord $record): array
    {
        $record->load(['entries.machine', 'formTemplate']);

        $counts = ['done' => 0, 'issue' => 0, 'na' => 0];
        foreach ($record->entries as $e) {
            $counts[$e->status] = ($counts[$e->status] ?? 0) + 1;
        }

        return array_merge($record->toArray(), [
            'summary' => array_merge($counts, ['total' => $record->entries->count()]),
            'doc_code' => $record->formTemplate?->code ?? 'FM-IT-03',
            'revision' => $record->formTemplate?->revision,
        ]);
    }
}
