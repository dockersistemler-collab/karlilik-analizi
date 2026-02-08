<?php

namespace App\Http\Controllers\Admin\Notifications;

use App\Http\Controllers\Controller;
use App\Models\MailLog;
use App\Models\MailTemplate;
use App\Services\Mail\MailSender;
use App\Services\Mail\MailPolicyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class MailTemplateController extends Controller
{
    public function index(Request $request): View
    {
        $key = trim((string) $request->get('key', ''));
        $category = trim((string) $request->get('category', ''));
        $enabled = $request->get('enabled', '');

        $query = MailTemplate::query()->orderBy('key');

        if ($key !== '') {
            $query->where('key', 'like', '%'.$key.'%');
        }

        if ($category !== '') {
            $query->where('category', $category);
        }

        if ($enabled !== '') {
            $query->where('enabled', (bool) $enabled);
        }
$templates = $query->paginate(20)->withQueryString();

        $routePrefix = $this->routePrefix($request);

        return view('admin.notifications.mail_templates.index', compact('templates', 'key', 'category', 'enabled', 'routePrefix'));
    }

    public function show(Request $request, MailTemplate $template): View
    {
        $decision = app(MailPolicyService::class)->canSend($template->key, $request->user(), []);
        $sampleData = $this->sampleDataForTemplate($template, $request->user());
        $mailSender = app(MailSender::class);
        $previewSubject = $template->subject ? $mailSender->renderPreview($template->subject, $sampleData) : '';
        $previewBody = $template->body_html ? $mailSender->renderPreview($template->body_html, $sampleData) : '';
        $variables = $this->extractVariables($template);
        $routePrefix = $this->routePrefix($request);

        return view('admin.notifications.mail_templates.show', compact('template', 'decision', 'previewSubject', 'previewBody', 'variables', 'routePrefix'));
    }

    public function toggle(Request $request, MailTemplate $template): RedirectResponse
    {
        $decision = app(MailPolicyService::class)->canSend($template->key, $request->user(), []);
        if (($decision['decision'] ?? null) === MailPolicyService::DECISION_BLOCKED) {
            abort(403);
        }
        if (($decision['decision'] ?? null) === MailPolicyService::DECISION_SKIPPED) {
            return redirect()
                ->back()
                ->with('error', 'Bu şablon sistemde kapalı.');
        }
$template->update([
            'enabled' => !$template->enabled,
        ]);

        return redirect()
            ->back()
            ->with('success', 'Durum guncellendi.');
    }

    public function testSend(Request $request, MailTemplate $template): RedirectResponse
    {
        $user = $request->user();
        $decision = app(MailPolicyService::class)->canSend($template->key, $user, []);
        if (($decision['decision'] ?? null) === MailPolicyService::DECISION_BLOCKED) {
            abort(403);
        }
        if (($decision['decision'] ?? null) === MailPolicyService::DECISION_SKIPPED) {
            return redirect()
                ->back()
                ->with('error', 'Bu şablon sistemde kapalı.');
        }
$rateKey = 'mail_template_test:'.$user->id.':'.$template->id;
        if (!Cache::add($rateKey, true, 60)) {
            MailLog::create([
                'key' => $template->key,
                'user_id' => $user->id,
                'status' => 'deduped',
                'provider_message_id' => null,
                'error' => 'rate_limit',
                'metadata_json' => [
                    'reason' => 'test_rate_limit',
                    'window_seconds' => 60,
                ],
                'sent_at' => null,
            ]);

            return redirect()
                ->back()
                ->with('error', 'Bu şablon için kısa süre içinde tekrar test maili gönderemezsiniz.');
        }
$sampleData = $this->sampleDataForTemplate($template, $user);
        app(MailSender::class)->send($template->key, $user, $sampleData, [
            'source' => 'admin_test',
        ]);

        return redirect()
            ->back()
            ->with('success', 'Test maili gönderildi.');
    }

    private function routePrefix(Request $request): string
    {
        $name = $request->route()?->getName();

        return $name && str_starts_with($name, 'super-admin.') ? 'super-admin.' : 'portal.';
    }

    private function sampleDataForTemplate(MailTemplate $template, $user): array
    {
        $now = now();
        $nowFormatted = $now->format('d.m.Y H:i');
        $inSevenDays = $now->copy()->addDays(7)->format('d.m.Y H:i');
        $inOneMonth = $now->copy()->addMonth()->format('d.m.Y H:i');
        $oneDayAgo = $now->copy()->subDay()->format('d.m.Y H:i');
        $tenMinutesAgo = $now->copy()->subMinutes(10)->format('d.m.Y H:i');
        $userName = $user->name ?: 'Test Kullanıcı';
        $pricingUrl = Route::has('pricing') ? route('pricing') : url('/pricing');
        $dashboardUrl = Route::has('portal.dashboard') ? route('portal.dashboard') : url('/');
        $supportUrl = Route::has('portal.help.support') ? route('portal.help.support') : url('/help/support');
        $billingUrl = Route::has('portal.billing') ? route('portal.billing') : url('/billing');
        $integrationsUrl = Route::has('portal.integrations.index') ? route('portal.integrations.index') : url('/integrations');
        $portalBase = rtrim($dashboardUrl, '/');

        $base = [
            'user_name' => $userName,
            'dashboard_url' => $dashboardUrl,
            'panel_url' => $dashboardUrl,
            'pricing_url' => $pricingUrl,
            'plans_url' => $pricingUrl,
            'billing_url' => $billingUrl,
            'billing_settings_url' => $billingUrl,
            'retry_url' => $billingUrl,
            'support_url' => $supportUrl,
        ];

        return match ($template->key) {
            'quota.warning_80' => array_merge($base, [
                'quota_type_label' => 'Aylık Sipariş',
                'percent' => 80,
                'used' => 400,
                'limit' => 500,
            ]),
            'mp.token_expiring' => array_merge($base, [
                'marketplace' => 'Trendyol',
                'days_left' => 7,
                'expires_at' => $inSevenDays,
                'reconnect_url' => $integrationsUrl,
            ]),
            'payment.succeeded' => array_merge($base, [
                'amount' => '499.00',
                'currency' => 'TRY',
                'occurred_at' => $nowFormatted,
                'provider' => 'iyzico',
                'transaction_id' => 'TX-123456',
                'receipt_url' => $portalBase.'/billing/receipt/123',
            ]),
            'payment.failed' => array_merge($base, [
                'amount' => '499.00',
                'currency' => 'TRY',
                'error_message' => 'Kart reddedildi',
                'retry_url' => $portalBase.'/billing/retry',
            ]),
            'invoice.created' => array_merge($base, [
                'invoice_number' => 'INV-1001',
                'total_amount' => '299.00',
                'currency' => 'TRY',
                'marketplace' => 'Trendyol',
                'order_id' => 'ORDER-123',
                'invoice_url' => $portalBase.'/invoices/INV-1001',
            ]),
            'invoice.failed' => array_merge($base, [
                'marketplace' => 'Trendyol',
                'order_id' => 'ORDER-123',
                'error_message' => 'Servis yanıt vermedi',
                'retry_url' => $portalBase.'/invoices/retry/ORDER-123',
            ]),
            'subscription.started' => array_merge($base, [
                'plan_name' => 'Pro',
                'started_at' => $oneDayAgo,
                'ends_at' => $inOneMonth,
                'panel_url' => $dashboardUrl,
            ]),
            'subscription.renewed' => array_merge($base, [
                'plan_name' => 'Pro',
                'period_start' => $now->copy()->subMonth()->format('d.m.Y H:i'),
                'period_end' => $nowFormatted,
                'amount' => '999.00',
                'currency' => 'TRY',
                'panel_url' => $dashboardUrl,
            ]),
            'subscription.cancelled' => array_merge($base, [
                'plan_name' => 'Pro',
                'access_ends_at' => $inSevenDays,
                'reactivate_url' => $portalBase.'/subscription/reactivate',
                'plans_url' => url('/pricing'),
            ]),
            'trial.ended' => array_merge($base, [
                'trial_ended_at' => $nowFormatted,
                'pricing_url' => url('/pricing'),
                'dashboard_url' => $dashboardUrl,
            ]),
            'security.support_view_used' => array_merge($base, [
                'admin_name' => 'Super Admin',
                'started_at' => $tenMinutesAgo,
                'reason' => 'Deneme',
                'log_id' => 42,
            ]),
            'mp.connection_lost' => array_merge($base, [
                'marketplace' => 'Trendyol',
                'store_id' => 'STORE-001',
                'reason' => 'Token süresi doldu',
                'occurred_at' => $nowFormatted,
            ]),
            default => $base,
        };
    }

    private function extractVariables(MailTemplate $template): array
    {
        $values = [
            (string) ($template->subject ?? ''),
            (string) ($template->body_html ?? ''),
        ];

        $matches = [];
        foreach ($values as $value) {
            preg_match_all('/{{\\s*([a-zA-Z0-9_]+)\\s*}}/', $value, $found);
            if (!empty($found[1])) {
                $matches = array_merge($matches, $found[1]);
            }
        }
$matches = array_values(array_unique($matches));
        sort($matches);

        return $matches;
    }
}






