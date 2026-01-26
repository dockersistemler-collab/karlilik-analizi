<?php

namespace App\Http\Controllers\SubUser;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function edit(): View
    {
        $subUser = Auth::guard('subuser')->user();
        if (!$subUser) {
            abort(403);
        }

        return view('admin.sub-users.password');
    }

    public function update(Request $request): RedirectResponse
    {
        $subUser = Auth::guard('subuser')->user();
        if (!$subUser) {
            abort(403);
        }

        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if (!Hash::check($validated['current_password'], $subUser->password)) {
            return back()->withErrors(['current_password' => 'Mevcut şifre yanlış.']);
        }

        $subUser->update([
            'password' => $validated['password'],
        ]);

        return back()->with('success', 'Şifre güncellendi.');
    }
}
