<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Settings\PostedPeriode;
use Illuminate\Http\Request;

class PostedPeriodeController extends Controller
{
    public function index(Request $request)
    {
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
        $validated = $request->validate([
            'status' => 'required|boolean',
        ]);

        $periode->update(['status' => $validated['status']]);

        return response()->json([
            'message' => 'Status periode berhasil diubah.',
            'periode' => $periode
        ]);
    }
}
