<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Integrations\Marketplaces\MarketplaceAdapterResolver;
use App\Integrations\Marketplaces\Support\DateRangeFactory;
use App\Models\Marketplace;
use App\Models\MarketplaceAccount;
use App\Support\SupportUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfitabilityAccountController extends Controller
{
    public function index(): View
    {
        $user = SupportUser::currentUser();

        $accounts = MarketplaceAccount::query()
            ->where('tenant_id', $user?->id)
            ->orderByDesc('created_at')
            ->get();

        $marketplaces = Marketplace::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['code', 'name']);

        return view('admin.profitability.accounts.index', compact('accounts', 'marketplaces'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = SupportUser::currentUser();

        $validated = $request->validate([
            'marketplace' => 'required|string|max:50',
            'store_name' => 'nullable|string|max:255',
            'credentials_json' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $credentials = $this->parseCredentials($validated['credentials_json'] ?? null);

        MarketplaceAccount::query()->create([
            'tenant_id' => $user?->id,
            'marketplace' => $validated['marketplace'],
            'store_name' => $validated['store_name'] ?? null,
            'credentials' => $credentials,
            'status' => $validated['status'],
        ]);

        return back()->with('success', 'Marketplace hesabı eklendi.');
    }

    public function edit(MarketplaceAccount $account): View
    {
        $this->authorizeAccount($account);

        $marketplaces = Marketplace::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['code', 'name']);

        return view('admin.profitability.accounts.edit', compact('account', 'marketplaces'));
    }

    public function update(Request $request, MarketplaceAccount $account): RedirectResponse
    {
        $this->authorizeAccount($account);

        $validated = $request->validate([
            'marketplace' => 'required|string|max:50',
            'store_name' => 'nullable|string|max:255',
            'credentials_json' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $data = [
            'marketplace' => $validated['marketplace'],
            'store_name' => $validated['store_name'] ?? null,
            'status' => $validated['status'],
        ];

        if (!empty($validated['credentials_json'])) {
            $data['credentials'] = $this->parseCredentials($validated['credentials_json']);
        }

        $account->update($data);

        return redirect()->route('portal.profitability.accounts.index')
            ->with('success', 'Marketplace hesabı güncellendi.');
    }

    public function destroy(MarketplaceAccount $account): RedirectResponse
    {
        $this->authorizeAccount($account);
        $account->delete();

        return back()->with('success', 'Marketplace hesabı silindi.');
    }

    public function test(
        MarketplaceAccount $account,
        MarketplaceAdapterResolver $resolver,
        DateRangeFactory $rangeFactory
    ): RedirectResponse {
        $this->authorizeAccount($account);

        try {
            $adapter = $resolver->resolve($account->marketplace);
            $range = $rangeFactory->fromString('last1day');
            foreach ($adapter->fetchOrders($account, $range) as $unused) {
                break;
            }

            return back()->with('success', 'Bağlantı testi başarılı.');
        } catch (\Throwable $e) {
            \Log::warning('Marketplace connection test failed', [
                'account_id' => $account->id,
                'tenant_id' => $account->tenant_id,
                'marketplace' => $account->marketplace,
                'error' => $e->getMessage(),
            ]);

            return back()->with('info', 'Bağlantı testi başarısız. Lütfen daha sonra tekrar deneyin.');
        }
    }

    private function authorizeAccount(MarketplaceAccount $account): void
    {
        $user = SupportUser::currentUser() ?? auth()->user();
        if (!$user || (int) $account->tenant_id !== (int) $user->id) {
            abort(403);
        }
    }

    private function parseCredentials(?string $payload): array
    {
        if ($payload === null || trim($payload) === '') {
            return [];
        }

        $decoded = json_decode($payload, true);
        if (!is_array($decoded)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'credentials_json' => 'Geçerli bir JSON girin.',
            ]);
        }

        return $decoded;
    }
}

