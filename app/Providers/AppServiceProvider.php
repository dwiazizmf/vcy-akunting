<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Accounting\Journal;
use App\Models\Accounting\Ledger;
use App\Observers\JournalObserver;
use App\Observers\LedgerObserver;

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
        Journal::observe(JournalObserver::class);
        Ledger::observe(LedgerObserver::class);
    }
}
