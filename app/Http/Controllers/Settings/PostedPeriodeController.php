<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Settings\PostedPeriode;
use Illuminate\Http\Request;

class PostedPeriodeController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->user() || !($request->user()->hasRole('admin') || $request->user()->hasRole('super-admin'))) {
            abort(403, 'Unauthorized access.');
        }

        $year = $request->query('year', date('Y'));

        // Ensure 12 months exist for the selected year
        for ($month = 1; $month <= 12; $month++) {
            PostedPeriode::firstOrCreate([
                'bulan' => $month,
                'tahun' => $year,
            ], [
                'status' => false // default open
            ]);
        }

        $periods = PostedPeriode::where('tahun', $year)
            ->orderBy('bulan')
            ->get();

        return response()->json($periods);
    }

    public function toggle(Request $request, PostedPeriode $periode)
    {
        if (!$request->user() || !($request->user()->hasRole('admin') || $request->user()->hasRole('super-admin'))) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'status' => 'required|boolean',
        ]);

        if ($validated['status']) {
            $month = $periode->bulan;
            $year = $periode->tahun;
            
            // Anti-Draft Validation: Prevent locking if drafts exist
            $hasDrafts = false;
            $draftMessages = [];
            
            // Check Expenses
            $draftExpenses = \App\Models\Expenses\Expense::whereMonth('expense_date', $month)->whereYear('expense_date', $year)->where('expense_status_code', 'draft')->count();
            if ($draftExpenses > 0) {
                $hasDrafts = true;
                $draftMessages[] = "{$draftExpenses} Expense(s)";
            }
            
            // Check Expense Payments
            $draftExpPayments = \App\Models\Expenses\ExpensePayment::whereMonth('payment_date', $month)->whereYear('payment_date', $year)->where('status', 'draft')->count();
            if ($draftExpPayments > 0) {
                $hasDrafts = true;
                $draftMessages[] = "{$draftExpPayments} Bill Payment(s)";
            }
            
            // Check Invoices
            $draftInvoices = \App\Models\Incomes\Invoice::whereMonth('invoiced_at', $month)->whereYear('invoiced_at', $year)->where('invoice_status_code', 'draft')->count();
            if ($draftInvoices > 0) {
                $hasDrafts = true;
                $draftMessages[] = "{$draftInvoices} Invoice(s)";
            }
            
            // Check Payments (Invoice Receipts)
            $draftPayments = \App\Models\Expenses\Payment::whereMonth('paid_at', $month)->whereYear('paid_at', $year)->where('status', 'draft')->count();
            if ($draftPayments > 0) {
                $hasDrafts = true;
                $draftMessages[] = "{$draftPayments} Payment Receipt(s)";
            }
            
            // Check Manual Journals
            $draftJournals = \App\Models\Accounting\Journal::whereMonth('date', $month)->whereYear('date', $year)->where('status', 'draft')->count();
            if ($draftJournals > 0) {
                $hasDrafts = true;
                $draftMessages[] = "{$draftJournals} Manual Journal(s)";
            }
            
            if ($hasDrafts) {
                return response()->json([
                    'message' => 'Tidak dapat mengunci periode karena masih terdapat dokumen berstatus Draft: ' . implode(', ', $draftMessages)
                ], 422);
            }
        }

        $periode->update(['status' => $validated['status']]);

        return response()->json([
            'message' => 'Status periode berhasil diubah.',
            'periode' => $periode
        ]);
    }
}
