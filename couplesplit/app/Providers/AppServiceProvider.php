<?php

namespace App\Providers;

use App\Services\NotificationService;
use App\Services\PaymentService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Garante uma instância por request em produção e uma por teste em testes —
        // evita que o cache interno de PaymentService vaze entre requests/testes.
        $this->app->scoped(PaymentService::class);
    }

    public function boot(): void
    {
        Paginator::useTailwind();

        View::composer('layouts.navigation', function ($view) {
            $count = 0;

            if (Auth::check()) {
                $notifications = app(NotificationService::class)->getActive(Auth::user());
                $seen = session('budget_notifications_seen', []);

                $count = collect($notifications)->filter(function ($n) use ($seen) {
                    if (in_array($n['type'], ['budget_exceeded', 'budget_warning'])) {
                        return !in_array($n['category'], $seen);
                    }
                    return true; // card_closing sempre conta
                })->count();
            }

            $view->with('navNotificationCount', $count);
        });
    }
}
