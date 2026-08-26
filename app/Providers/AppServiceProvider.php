<?php

namespace App\Providers;

use App\Models\LeaveRequest;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer('owner.dashboard', function ($view) {
            $view->with('pendingLeaveCount', LeaveRequest::where('status', 'menunggu')->count());
            $view->with('pendingLeaveList', LeaveRequest::with('employee')
                ->where('status', 'menunggu')
                ->latest()
                ->take(5)
                ->get());
        });
    }
}