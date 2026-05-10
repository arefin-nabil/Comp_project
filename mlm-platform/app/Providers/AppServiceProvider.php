<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // MLM Business Events
        \Illuminate\Support\Facades\Event::listen(
            \App\Events\UserRegistered::class,
            [\App\Listeners\CheckClubActivationEligibility::class, 'handle']
        );

        \Illuminate\Support\Facades\Event::listen(
            \App\Events\TeamIncomeDistributed::class,
            [\App\Listeners\CheckClubActivationEligibility::class, 'handle']
        );

        \Illuminate\Support\Facades\Event::listen(
            \App\Events\ClubActivated::class,
            [\App\Listeners\CheckClubBonus::class, 'handle']
        );

        \Illuminate\Support\Facades\Event::listen(
            \App\Events\ClubActivated::class,
            [\App\Listeners\CheckRoyalty::class, 'handle']
        );
    }
}
