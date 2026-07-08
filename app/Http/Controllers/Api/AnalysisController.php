<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceRecord;
use App\Support\ChecklistAnalyzer;
use Illuminate\Http\JsonResponse;

class AnalysisController extends Controller
{
    public function __construct(private readonly ChecklistAnalyzer $analyzer)
    {
    }

    /**
     * Analyse a single maintenance record against the standards.
     */
    public function record(MaintenanceRecord $record): JsonResponse
    {
        return response()->json([
            'record_id' => $record->id,
            'analysis' => $this->analyzer->analyze($record),
        ]);
    }

    /**
     * Aggregate dashboard summary across all records (optionally by year).
     */
    public function summary(\Illuminate\Http\Request $request): JsonResponse
    {
        $query = MaintenanceRecord::query();
        if ($year = $request->integer('year')) {
            $query->where('year', $year);
        }

        $records = $query->with(['server:id,name', 'readings'])->get();

        $totals = ['normal' => 0, 'risk' => 0, 'fault' => 0];
        $byStatus = [
            MaintenanceRecord::STATUS_DRAFT => 0,
            MaintenanceRecord::STATUS_SUBMITTED => 0,
            MaintenanceRecord::STATUS_APPROVED => 0,
            MaintenanceRecord::STATUS_REJECTED => 0,
        ];
        $perRecord = [];

        foreach ($records as $record) {
            $analysis = $this->analyzer->analyze($record);
            foreach ($totals as $key => $_) {
                $totals[$key] += $analysis['summary'][$key] ?? 0;
            }
            $byStatus[$record->status] = ($byStatus[$record->status] ?? 0) + 1;

            $perRecord[] = [
                'record_id' => $record->id,
                'server' => $record->server?->name,
                'year' => $record->year,
                'status' => $record->status,
                'summary' => $analysis['summary'],
                'health_score' => $analysis['health_score'],
                'attention_count' => count($analysis['attention']),
            ];
        }

        return response()->json([
            'totals' => $totals,
            'records_by_status' => $byStatus,
            'records' => $perRecord,
            'record_count' => $records->count(),
        ]);
    }
}
