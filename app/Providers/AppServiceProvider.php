<?php

namespace App\Providers;

use App\Models\LeaveRequest;
use Illuminate\Support\Facades\Auth;
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

        View::composer('karyawan.dashboard', function ($view) {
            $user = Auth::user();
            $employee = $user?->employee;

            if ($employee) {
                $recentUpdates = $employee->leaveRequests()
                    ->whereIn('status', ['disetujui', 'ditolak'])
                    ->whereNotNull('approved_at')
                    ->latest('approved_at')
                    ->take(5)
                    ->get();

                $recentCount = $employee->leaveRequests()
                    ->whereIn('status', ['disetujui', 'ditolak'])
                    ->where('approved_at', '>=', now()->subDays(7))
                    ->count();
            } else {
                $recentUpdates = collect();
                $recentCount = 0;
            }

            $view->with('myLeaveNotifications', $recentUpdates);
            $view->with('myLeaveNotifCount', $recentCount);
        });
    }
}