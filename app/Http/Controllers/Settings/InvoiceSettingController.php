<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Settings\InvoiceSetting;
use Illuminate\Http\Request;

class InvoiceSettingController extends Controller
{
    public function index()
    {
        $setting = InvoiceSetting::getSetting();

        return response()->json(['setting' => $setting]);
    }

    public function save(Request $request)
    {
        $validated = $request->validate([
            'first_faktur'  => 'nullable|string|max:50',
            'second_faktur' => 'nullable|string|max:50',
            'third_faktur'  => 'nullable|string|max:50',
            'fourth_faktur' => 'nullable|string|max:50',
            'no_awal'       => 'required|integer|min:0',
            'no_akhir'      => 'required|integer|min:1',
            'company'       => 'nullable|integer',
        ]);

        $setting = InvoiceSetting::getSetting();
        $setting->update($validated);

        return response()->json(['success' => true, 'setting' => $setting->fresh()]);
    }
}
