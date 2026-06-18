<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Accounting\Accounting\Journal;
use App\Models\Accounting\Accounting\Ledger;
use App\Models\Accounting\Accounting\Account;
use App\Models\Incomes\Customer;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class JournalController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('company_id') ?: 1;
        
        $query = Journal::with(['ledgers', 'postedBy'])
            ->where('company_id', $companyId);
            
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('journal_number', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('reference', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $journals = $query->orderBy('date', 'desc')
                          ->orderBy('id', 'desc')
                          ->paginate($request->input('per_page', 25))
                          ->withQueryString();
                          
        // Transform the collection to include total amounts
        $journals->getCollection()->transform(function ($journal) {
            $journal->total_debit = $journal->ledgers->sum('debit');
            $journal->total_credit = $journal->ledgers->sum('credit');
            return $journal;
        });

        return Inertia::render('Accounting/Journals/Index', [
            'journals' => $journals,
            'filters' => [
                'search' => $request->input('search', ''),
                'status' => $request->input('status', '')
            ]
        ]);
    }

    public function create()
    {
        $companyId = session('company_id') ?: 1;
        
        // Only active accounts for dropdown
        $accounts = \App\Helpers\AccountHelper::getFormattedAccounts($companyId);
                           
        // Contacts for sub-ledgers
        $contacts = Customer::select('id', 'name')->get();

        return Inertia::render('Accounting/Journals/Create', [
            'accounts' => $accounts,
            'contacts' => $contacts,
            'currentDate' => Carbon::today()->format('Y-m-d')
        ]);
    }

    public function store(Request $request)
    {
        $companyId = session('company_id') ?: 1;

        $validated = $request->validate([
            'date' => 'required|date',
            'reference' => 'nullable|string',
            'description' => 'required|string',
            'status' => 'required|in:draft,posted',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:accounts,id',
            'lines.*.contact_id' => 'nullable|exists:customers,id',
            'lines.*.description' => 'nullable|string',
            'lines.*.debit' => 'required|numeric|min:0',
            'lines.*.credit' => 'required|numeric|min:0',
        ]);

        // Validation: Double Entry Must Balance
        $totalDebit = collect($validated['lines'])->sum('debit');
        $totalCredit = collect($validated['lines'])->sum('credit');
        
        if (abs($totalDebit - $totalCredit) > 0.001) {
            return back()->withErrors(['lines' => 'Total Debit dan Credit harus seimbang (Balance).'])->withInput();
        }
        
        if ($totalDebit <= 0) {
            return back()->withErrors(['lines' => 'Total transaksi tidak boleh nol.'])->withInput();
        }

        DB::beginTransaction();
        try {
            // Generate Journal Number
            $date = Carbon::parse($validated['date']);
            $prefix = 'JRN-' . $date->format('Ym') . '-';
            
            $lastJournal = Journal::where('company_id', $companyId)
                                  ->where('journal_number', 'like', $prefix . '%')
                                  ->orderBy('journal_number', 'desc')
                                  ->first();
                                  
            if ($lastJournal) {
                $lastNumber = intval(substr($lastJournal->journal_number, -4));
                $newNumber = $lastNumber + 1;
            } else {
                $newNumber = 1;
            }
            
            $journalNumber = $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);

            // Create Header
            $journalData = [
                'company_id' => $companyId,
                'journal_number' => $journalNumber,
                'date' => $validated['date'],
                'reference' => $validated['reference'],
                'description' => $validated['description'],
                'status' => $validated['status'],
            ];
            
            if ($validated['status'] === 'posted') {
                $journalData['posted_at'] = now();
                $journalData['posted_by'] = auth()->id() ?? 1; // Fallback if no auth
            }
            
            $journal = Journal::create($journalData);

            // Create Lines (Ledgers)
            foreach ($validated['lines'] as $line) {
                Ledger::create([
                    'company_id' => $companyId,
                    'journal_id' => $journal->id,
                    'account_id' => $line['account_id'],
                    'contact_id' => $line['contact_id'],
                    'debit' => $line['debit'],
                    'credit' => $line['credit'],
                    'description' => $line['description'] ?? $validated['description'],
                ]);
            }

            DB::commit();
            return redirect()->route('journals.index')->with('success', 'Jurnal berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyimpan jurnal: ' . $e->getMessage())->withInput();
        }
    }

    public function show(Journal $journal)
    {
        $journal->load(['ledgers.account', 'ledgers.contact', 'postedBy']);
        
        return Inertia::render('Accounting/Journals/Show', [
            'journal' => $journal
        ]);
    }
    
    public function updateStatus(Request $request, Journal $journal)
    {
        $validated = $request->validate([
            'status' => 'required|in:posted,void'
        ]);
        
        if ($journal->status === 'void') {
            return back()->with('error', 'Jurnal yang sudah void tidak dapat diubah.');
        }
        
        $journal->status = $validated['status'];
        
        if ($validated['status'] === 'posted') {
            $journal->posted_at = now();
            $journal->posted_by = auth()->id() ?? 1;
        }
        
        $journal->save();
        
        return back()->with('success', 'Status jurnal berhasil diperbarui.');
    }
}
