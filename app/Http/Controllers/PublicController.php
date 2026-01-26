<?php

namespace App\Http\Controllers;

use App\Models\Plan;

class PublicController extends Controller
{
    public function home()
    {
        $plans = Plan::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('price')
            ->take(3)
            ->get();

        return view('public.home', compact('plans'));
    }

    public function pricing()
    {
        $plans = Plan::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('price')
            ->get();

        return view('public.pricing', compact('plans'));
    }
}
