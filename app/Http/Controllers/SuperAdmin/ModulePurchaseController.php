<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\ModulePurchase;
use App\Models\User;
use App\Services\Purchases\ModulePurchaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModulePurchaseController extends Controller
{
    public function __construct(private readonly ModulePurchaseService $purchases)
    {
    }

    public function index(Request $request): View
    {
        $status = (string) $request->query('status', 'pending');
        if (!in_array($status, ['pending', 'paid', 'cancelled', 'refunded'], true)) {
            $status = 'pending';
        }
$purchases = ModulePurchase::query()
            ->with(['user', 'module'])
            ->where('status', $status)
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('super-admin.module-purchases.index', compact('purchases', 'status'));
    }

    public function show(ModulePurchase $modulePurchase): View
    {
        $modulePurchase->loadMissing(['user', 'module']);
        return view('super-admin.module-purchases.show', compact('modulePurchase'));
    }

    public function create(): View
    {
        $users = User::query()
            ->where('role', 'client')
            ->orderBy('name')
            ->get();

        $modules = Module::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('super-admin.module-purchases.create', compact('users', 'modules'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(['user_id' => 'required|exists:users,id',
            'module_id' => 'required|exists:modules,id',
            'period' => 'required|in:monthly,yearly,one_time',
            'amount' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'note' => 'nullable|string|max:2000',
        ]);

        $meta = [];
        if (!empty($validated['note'])) {
            $meta['note'] = $validated['note'];
        }
$purchase = ModulePurchase::create([
            'user_id' => (int) $validated['user_id'],
            'module_id' => (int) $validated['module_id'],
            'provider' => 'manual',
            'provider_payment_id' => null,
            'amount' => $validated['amount'] ?? null,
            'currency' => $validated['currency'] ?? 'TRY',
            'period' => $validated['period'],
            'status' => 'pending',
            'meta' => empty($meta) ? null : $meta,
        ]);

        return redirect()
            ->route('super-admin.module-purchases.show', $purchase)
            ->with('success', 'Manuel satış kaydı oluşturuldu.');
    }

    public function markPaid(ModulePurchase $modulePurchase): RedirectResponse
    {
        $this->purchases->markPaid($modulePurchase);

        return back()->with('success', 'Satış ödendi olarak işaretlendi.');
    }

    public function markCancelled(ModulePurchase $modulePurchase): RedirectResponse
    {
        $this->purchases->markCancelled($modulePurchase);

        return back()->with('success', 'Satış iptal edildi.');
    }

    public function markRefunded(ModulePurchase $modulePurchase): RedirectResponse
    {
        $this->purchases->markRefunded($modulePurchase);

        return back()->with('success', 'Satış iade edildi.');
    }
}

