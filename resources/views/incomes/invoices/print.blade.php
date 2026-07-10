<script src="//code.jquery.com/jquery-1.11.1.min.js"></script>
<link href="//netdna.bootstrapcdn.com/bootstrap/3.1.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
<script src="//netdna.bootstrapcdn.com/bootstrap/3.1.0/js/bootstrap.min.js"></script>

<!------ Include the above in your HEAD tag ---------->
<style type="text/css">
    .invoice-title h2, .invoice-title h3 {
        display: inline-block;
    }
    .table > tbody > tr > .no-line {
        border-top: none;
    }
    .table > thead > tr > .no-line {
        border-bottom: none;
    }
    .table > tbody > tr > .thick-line {
        border-top: 3px solid black;
    }
    .no_padding_left {
        padding-left:0px;
        padding-bottom:10px;
        font-size:10px;
    }
    .table-bordered th,
    .table-bordered td {
      border: 1px solid #000 !important;
    }
    .border_bottom {
        border: 1px solid black;
        height: 40;
        width: 618px;
    }
    .font_size_10{
        font-size: 10px;
    }
    .font_size_9 {
        font-size: 9px;
    }
    .p_style{
        font-size: 10px;
        margin-bottom: 0px;
    }
    .truncate-line {
        white-space: nowrap; 
        overflow: hidden; 
        text-overflow: ellipsis;
        height: 16px;
    }
    .fixed-address {
        font-size: 10px; 
        height: 40px; 
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        line-height: 1.3;
    }
    @page {
      size: letter;
      margin: 0;
    }
    @media print {
      html, body {
        width: 215.9mm;
        height: 279.4mm;
      }
    }
</style>
<div class="container">   
    @php
        $data_multi = $abc['data_multi'];
        $company_id = $abc['company'];
        $bln_txt = array(1=>"Januari","Februari","Maret", "April", "Mei","Juni","Juli","Agustus","September","Oktober", "November","Desember"); 
        $array_bln = array(1=>"I","II","III", "IV", "V","VI","VII","VIII","IX","X", "XI","XII");
    @endphp

    @foreach ($data_multi as $multiIndex => $multi)
        @php
            $inv = $multi['inv'];
            $pages = $multi['pages'];

            $isFCL = $inv->isFCL;
            $invoiced_at = $inv->invoiced_at;
            $isTax = $inv->isTax;
            $amount_inv = $inv->amount; // subtotal

            $start_date_pajak = strtotime('2022-04-01');
            $end_date_pajak = strtotime($invoiced_at);
            $persenPajak = 0.011;
            $persenPajakBagi = 1.011;

            $subTotal = 0;
            $dpp = 0;
            $ppn = 0;
        @endphp

        @foreach ($pages as $pageIndex => $pageItems)
            <div class="row">
                <div class="text-right font_size_10">&nbsp;</div>
                <div class="col-xs-12">
                    <div class="row">
                        <div class="col-xs-6"> 
                            <div class="invoice-title" style="margin-top: 0px; height: 96px;"></div>
                        </div>
                        <div class="col-xs-6" style="padding-top: 20px;">
                            <h4><strong><u></u></strong></h4>
                        </div>
                    </div>
                    
                    <div style="margin-top: 10px; margin-bottom: 10px;"></div>
                    
                    <div class="row">
                        <div class="col-xs-8">
                            <div class="col-xs-12">
                                <div class="col-xs-3 text-left no_padding_left">&nbsp;</div>
                                <div class="col-xs-1 text-left font_size_10" style="padding:0px;width: 1%;">&nbsp;</div>
                                <div class="col-xs-8 text-left font_size_10 truncate-line">{{ $inv->terima_dari }}</div>
                            </div>
                            <div class="col-xs-12">
                                <div class="col-xs-3 text-left no_padding_left">&nbsp;</div>
                                <div class="col-xs-1 text-left font_size_10" style="padding:0px;width: 1%;">&nbsp;</div>
                                <div class="col-xs-8 text-left fixed-address">{!! $inv->alamat_invoice !!}</div>
                            </div>
                            <div class="col-xs-12">
                                <div class="col-xs-3 text-left no_padding_left">&nbsp;</div>
                                <div class="col-xs-1 text-left font_size_10" style="padding:0px;width: 1%;">&nbsp;</div>
                                <div class="col-xs-8 text-left font_size_10 truncate-line">{{ $inv->nama_kapal }}</div>
                            </div>
                            <div class="col-xs-12">
                                <div class="col-xs-3 text-left no_padding_left">&nbsp;</div>
                                <div class="col-xs-1 text-left font_size_10" style="padding:0px;width: 1%;">&nbsp;</div>
                                <div class="col-xs-8 text-left font_size_10 truncate-line">{{ $inv->voy . " / " . date('j', strtotime($inv->departure_date)) . " " . $bln_txt[date('n', strtotime($inv->departure_date))] . " " . date('Y', strtotime($inv->departure_date)) }}</div>
                            </div>
                            <div class="col-xs-12">
                                <div class="col-xs-3 text-left no_padding_left">&nbsp;</div>
                                <div class="col-xs-1 text-left font_size_10" style="padding:0px;width: 1%;">&nbsp;</div>
                                <div class="col-xs-8 text-left font_size_10 truncate-line">{{ $inv->pelabuhan_asal }}</div>
                            </div>
                            <div class="col-xs-12">
                                <div class="col-xs-3 text-left no_padding_left">&nbsp;</div>
                                <div class="col-xs-1 text-left font_size_10" style="padding:0px;width: 1%;">&nbsp;</div>
                                <div class="col-xs-8 text-left font_size_10 truncate-line">{{ $inv->pelabuhan_tujuan }}</div>
                            </div>
                        </div>
                        <div class="col-xs-4" style="padding-right: 0px; padding-left: 0px;">
                            <div class="col-xs-12">
                                <div class="col-xs-6 text-left no_padding_left"></div>
                                <div class="col-xs-6 text-left font_size_10"></div>
                            </div>
                            <div class="col-xs-12">
                                <div class="col-xs-5 text-left no_padding_left">&nbsp;</div>
                                <div class="col-xs-7 text-left font_size_10">&nbsp; hal {{ $pageIndex + 1 }}</div>
                            </div>
                            <div class="col-xs-12">
                                <div class="col-xs-5 text-left no_padding_left">&nbsp;</div>
                                <div class="col-xs-7 text-left font_size_10">&nbsp; 
                                    @php 
                                        $invoice1 = str_replace('INV-', "", $inv->invoice_number);
                                        $bln = $array_bln[date('n', strtotime($inv->invoiced_at))];
                                        if($company_id == 1){
                                            echo $invoice1 . "/" . $bln . "/" . date("Y", strtotime($inv->invoiced_at));
                                        } else {
                                            echo $invoice1 . "/MBK/" . $bln . "/" . date("Y", strtotime($inv->invoiced_at));
                                        }
                                    @endphp 
                                </div>
                            </div>
                            <div class="col-xs-12">
                                <div class="col-xs-5 text-left no_padding_left">&nbsp;</div>
                                <div class="col-xs-7 text-left font_size_10">&nbsp; {{ date('j', strtotime($inv->invoiced_at)) . " " . $bln_txt[date('n', strtotime($inv->invoiced_at))] . " " . date('Y', strtotime($inv->invoiced_at)) }}</div>
                            </div>
                            
                            <div class="col-xs-12">
                                <div class="col-xs-5 text-left no_padding_left">&nbsp;</div>
                                <div class="col-xs-7 text-left font_size_9">&nbsp; 
                                    @php
                                        if($company_id == 1 || ($company_id == 2 && ($inv->no_faktur_pajak != '' && $inv->no_faktur_pajak != '-' && $inv->no_faktur_pajak != null))) {
                                            if($inv->no_faktur_pajak != '' && (int)(date('Y', strtotime($inv->invoiced_at)) < 2025)) {
                                                $exp_faktur = explode(".", $inv->no_faktur_pajak);
                                                if(count($exp_faktur) >= 3) {
                                                    $faktur1 = substr($exp_faktur[2], 0, 4);
                                                    $faktur2 = substr($exp_faktur[2], 4, 4);
                                                    echo $exp_faktur[0] . "." . $exp_faktur[1] . "." . $faktur1 . " " . $faktur2;
                                                } else {
                                                    echo $inv->no_faktur_pajak;
                                                }
                                            } else {
                                                echo $inv->no_faktur_pajak;
                                            }
                                        } else {
                                            echo "&nbsp;";
                                        } 
                                    @endphp
                                </div>
                            </div>
                           
                            <div class="col-xs-12">
                                <div class="col-xs-5 text-left no_padding_left" style="padding-right: 0px;">&nbsp;</div>
                                <div class="col-xs-7 text-left font_size_10">&nbsp;</div>
                            </div>
                            <div class="col-xs-12">
                                <div class="col-xs-5 text-left no_padding_left" style="padding-right: 0px;">&nbsp;</div>
                                <div class="col-xs-7 text-left font_size_10">&nbsp;{{ $inv->r_invoice_text }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-xs-12">
                    <div class="panel" style="margin-bottom: 0px; border: none; box-shadow: none;">
                        <div class="panel-heading" style="border: none;">
                            <h5 class="panel-title "><strong style="font-size: 13;">&nbsp;</strong></h5>
                        </div>
                        <div class="panel-body" style="padding-top: 0px; padding-bottom: 0px; border: none;">
                            <div class="table-responsive" style="border: none; overflow: hidden;">
                                <table class="table ">
                                    <thead>
                                        <tr>
                                            <td width="40%" class="text-center"><strong style="font-size: 13px;">&nbsp;</strong></td>
                                            <td width="20%" class="text-center"><strong style="font-size: 13px;">&nbsp;</strong></td>
                                            <td width="5%" class="text-center"><strong style="font-size: 13px;">&nbsp;</strong></td>
                                            <td width="10%" class="text-right"><strong style="font-size: 13px;">&nbsp;</strong></td>
                                            <td width="10%" class="text-right"><strong style="font-size: 13px;">&nbsp;</strong></td>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $jml_enter_page = 0;
                                            $jml_kosong_page = 0;
                                        @endphp
                                        
                                        @foreach($pageItems as $i => $itemInfo)
                                            @php
                                                $subTotal += $itemInfo['total'];
                                                $dpp += ($itemInfo['total'] * 0.1);
                                                $ppn += (($start_date_pajak <= $end_date_pajak) ? ($itemInfo['total'] * $persenPajak) : ($itemInfo['total'] * 0.01));
                                                
                                                $jml_enter_page += $itemInfo['jml_enter'];
                                                if ($itemInfo['is_kosong']) {
                                                    $jml_kosong_page++;
                                                }
                                            @endphp
                                        @endforeach
                                        
                                        <tr>
                                            <td class="item" style="border-top: 1px solid #fff;">
                                                @foreach($pageItems as $itemInfo)
                                                    {!! $itemInfo['html_jenis'] !!}<br/>
                                                @endforeach
                                                @if(count($pageItems) <= 15)
                                                    @for ($zz = 1; $zz <= (15 - $jml_enter_page - $jml_kosong_page); $zz++)
                                                        <small style="font-size: 12px;">&nbsp;</small><br/>
                                                    @endfor
                                                @endif
                                            </td>
                                            
                                            <td class="quantity text-center" style="border-top: 1px solid #fff;">
                                                @foreach($pageItems as $itemInfo)
                                                    {!! $itemInfo['html_jumlah'] !!}
                                                @endforeach
                                            </td>
                                            
                                            <td style="border-top: 1px solid #fff;padding-right: 0px;" class="text-left">
                                                @foreach($pageItems as $itemInfo)
                                                    {!! $itemInfo['html_cubication'] !!}
                                                @endforeach
                                            </td>
                                            
                                            <td class="style-price price text-right" style="border-top: 1px solid #fff;padding-left: 0px;">
                                                @foreach($pageItems as $itemInfo)
                                                    {!! $itemInfo['html_tarif'] !!}
                                                @endforeach
                                            </td>
                                            
                                            <td class="style-price total text-right" style="border-top: 1px solid #fff;">
                                                @foreach($pageItems as $itemInfo)
                                                    {!! $itemInfo['html_jumlah_rp'] !!}
                                                @endforeach
                                            </td>
                                        </tr>  
                                         
                                        <tr>
                                            <td colspan="4" class="thick-line text-right" style="border-top: 3px solid white; padding-top: 10px;">
                                               <div class="col-xs-12">
                                                    <div class="col-xs-7 text-left no_padding_left" style="margin-bottom: 0px;">
                                                      @if($company_id == 1)
                                                        <p style="text-align: left; font-size: 13px; padding-top: 10px" >
                                                            Bank Account<br/>
                                                            A/N PT. Yudha Antar Nusa<br/>
                                                            BCA Capem Jembatan Dua - Jakarta &nbsp;&nbsp;&nbsp;&nbsp;: 074-301-3988<br/>
                                                            Bank Mandiri KCU Jakarta Pluit Selatan : 168-00-6672727-0
                                                        </p>
                                                      @else
                                                        <p style="text-align: left; font-size: 13px; margin-bottom: 10px;" >
                                                            Bank Account<br/>
                                                            A/N PT. Mitra Bahari Khatulistiwa<br/>
                                                            Bank Mandiri Cab. Pluit Kencana - Jakarta : 168-00-6007778-9<br/>
                                                            BCA KCP CBD Pluit : 806-066-3999
                                                        </p>
                                                      @endif
                                                    </div>
                                                    <div class="col-xs-4 text-left no_padding_left" style="margin-bottom: 0px;">
                                                        <div class="text-center">
                                                            @if($pageIndex == count($pages) - 1)
                                                              <img src="https://quickchart.io/qr?text={{ $inv->invoice_text }}|{{ date('Y-m-d', strtotime($invoiced_at)) }}|{{ $inv->order_number }}" class="qr-code img-thumbnail img-responsive" style="width: 84px;" />
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <div class="col-xs-1 text-left font_size_10" style="padding:0px;width: 1%;">
                                                        <p>
                                                            @if($isTax == 1)
                                                                <strong style="font-size: 12px;">&nbsp;</strong> <br>
                                                                <strong style="font-size: 12px;">&nbsp;</strong> <br>
                                                                <strong style="font-size: 12px;">&nbsp;</strong> <br>
                                                            @endif
                                                        </p>
                                                    </div>
                                                </div>    
                                            </td>
                                            <td class="thick-line text-right" style="border-top: 3px solid white; padding-top: {{ $isTax == 1 ? '20px' : '10px' }};">
                                                <p style="font-size: 12px;">
                                                @if($isTax == 1)
                                                    @if($pageIndex == count($pages) - 1)
                                                        @php
                                                            $calcDpp = ($start_date_pajak <= $end_date_pajak) ? $subTotal : $subTotal * 0.1;
                                                            $calcPpn = ($start_date_pajak <= $end_date_pajak) ? ($subTotal * $persenPajak) : ($subTotal * 0.01);
                                                        @endphp
                                                        {{ number_format($subTotal, 0, ",", ".") }}<br>
                                                        {{ number_format($calcDpp, 0, ",", ".") }}<br>
                                                        {{ number_format($calcPpn, 0, ",", ".") }}<br>
                                                    @else
                                                        &nbsp;<br>&nbsp;<br>&nbsp;<br>
                                                    @endif
                                                @endif
                                                </p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="4" class="no-line text-right"><strong style="font-size: 12px;">&nbsp;</strong></td>
                                            <td class="no-line text-right"> 
                                                <p style="font-size: 12px; {{ $isTax != 1 ? 'padding-top: 10px;' : '' }}">
                                                @if($pageIndex == count($pages) - 1)
                                                    @php
                                                        $finalTotal = $subTotal;
                                                        if(!$isFCL && $subTotal < 1) {
                                                            $finalTotal = $amount_inv;
                                                        }
                                                        if($isTax == 1 && ($isFCL || $subTotal >= 1)) {
                                                            $finalTotal += $calcPpn;
                                                        }
                                                    @endphp
                                                    {{ number_format($finalTotal, 0, ",", ".") }}
                                                @else
                                                    &nbsp;
                                                @endif
                                                </p>
                                            </td>
                                        </tr> 
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xs-12">
                    <div class="col-xs-2">
                        &nbsp; 
                    </div>
                    <div class="col-xs-10 border_bottom" style="padding-top: 9px; border: 1px solid white;">
                        @if($pageIndex == count($pages) - 1)
                            {{ \App\Http\Controllers\Incomes\PrintInvoiceHelper::terbilang(round($finalTotal)) }} rupiah
                        @else
                            &nbsp;
                        @endif
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-xs-12" style="padding-top: 16px;">
                    <div class="col-xs-9"> 
                        <div class="col-xs-12">
                            @if($isTax == 1)
                            <p class="p_style"><b style="padding-left: 342px;"></b> 
                                @if($pageIndex == count($pages) - 1)
                                    {{ number_format(($subTotal * 0.02), 0, ",", ".") }}
                                @else
                                    &nbsp;
                                @endif
                            </p>
                            @endif
                        </div>
                    </div> 
                    <div class="col-xs-3" style="height: 10px;">
                        <p style="text-align: left;font-size: 12px;">Jakarta, {{ date('j', strtotime($invoiced_at)) . " " . $bln_txt[date('n', strtotime($invoiced_at))] . " " . date('Y', strtotime($invoiced_at)) }}</p><br/><br/><br/>
                    </div> 
                </div>
            </div> 
            
            <div class="row">
                @php
                    $isLastPageInInvoice = ($pageIndex == count($pages) - 1);
                    $isLastInvoiceInMulti = ($multiIndex == count($data_multi) - 1);
                    
                    if ($isLastPageInInvoice) {
                        if ($isLastInvoiceInMulti) {
                            $padding = '10.03px';
                        } else {
                            $padding = $isTax == 1 ? '135.031px' : '128.312px';
                        }
                    } else {
                        $padding = $isTax == 1 ? '129.031px' : '128.312px';
                    }
                @endphp
                <div class="col-xs-12" style="padding-top: {{ $padding }};">&nbsp;</div>
            </div>
        @endforeach
    @endforeach
</div>

<script type="text/javascript">
   window.print();
</script>
