<?php

namespace App\Support;

use App\Models\ChecklistStandard;
use App\Models\MaintenanceRecord;

/**
 * Analyses a maintenance record's monthly readings against the configured
 * standards and summarises the check grid (ปกติ / เสี่ยง / ขัดข้อง).
 */
class ChecklistAnalyzer
{
    /**
     * @return array<string,mixed>
     */
    public function analyze(MaintenanceRecord $record): array
    {
        $record->loadMissing(['readings', 'entries']);

        $standards = ChecklistStandard::effectiveFor($record->form_template_id);
        $readings = $record->readings->keyBy('month');
        $months = range(1, 12);

        $summary = [
            ChecklistStandard::STATUS_NORMAL => 0,
            ChecklistStandard::STATUS_RISK => 0,
            ChecklistStandard::STATUS_FAULT => 0,
        ];

        $metrics = [];
        $attention = []; // months needing attention (risk/fault)

        foreach ($standards as $std) {
            $field = $std->metric_key; // cpu_load | memory_used | disk_used
            $monthsData = [];

            foreach ($months as $m) {
                $reading = $readings->get($m);
                $value = $reading ? $reading->{$field} : null;
                $value = $value === null ? null : (float) $value;
                $status = $std->classify($value);

                if ($status !== null) {
                    $summary[$status]++;
                }

                if (in_array($status, [ChecklistStandard::STATUS_RISK, ChecklistStandard::STATUS_FAULT], true)) {
                    $attention[] = [
                        'metric_key' => $std->metric_key,
                        'label' => $std->label,
                        'month' => $m,
                        'value' => $value,
                        'status' => $status,
                    ];
                }

                $monthsData[] = [
                    'month' => $m,
                    'value' => $value,
                    'status' => $status,
                ];
            }

            $metrics[] = [
                'metric_key' => $std->metric_key,
                'label' => $std->label,
                'unit' => $std->unit,
                'direction' => $std->direction,
                'standard' => [
                    'normal_text' => $std->normal_text,
                    'risk_text' => $std->risk_text,
                    'fault_text' => $std->fault_text,
                    'warn_threshold' => $std->warn_threshold,
                    'critical_threshold' => $std->critical_threshold,
                ],
                'months' => $monthsData,
            ];
        }

        // Grid faults: checklist items marked 'X' (fault) by month.
        $faultEntries = $record->entries
            ->where('status', 'fault')
            ->map(fn ($e) => ['checklist_item_id' => $e->checklist_item_id, 'month' => $e->month])
            ->values();

        $total = array_sum($summary);
        $healthScore = $total > 0
            ? round((($summary[ChecklistStandard::STATUS_NORMAL] * 100) + ($summary[ChecklistStandard::STATUS_RISK] * 50)) / ($total * 100) * 100, 1)
            : null;

        return [
            'summary' => $summary,
            'health_score' => $healthScore, // 0-100, higher = healthier
            'metrics' => $metrics,
            'attention' => $attention,
            'fault_entries' => $faultEntries,
        ];
    }
}
