<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class SystemController extends Controller
{
    public function index(): View
    {
        return view('super-admin.system.index');
    }
}
