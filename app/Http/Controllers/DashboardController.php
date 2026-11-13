<?php

namespace App\Http\Controllers;

use App\Support\AdminDashboardStats;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    /**
     * Dashboard admin: statistik ringkas dari data survei nyata.
     */
    public function admin(): View
    {
        return view('admin.dashboard', AdminDashboardStats::build());
    }
}
