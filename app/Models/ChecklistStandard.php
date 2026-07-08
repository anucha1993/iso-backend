<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChecklistStandard extends Model
{
    public const STATUS_NORMAL = 'normal'; // ปกติ
    public const STATUS_RISK   = 'risk';   // เสี่ยง
    public const STATUS_FAULT  = 'fault';  // ขัดข้อง

    protected $fillable = [
        'metric_key',
        'label',
        'unit',
        'direction',
        'warn_threshold',
        'critical_threshold',
        'normal_text',
        'risk_text',
        'fault_text',
        'order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'warn_threshold' => 'float',
            'critical_threshold' => 'float',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Classify a numeric reading against this standard.
     *
     * @return string|null one of STATUS_NORMAL|STATUS_RISK|STATUS_FAULT, or null when no value.
     */
    public function classify(?float $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($this->direction === 'lower_better') {
            // e.g. CPU: <=warn normal, <=critical risk, else fault
            if ($value <= $this->warn_threshold) {
                return self::STATUS_NORMAL;
            }
            if ($value <= $this->critical_threshold) {
                return self::STATUS_RISK;
            }

            return self::STATUS_FAULT;
        }

        // higher_better: e.g. free % — >=warn normal, >=critical risk, else fault
        if ($value >= $this->warn_threshold) {
            return self::STATUS_NORMAL;
        }
        if ($value >= $this->critical_threshold) {
            return self::STATUS_RISK;
        }

        return self::STATUS_FAULT;
    }
}
