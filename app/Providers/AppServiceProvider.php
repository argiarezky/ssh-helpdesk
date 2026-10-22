<?php

namespace App\Providers;

use App\Models\Ticket;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        View::composer('layouts.app', function (ViewContract $view): void {
            $user = Auth::user();

            if (!$user) {
                $view->with([
                    'unreadNotificationCount' => 0,
                    'latestNotifications' => collect(),
                ]);

                return;
            }

            $driver = DB::connection()->getDriverName();

            $ticketIdExpression = match ($driver) {
                'mysql' => "CAST(JSON_UNQUOTE(JSON_EXTRACT(notifications.data, '$.ticket_id')) AS UNSIGNED)",
                'sqlite' => "CAST(json_extract(notifications.data, '$.ticket_id') AS INTEGER)",
                'pgsql' => "CAST(notifications.data->>'ticket_id' AS BIGINT)",
                default => null,
            };

            if ($ticketIdExpression === null) {
                $view->with([
                    'unreadNotificationCount' => 0,
                    'latestNotifications' => collect(),
                ]);

                return;
            }

            $notifications = $user->notifications()
                ->whereIn(
                    DB::raw($ticketIdExpression),
                    Ticket::query()->select('id')
                );

            $unreadNotificationCount = (clone $notifications)
                ->whereNull('read_at')
                ->count();

            $latestNotifications = (clone $notifications)
                ->latest()
                ->limit(5)
                ->get();

            $view->with([
                'unreadNotificationCount' => $unreadNotificationCount,
                'latestNotifications' => $latestNotifications,
            ]);
        });
    }
}
