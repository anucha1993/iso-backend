<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceRecord;
use App\Models\MaintenanceRound;
use App\Models\Server;
use App\Models\User;
use App\Notifications\WorkflowNotification;
use App\Support\AuditLogger;
use App\Support\ChecklistAnalyzer;
use App\Support\SignatureStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MaintenanceRecordController extends Controller
{
    public function __construct(private readonly ChecklistAnalyzer $analyzer)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $query = MaintenanceRecord::query()
            ->with(['server:id,name', 'preparedBy:id,name', 'approvedBy:id,name'])
            ->latest();

        if ($serverId = $request->integer('server_id')) {
            $query->where('server_id', $serverId);
        }
        if ($year = $request->integer('year')) {
            $query->where('year', $year);
        }
        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        return response()->json($query->paginate($request->integer('per_page', 20)));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'server_id' => ['required', 'exists:servers,id'],
            'year' => ['required', 'integer', 'min:2400', 'max:2700'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        if (MaintenanceRecord::where('server_id', $data['server_id'])->where('year', $data['year'])->exists()) {
            throw ValidationException::withMessages([
                'year' => ['มีแบบฟอร์มของเซิร์ฟเวอร์นี้ในปีดังกล่าวอยู่แล้ว'],
            ]);
        }

        $server = Server::findOrFail($data['server_id']);
        $template = \App\Models\FormTemplate::where('module_key', 'server_maintenance')->first();

        $record = DB::transaction(function () use ($data, $server, $template) {
            $record = MaintenanceRecord::create([
                'form_template_id' => $template?->id,
                'created_revision' => $template?->revision,
                'server_id' => $server->id,
                'year' => $data['year'],
                'responsible' => $server->responsible,
                'status' => MaintenanceRecord::STATUS_DRAFT,
                'note' => $data['note'] ?? null,
            ]);

            // Pre-create the 12 monthly reading rows.
            $rows = [];
            foreach (range(1, 12) as $m) {
                $rows[] = [
                    'maintenance_record_id' => $record->id,
                    'month' => $m,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            DB::table('maintenance_readings')->insert($rows);

            // Pre-create the 12 monthly approval rounds (draft).
            $roundRows = [];
            foreach (range(1, 12) as $m) {
                $roundRows[] = [
                    'maintenance_record_id' => $record->id,
                    'month' => $m,
                    'status' => MaintenanceRecord::STATUS_DRAFT,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            DB::table('maintenance_rounds')->insert($roundRows);

            return $record;
        });

        AuditLogger::log('created', $record, "สร้างแบบฟอร์มบำรุงรักษา {$server->name} ปี {$record->year}");

        return response()->json(['data' => $this->fullRecord($record)], 201);
    }

    public function show(MaintenanceRecord $record): JsonResponse
    {
        return response()->json(['data' => $this->fullRecord($record)]);
    }

    public function destroy(Request $request, MaintenanceRecord $record): JsonResponse
    {
        // Enforce two-step deletion: all evidence files must be removed first.
        $attachmentCount = $record->attachments()->count();
        if ($attachmentCount > 0) {
            throw ValidationException::withMessages([
                'attachments' => ["ไม่สามารถลบแบบฟอร์มได้ กรุณาลบไฟล์หลักฐานที่แนบทั้งหมดก่อน (เหลือ {$attachmentCount} ไฟล์)"],
            ]);
        }

        $label = ($record->server?->name ?? ('#'.$record->id)).' · ปี '.$record->year;
        AuditLogger::log('deleted', $record, 'ลบแบบฟอร์มบำรุงรักษา: '.$label);

        DB::transaction(function () use ($record) {
            $record->entries()->delete();
            $record->readings()->delete();
            $record->rounds()->delete();
            $record->delete();
        });

        return response()->json(['ok' => true]);
    }

    public function update(Request $request, MaintenanceRecord $record): JsonResponse
    {
        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:2000'],
            'responsible' => ['nullable', 'string', 'max:255'],
            'na_checklist_items' => ['nullable', 'array'],
            'na_checklist_items.*' => ['integer'],

            'readings' => ['array'],
            'readings.*.month' => ['required', 'integer', 'between:1,12'],
            'readings.*.check_date' => ['nullable', 'date'],
            'readings.*.cpu_load' => ['nullable', 'numeric', 'between:0,100'],
            'readings.*.memory_used' => ['nullable', 'numeric', 'between:0,100'],
            'readings.*.disk_used' => ['nullable', 'numeric', 'between:0,100'],
            'readings.*.note' => ['nullable', 'string', 'max:2000'],

            'entries' => ['array'],
            'entries.*.checklist_item_id' => ['required', 'exists:checklist_items,id'],
            'entries.*.month' => ['required', 'integer', 'between:1,12'],
            'entries.*.status' => ['required', Rule::in(['checked', 'fault'])],
        ]);

        // Per-month lock: a month that is submitted/approved is read-only for
        // everyone except admin / approver (หัวหน้าขึ้นไป).
        $override = $request->user()->can('records.approve');
        $editableMonths = $record->rounds()
            ->get()
            ->filter(fn ($r) => $override || $r->isEditable())
            ->pluck('month')
            ->all();

        DB::transaction(function () use ($record, $data, $editableMonths) {
            $record->update([
                'note' => $data['note'] ?? $record->note,
                'responsible' => $data['responsible'] ?? $record->responsible,
                'na_checklist_items' => $data['na_checklist_items'] ?? $record->na_checklist_items,
            ]);

            foreach ($data['readings'] ?? [] as $reading) {
                if (! in_array($reading['month'], $editableMonths, true)) {
                    continue; // month is locked — ignore
                }
                $record->readings()->updateOrCreate(
                    ['month' => $reading['month']],
                    [
                        'check_date' => $reading['check_date'] ?? null,
                        'cpu_load' => $reading['cpu_load'] ?? null,
                        'memory_used' => $reading['memory_used'] ?? null,
                        'disk_used' => $reading['disk_used'] ?? null,
                        'note' => $reading['note'] ?? null,
                    ]
                );
            }

            // Replace grid entries ONLY for editable months; keep locked months intact.
            $record->entries()->whereIn('month', $editableMonths)->delete();
            $entries = collect($data['entries'] ?? [])
                ->filter(fn ($e) => in_array($e['month'], $editableMonths, true))
                ->map(fn ($e) => [
                    'maintenance_record_id' => $record->id,
                    'checklist_item_id' => $e['checklist_item_id'],
                    'month' => $e['month'],
                    'status' => $e['status'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->all();

            if ($entries !== []) {
                DB::table('maintenance_entries')->insert($entries);
            }
        });

        AuditLogger::log('updated', $record, "บันทึกข้อมูลแบบฟอร์ม {$record->server->name} ปี {$record->year}");

        return response()->json(['data' => $this->fullRecord($record->fresh())]);
    }

    /**
     * Preparer signs & submits a single month/round (ผู้ตรวจเช็ค ลงชื่อและส่งรายเดือน).
     */
    public function submitMonth(Request $request, MaintenanceRecord $record, int $month): JsonResponse
    {
        $round = $this->roundFor($record, $month);

        if (! $round->isEditable()) {
            throw ValidationException::withMessages([
                'status' => ['เดือนนี้ถูกส่ง/อนุมัติแล้ว ไม่สามารถส่งซ้ำได้'],
            ]);
        }

        $request->validate(['signature' => ['nullable', 'string']]);
        $user = $request->user();
        $signaturePath = $this->resolveSignature($request, $user);

        $round->update([
            'status' => MaintenanceRound::STATUS_SUBMITTED,
            'prepared_by' => $user->id,
            'prepared_name' => $user->name,
            'prepared_position' => $user->position,
            'prepared_signature_path' => $signaturePath,
            'prepared_signed_at' => now(),
            'rejected_reason' => null,
            'rejected_at' => null,
        ]);

        $this->syncAggregate($record);
        AuditLogger::log('submitted', $record, "ส่งอนุมัติรอบเดือน {$month} — {$record->server->name} ปี {$record->year}");

        $approvers = User::permission('records.approve')->where('is_active', true)->where('id', '!=', $user->id)->get();
        Notification::send($approvers, new WorkflowNotification(
            'submitted',
            'มีคำขออนุมัติใหม่',
            "{$record->server->name} · {$this->monthName($month)} {$record->year} — รอการอนุมัติ (ส่งโดย {$user->name})",
            "/records/{$record->id}?month={$month}",
        ));

        return response()->json(['data' => $this->fullRecord($record->fresh())]);
    }

    /**
     * Approver signs & approves a single month/round (ผู้ตรวจสอบ อนุมัติรายเดือน).
     */
    public function approveMonth(Request $request, MaintenanceRecord $record, int $month): JsonResponse
    {
        $round = $this->roundFor($record, $month);

        if ($round->status !== MaintenanceRound::STATUS_SUBMITTED) {
            throw ValidationException::withMessages([
                'status' => ['ต้องเป็นรอบที่ส่งแล้วเท่านั้นจึงจะอนุมัติได้'],
            ]);
        }

        $request->validate(['signature' => ['nullable', 'string']]);
        $user = $request->user();
        $signaturePath = $this->resolveSignature($request, $user);

        $round->update([
            'status' => MaintenanceRound::STATUS_APPROVED,
            'approved_by' => $user->id,
            'approved_name' => $user->name,
            'approved_position' => $user->position,
            'approved_signature_path' => $signaturePath,
            'approved_signed_at' => now(),
        ]);

        $this->syncAggregate($record);
        AuditLogger::log('approved', $record, "อนุมัติรอบเดือน {$month} — {$record->server->name} ปี {$record->year}");

        if ($round->prepared_by && $round->prepared_by !== $user->id) {
            User::find($round->prepared_by)?->notify(new WorkflowNotification(
                'approved',
                'อนุมัติแล้ว ✓',
                "{$record->server->name} · {$this->monthName($month)} {$record->year} — อนุมัติโดย {$user->name}",
                "/records/{$record->id}?month={$month}",
            ));
        }

        return response()->json(['data' => $this->fullRecord($record->fresh())]);
    }

    /**
     * Approver rejects a single month/round (ตีกลับรายเดือน).
     */
    public function rejectMonth(Request $request, MaintenanceRecord $record, int $month): JsonResponse
    {
        $round = $this->roundFor($record, $month);

        if (! in_array($round->status, [MaintenanceRound::STATUS_SUBMITTED, MaintenanceRound::STATUS_APPROVED], true)) {
            throw ValidationException::withMessages([
                'status' => ['ต้องเป็นรอบที่ส่งแล้วหรืออนุมัติแล้วเท่านั้นจึงจะตีกลับได้'],
            ]);
        }

        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $user = $request->user();

        // Rejecting an approved round voids the approval and sends it back for revision.
        $round->update([
            'status' => MaintenanceRound::STATUS_REJECTED,
            'rejected_reason' => $data['reason'],
            'rejected_at' => now(),
            'approved_by' => null,
            'approved_name' => null,
            'approved_position' => null,
            'approved_signature_path' => null,
            'approved_signed_at' => null,
        ]);

        $this->syncAggregate($record);
        AuditLogger::log('rejected', $record, "ตีกลับรอบเดือน {$month} — {$record->server->name} ปี {$record->year}", [
            'reason' => $data['reason'],
        ]);

        if ($round->prepared_by && $round->prepared_by !== $user->id) {
            User::find($round->prepared_by)?->notify(new WorkflowNotification(
                'rejected',
                'ถูกตีกลับ — ต้องแก้ไข',
                "{$record->server->name} · {$this->monthName($month)} {$record->year} — เหตุผล: {$data['reason']}",
                "/records/{$record->id}?month={$month}",
            ));
        }

        return response()->json(['data' => $this->fullRecord($record->fresh())]);
    }

    private function monthName(int $m): string
    {
        $names = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
        return $names[$m] ?? (string) $m;
    }

    private function roundFor(MaintenanceRecord $record, int $month): MaintenanceRound
    {
        abort_unless($month >= 1 && $month <= 12, 404);

        return $record->rounds()->firstOrCreate(
            ['month' => $month],
            ['status' => MaintenanceRound::STATUS_DRAFT],
        );
    }

    /**
     * Recompute the record's overall status + overview signatures from its
     * rounds. Overall status priority: submitted > rejected > approved > draft.
     * Overview signatures = the latest (highest-month) signed round.
     */
    private function syncAggregate(MaintenanceRecord $record): void
    {
        $rounds = $record->rounds()->get();
        $statuses = $rounds->pluck('status');

        $status = MaintenanceRecord::STATUS_DRAFT;
        if ($statuses->contains(MaintenanceRound::STATUS_SUBMITTED)) {
            $status = MaintenanceRecord::STATUS_SUBMITTED;
        } elseif ($statuses->contains(MaintenanceRound::STATUS_REJECTED)) {
            $status = MaintenanceRecord::STATUS_REJECTED;
        } elseif ($statuses->contains(MaintenanceRound::STATUS_APPROVED)) {
            $status = MaintenanceRecord::STATUS_APPROVED;
        }

        $lastPrepared = $rounds->whereNotNull('prepared_signed_at')->sortBy('month')->last();
        $lastApproved = $rounds->whereNotNull('approved_signed_at')->sortBy('month')->last();

        $record->update([
            'status' => $status,
            'prepared_by' => $lastPrepared?->prepared_by,
            'prepared_name' => $lastPrepared?->prepared_name,
            'prepared_position' => $lastPrepared?->prepared_position,
            'prepared_signature_path' => $lastPrepared?->prepared_signature_path,
            'prepared_signed_at' => $lastPrepared?->prepared_signed_at,
            'approved_by' => $lastApproved?->approved_by,
            'approved_name' => $lastApproved?->approved_name,
            'approved_position' => $lastApproved?->approved_position,
            'approved_signature_path' => $lastApproved?->approved_signature_path,
            'approved_signed_at' => $lastApproved?->approved_signed_at,
        ]);
    }

    /**
     * Use the freshly-drawn signature (also saved to the user profile) or fall
     * back to the signature already stored on the user's profile.
     */
    private function resolveSignature(Request $request, User $user): string
    {
        if ($request->filled('signature')) {
            $path = SignatureStorage::storeDataUrl($request->string('signature')->toString(), 'user'.$user->id);
            $user->update(['signature_path' => $path]); // persist to user profile
            AuditLogger::log('updated', $user, 'บันทึกลายเซ็นในโปรไฟล์ (ขณะลงชื่อ)', [], $user->id);

            return $path;
        }

        if ($user->signature_path) {
            return $user->signature_path;
        }

        throw ValidationException::withMessages([
            'signature' => ['กรุณาบันทึกลายเซ็นก่อน หรือวาดลายเซ็นเพื่อลงชื่อ'],
        ]);
    }

    /**
     * Load a record with everything the detail/analysis view needs.
     *
     * @return array<string,mixed>
     */
    private function fullRecord(MaintenanceRecord $record): array
    {
        $record->load([
            'server',
            'preparedBy:id,name,position',
            'approvedBy:id,name,position',
            'entries',
            'readings',
            'rounds',
            'attachments',
            'formTemplate:id,code,name,revision,frequency_note',
        ]);

        return array_merge($record->toArray(), [
            'prepared_signature_url' => $this->signatureUrl($record->prepared_signature_path),
            'approved_signature_url' => $this->signatureUrl($record->approved_signature_path),
            'analysis' => $this->analyzer->analyze($record),
        ]);
    }

    private function signatureUrl(?string $path): ?string
    {
        return $path ? \Illuminate\Support\Facades\Storage::disk('public')->url($path) : null;
    }
}
