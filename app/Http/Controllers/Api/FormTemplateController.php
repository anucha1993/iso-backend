<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FormTemplate;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FormTemplateController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request, null);
        $template = FormTemplate::create($data);
        AuditLogger::log('created', $template, "เพิ่มแบบฟอร์มในทะเบียน {$template->code}");

        return response()->json(['data' => $template->load('category:id,name')], 201);
    }

    public function update(Request $request, FormTemplate $formTemplate): JsonResponse
    {
        $data = $this->validated($request, $formTemplate->id);
        $formTemplate->update($data);
        AuditLogger::log('updated', $formTemplate, "แก้ไขทะเบียนแบบฟอร์ม {$formTemplate->code}", [
            'revision' => $formTemplate->revision,
            'confidentiality' => $formTemplate->confidentiality,
            'dar_log' => $formTemplate->dar_log,
        ]);

        return response()->json(['data' => $formTemplate->fresh()->load('category:id,name')]);
    }

    /**
     * @return array<string,mixed>
     */
    private function validated(Request $request, ?int $id): array
    {
        return $request->validate([
            'form_category_id' => ['required', 'exists:form_categories,id'],
            'code' => ['required', 'string', 'max:100', Rule::unique('form_templates', 'code')->ignore($id)],
            'name' => ['required', 'string', 'max:255'],
            'revision' => ['nullable', 'string', 'max:20'],
            'frequency_note' => ['nullable', 'string', 'max:100'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'module_key' => ['required', 'string', 'max:100'],
            'standard_profile_id' => ['nullable', 'exists:standard_profiles,id'],
            'route' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'confidentiality' => ['required', Rule::in(['general', 'confidential'])],
            'dar_log' => ['nullable', 'string', 'max:100'],
            'effective_date' => ['nullable', 'date'],
            'order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);
    }
}
