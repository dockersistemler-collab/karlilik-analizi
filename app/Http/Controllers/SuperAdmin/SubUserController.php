<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SubUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubUserController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'owner_id', 'status']);

        $query = SubUser::query()->with('owner');

        if ($filters['search'] ?? null) {
            $search = $filters['search'];
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            });
        }

        if ($filters['owner_id'] ?? null) {
            $query->where('owner_user_id', $filters['owner_id']);
        }

        if (($filters['status'] ?? null) === 'active') {
            $query->where('is_active', true);
        }

        if (($filters['status'] ?? null) === 'inactive') {
            $query->where('is_active', false);
        }
$subUsers = $query->latest()->paginate(30)->withQueryString();
        $owners = User::query()->where('role', 'client')->orderBy('name')->get();

        return view('super-admin.sub-users.index', compact('subUsers', 'owners', 'filters'));
    }
}
