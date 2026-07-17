<?php

namespace App\Observers;

use App\Models\Accounting\Journal;
use App\Services\CoaBalanceService;

class JournalObserver
{
    protected $balanceService;

    public function __construct(CoaBalanceService $balanceService)
    {
        $this->balanceService = $balanceService;
    }

    /**
     * Handle the Journal "updated" event.
     */
    public function updated(Journal $journal): void
    {
        // If status changed from draft/void to posted
        if ($journal->getOriginal('status') !== 'posted' && $journal->status === 'posted') {
            foreach ($journal->ledgers as $ledger) {
                $this->balanceService->applyDelta(
                    $ledger->account,
                    $journal->date,
                    $ledger->debit,
                    $ledger->credit
                );
            }
        }
        
        // If status changed from posted to draft/void
        if ($journal->getOriginal('status') === 'posted' && $journal->status !== 'posted') {
            foreach ($journal->ledgers as $ledger) {
                $this->balanceService->applyDelta(
                    $ledger->account,
                    $journal->date,
                    -($ledger->debit),
                    -($ledger->credit)
                );
            }
        }
    }
}
