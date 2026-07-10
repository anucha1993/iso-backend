<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceRecord;
use App\Models\RecordAttachment;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class RecordAttachmentController extends Controller
{
    /**
     * Attach an evidence file (image / PDF) to a record.
     */
    public function store(Request $request, MaintenanceRecord $record): JsonResponse
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'], // 10 MB
            'month' => ['required', 'integer', 'between:1,12'],
            'caption' => ['nullable', 'string', 'max:255'],
        ], [
            'month.required' => 'กรุณาระบุรอบเดือนของหลักฐานก่อนแนบไฟล์',
            'month.between' => 'รอบเดือนไม่ถูกต้อง',
        ]);

        $file = $request->file('file');
        $disk = config('filesystems.attachment', 'public');
        $path = $file->store('attachments/'.$record->id, $disk);

        $attachment = $record->attachments()->create([
            'month' => $data['month'] ?? null,
            'caption' => $data['caption'] ?? null,
            'original_name' => $file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => $request->user()->id,
        ]);

        AuditLogger::log('created', $record, 'แนบหลักฐาน: '.$file->getClientOriginalName());

        return response()->json(['data' => $attachment], 201);
    }

    public function destroy(Request $request, MaintenanceRecord $record, RecordAttachment $attachment): JsonResponse
    {
        abort_unless($attachment->maintenance_record_id === $record->id, 404);

        if (! $record->isEditable() && ! $request->user()->can('records.approve')) {
            throw ValidationException::withMessages([
                'file' => ['ไม่สามารถลบไฟล์ได้ เนื่องจากแบบฟอร์มถูกส่ง/อนุมัติแล้ว'],
            ]);
        }

        $disk = config('filesystems.attachment', 'public');
        if ($attachment->path && Storage::disk($disk)->exists($attachment->path)) {
            Storage::disk($disk)->delete($attachment->path);
        }

        $name = $attachment->original_name;
        $attachment->delete();
        AuditLogger::log('updated', $record, 'ลบหลักฐาน: '.$name);

        return response()->json(['ok' => true]);
    }
}
