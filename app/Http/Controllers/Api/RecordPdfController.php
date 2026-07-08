<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChecklistItem;
use App\Models\ChecklistStandard;
use App\Models\MaintenanceRecord;
use App\Models\MaintenanceRound;
use App\Support\AuditLogger;
use App\Support\ChecklistAnalyzer;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

class RecordPdfController extends Controller
{
    private const MONTHS = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

    public function __construct(private readonly ChecklistAnalyzer $analyzer)
    {
    }

    public function show(Request $request, MaintenanceRecord $record)
    {
        $record->load(['server', 'preparedBy', 'approvedBy', 'entries', 'readings', 'rounds', 'formTemplate']);
        $template = $record->formTemplate;
        $analysis = $this->analyzer->analyze($record);

        // Which round (month) does this printout represent?
        $monthParam = (int) $request->integer('month');
        if ($monthParam >= 1 && $monthParam <= 12) {
            $targetRound = $record->rounds->firstWhere('month', $monthParam);
        } else {
            $targetRound = $record->rounds->whereNotNull('approved_signed_at')->sortBy('month')->last()
                ?? $record->rounds->whereNotNull('prepared_signed_at')->sortBy('month')->last();
        }

        // A month that is not yet approved may only be printed by approver/admin (หัวหน้าขึ้นไป).
        $roundApproved = $targetRound && $targetRound->status === MaintenanceRound::STATUS_APPROVED;
        if (! $roundApproved && ! $request->user()->can('records.approve')) {
            abort(403, 'เดือนนี้ยังไม่ได้รับการอนุมัติ จึงพิมพ์ได้เฉพาะหัวหน้า/ผู้ดูแลระบบเท่านั้น');
        }

        $items = ChecklistItem::where('is_active', true)->orderBy('order')->get()
            ->map(fn ($i) => ['id' => $i->id, 'name' => $i->name, 'freq' => $i->frequency_note])->all();

        $standards = ChecklistStandard::where('is_active', true)->orderBy('order')->get()
            ->map(fn ($s) => ['label' => $s->label, 'normal' => $s->normal_text, 'risk' => $s->risk_text, 'fault' => $s->fault_text])->all();

        $grid = [];
        foreach ($record->entries as $e) {
            $grid[$e->checklist_item_id][$e->month] = $e->status === 'fault' ? 'X' : '/';
        }

        // metric_key -> month -> status (for colour coding the readings)
        $mstat = [];
        foreach ($analysis['metrics'] as $m) {
            foreach ($m['months'] as $mm) {
                $mstat[$m['metric_key']][$mm['month']] = $mm['status'];
            }
        }

        $readings = [];
        foreach (range(1, 12) as $mo) {
            $r = $record->readings->firstWhere('month', $mo);
            $readings[$mo] = [
                'date' => ($r && $r->check_date) ? $r->check_date->format('d/m/').($r->check_date->year + 543) : '',
                'cpu' => $this->num($r?->cpu_load),
                'mem' => $this->num($r?->memory_used),
                'disk' => $this->num($r?->disk_used),
                'note' => $r?->note ?? '',
                'cpu_s' => $mstat['cpu_load'][$mo] ?? null,
                'mem_s' => $mstat['memory_used'][$mo] ?? null,
                'disk_s' => $mstat['disk_used'][$mo] ?? null,
            ];
        }

        $statusLabels = ['draft' => 'ร่าง', 'submitted' => 'รออนุมัติ', 'approved' => 'อนุมัติแล้ว', 'rejected' => 'ตีกลับ'];
        $classification = ($template?->confidentiality === 'confidential') ? 'ข้อมูลลับ' : 'ข้อมูลทั่วไป';
        $effective = $template?->effective_date
            ? $template->effective_date->format('d/m/').($template->effective_date->year + 543)
            : '-';

        $roundLabel = ($monthParam >= 1 && $monthParam <= 12)
            ? 'รอบ '.self::MONTHS[$monthParam - 1]
            : ($targetRound ? 'ภาพรวม (รอบ '.self::MONTHS[$targetRound->month - 1].')' : 'ภาพรวม');

        $data = [
            'docCode' => $template?->code ?? 'FM-IT-02',
            'rev' => $template?->revision ?? '',
            'createdRevision' => $record->created_revision,
            'server' => $record->server?->name ?? '-',
            'year' => $record->year,
            'responsible' => $record->responsible ?? '-',
            'statusLabel' => $targetRound ? ($statusLabels[$targetRound->status] ?? $targetRound->status) : ($statusLabels[$record->status] ?? $record->status),
            'roundLabel' => $roundLabel,
            'classification' => $classification,
            'months' => self::MONTHS,
            'items' => $items,
            'standards' => $standards,
            'grid' => $grid,
            'readings' => $readings,
            'summary' => $analysis['summary'],
            'health' => $analysis['health_score'],
            'logo' => $this->fileDataUri(public_path('images/company-logo.png')),
            'preparedName' => $targetRound?->prepared_name,
            'preparedPosition' => $targetRound?->prepared_position,
            'preparedDate' => $this->beDate($targetRound?->prepared_signed_at),
            'preparedSig' => $this->dataUri($targetRound?->prepared_signature_path),
            'approvedName' => $targetRound?->approved_name,
            'approvedPosition' => $targetRound?->approved_position,
            'approvedDate' => $this->beDate($targetRound?->approved_signed_at),
            'approvedSig' => $this->dataUri($targetRound?->approved_signature_path),
        ];

        $html = View::make('pdf.maintenance-record', $data)->render();

        $footer = '<table width="100%" style="font-family: garuda; font-size:7pt; color:#94a3b8;"><tr>'
            .'<td width="50%">วันที่บังคับใช้: '.e($effective).'</td>'
            .'<td width="50%" style="text-align:right;">'.e($data['docCode']).' (Rev'.e($data['rev']).')('.e($classification).')</td>'
            .'</tr></table>';

        $tmp = storage_path('app/mpdf');
        File::ensureDirectoryExists($tmp);

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'default_font' => 'garuda',
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

        AuditLogger::log('viewed', $record, 'ออกเอกสาร PDF: '.$data['docCode'].' '.$data['server'].' ปี '.$data['year'].' ('.$roundLabel.')');

        $filename = $data['docCode'].'-'.$data['server'].'-'.$data['year'].'.pdf';

        return response($mpdf->Output($filename, Destination::STRING_RETURN), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    private function num($v): string
    {
        return $v === null ? '' : rtrim(rtrim(number_format((float) $v, 2), '0'), '.');
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
