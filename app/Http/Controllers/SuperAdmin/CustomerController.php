<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'user_id', 'email', 'phone']);

        $query = Customer::query()
            ->with('user')
            ->when($filters['search'] ?? null, function ($builder, $search) {
                $builder->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            })
            ->when($filters['email'] ?? null, function ($builder, $email) {
                $builder->where('email', 'like', '%'.$email.'%');
            })
            ->when($filters['phone'] ?? null, function ($builder, $phone) {
                $builder->where('phone', 'like', '%'.$phone.'%');
            })
            ->when($filters['user_id'] ?? null, function ($builder, $userId) {
                if ($userId === 'none') {
                    $builder->whereNull('user_id');
                } else {
                    $builder->where('user_id', $userId);
                }
            })
            ->latest();

        $customers = $query->paginate(25)->withQueryString();
        $clients = User::query()->where('role', 'client')->orderBy('name')->get();

        return view('super-admin.customers.index', compact('customers', 'filters', 'clients'));
    }

    public function create(): View
    {
        $clients = User::query()->where('role', 'client')->orderBy('name')->get();

        return view('super-admin.customers.create', compact('clients'));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'nullable|integer|exists:users,id',
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('customers', 'email'),
                Rule::when(!$request->filled('user_id'), ['unique:users,email']),
            ],
            'phone' => 'nullable|string|max:30',
            'city' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'neighborhood' => 'nullable|string|max:150',
            'street' => 'nullable|string|max:150',
            'billing_address' => 'nullable|string|max:1000',
            'customer_type' => 'required|in:individual,corporate',
            'company_title' => [
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf($request->input('customer_type') === 'corporate'),
            ],
            'tax_id' => [
                'required',
                Rule::unique('customers', 'tax_id'),
                Rule::when($request->input('customer_type') === 'individual', ['digits:11']),
                Rule::when($request->input('customer_type') === 'corporate', ['digits:10']),
            ],
            'tax_office' => 'nullable|string|max:150',
        ]);

        if (!$request->filled('user_id')) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Str::random(32),
                'role' => 'client',
                'is_active' => true,
                'billing_name' => $validated['name'],
                'billing_email' => $validated['email'],
                'billing_address' => $validated['billing_address'] ?? null,
            ]);
            $validated['user_id'] = $user->id;
        }

        Customer::create($validated);

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'user_id' => $validated['user_id'] ?? null,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'billing_address' => $validated['billing_address'] ?? null,
            ]);
        }

        return redirect()->route('super-admin.customers.index')
            ->with('success', 'Müşteri eklendi.');
    }

    public function show(Customer $customer): View
    {
        $customer->load('user');

        return view('super-admin.customers.show', compact('customer'));
    }

    public function edit(Customer $customer): View
    {
        $clients = User::query()->where('role', 'client')->orderBy('name')->get();

        return view('super-admin.customers.edit', compact('customer', 'clients'));
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => 'nullable|integer|exists:users,id',
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('customers', 'email')->ignore($customer->id),
                Rule::when(!$request->filled('user_id'), ['unique:users,email']),
            ],
            'phone' => 'nullable|string|max:30',
            'city' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'neighborhood' => 'nullable|string|max:150',
            'street' => 'nullable|string|max:150',
            'billing_address' => 'nullable|string|max:1000',
            'customer_type' => 'required|in:individual,corporate',
            'company_title' => [
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf($request->input('customer_type') === 'corporate'),
            ],
            'tax_id' => [
                'required',
                Rule::unique('customers', 'tax_id')->ignore($customer->id),
                Rule::when($request->input('customer_type') === 'individual', ['digits:11']),
                Rule::when($request->input('customer_type') === 'corporate', ['digits:10']),
            ],
            'tax_office' => 'nullable|string|max:150',
        ]);

        $customer->update($validated);

        return redirect()->route('super-admin.customers.show', $customer)
            ->with('success', 'Müşteri güncellendi.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $customer->delete();

        return redirect()->route('super-admin.customers.index')
            ->with('success', 'Müşteri silindi.');
    }
}
