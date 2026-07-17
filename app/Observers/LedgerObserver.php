<?php

namespace App\Observers;

use App\Models\Accounting\Ledger;
use App\Services\CoaBalanceService;

class LedgerObserver
{
    protected $balanceService;

    public function __construct(CoaBalanceService $balanceService)
    {
        $this->balanceService = $balanceService;
    }

    /**
     * Handle the Ledger "created" event.
     */
    public function created(Ledger $ledger): void
    {
        if ($ledger->journal && $ledger->journal->date && $ledger->journal->status === 'posted') {
            $this->balanceService->applyDelta(
                $ledger->account,
                $ledger->journal->date,
                $ledger->debit,
                $ledger->credit
            );
        }
    }

    /**
     * Handle the Ledger "updated" event.
     */
    public function updated(Ledger $ledger): void
    {
        if ($ledger->journal && $ledger->journal->date && $ledger->journal->status === 'posted') {
            $deltaDebit = $ledger->debit - $ledger->getOriginal('debit');
            $deltaCredit = $ledger->credit - $ledger->getOriginal('credit');
            
            $this->balanceService->applyDelta(
                $ledger->account,
                $ledger->journal->date,
                $deltaDebit,
                $deltaCredit
            );
        }
    }

    /**
     * Handle the Ledger "deleted" event. (Soft delete)
     */
    public function deleted(Ledger $ledger): void
    {
        if ($ledger->journal && $ledger->journal->date && $ledger->journal->status === 'posted') {
            $this->balanceService->applyDelta(
                $ledger->account,
                $ledger->journal->date,
                -($ledger->debit),
                -($ledger->credit)
            );
        }
    }

    /**
     * Handle the Ledger "restored" event.
     */
    public function restored(Ledger $ledger): void
    {
        if ($ledger->journal && $ledger->journal->date && $ledger->journal->status === 'posted') {
            $this->balanceService->applyDelta(
                $ledger->account,
                $ledger->journal->date,
                $ledger->debit,
                $ledger->credit
            );
        }
    }

    /**
     * Handle the Ledger "force deleted" event.
     */
    public function forceDeleted(Ledger $ledger): void
    {
        if ($ledger->journal && $ledger->journal->date && $ledger->journal->status === 'posted') {
            $this->balanceService->applyDelta(
                $ledger->account,
                $ledger->journal->date,
                -($ledger->debit),
                -($ledger->credit)
            );
        }
    }
}
