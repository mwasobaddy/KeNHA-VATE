<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\ProfileCompleted;
use App\Events\SupervisorApprovalRequested;
use App\Events\UserLoggedIn;
use App\Listeners\HandleUserLogin;
use App\Listeners\NotifySupervisor;
use App\Listeners\SendWelcomeNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        UserLoggedIn::class => [
            HandleUserLogin::class,
        ],
        ProfileCompleted::class => [
            SendWelcomeNotification::class,
        ],
        SupervisorApprovalRequested::class => [
            NotifySupervisor::class,
        ],
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
