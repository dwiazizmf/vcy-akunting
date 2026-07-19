<?php

namespace App\Http\Controllers\Incomes;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Shuchkin\SimpleXLSX;

class UploadFakturController extends Controller
{
    /**
     * Store a newly uploaded excel file and parse it
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240', // Max 10MB
        ]);

        try {
            $filePath = $request->file('file')->path();
            
            if ($xlsx = SimpleXLSX::parse($filePath)) {
                $rows = $xlsx->rows();
                $updatedCount = 0;

                foreach ($rows as $index => $row) {
                    if ($index === 0) continue; // Skip header
                    
                    $invoiceText = trim($row[0] ?? '');
                    $noFaktur = trim($row[1] ?? '');
                    
                    if (!empty($invoiceText)) {
                        $affected = DB::table('invoices')
                            ->where('invoice_text', $invoiceText)
                            ->update([
                                'no_faktur_pajak' => $noFaktur,
                                'no_faktur_int' => 0,
                                'isFaktur' => 0
                            ]);
                            
                        if ($affected > 0) {
                            $updatedCount++;
                        }
                    }
                }
                
                return back()->with('success', "File faktur berhasil diunggah. Sebanyak {$updatedCount} invoice telah diperbarui.");
            } else {
                return back()->with('error', 'Gagal mem-parsing file Excel: ' . SimpleXLSX::parseError());
            }
        } catch (\Exception $e) {
            Log::error('Error uploading faktur: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan saat memproses file: ' . $e->getMessage());
        }
    }
}
