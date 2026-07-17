<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChecklistItem;
use App\Models\ChecklistStandard;
use App\Models\MaintenanceEntry;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
     * Threshold standards. With ?form_template_id → that form's effective set
     * (its own if any, else the global default). ?admin=1 (manager) includes
     * inactive rows and reports whether the set is inherited from global.
     */
    public function standards(Request $request): JsonResponse
    {
        $formId = $request->integer('form_template_id') ?: null;

        return response()->json(['data' => ChecklistStandard::effectiveFor($formId)]);
    }

    public function storeItem(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $data['order'] = $data['order'] ?? ((int) ChecklistItem::where('form_template_id', $data['form_template_id'])->max('order') + 1);
        $data['is_active'] = $data['is_active'] ?? true;

        $item = ChecklistItem::create($data);
        if (empty($item->key)) {
            $item->update(['key' => 'item'.$item->id]);
        }
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

    public function storeStandard(Request $request): JsonResponse
    {
        $data = $this->validatedStandard($request);
        $data['order'] = $data['order'] ?? ((int) ChecklistStandard::where('standard_profile_id', $data['standard_profile_id'])->max('order') + 1);
        $data['is_active'] = $data['is_active'] ?? true;

        $std = ChecklistStandard::create($data);
        AuditLogger::log('created', $std, "เพิ่มเกณฑ์มาตรฐาน: {$std->label}");

        return response()->json(['data' => $std], 201);
    }

    public function updateStandard(Request $request, ChecklistStandard $standard): JsonResponse
    {
        $standard->update($this->validatedStandard($request));
        AuditLogger::log('updated', $standard, "แก้ไขเกณฑ์มาตรฐาน: {$standard->label}");

        return response()->json(['data' => $standard->fresh()]);
    }

    public function destroyStandard(ChecklistStandard $standard): JsonResponse
    {
        $label = $standard->label;
        $standard->delete();
        AuditLogger::log('deleted', $standard, "ลบเกณฑ์มาตรฐาน: {$label}");

        return response()->json(['ok' => true]);
    }

    /**
     * @return array<string,mixed>
     */
    private function validatedStandard(Request $request): array
    {
        return $request->validate([
            'standard_profile_id' => ['required', 'exists:standard_profiles,id'],
            'metric_key' => ['required', 'string', 'max:100'],
            'label' => ['required', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:20'],
            'direction' => ['required', Rule::in(['lower_better', 'higher_better'])],
            'warn_threshold' => ['required', 'numeric'],
            'critical_threshold' => ['required', 'numeric'],
            'normal_text' => ['nullable', 'string', 'max:100'],
            'risk_text' => ['nullable', 'string', 'max:100'],
            'fault_text' => ['nullable', 'string', 'max:100'],
            'order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);
    }

    /**
     * @return array<string,mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'form_template_id' => ['required', 'exists:form_templates,id'],
            'name' => ['required', 'string', 'max:255'],
            'key' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'check_method' => ['nullable', 'string', 'max:2000'],
            'frequency_note' => ['nullable', 'string', 'max:100'],
            'iso_control' => ['nullable', 'string', 'max:50'],
            'order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);
    }
}
