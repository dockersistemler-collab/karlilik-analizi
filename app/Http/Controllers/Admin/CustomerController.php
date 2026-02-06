<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $customers = Customer::query()
            ->where('user_id', $user->id)
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->search;
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.customers.index', compact('customers'));
    }

    public function create(): View
    {
        return view('admin.customers.create');
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate(['name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('customers', 'email')],
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

        Customer::create([
            'user_id' => $user->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'city' => $validated['city'] ?? null,
            'district' => $validated['district'] ?? null,
            'neighborhood' => $validated['neighborhood'] ?? null,
            'street' => $validated['street'] ?? null,
            'billing_address' => $validated['billing_address'] ?? null,
            'customer_type' => $validated['customer_type'],
            'company_title' => $validated['company_title'] ?? null,
            'tax_id' => $validated['tax_id'] ?? null,
            'tax_office' => $validated['tax_office'] ?? null,
        ]);

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'billing_address' => $validated['billing_address'] ?? null,
            ]);
        }

        return redirect()->route('portal.customers.index')
            ->with('success', 'Müşteri eklendi.');
    }

    public function show(Customer $customer): View
    {
        $user = request()->user();

        if ($customer->user_id !== $user->id) {
            abort(403);
        }

        return view('admin.customers.show', compact('customer'));
    }

    public function edit(Customer $customer): View
    {
        $user = request()->user();

        if ($customer->user_id !== $user->id) {
            abort(403);
        }

        return view('admin.customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $user = $request->user();

        if ($customer->user_id !== $user->id) {
            abort(403);
        }
$validated = $request->validate(['name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('customers', 'email')->ignore($customer->id)],
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

        return redirect()->route('portal.customers.show', $customer)
            ->with('success', 'Müşteri güncellendi.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $user = request()->user();

        if ($customer->user_id !== $user->id) {
            abort(403);
        }
$customer->delete();

        return redirect()->route('portal.customers.index')
            ->with('success', 'Müşteri silindi.');
    }
}




