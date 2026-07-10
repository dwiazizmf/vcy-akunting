<?php

namespace App\Http\Controllers\Incomes;

class PrintInvoiceHelper
{
    public static function penyebut($nilai)
    {
        $nilai = abs($nilai);
        $huruf = array("", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan", "sepuluh", "sebelas");
        $temp = "";
        if ($nilai < 12) {
            $temp = " " . $huruf[(int)$nilai];
        } else if ($nilai < 20) {
            $temp = self::penyebut($nilai - 10) . " belas";
        } else if ($nilai < 100) {
            $temp = self::penyebut($nilai / 10) . " puluh" . self::penyebut($nilai % 10);
        } else if ($nilai < 200) {
            $temp = " seratus" . self::penyebut($nilai - 100);
        } else if ($nilai < 1000) {
            $temp = self::penyebut($nilai / 100) . " ratus" . self::penyebut($nilai % 100);
        } else if ($nilai < 2000) {
            $temp = " seribu" . self::penyebut($nilai - 1000);
        } else if ($nilai < 1000000) {
            $temp = self::penyebut($nilai / 1000) . " ribu" . self::penyebut($nilai % 1000);
        } else if ($nilai < 1000000000) {
            $temp = self::penyebut($nilai / 1000000) . " juta" . self::penyebut($nilai % 1000000);
        } else if ($nilai < 1000000000000) {
            $temp = self::penyebut($nilai / 1000000000) . " milyar" . self::penyebut(fmod($nilai, 1000000000));
        } else if ($nilai < 1000000000000000) {
            $temp = self::penyebut($nilai / 1000000000000) . " trilyun" . self::penyebut(fmod($nilai, 1000000000000));
        }
        return $temp;
    }

    public static function terbilang($nilai)
    {
        if ($nilai < 0) {
            $hasil = "minus " . trim(self::penyebut($nilai));
        } else {
            $hasil = trim(self::penyebut($nilai));
        }
        return $hasil;
    }

    public static function formatItemForPrint($item)
    {
        $nama_item_len = strlen($item->name);
        $nama_item = '';
        $enterName = '';
        $br_jns = '';
        $jml_enter = 0;

        if ($nama_item_len > 40) {
            $ceil_40 = 0;
            $ceil_total = (int)ceil($nama_item_len / 40);
            for ($i = 0; $i < $ceil_total; $i++) {
                if ($i == ($ceil_total - 1)) {
                    $nama_item .= substr($item->name, $ceil_40, 40);
                } else {
                    $nama_item .= substr($item->name, $ceil_40, 40) . "<br/>";
                }
                $ceil_40 += 40;
                $br_jns .= "<br/>";
            }
            $jml_enter += $ceil_total;
        } else {
            $jml_enter++;
            $nama_item = $item->name;
            $br_jns .= "<br/>";
        }

        $br_m3 = '';
        $margin_top = '';
        if (isset($item->cubication) && (($item->cubication != 0) || ($item->total_volume != 0))) {
            if (($item->cubication != 0) && ($item->total_volume != 0)) {
                $br_m3 = "<br/>";
                $margin_top = 'margin-top: 7px!important; margin-bottom: 10px!important;';
            }
        }

        $isKosong = ($nama_item == '-');
        $html_jenis = '';
        if (!$isKosong) {
            $html_jenis = '<small style="font-size: 12px;">--' . $nama_item . $br_m3 . "&nbsp;</small>";
        }

        $html_jumlah = '';
        if ($item->name != '-') {
            $html_jumlah = '<small style="font-size: 12px;">' . number_format($item->quantity, 0, ",", ".") . " " . $item->satuan . $enterName . $br_m3 . $br_jns . "&nbsp;</small>";
        }

        $html_cubication = '&nbsp;';
        if (isset($item->cubication) && (($item->cubication != 0) || ($item->total_volume != 0))) {
            $br_m4 = $br_m3;
            if (($item->cubication != 0) && ($item->total_volume != 0)) {
                $num_text = sprintf("%.3f", $item->total_volume) . " TON<br/>&nbsp;" . sprintf("%.3f", $item->cubication) . " M3";
                $jml_enter++;
                $br_m4 = '';
            } elseif ($item->total_volume != 0) {
                $num_text = sprintf("%.3f", $item->total_volume) . " TON";
            } else {
                $num_text = sprintf("%.3f", $item->cubication) . " M3";
            }
            $html_cubication = '<small style="font-size: 12px; margin-right: 0px;">&nbsp;' . $num_text . $enterName . $br_m4 . $br_jns . "</small>";
        } else {
            if (isset($item->cubication)) {
                $html_cubication = '<small style="font-size: 12px; margin-right: 0px;">&nbsp;' . $enterName . $br_m3 . $br_jns . '</small>';
            }
        }

        $itemPrice = '&nbsp;';
        if ($item->tariff_per_volume != 0 && $item->tariff_per_volume != null) {
            $itemPrice = number_format($item->tariff_per_volume, 0, ",", ".");
        } else {
            if ($item->cubication != 0 && $item->cubication != null) {
                $itemPrice = number_format($item->price / $item->cubication, 0, ",", ".");
            } else {
                if ($item->price != 0) {
                    $itemPrice = number_format($item->price, 0, ",", ".");
                }
            }
        }
        $html_tarif = '<small style="font-size: 12px; ' . $margin_top . '">' . $itemPrice . $enterName . $br_m3 . $br_jns . "&nbsp;</small>";

        $itemTotal = ($item->total != 0) ? number_format($item->total, 0, ",", ".") : '&nbsp;';
        $html_jumlah_rp = '<small style="font-size: 12px; ' . $margin_top . '">' . $itemTotal . $enterName . $br_m3 . $br_jns . "&nbsp;</small>";

        return [
            'html_jenis' => $html_jenis,
            'html_jumlah' => $html_jumlah,
            'html_cubication' => $html_cubication,
            'html_tarif' => $html_tarif,
            'html_jumlah_rp' => $html_jumlah_rp,
            'jml_enter' => $jml_enter,
            'is_kosong' => $isKosong,
            'total' => $item->total
        ];
    }
}
