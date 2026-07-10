@php
    function penyebut($nilai) { 
        $nilai = abs($nilai);
        $huruf = array("", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan", "sepuluh", "sebelas");
        $temp = "";
        if ($nilai < 12) {
            $temp = " ". $huruf[$nilai];
        } else if ($nilai <20) {
            $temp = penyebut($nilai - 10). " belas";
        } else if ($nilai < 100) {
            $temp = penyebut($nilai/10)." puluh". penyebut($nilai % 10);
        } else if ($nilai < 200) {
            $temp = " seratus" . penyebut($nilai - 100);
        } else if ($nilai < 1000) {
            $temp = penyebut($nilai/100) . " ratus" . penyebut($nilai % 100);
        } else if ($nilai < 2000) {
            $temp = " seribu" . penyebut($nilai - 1000);
        } else if ($nilai < 1000000) {
            $temp = penyebut($nilai/1000) . " ribu" . penyebut($nilai % 1000);
        } else if ($nilai < 1000000000) {
            $temp = penyebut($nilai/1000000) . " juta" . penyebut($nilai % 1000000);
        } else if ($nilai < 1000000000000) {
            $temp = penyebut($nilai/1000000000) . " milyar" . penyebut(fmod($nilai,1000000000));
        } else if ($nilai < 1000000000000000) {
            $temp = penyebut($nilai/1000000000000) . " trilyun" . penyebut(fmod($nilai,1000000000000));
        }     
        return $temp;
    }
 
    function terbilang($nilai) {
        if($nilai<0) {
            $hasil = "minus ". trim(penyebut($nilai));
        } else {
            $hasil = trim(penyebut($nilai));
        }           
        return $hasil;
    }
@endphp
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

    @page {
      size: letter;
      margin: 0;
    }
    @media print {
      html, body {
        width: 215.9mm;
        height: 279.4mm;
      }
      /* ... the rest of the rules ... */
    }
</style>
<div class="container">   
   @php
    $i = 0;
    
    $isFCL = '';
    $invoiced_at = '';
    $data_multi = $abc['data_multi'];
    
    for ($ii=0; $ii < count($data_multi); $ii++) { 
        $subTotal = 0;
        $dpp = 0;
        $ppn = 0;

        $data_inv = $data_multi[$ii]['inv'];
        $data_item = $data_multi[$ii]['item'];

        $aa = 0;
        $bb = 0;
        $data_item_bagi = array();
    @endphp

    @php $jml_enter9 = 0; $jmlItemBagi = 0; $ceil_total = 1; @endphp
    @foreach($data_item as $item2)
        @php        
            $nama_item_len = strlen($item2->name);
            

            if($nama_item_len > 40){

                $ceil_total = ceil($nama_item_len/40);
                $jml_enter9 = $jml_enter9+$ceil_total;
                
            }else{
                $jml_enter9++;
            }
            
            if($jml_enter9 > 14){
                $jml_enter9 = 0 + $ceil_total;
                $jmlItemBagi++;
                ++$bb;
            }

            $data_item_bagi[$bb][] = $item2;
        @endphp
    @endforeach
    
   @php
    for($cc=0;$cc < count($data_item_bagi);$cc++){
        $arr_jenis = array();
        $arr_jumlah = array();
        $arr_cubication = array();
        $arr_tarif_kirim = array();
        $arr_jumlah_rp = array();
   @endphp
    <div class="row">
        <div class="text-right font_size_10">
        @php echo '&nbsp'; @endphp
        </div>
        <div class="col-xs-12">
            <div class="row">
                <div class="col-xs-6"> 
                        @php
                            //if($abc['company'] == 1){
                        @endphp
                            <div class="invoice-title" style="margin-top: 0px; height: 96px;"> 
                        @php
                            //}else{
                        @endphp
                            <!--<div class="invoice-title" style="margin-top: 0px; height: 106px;">-->
                        @php
                            //}
                        @endphp
                    </div>
                </div>
                <div class="col-xs-6" style="padding-top: 20px;">
                    <h4><strong><u></u></strong></h4>
                </div>
            </div>
            <?php 
                $bln_txt = array(1=>"Januari","Februari","Maret", "April", "Mei","Juni","Juli","Agustus","September","Oktober", "November","Desember"); 
            ?>
            <div style="margin-top: 10; margin-bottom: 10;"></div>
            @foreach($data_inv as $item)
            @php
                $isFCL = $item->isFCL;
                $amount_inv = $item->amount;
                $invoiced_text = $item->invoice_text;
                $invoiced_at = $item->invoiced_at;
                $connote = $item->order_number;
                $isTax = $item->isTax;
                //if($i == 0){
            @endphp
            <div class="row">
                <div class="col-xs-8">
                    <div class="col-xs-12">
                        <div class="col-xs-3 text-left no_padding_left">&nbsp;</div>
                        <div class="col-xs-1 text-left font_size_10" style="padding:0px;width: 1%;">&nbsp;</div>
                        <div class="col-xs-8 text-left font_size_10">@php echo $item->terima_dari; @endphp </div>
                    </div>
                    <div class="col-xs-12">
                        <div class="col-xs-3 text-left no_padding_left">&nbsp;</div>
                        <div class="col-xs-1 text-left font_size_10" style="padding:0px;width: 1%;">&nbsp;</div>
                        <div class="col-xs-8 text-left font_size_10" style="font-size: 10px; height: 40px; overflow: hidden;">@php echo $item->alamat_invoice; @endphp </div>
                    </div>
                    <div class="col-xs-12">
                        <div class="col-xs-3 text-left no_padding_left">&nbsp;</div>
                        <div class="col-xs-1 text-left font_size_10" style="padding:0px;width: 1%;">&nbsp;</div>
                        <div class="col-xs-8 text-left font_size_10">@php echo $item->nama_kapal; @endphp </div>
                    </div>
                    <div class="col-xs-12">
                        <div class="col-xs-3 text-left no_padding_left">&nbsp;</div>
                        <div class="col-xs-1 text-left font_size_10" style="padding:0px;width: 1%;">&nbsp;</div>
                        <div class="col-xs-8 text-left font_size_10">@php echo $item->voy." / ".date('j',strtotime($item->departure_date))." ".$bln_txt[date('n',strtotime($item->departure_date))]." ".date('Y',strtotime($item->departure_date)); @endphp </div>
                    </div>
                    <div class="col-xs-12">
                        <div class="col-xs-3 text-left no_padding_left">&nbsp;</div>
                        <div class="col-xs-1 text-left font_size_10" style="padding:0px;width: 1%;">&nbsp;</div>
                        <div class="col-xs-8 text-left font_size_10" >@php echo $item->pelabuhan_asal; @endphp </div>
                    </div>
                    <div class="col-xs-12">
                        <div class="col-xs-3 text-left no_padding_left">&nbsp;</div>
                        <div class="col-xs-1 text-left font_size_10" style="padding:0px;width: 1%;">&nbsp;</div>
                        <div class="col-xs-8 text-left font_size_10">@php echo $item->pelabuhan_tujuan; @endphp </div>
                    </div>
                </div>
                <div class="col-xs-4" style="padding-right: 0px; padding-left: 0px;">
                    <div class="col-xs-12">
                        <div class="col-xs-6 text-left no_padding_left"></div>
                        <div class="col-xs-6 text-left font_size_10"></div>
                    </div>
                    <div class="col-xs-12">
                        <div class="col-xs-5 text-left no_padding_left">&nbsp;</div>
                        <div class="col-xs-7 text-left font_size_10">&nbsp; @php echo 'hal '.($cc+1); @endphp</div>
                    </div>
                    <div class="col-xs-12">
                        <div class="col-xs-5 text-left no_padding_left">&nbsp;</div>
                        <!--<div class="col-xs-7 text-left font_size_10" style="font-size: 13px; padding-left: 11px;">&nbsp;-->
                        <div class="col-xs-7 text-left font_size_10">&nbsp; 
                            @php 
                        $invoice1 = str_replace('INV-',"",$item->invoice_number);
                        $array_bln = array(1=>"I","II","III", "IV", "V","VI","VII","VIII","IX","X", "XI","XII");
                        if($abc['company'] == 1){
                            $bln = $array_bln[date('n',strtotime($item->invoiced_at))];
                                echo $invoice1."/".$bln."/".date("Y",strtotime($item->invoiced_at));
                        }else{
                            $bln = $array_bln[date('n',strtotime($item->invoiced_at))];
                                echo $invoice1."/MBK/".$bln."/".date("Y",strtotime($item->invoiced_at));
                        }
                            @endphp </div>
                    </div>
                    <div class="col-xs-12">
                        <div class="col-xs-5 text-left no_padding_left">&nbsp;</div>
                        <div class="col-xs-7 text-left font_size_10">&nbsp;  @php echo date('j',strtotime($item->invoiced_at))." ".$bln_txt[date('n',strtotime($item->invoiced_at))]." ".date('Y',strtotime($item->invoiced_at)); @endphp </div>
                    </div>
                    
                    <div class="col-xs-12">
                        <div class="col-xs-5 text-left no_padding_left">&nbsp;</div>
                        <div class="col-xs-7 text-left font_size_9">&nbsp; 
                            @php
                                if( $abc['company'] == 1 || ($abc['company'] == 2 && ($item->no_faktur_pajak != '' && $item->no_faktur_pajak != '-' && $item->no_faktur_pajak != null)) ){
                                    if($item->no_faktur_pajak != '' && (int)(date('Y',strtotime($item->invoiced_at)) < 2025)){
                                        //echo "040.002-20.".$item->no_faktur_pajak;
                                        $exp_faktur = explode(".",$item->no_faktur_pajak);
                                        if (count($exp_faktur) >= 3) {
                                            $faktur1 = substr($exp_faktur[2],0,4);
                                            $faktur2 = substr($exp_faktur[2],4,4);
                                            echo $exp_faktur[0].".".$exp_faktur[1].".".$faktur1." ".$faktur2;
                                        } else {
                                            echo $item->no_faktur_pajak;
                                        }
                                    }else{
                                        echo $item->no_faktur_pajak;
                                    }
                                }else{
                                    echo "&nbsp"; 
                                } 
                            @endphp
                        </div>
                    </div>
                   
                    <div class="col-xs-12">
                        <div class="col-xs-5 text-left no_padding_left" style="padding-right: 0px;">&nbsp;</div>
                        <div class="col-xs-7 text-left font_size_10">&nbsp;  @php //echo date('j',strtotime($item->due_at))." ".$bln_txt[date('n',strtotime($item->due_at))]." ".date('Y',strtotime($item->due_at)); @endphp
                        </div>
                    </div>
                    <div class="col-xs-12">
                        <div class="col-xs-5 text-left no_padding_left" style="padding-right: 0px;">&nbsp;</div>
                        <div class="col-xs-7 text-left font_size_10">&nbsp;
                        @php
                            echo $item->r_invoice_text;
                        @endphp
                        </div>
                    </div>
                    
                </div>
            </div>
            @php
                //}
                $i++;
            @endphp
            @endforeach
            
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12">
            <div class="panel" style="margin-bottom: 0px;">
                <div class="panel-heading">
                    <h5 class="panel-title "><strong style="font-size: 13;">&nbsp;</strong></h5>
                </div>
                <div class="panel-body" style="padding-top: 0px; padding-bottom: 0px;">
                    <div class="table-responsive">
                        <table class="table ">
                            <thead>
                                <tr>
                                    <td width="40%" class="text-center"><strong style="font-size: 13;">&nbsp;</strong></td>
                                    <td width="20%" class="text-center"><strong style="font-size: 13;">&nbsp;</strong></td>
                                    <td width="5%" class="text-center"><strong style="font-size: 13;">&nbsp;</strong></td>
                                    <td width="10%" class="text-right"><strong style="font-size: 13;">&nbsp;</strong></td>
                                    <td width="10%" class="text-right"><strong style="font-size: 13;">&nbsp;</strong></td>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- foreach ($order->lineItems as $line) or some such thing here --> 
                                @php
                                    $j = 0;
                                    $jml_enter = 0;
                                    $jml_kosong = 0;
                                @endphp  
                                @foreach($data_item_bagi[$cc] as $item)
                                @php
                                    //var_dump($item->cubication);
                                    if($item->tariff_per_volume != 0 || $item->tariff_per_volume != null){
                                        $itemPrice = ($item->tariff_per_volume != null || $item->tariff_per_volume != 0)?number_format(($item->tariff_per_volume),0,",","."):'&nbsp;';
                                    }else{
                                        if($item->cubication != 0 || $item->cubication != null){
                                            $num = $item->cubication;
                                            $itemPrice1 = $item->price/$num;
                                            $itemPrice = number_format(($itemPrice1),0,",",".");
                                        }else{
                                            $itemPrice = ($item->price != 0)?number_format(($item->price),0,",","."):'&nbsp;';
                                        }
                                    }
                                   
                                   
                                    $itemTotal = ($item->total != 0)?number_format($item->total,0,",","."):'&nbsp;';
                                    
                                    $nama_item_len = strlen($item->name);
                                    $nama_item = '';
                                    $enterName = '';
                                    $br_jns = '';
                                    if($nama_item_len > 40){
                                        $ceil_40 = 0;
                                        $ceil_total = ceil($nama_item_len/40);
                                        for($i=0; $i < $ceil_total; $i++){
                                            if($i == ($ceil_total-1)){
                                                $nama_item .= substr($item->name,$ceil_40,40);
                                            }else{
                                                $nama_item .= substr($item->name,$ceil_40,40)."</br>";
                                            }
                                            $ceil_40 = $ceil_40 + 40;
                                            $br_jns .= "</br>";
                                        }
                                        $jml_enter = $jml_enter + $ceil_total;
                                    }else{
                                        $jml_enter++;
                                        $nama_item = $item->name;
                                        $br_jns .= "</br>";
                                    }

                                    $br_m3 = '';

                                    $margin_top = '';
                                    if(isset($item->cubication)){
                                        if(($item->cubication != 0 || $item->cubication != null) || ($item->total_volume != 0 || $item->total_volume != null)){
                                            if(($item->cubication != 0 || $item->cubication != null) && $item->total_volume != 0){
                                                $br_m3 = "</br>";
                                                $margin_top = 'margin-top: 7px!important; margin-bottom: 10px!important;';
                                            }
                                        }
                                    }

                                    if($nama_item != '-'){
                                        $arr_jenis[] = '<small style="font-size: 12;">--'.$nama_item.$br_m3."&nbsp;</small>";
                                    }else{
                                        $jml_kosong++;
                                    }
                                                                                                            
                                    if($item->name != '-'){
                                        $arr_jumlah[] = '<small style="font-size: 12;">'.number_format($item->quantity,0,",",".")." ".$item->satuan.$enterName.$br_m3.$br_jns."&nbsp;</small>";
                                    }

                                    if(isset($item->cubication)){
                                        if(($item->cubication != 0 || $item->cubication != null) || ($item->total_volume != 0 || $item->total_volume != null)){
                                            $br_m4 = $br_m3;
                                            if(($item->cubication != 0 || $item->cubication != null) && $item->total_volume != 0){
                                                $num = $item->total_volume;
                                                $num_ = 'TON';
                                                $myvalue =  sprintf("%.3f", $num);
                                                $num_text_ = $myvalue." ".$num_;

                                                $num_ = $item->cubication;
                                                $num_m = 'M3';
                                                $myvalue_m =  sprintf("%.3f", $num_);
                                                $num_text_m = $myvalue_m." ".$num_m;

                                                $num_text = $num_text_."</br>&nbsp;".$num_text_m;
                                                $jml_enter++;
                                                $br_m4 = '';
                                            }elseif($item->total_volume != 0){
                                                $num = $item->total_volume;
                                                $num_ = 'TON';
                                                $myvalue =  sprintf("%.3f", $num);
                                                $num_text = $myvalue." ".$num_;
                                            }else{
                                                $num = $item->cubication;
                                                $num_ = 'M3';
                                                $myvalue =  sprintf("%.3f", $num);
                                                $num_text = $myvalue." ".$num_;
                                            }
                                            
                                            
                                            $arr_cubication[] = '<small style="font-size: 12; margin-right: 0px;">&nbsp;'.$num_text.$enterName.$br_m4.$br_jns."</small>";
                                        }else{
                                            $arr_cubication[] = '<small style="font-size: 12; margin-right: 0px;">&nbsp;'.$enterName.$br_m3.$br_jns.'</small>'; 
                                        }
                                    }else{
                                        $arr_cubication[] = '&nbsp;';
                                    }


                                    $arr_tarif_kirim[] = '<small style="font-size: 12;  '.$margin_top.'">'.$itemPrice.$enterName.$br_m3.$br_jns."&nbsp;</small>";
                                    
                                    $arr_jumlah_rp[] = '<small style="font-size: 12; '.$margin_top.'">'.$itemTotal.$enterName.$br_m3.$br_jns."&nbsp;</small>"; 

                                    $start_date_pajak = strtotime('2022-04-01');
                                    $end_date_pajak = strtotime($invoiced_at);
                                    $year_invoiced = date('Y',$end_date_pajak);
                                    $persenPajak = 0.011;
                                    $persenPajakBagi = 1.011;
                                    if(($end_date_pajak >= strtotime('2025-01-01')) && ($end_date_pajak <= strtotime('2025-02-03'))){
                                        //$persenPajak = 0.012;
                                        //$persenPajakBagi = 1.012;
                                    }

                                    $subTotal = $subTotal + $item->total;
                                    $dpp = $dpp + ($item->total * 0.1);
                                    $ppn = $ppn + (($start_date_pajak <= $end_date_pajak)?($item->total * $persenPajak):($item->total * 0.01));
        

                                    if(count($data_item_bagi[$cc]) == ($j+1)){
                                        $enter = '';
                                        if(count($data_item_bagi[$cc]) <= 15){
                                            $tu = 15 - ($jml_enter) - $jml_kosong;
                                            for ($zz = 1; $zz <= $tu; $zz++) {
                                                $arr_jenis[]= '<small style="font-size: 12;">&nbsp;</small>';
                                            }
                                        }
                                    }
                                    
                                    $j++;
                                @endphp
                                @endforeach 
                                <tr>
                                 
                                    @stack('actions_td_start')
                                    @stack('actions_td_end')
                                    @stack('name_td_start')
                                    <td class="item" style="border-top: 1px solid #fff;">
                                       @php
                                        echo implode("</br>",$arr_jenis);
                                       @endphp
                                    </td>
                                    @stack('name_td_end')
                                    @stack('quantity_td_start')
                                    <td class="quantity text-center" style="border-top: 1px solid #fff;">
                                        @php
                                            echo implode("",$arr_jumlah);
                                        @endphp
                                    </td>
                                    @stack('quantity_td_end')
                                    @stack('cubication_td_start')
                                    <td style="border-top: 1px solid #fff;padding-right: 0px;" class="text-left">
                                         @php
                                            echo implode("",$arr_cubication);
                                        @endphp
                                    </td>
                                    @stack('cubication_td_end')
                                    @stack('price_td_start')
                                    <td class="style-price price text-right" style="border-top: 1px solid #fff;padding-left: 0px;">
                                        @php
                                            echo implode("",$arr_tarif_kirim);
                                        @endphp
                                    </td>
                                    @stack('price_td_end')
                                    @stack('taxes_td_start')
                                    @stack('taxes_td_end')
                                    @stack('total_td_start')
                                    <td class="style-price total text-right" style="border-top: 1px solid #fff;">
                                        @php
                                            echo implode("",$arr_jumlah_rp);
                                        @endphp
                                    </td>
                                    @stack('total_td_end')
                                @php
                                    
                                @endphp
                               
                                </tr>  
                                 
                                <tr>
                                    <td colspan="4" class="thick-line text-right" style="border-top: 3px solid white; padding-top: 10px;">
                                       <div class="col-xs-12">
                                            <div class="col-xs-7 text-left no_padding_left" style="margin-bottom: 0px;">
                                              @if($abc['company'] == 1)
                                                <p style="text-align: left; font-size: 13; padding-top: 10px" >
                                                    Bank Account</br>
                                                    A/N PT. Yudha Antar Nusa</br>
                                                    BCA Capem Jembatan Dua - Jakarta &nbsp;&nbsp;&nbsp;&nbsp;: 074-301-3988</br>
                                                    Bank Mandiri KCU Jakarta Pluit Selatan : 168-00-6672727-0
                                                </p>
                                              @else
                                                <p style="text-align: left; font-size: 13; margin-bottom: 10px;" >
                                                    Bank Account</br>
                                                    A/N PT. Mitra Bahari Khatulistiwa</br>
                                                    Bank Mandiri Cab. Pluit Kencana - Jakarta : 168-00-6007778-9</br>
                                                    BCA KCP CBD Pluit : 806-066-3999
                                                </p>
                                              @endif
                                            </div>
                                            <div class="col-xs-4 text-left no_padding_left" style="margin-bottom: 0px;">
                                                <div class="text-center">
                                                    @if($cc == count($data_item_bagi)-1)
                                                      <img src="https://quickchart.io/qr?text=<?=$invoiced_text;?>|<?=date('Y-m-d',strtotime($invoiced_at));?>|<?=$connote;?>" class="qr-code img-thumbnail img-responsive" style="width: 84px;" />
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="col-xs-1 text-left font_size_10" style="padding:0px;width: 1%;"><p>
                                                            <!-- "if($abc['company'] == 1)" -->
                                                            @if($isTax == 1)
                                                                <strong style="font-size: 12;">&nbsp;</strong> <br>
                                                            
                                                                <strong style="font-size: 12;">&nbsp;</strong> <br>
                                                                <strong style="font-size: 12;">&nbsp;</strong> <br>
                                                            @endif
                                                            </p>
                                            </div>
                                           
                                        </div>    
                                    </td>
                                    @php
                                    //if($abc['company'] == 1){
                                    if($isTax == 1){
                                        echo '<td class="thick-line text-right" style="border-top: 3px solid white; padding-top: 20px;">';
                                    }else{
                                        echo '<td class="thick-line text-right" style="border-top: 3px solid white; padding-top: 10px;">';
                                    }
                                    @endphp
                                    
                                        <p style="font-size: 12;">
                                        @if($isTax == 1)
                                        @php
                                            if($cc == count($data_item_bagi)-1){
                                               //if($subTotal < 1){
                                                    $subTotal = ($start_date_pajak <= $end_date_pajak)?($amount_inv/$persenPajakBagi):($amount_inv/1.01);
                                                //}

                                                
                                                $dpp = ($start_date_pajak <= $end_date_pajak)? $subTotal : $subTotal * 0.1;
                                                $ppn = ($start_date_pajak <= $end_date_pajak)?($subTotal * $persenPajak):($subTotal * 0.01);
                                                echo number_format($subTotal,0,",",".")."<br>";
                                        @endphp
                                        
                                        @php
                                                echo number_format($dpp,0,",",".")."<br>";
                                        @endphp
                                        @php
                                                echo number_format($ppn,0,",",".")."<br>";
                                            }else{
                                                echo "&nbsp;<br>&nbsp;<br>&nbsp;<br>";
                                            }
                                        @endphp
                                        @endif
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="no-line text-right"><strong style="font-size: 12;">&nbsp;</strong></td>
                                    <td class="no-line text-right"> 
                                        @php
                                            //if($abc['company'] == 1){
                                            if($isTax == 1){
                                                echo '<p style="font-size: 12;">';
                                            }else{
                                                echo '<p style="font-size: 12; padding-top: 10px;">';
                                            }
                                        @endphp
                                        
                                        @php
                                        if($cc == count($data_item_bagi)-1){
                                            if($isTax == 1){
                                                if(!($isFCL) && $subTotal < 1){
                                                    $subTotal = $amount_inv;
                                                    echo number_format(($subTotal),0,",",".");
                                                }else{
                                                    echo number_format(($subTotal+$ppn),0,",",".");
                                                }
                                            }else{
                                                if(!($isFCL) && $subTotal < 1){
                                                    $subTotal = $amount_inv;
                                                }
                                                echo number_format(($subTotal),0,",",".");
                                            }
                                        }else{
                                            echo "&nbsp;";
                                        } 
                                        @endphp
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
                @php
                if($cc == count($data_item_bagi)-1){
                    if($isTax == 1){
                        echo terbilang(round(($subTotal+$ppn)))." rupiah";
                    }else{
                        echo terbilang(($subTotal))." rupiah";
                    }
                }else{
                    echo "&nbsp;";
                } 
                @endphp
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12" style="padding-top: 16px;">
            <div class="col-xs-9"> 
                <div class="col-xs-12">
                    @if($isTax == 1)
                    <p class="p_style" ><b style="padding-left: 342px;"></b> @php
                                        if($cc == count($data_item_bagi)-1){
                                            echo number_format(($subTotal*0.02),0,",",".");
                                        }else{
                                            echo "&nbsp;";
                                        } 
                                        @endphp</p>
                    @endif
                </div>
            </div> 
            <div class="col-xs-3" style="height: 10px;">
               
                <p style="text-align: left;font-size: 12px;">Jakarta, <?php echo date('j',strtotime($invoiced_at))." ".$bln_txt[date('n',strtotime($invoiced_at))]." ".date('Y',strtotime($invoiced_at));?></p></br></br></br>
            </div> 
        </div>
    </div> 
    <div class="row">
        
            @php
            if($cc == (count($data_item_bagi)-1)){
                if(count($data_multi) == ($ii+1)){
                    echo '<div class="col-xs-12" style="padding-top: 10.03px;">&nbsp;';
                }else{
                    if($isTax == 1){
                        echo '<div class="col-xs-12" style="padding-top: 135.031px;">&nbsp;';
                    }else{
                        echo '<div class="col-xs-12" style="padding-top: 128.312px;">&nbsp;';
                    }
                }
                
            }else{
                if($isTax == 1){
                    echo '<div class="col-xs-12" style="padding-top: 129.031px;">&nbsp;';
                }else{
                    echo '<div class="col-xs-12" style="padding-top: 128.312px;">&nbsp;';
                }
            }
            @endphp
        </div>
    </div>
     
    @php
      }
    }
    @endphp
</div>

<script type="text/javascript">
   window.print();
</script>
