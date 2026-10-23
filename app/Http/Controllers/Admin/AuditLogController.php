<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $logs = $this->query($request)->paginate(50)->withQueryString();

        $actions = AuditLog::query()->select('action')->distinct()->orderBy('action')->pluck('action');
        $users = User::withTrashed()->orderBy('name')->get(['id', 'name']);

        return view('admin.audit', [
            'logs' => $logs,
            'actions' => $actions,
            'users' => $users,
            'filters' => $request->only(['q', 'action', 'user', 'from', 'to']),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $query = $this->query($request);

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM für Excel
            fputcsv($out, ['Zeit', 'Nutzer', 'Aktion', 'Objekt', 'Details', 'IP'], ';');
            $query->chunk(500, function ($logs) use ($out) {
                foreach ($logs as $log) {
                    fputcsv($out, [
                        $log->created_at?->format('d.m.Y H:i:s'),
                        $log->user_name,
                        $log->description(),
                        self::csvSafe((string) $log->subject_label),
                        self::csvSafe($log->properties ? json_encode($log->properties, JSON_UNESCAPED_UNICODE) : ''),
                        $log->ip_address,
                    ], ';');
                }
            });
            fclose($out);
        }, 'protokoll-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Verhindert CSV-/Formel-Injection beim Öffnen in Excel.
     */
    private static function csvSafe(string $value): string
    {
        return preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }

    /**
     * @return Builder<AuditLog>
     */
    private function query(Request $request): Builder
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'action' => ['nullable', 'string', 'max:64'],
            'user' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        return AuditLog::query()
            ->when($data['q'] ?? null, fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('subject_label', 'like', '%'.$s.'%')
                ->orWhere('user_name', 'like', '%'.$s.'%')
                ->orWhere('properties', 'like', '%'.$s.'%')))
            ->when($data['action'] ?? null, fn ($q, $a) => $q->where('action', $a))
            ->when($data['user'] ?? null, fn ($q, $u) => $q->where('user_id', $u))
            ->when($data['from'] ?? null, fn ($q, $d) => $q->where('created_at', '>=', $d))
            ->when($data['to'] ?? null, fn ($q, $d) => $q->where('created_at', '<', Carbon::parse($d)->addDay()))
            ->latest('created_at')->latest('id');
    }
}
