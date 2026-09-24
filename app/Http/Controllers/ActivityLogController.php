<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\IndexActivityLogRequest;
use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ActivityLogController extends Controller
{
    public function index(IndexActivityLogRequest $request): View
    {
        $filters = $request->filters();
        $logs = $this->query($filters)->paginate(25)->withQueryString();

        return view('logs.index', [
            'logs' => $logs,
            'filters' => $filters,
        ]);
    }

    public function download(IndexActivityLogRequest $request): StreamedResponse
    {
        $filters = $request->filters();
        $logs = $this->query($filters);
        $filename = 'activity-logs-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($logs): void {
            $stream = fopen('php://output', 'wb');

            if ($stream === false) {
                return;
            }

            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, [
                'Date and time',
                'Name',
                'Username',
                'Interaction type',
                'Description',
                'Action',
                'Affected records',
                'Method',
                'Route',
                'Path',
                'Status',
                'IP address',
                'Duration (ms)',
                'Browser',
            ]);

            foreach ($logs->cursor() as $log) {
                fputcsv($stream, [
                    $this->csvValue($log->created_at?->format('Y-m-d H:i:s')),
                    $this->csvValue($log->actor_name ?? 'Guest'),
                    $this->csvValue($log->actor_username),
                    $this->csvValue($log->interaction_type),
                    $this->csvValue($log->description),
                    $this->csvValue($log->action),
                    $this->csvValue($log->affectedRecordSummary()),
                    $this->csvValue($log->method),
                    $this->csvValue($log->route_name),
                    $this->csvValue($log->path),
                    $this->csvValue((string) $log->status_code),
                    $this->csvValue($log->ip_address),
                    $this->csvValue((string) $log->duration_ms),
                    $this->csvValue($log->user_agent),
                ]);
            }

            fclose($stream);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @param  array{q: ?string, sort: string, direction: string}  $filters
     * @return Builder<ActivityLog>
     */
    private function query(array $filters): Builder
    {
        return ActivityLog::query()
            ->whereIn('interaction_type', ['authentication', 'create', 'update', 'delete'])
            ->where('action', '!=', 'logout')
            ->search($filters['q'])
            ->orderBy($filters['sort'], $filters['direction'])
            ->orderBy('id', $filters['direction']);
    }

    private function csvValue(?string $value): string
    {
        $value = preg_replace('/[\r\n]+/u', ' ', $value ?? '') ?? '';

        return preg_match('/^[=+\-@]/', $value) === 1 ? "'{$value}" : $value;
    }
}
