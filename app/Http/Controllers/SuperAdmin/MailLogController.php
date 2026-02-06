<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\MailLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;

class MailLogController extends Controller
{
    public function index(Request $request): View
    {
        [$query, $filters] = $this->buildQuery($request);
        $logs = $query->paginate(20)->withQueryString();

        $key = $filters['key'];
        $category = $filters['category'];
        $status = $filters['status'];
        $email = $filters['email'];
        $dateFrom = $filters['date_from'];
        $dateTo = $filters['date_to'];

        return view('super-admin.mail-logs.index', compact(
            'logs',
            'key',
            'category',
            'status',
            'email',
            'dateFrom',
            'dateTo'
        ));
    }

    public function show(MailLog $mailLog): View
    {
        $user = $mailLog->user_id ? User::query()->find($mailLog->user_id) : null;

        return view('super-admin.mail-logs.show', compact('mailLog', 'user'));
    }

    public function export(Request $request)
    {
        [$query] = $this->buildQuery($request);

        $filename = 'mail-logs-'.now()->format('Ymd-His').'.csv';

        return Response::streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'created_at',
                'key',
                'category',
                'status',
                'user_name',
                'user_email',
                'error',
                'metadata_json',
                'sent_at',
            ]);

            $query->chunk(500, function ($rows) use ($handle): void {
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        optional($row->created_at)->format('Y-m-d H:i:s'),
                        $row->key,
                        $row->category,
                        $row->status,
                        $row->user_name,
                        $row->user_email,
                        $row->error,
                        is_array($row->metadata_json) ? json_encode($row->metadata_json, JSON_UNESCAPED_UNICODE) : $row->metadata_json,
                        optional($row->sent_at)->format('Y-m-d H:i:s'),
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function buildQuery(Request $request): array
    {
        $key = trim((string) $request->get('key', ''));
        $category = trim((string) $request->get('category', ''));
        $status = trim((string) $request->get('status', ''));
        $email = trim((string) $request->get('email', $request->get('user', '')));
        $dateFrom = trim((string) $request->get('date_from', ''));
        $dateTo = trim((string) $request->get('date_to', ''));

        $now = Carbon::now();
        if ($dateFrom === '') {
            $dateFrom = $now->copy()->subDays(30)->toDateString();
        }
        if ($dateTo === '') {
            $dateTo = $now->toDateString();
        }
$query = MailLog::query()
            ->leftJoin('users', 'mail_logs.user_id', '=', 'users.id')
            ->leftJoin('mail_templates', 'mail_logs.key', '=', 'mail_templates.key')
            ->select(
                'mail_logs.*',
                'users.name as user_name',
                'users.email as user_email',
                'mail_templates.category as category'
            )
            ->orderByDesc('mail_logs.created_at');

        if ($key !== '') {
            $query->where('mail_logs.key', $key);
        }

        if ($category !== '') {
            $query->where('mail_templates.category', $category);
        }

        if ($status !== '') {
            $query->where('mail_logs.status', $status);
        }

        if ($email !== '') {
            $query->where('users.email', 'like', '%'.$email.'%');
        }

        if ($dateFrom !== '') {
            $query->whereDate('mail_logs.created_at', '>=', $dateFrom);
        }

        if ($dateTo !== '') {
            $query->whereDate('mail_logs.created_at', '<=', $dateTo);
        }

        return [$query, [
            'key' => $key,
            'category' => $category,
            'status' => $status,
            'email' => $email,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ]];
    }
}
