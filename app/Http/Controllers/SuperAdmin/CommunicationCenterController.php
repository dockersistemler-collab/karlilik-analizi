<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\CommunicationSetting;
use App\Models\CommunicationSlaRule;
use App\Models\CommunicationTemplate;
use App\Models\Marketplace;
use App\Models\MarketplaceStore;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommunicationCenterController extends Controller
{
    public function settings(): View
    {
        $setting = CommunicationSetting::query()->whereNull('user_id')->first();

        return view('super-admin.communication-center.settings', compact('setting'));
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ai_enabled' => ['nullable', 'boolean'],
            'notification_email' => ['nullable', 'email'],
            'cron_interval_minutes' => ['required', 'integer', 'min:1', 'max:60'],
            'priority_weights.time_left' => ['nullable', 'integer', 'min:1', 'max:10'],
            'priority_weights.store_rating_risk' => ['nullable', 'integer', 'min:0', 'max:10'],
            'priority_weights.sales_velocity' => ['nullable', 'integer', 'min:0', 'max:10'],
            'priority_weights.margin' => ['nullable', 'integer', 'min:0', 'max:10'],
            'priority_weights.buybox_critical' => ['nullable', 'integer', 'min:0', 'max:10'],
            'priority_weights.critical_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
        ]);

        CommunicationSetting::query()->updateOrCreate(
            ['user_id' => null],
            [
                'ai_enabled' => $request->boolean('ai_enabled'),
                'notification_email' => $validated['notification_email'] ?? null,
                'cron_interval_minutes' => (int) $validated['cron_interval_minutes'],
                'priority_weights' => $validated['priority_weights'] ?? [
                    'time_left' => 3,
                    'store_rating_risk' => 0,
                    'sales_velocity' => 0,
                    'margin' => 0,
                    'buybox_critical' => 0,
                    'critical_minutes' => 30,
                ],
            ]
        );

        return back()->with('success', 'Ayarlar güncellendi.');
    }

    public function templates(): View
    {
        $templates = CommunicationTemplate::query()->orderByDesc('id')->paginate(20);
        return view('super-admin.communication-center.templates.index', compact('templates'));
    }

    public function createTemplate(): View
    {
        $marketplaces = Marketplace::query()->orderBy('name')->get();
        $clients = User::query()->where('role', 'client')->orderBy('name')->get();
        return view('super-admin.communication-center.templates.create', compact('marketplaces', 'clients'));
    }

    public function storeTemplate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
            'category' => ['required', 'in:shipping,return,product,warranty,general'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'marketplaces' => ['nullable', 'array'],
            'marketplaces.*' => ['string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        CommunicationTemplate::query()->create([
            'user_id' => $validated['user_id'] ?? null,
            'category' => $validated['category'],
            'title' => $validated['title'],
            'body' => $validated['body'],
            'marketplaces' => $validated['marketplaces'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('super-admin.communication-center.templates.index')
            ->with('success', 'Şablon oluşturuldu.');
    }

    public function editTemplate(CommunicationTemplate $template): View
    {
        $marketplaces = Marketplace::query()->orderBy('name')->get();
        $clients = User::query()->where('role', 'client')->orderBy('name')->get();
        return view('super-admin.communication-center.templates.edit', compact('template', 'marketplaces', 'clients'));
    }

    public function updateTemplate(Request $request, CommunicationTemplate $template): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
            'category' => ['required', 'in:shipping,return,product,warranty,general'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'marketplaces' => ['nullable', 'array'],
            'marketplaces.*' => ['string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $template->update([
            'user_id' => $validated['user_id'] ?? null,
            'category' => $validated['category'],
            'title' => $validated['title'],
            'body' => $validated['body'],
            'marketplaces' => $validated['marketplaces'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Şablon güncellendi.');
    }

    public function destroyTemplate(CommunicationTemplate $template): RedirectResponse
    {
        $template->delete();
        return back()->with('success', 'Şablon silindi.');
    }

    public function stores(): View
    {
        $stores = MarketplaceStore::query()
            ->with(['user', 'marketplace'])
            ->orderByDesc('id')
            ->paginate(20);
        return view('super-admin.communication-center.stores.index', compact('stores'));
    }

    public function updateStore(Request $request, MarketplaceStore $store): RedirectResponse
    {
        $validated = $request->validate([
            'store_name' => ['required', 'string', 'max:255'],
            'store_external_id' => ['nullable', 'string', 'max:255'],
            'base_url' => ['nullable', 'url', 'max:1000'],
            'auth_type' => ['nullable', 'in:auto,bearer,basic,header'],
            'api_key' => ['nullable', 'string', 'max:255'],
            'api_secret' => ['nullable', 'string', 'max:255'],
            'access_token' => ['nullable', 'string', 'max:4000'],
            'threads_endpoint' => ['nullable', 'string', 'max:1000'],
            'messages_endpoint' => ['nullable', 'string', 'max:1000'],
            'send_reply_endpoint' => ['nullable', 'string', 'max:1000'],
            'send_reply_method' => ['nullable', 'in:POST,PUT,PATCH'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $credentials = (array) ($store->credentials ?? []);
        if (isset($validated['base_url'])) {
            $credentials['base_url'] = trim((string) $validated['base_url']);
        }
        if (isset($validated['auth_type'])) {
            $credentials['auth_type'] = (string) $validated['auth_type'];
        }
        if (isset($validated['api_key'])) {
            $credentials['api_key'] = $validated['api_key'];
        }
        if (isset($validated['api_secret'])) {
            $credentials['api_secret'] = $validated['api_secret'];
        }
        if (isset($validated['access_token'])) {
            $credentials['access_token'] = $validated['access_token'];
        }
        if (isset($validated['threads_endpoint'])) {
            $credentials['threads_endpoint'] = trim((string) $validated['threads_endpoint']);
        }
        if (isset($validated['messages_endpoint'])) {
            $credentials['messages_endpoint'] = trim((string) $validated['messages_endpoint']);
        }
        if (isset($validated['send_reply_endpoint'])) {
            $credentials['send_reply_endpoint'] = trim((string) $validated['send_reply_endpoint']);
        }
        if (isset($validated['send_reply_method'])) {
            $credentials['send_reply_method'] = strtoupper((string) $validated['send_reply_method']);
        }

        $store->update([
            'store_name' => $validated['store_name'],
            'store_external_id' => $validated['store_external_id'] ?? null,
            'credentials' => $credentials,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Mağaza güncellendi.');
    }

    public function slaIndex(): View
    {
        $slaRules = CommunicationSlaRule::query()->with('marketplace')->orderBy('channel')->get();
        $marketplaces = Marketplace::query()->orderBy('name')->get();
        return view('super-admin.communication-center.sla.index', compact('slaRules', 'marketplaces'));
    }

    public function slaStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'marketplace_id' => ['nullable', 'exists:marketplaces,id'],
            'channel' => ['required', 'in:question,message,review,return'],
            'sla_minutes' => ['required', 'integer', 'min:1', 'max:10080'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        CommunicationSlaRule::query()->create([
            'marketplace_id' => $validated['marketplace_id'] ?? null,
            'channel' => $validated['channel'],
            'sla_minutes' => (int) $validated['sla_minutes'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'SLA kuralı eklendi.');
    }

    public function slaUpdate(Request $request, CommunicationSlaRule $sla): RedirectResponse
    {
        $validated = $request->validate([
            'marketplace_id' => ['nullable', 'exists:marketplaces,id'],
            'channel' => ['required', 'in:question,message,review,return'],
            'sla_minutes' => ['required', 'integer', 'min:1', 'max:10080'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $sla->update([
            'marketplace_id' => $validated['marketplace_id'] ?? null,
            'channel' => $validated['channel'],
            'sla_minutes' => (int) $validated['sla_minutes'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'SLA kuralı güncellendi.');
    }

    public function slaDestroy(CommunicationSlaRule $sla): RedirectResponse
    {
        $sla->delete();
        return back()->with('success', 'SLA kuralı silindi.');
    }
}
