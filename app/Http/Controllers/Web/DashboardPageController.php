<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardPageController extends Controller
{
    public function user(): View
    {
        return view('dashboard.user');
    }

    public function admin(): View
    {
        return view('dashboard.admin');
    }

    public function adminUsers(): View
    {
        return view('admin.users.index');
    }
}
