<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use App\Models\ReferralProgram;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReferralController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->only(['status', 'referrer_id', 'referred_email', 'program_id']);

        $query = Referral::query()
            ->with(['referrer', 'referredUser', 'program'])
            ->when($filters['status'] ?? null, function ($builder, $status) {
                $builder->where('status', $status);
            })
            ->when($filters['referrer_id'] ?? null, function ($builder, $referrerId) {
                $builder->where('referrer_id', $referrerId);
            })
            ->when($filters['referred_email'] ?? null, function ($builder, $email) {
                $builder->where('referred_email', 'like', '%'.$email.'%');
            })
            ->when($filters['program_id'] ?? null, function ($builder, $programId) {
                $builder->where('program_id', $programId);
            })
            ->latest();

        $referrals = $query->paginate(25)->withQueryString();
        $programs = ReferralProgram::query()->orderByDesc('id')->get();

        return view('super-admin.referrals.index', compact('referrals', 'filters', 'programs'));
    }

    public function show(Referral $referral): View
    {
        $referral->load(['referrer', 'referredUser', 'program']);

        return view('super-admin.referrals.show', compact('referral'));
    }

    public function export(Request $request)
    {
        $filters = $request->only(['status', 'referrer_id', 'referred_email', 'program_id']);

        $query = Referral::query()
            ->with(['referrer', 'referredUser', 'program'])
            ->when($filters['status'] ?? null, function ($builder, $status) {
                $builder->where('status', $status);
            })
            ->when($filters['referrer_id'] ?? null, function ($builder, $referrerId) {
                $builder->where('referrer_id', $referrerId);
            })
            ->when($filters['referred_email'] ?? null, function ($builder, $email) {
                $builder->where('referred_email', 'like', '%'.$email.'%');
            })
            ->when($filters['program_id'] ?? null, function ($builder, $programId) {
                $builder->where('program_id', $programId);
            })
            ->latest();

        $filename = 'referrals-' . now()->format('Ymd-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Referrer ID',
                'Referrer Name',
                'Referred Email',
                'Referred User ID',
                'Program',
                'Status',
                'Referrer Reward',
                'Referred Reward',
                'Rewarded At',
                'Created At',
            ]);
            $query->chunk(200, function ($rows) use ($handle) {
                foreach ($rows as $referral) {
                    fputcsv($handle, [
                        $referral->referrer_id,
                        $referral->referrer?->name,
                        $referral->referred_email,
                        $referral->referred_user_id,
                        $referral->program?->name,
                        $referral->status,
                        $referral->referrer_reward_type ? ($referral->referrer_reward_type.' '.$referral->referrer_reward_value) : null,
                        $referral->referred_reward_type ? ($referral->referred_reward_type.' '.$referral->referred_reward_value) : null,
                        optional($referral->rewarded_at)->format('Y-m-d H:i'),
                        optional($referral->created_at)->format('Y-m-d H:i'),
                    ]);
                }
            });
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
