<?php

namespace App\Providers;

use App\Events\Requests\AddRequestEvent;
use App\Events\Requests\UpdateRequestEvent;
use App\Listeners\Requests\AddRequestsDone;
use App\Listeners\Requests\UpdateRequestsDone;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        // Registered::class => [
        //     SendEmailVerificationNotification::class,
        // ],
        AddRequestEvent::class => [
            AddRequestsDone::class,
        ],
        UpdateRequestEvent::class => [
            UpdateRequestsDone::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
