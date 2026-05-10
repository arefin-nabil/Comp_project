<?php

use Illuminate\Support\Facades\Schedule;
use App\Jobs\SettleClubIncomeJob;
use App\Jobs\ReconciliationJob;
use Carbon\Carbon;

// Settle club income daily at 00:05 for the previous day
Schedule::job(new SettleClubIncomeJob(Carbon::yesterday()->toDateString()))
    ->dailyAt('00:05')
    ->name('settle-club-income')
    ->withoutOverlapping();

// Reconcile wallets daily at 01:00
Schedule::job(new ReconciliationJob(Carbon::today()->toDateString()))
    ->dailyAt('01:00')
    ->name('reconcile-wallets')
    ->withoutOverlapping();

// Optionally schedule horizon snapshot for metrics
Schedule::command('horizon:snapshot')->everyFiveMinutes();
