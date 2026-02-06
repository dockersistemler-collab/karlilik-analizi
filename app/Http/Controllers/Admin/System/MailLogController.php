<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Models\MailLog;
use App\Models\User;
use App\Support\SupportUser;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MailLogController extends Controller
{
    public function index(Request $request): View
    {
        $key = trim((string) $request->get('key', ''));
        $status = trim((string) $request->get('status', ''));
        $userQuery = trim((string) $request->get('user', ''));
        $dateFrom = trim((string) $request->get('date_from', ''));
        $dateTo = trim((string) $request->get('date_to', ''));

        $user = SupportUser::currentUser();
        $subUser = auth('subuser')->user();
        $canViewCompanyLogs = !$subUser || $subUser->hasPermission('settings');

        $companyUserIds = collect([$user->id]);
        if ($canViewCompanyLogs && $user->company_name) {
            $companyUserIds = User::query()
                ->where('company_name', $user->company_name)
                ->when($user->company_phone, function ($query) use ($user): void {
                    $query->where('company_phone', $user->company_phone);
                })
                ->pluck('id');
        }
$query = MailLog::query()
            ->leftJoin('users', 'mail_logs.user_id', '=', 'users.id')
            ->select('mail_logs.*', 'users.name as user_name', 'users.email as user_email')
            ->when($canViewCompanyLogs, function ($builder) use ($companyUserIds): void {
                $builder->whereIn('mail_logs.user_id', $companyUserIds);
            }, function ($builder) use ($user): void {
                $builder->where('mail_logs.user_id', $user->id);
            })
            ->orderByDesc('mail_logs.created_at');

        if ($key !== '') {
            $query->where('mail_logs.key', $key);
        }

        if ($status !== '') {
            $query->where('mail_logs.status', $status);
        }

        if ($userQuery !== '') {
            $query->where(function ($builder) use ($userQuery): void {
                $builder->where('users.email', 'like', '%'.$userQuery.'%')
                    ->orWhere('users.name', 'like', '%'.$userQuery.'%');
            });
        }

        if ($dateFrom !== '') {
            $query->where(function ($builder) use ($dateFrom): void {
                $builder->whereDate('mail_logs.created_at', '>=', $dateFrom)
                    ->orWhereDate('mail_logs.sent_at', '>=', $dateFrom);
            });
        }

        if ($dateTo !== '') {
            $query->where(function ($builder) use ($dateTo): void {
                $builder->whereDate('mail_logs.created_at', '<=', $dateTo)
                    ->orWhereDate('mail_logs.sent_at', '<=', $dateTo);
            });
        }
$logs = $query->paginate(20)->withQueryString();

        return view('admin.system.mail_logs.index', compact(
            'logs',
            'key',
            'status',
            'userQuery',
            'dateFrom',
            'dateTo'
        ));
    }

    public function show(Request $request, MailLog $mailLog): View
    {
        $user = SupportUser::currentUser();
        $subUser = auth('subuser')->user();
        $canViewCompanyLogs = !$subUser || $subUser->hasPermission('settings');

        $companyUserIds = collect([$user->id]);
        if ($canViewCompanyLogs && $user->company_name) {
            $companyUserIds = User::query()
                ->where('company_name', $user->company_name)
                ->when($user->company_phone, function ($query) use ($user): void {
                    $query->where('company_phone', $user->company_phone);
                })
                ->pluck('id');
        }

        if (!$companyUserIds->contains((int) $mailLog->user_id)) {
            abort(404);
        }
$user = $mailLog->user_id ? User::query()->find($mailLog->user_id) : null;

        return view('admin.system.mail_logs.show', compact('mailLog', 'user'));
    }
}
