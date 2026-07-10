<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChecklistItem;
use App\Models\ChecklistStandard;
use App\Models\MaintenanceEntry;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChecklistController extends Controller
{
    /**
     * Master checklist tasks (FM-IT-02). Active only, unless a manager asks for all.
     */
    public function items(Request $request): JsonResponse
    {
        $query = ChecklistItem::orderBy('order');

        if ($templateId = $request->integer('form_template_id')) {
            $query->where('form_template_id', $templateId);
        }

        if (! ($request->boolean('all') && $request->user()?->can('users.manage'))) {
            $query->where('is_active', true);
        }

        return response()->json(['data' => $query->get()]);
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

    public function storeItem(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $data['order'] = $data['order'] ?? ((int) ChecklistItem::where('form_template_id', $data['form_template_id'])->max('order') + 1);
        $data['is_active'] = $data['is_active'] ?? true;

        $item = ChecklistItem::create($data);
        AuditLogger::log('created', $item, "เพิ่มรายการ Checklist: {$item->name}");

        return response()->json(['data' => $item], 201);
    }

    public function updateItem(Request $request, ChecklistItem $item): JsonResponse
    {
        $data = $this->validated($request);
        $item->update($data);
        AuditLogger::log('updated', $item, "แก้ไขรายการ Checklist: {$item->name}");

        return response()->json(['data' => $item->fresh()]);
    }

    public function destroyItem(ChecklistItem $item): JsonResponse
    {
        // Keep history intact: if the item is referenced by any record entry,
        // deactivate instead of hard-deleting.
        if (MaintenanceEntry::where('checklist_item_id', $item->id)->exists()) {
            $item->update(['is_active' => false]);
            AuditLogger::log('updated', $item, "ปิดใช้งานรายการ Checklist (มีข้อมูลอ้างอิง): {$item->name}");

            return response()->json(['data' => $item->fresh(), 'soft' => true]);
        }

        $name = $item->name;
        $item->delete();
        AuditLogger::log('deleted', $item, "ลบรายการ Checklist: {$name}");

        return response()->json(['ok' => true]);
    }

    /**
     * @return array<string,mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'form_template_id' => ['required', 'exists:form_templates,id'],
            'name' => ['required', 'string', 'max:255'],
            'frequency_note' => ['nullable', 'string', 'max:100'],
            'iso_control' => ['nullable', 'string', 'max:50'],
            'order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);
    }
}
