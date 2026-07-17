<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClientMachine;
use App\Models\CorrectiveAction;
use App\Models\Server;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CorrectiveActionController extends Controller
{
    /** short alias => asset model the finding belongs to (polymorphic subject) */
    private const SUBJECTS = [
        'client_machine' => ClientMachine::class,
        'server' => Server::class,
    ];

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subject_type' => ['required', Rule::in(array_keys(self::SUBJECTS))],
            'subject_id' => ['required', 'integer'],
        ]);

        $items = CorrectiveAction::where('subject_type', self::SUBJECTS[$data['subject_type']])
            ->where('subject_id', $data['subject_id'])
            ->latest()
            ->get();

        return response()->json(['data' => $items]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request, true);
        $data['subject_type'] = self::SUBJECTS[$data['subject_type']];
        $data['created_by'] = $request->user()->id;
        $data['created_by_name'] = $request->user()->name;

        $item = CorrectiveAction::create($data);
        AuditLogger::log('created', $item, 'บันทึกการแก้ไขปัญหา: '.mb_substr($item->finding, 0, 60));

        return response()->json(['data' => $item], 201);
    }

    public function update(Request $request, CorrectiveAction $correctiveAction): JsonResponse
    {
        $correctiveAction->update($this->validated($request, false));

        return response()->json(['data' => $correctiveAction->fresh()]);
    }

    public function destroy(CorrectiveAction $correctiveAction): JsonResponse
    {
        $correctiveAction->delete();

        return response()->json(['ok' => true]);
    }

    /**
     * @return array<string,mixed>
     */
    private function validated(Request $request, bool $withSubject): array
    {
        $rules = [
            'ref' => ['nullable', 'string', 'max:255'],
            'finding' => ['required', 'string', 'max:2000'],
            'action' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(['resolved', 'pending'])],
            'round_ref' => ['nullable', 'string', 'max:255'],
            'occurred_on' => ['nullable', 'date'],
        ];
        if ($withSubject) {
            $rules['subject_type'] = ['required', Rule::in(array_keys(self::SUBJECTS))];
            $rules['subject_id'] = ['required', 'integer'];
        }

        return $request->validate($rules);
    }
}
