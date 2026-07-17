<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChecklistStandard;
use App\Models\FormTemplate;
use App\Models\StandardProfile;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StandardProfileController extends Controller
{
    public function index(): JsonResponse
    {
        $profiles = StandardProfile::orderBy('order')->orderBy('name')
            ->withCount(['standards', 'formTemplates'])
            ->get();

        return response()->json(['data' => $profiles]);
    }

    public function show(StandardProfile $profile): JsonResponse
    {
        $profile->load('standards');
        $forms = FormTemplate::where('standard_profile_id', $profile->id)->get(['id', 'code', 'name']);

        return response()->json(['data' => array_merge($profile->toArray(), ['forms' => $forms])]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $data['order'] = $data['order'] ?? ((int) StandardProfile::max('order') + 1);
        $data['is_active'] = $data['is_active'] ?? true;
        $profile = StandardProfile::create($data);
        AuditLogger::log('created', $profile, "เพิ่มโปรไฟล์เกณฑ์: {$profile->name}");

        return response()->json(['data' => $profile], 201);
    }

    public function update(Request $request, StandardProfile $profile): JsonResponse
    {
        $profile->update($this->validated($request));
        AuditLogger::log('updated', $profile, "แก้ไขโปรไฟล์เกณฑ์: {$profile->name}");

        return response()->json(['data' => $profile->fresh()]);
    }

    public function destroy(StandardProfile $profile): JsonResponse
    {
        $name = $profile->name;
        FormTemplate::where('standard_profile_id', $profile->id)->update(['standard_profile_id' => null]);
        ChecklistStandard::where('standard_profile_id', $profile->id)->delete();
        $profile->delete();
        AuditLogger::log('deleted', null, "ลบโปรไฟล์เกณฑ์: {$name}");

        return response()->json(['ok' => true]);
    }

    /**
     * @return array<string,mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);
    }
}
