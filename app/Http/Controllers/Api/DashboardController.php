<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\FormCategory;
use App\Models\MaintenanceRecord;
use App\Support\ChecklistAnalyzer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Generic, module-agnostic dashboard. Aggregates workflow status across all
 * registered forms, surfaces the current user's pending tasks, and lets each
 * module contribute its own widget (e.g. Server CPU/RAM/Disk health).
 */
class DashboardController extends Controller
{
    public function __construct(private readonly ChecklistAnalyzer $analyzer)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $canApprove = $user->can('records.approve');
        $canSubmit = $user->can('records.submit');

        // ---- Totals by workflow status (across forms) ----
        $statusCounts = MaintenanceRecord::selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');
        $totals = [
            'draft' => (int) ($statusCounts['draft'] ?? 0),
            'submitted' => (int) ($statusCounts['submitted'] ?? 0),
            'approved' => (int) ($statusCounts['approved'] ?? 0),
            'rejected' => (int) ($statusCounts['rejected'] ?? 0),
        ];
        $totals['total'] = array_sum($totals);

        // ---- My tasks (cross-form; currently server_maintenance) ----
        $tasks = collect();
        if ($canApprove) {
            $tasks = $tasks->merge(
                MaintenanceRecord::with('server:id,name')->where('status', 'submitted')->latest()->limit(10)->get()
                    ->map(fn ($r) => [
                        'id' => $r->id,
                        'form' => 'บำรุงรักษา Server',
                        'title' => ($r->server?->name ?? '#'.$r->server_id).' · ปี '.$r->year,
                        'action' => 'รออนุมัติ',
                        'status' => $r->status,
                        'route' => '/records/'.$r->id,
                    ])
            );
        }
        if ($canSubmit) {
            $tasks = $tasks->merge(
                MaintenanceRecord::with('server:id,name')->whereIn('status', ['draft', 'rejected'])->latest()->limit(10)->get()
                    ->map(fn ($r) => [
                        'id' => $r->id,
                        'form' => 'บำรุงรักษา Server',
                        'title' => ($r->server?->name ?? '#'.$r->server_id).' · ปี '.$r->year,
                        'action' => $r->status === 'rejected' ? 'ตีกลับ — ต้องแก้ไข' : 'ร่าง — ต้องส่ง',
                        'status' => $r->status,
                        'route' => '/records/'.$r->id,
                    ])
            );
        }
        $myTasks = $tasks->take(12)->values();

        // ---- Summary by category / form ----
        $categories = FormCategory::with(['templates' => fn ($q) => $q->where('is_active', true)->orderBy('order')])
            ->where('is_active', true)->orderBy('order')->get();

        $byCategory = $categories->map(fn ($cat) => [
            'code' => $cat->code,
            'name' => $cat->name,
            'templates' => $cat->templates->map(fn ($t) => [
                'code' => $t->code,
                'name' => $t->name,
                'route' => $t->route,
                'module_key' => $t->module_key,
                'counts' => $this->countsForModule($t->module_key),
            ])->values(),
        ]);

        // ---- Server-maintenance module widget: CPU/RAM/Disk health ----
        $health = ['normal' => 0, 'risk' => 0, 'fault' => 0];
        foreach (MaintenanceRecord::with(['readings', 'entries'])->get() as $r) {
            $a = $this->analyzer->analyze($r);
            $health['normal'] += $a['summary']['normal'] ?? 0;
            $health['risk'] += $a['summary']['risk'] ?? 0;
            $health['fault'] += $a['summary']['fault'] ?? 0;
        }

        // ---- Recent activity (audit) for those allowed ----
        $recent = [];
        if ($user->can('audit.view')) {
            $recent = AuditLog::with('user:id,name')->latest('created_at')->limit(8)->get()
                ->map(fn ($l) => [
                    'event' => $l->event,
                    'description' => $l->description,
                    'user' => $l->user?->name,
                    'created_at' => $l->created_at,
                ]);
        }

        return response()->json([
            'totals' => $totals,
            'my_tasks' => $myTasks,
            'by_category' => $byCategory,
            'server_health' => $health,
            'recent_activity' => $recent,
        ]);
    }

    /**
     * Status counts for a given module. Add a branch per future module.
     *
     * @return array<string,int>
     */
    private function countsForModule(string $moduleKey): array
    {
        $counts = ['draft' => 0, 'submitted' => 0, 'approved' => 0, 'rejected' => 0, 'total' => 0];

        if ($moduleKey === 'server_maintenance') {
            $c = MaintenanceRecord::selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');
            foreach (['draft', 'submitted', 'approved', 'rejected'] as $s) {
                $counts[$s] = (int) ($c[$s] ?? 0);
            }
            $counts['total'] = $counts['draft'] + $counts['submitted'] + $counts['approved'] + $counts['rejected'];
        }

        return $counts;
    }
}
