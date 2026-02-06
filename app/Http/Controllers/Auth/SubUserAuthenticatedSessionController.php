<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SubUserLoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SubUserAuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.subuser-login');
    }

    public function store(SubUserLoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $subUser = Auth::guard('subuser')->user();
        if ($subUser) {
            $subUser->forceFill(['last_login_at' => now()])->save();
        }
$request->session()->regenerate();

        return redirect()->intended(route('portal.dashboard', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('subuser')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('subuser.login');
    }
}


