<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $invoices = \App\Models\Invoice::all();

        if ($invoices->isEmpty()) {
            return "";
        }

        return $invoices;
    }
}
