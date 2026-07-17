<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChecklistItem;
use App\Models\ChecklistStandard;
use App\Models\ClientMaRecord;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

class ClientMaPdfController extends Controller
{
    private const MONTHS = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

    public function show(Request $request, ClientMaRecord $record)
    {
        $record->load(['entries.machine', 'formTemplate']);
        $template = $record->formTemplate;

        $tasksDef = ChecklistItem::where('is_active', true)
            ->where('form_template_id', $record->form_template_id)
            ->whereNotNull('key')
            ->orderBy('order')->get()
            ->map(fn ($i) => ['key' => (string) $i->key, 'name' => $i->name])->values()->all();

        $standards = ChecklistStandard::effectiveFor($record->form_template_id);

        $rows = [];
        $entries = $record->entries->sortBy(fn ($e) => sprintf('%s|%s', $e->snap_floor ?? '~', $e->snap_name ?? ''));
        foreach ($entries as $e) {
            $tasks = $this->toArray($e->tasks);
            $metricsRaw = $this->toArray($e->metrics);

            $metricCells = [];
            foreach ($standards as $std) {
                $raw = $metricsRaw[$std->metric_key] ?? null;
                $metricCells[] = [
                    'val' => ($raw === null || $raw === '') ? '' : (string) $raw,
                    'status' => $this->classify($std, $raw),
                ];
            }

            $taskCells = [];
            foreach ($tasksDef as $t) {
                $taskCells[] = ! empty($tasks[$t['key']]);
            }

            $rows[] = [
                'name' => $e->snap_name ?: ($e->machine?->name ?? ('#'.$e->client_machine_id)),
                'floor' => $e->snap_floor ?: '',
                'metrics' => $metricCells,
                'tasks' => $taskCells,
                'issue' => $e->status === 'issue',
                'na' => $e->status === 'na',
                'note' => $e->note ?? '',
            ];
        }

        $counts = ['done' => 0, 'issue' => 0, 'na' => 0];
        foreach ($record->entries as $e) {
            $counts[$e->status] = ($counts[$e->status] ?? 0) + 1;
        }

        $statusLabels = ['draft' => 'ร่าง', 'submitted' => 'รออนุมัติ', 'approved' => 'อนุมัติแล้ว', 'rejected' => 'ตีกลับ'];
        $classification = ($template?->confidentiality === 'confidential') ? 'ข้อมูลลับ' : 'ข้อมูลทั่วไป';
        $effective = $template?->effective_date
            ? $template->effective_date->format('d/m/').($template->effective_date->year + 543)
            : '-';

        $data = [
            'docCode' => $template?->code ?? 'FM-IT-03',
            'rev' => $template?->revision ?? '',
            'effective' => $effective,
            'monthLabel' => self::MONTHS[$record->month] ?? (string) $record->month,
            'year' => $record->year,
            'responsible' => $record->responsible ?? '-',
            'statusLabel' => $statusLabels[$record->status] ?? $record->status,
            'classification' => $classification,
            'machineCount' => $record->entries->count(),
            'counts' => $counts,
            'tasksDef' => $tasksDef,
            'standards' => array_map(fn ($s) => [
                'label' => $s->label,
                'short' => $this->short($s),
                'unit' => $s->unit,
                'normal' => $s->normal_text,
                'risk' => $s->risk_text,
                'fault' => $s->fault_text,
            ], $standards->all()),
            'rows' => $rows,
            'note' => $record->note,
            'logo' => $this->fileDataUri(public_path('images/company-logo.png')),
            'preparedName' => $record->prepared_name,
            'preparedPosition' => $record->prepared_position,
            'preparedDate' => $this->beDate($record->prepared_signed_at),
            'preparedSig' => $this->dataUri($record->prepared_signature_path),
            'approvedName' => $record->approved_name,
            'approvedPosition' => $record->approved_position,
            'approvedDate' => $this->beDate($record->approved_signed_at),
            'approvedSig' => $this->dataUri($record->approved_signature_path),
        ];

        $html = View::make('pdf.client-ma-record', $data)->render();

        $footer = '<table width="100%" style="font-family: sarabun; font-size:7pt; color:#94a3b8;"><tr>'
            .'<td width="50%">วันที่บังคับใช้: '.e($effective).'</td>'
            .'<td width="50%" style="text-align:right;">'.e($data['docCode']).' (Rev'.e($data['rev']).')('.e($classification).')</td>'
            .'</tr></table>';

        $tmp = storage_path('app/mpdf');
        File::ensureDirectoryExists($tmp);

        $defaultFontDirs = (new \Mpdf\Config\ConfigVariables())->getDefaults()['fontDir'];
        $defaultFontData = (new \Mpdf\Config\FontVariables())->getDefaults()['fontdata'];

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'fontDir' => array_merge($defaultFontDirs, [resource_path('fonts')]),
            'fontdata' => $defaultFontData + [
                'sarabun' => [
                    'R' => 'Sarabun-Regular.ttf',
                    'B' => 'Sarabun-Bold.ttf',
                    'I' => 'Sarabun-Italic.ttf',
                    'BI' => 'Sarabun-BoldItalic.ttf',
                ],
            ],
            'default_font' => 'sarabun',
            'default_font_size' => 8,
            'margin_top' => 8,
            'margin_bottom' => 12,
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_footer' => 5,
            'tempDir' => $tmp,
        ]);
        $mpdf->SetHTMLFooter($footer);
        $mpdf->WriteHTML($html);

        AuditLogger::log('viewed', $record, 'ออกเอกสาร PDF: '.$data['docCode'].' '.$data['monthLabel'].' ปี '.$data['year']);

        $filename = $data['docCode'].'-'.$data['monthLabel'].$data['year'].'.pdf';

        return response($mpdf->Output($filename, Destination::STRING_RETURN), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(mixed $v): array
    {
        if (is_array($v)) {
            return $v;
        }
        if (is_string($v) && $v !== '') {
            $decoded = json_decode($v, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    private function classify(ChecklistStandard $std, mixed $raw): ?string
    {
        if ($raw === null || $raw === '' || ! is_numeric($raw)) {
            return null;
        }
        $v = (float) $raw;
        if ($std->direction === 'lower_better') {
            if ($v <= $std->warn_threshold) {
                return 'normal';
            }
            if ($v <= $std->critical_threshold) {
                return 'risk';
            }

            return 'fault';
        }
        if ($v >= $std->warn_threshold) {
            return 'normal';
        }
        if ($v >= $std->critical_threshold) {
            return 'risk';
        }

        return 'fault';
    }

    private function short(ChecklistStandard $s): string
    {
        $raw = trim((string) ($s->label ?? $s->metric_key));
        // Legacy labels like "CPU: Load usage (%)" collapse to prefix; concise ones (e.g. "Disk C: Free") render as-is.
        $base = preg_match('/\(.+\)/', $raw) ? trim(explode(':', $raw)[0]) : $raw;

        return $s->unit ? $base.' '.$s->unit : $base;
    }

    private function beDate(?Carbon $d): string
    {
        return $d ? $d->format('d/m/').($d->year + 543) : '';
    }

    private function dataUri(?string $path): ?string
    {
        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode(Storage::disk('public')->get($path));
    }

    private function fileDataUri(string $path): ?string
    {
        if (! is_file($path)) {
            return null;
        }

        $mime = str_ends_with(strtolower($path), '.png') ? 'image/png' : 'image/jpeg';

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($path));
    }
}
