<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <title>Orçamento para</title>

    <link rel="stylesheet" href="assets/font/Poppins-Regular.ttf">

    <style> * { font-family: "Poppins", sans-serif; padding: 0; margin: 0; } .col { width: calc(8.33% * 1); } .col-2 { width: calc(8.33% * 2); } .col-3 { width: calc(8.33% * 3); } .col-4 { width: calc(8.33% * 4); } .col-5 { width: calc(8.33% * 5); } .col-6 { width: calc(8.33% * 6); } .col-7 { width: calc(8.33% * 7); } .col-8 { width: calc(8.33% * 8); } .col-9 { width: calc(8.33% * 9); } .col-10 { width: calc(8.33% * 10); } .col-11 { width: calc(8.33% * 11); } .col-12 { width: 100%; } .text-left { text-align: left; } .text-right { text-align: right; } .text-center { text-align: center; } body { margin: 25px 50px; } ul { list-style: none; margin: 0; } table { width: 100%; border-collapse: collapse; margin: 0; padding: 0; } table td { letter-spacing: 1.25px; } table td b { font-size: 14px; } table td span { font-size: 12px; } header { padding-bottom: 15px; border-bottom: 1px solid #eaeaea; } header table tr img { width: 175px; height: 100px; } header table ul li b { letter-spacing: 1.25px; } header table ul li span { color: #878787 } /* Main */ main td ul li:nth-child(1) { margin-bottom: 5px; } main td ul li b { font-size: 12.5px; color: #3b3b3b; } main td ul li p { font-size: 11.5px; color: #7e7e7e; /*letter-spacing: 2px;*/ line-height: 1.5; } /* Card Info */ main .card-info { margin: 25px auto; } main .card-info table tr .deadline ul { display: inline-block; padding: 15px 30px; text-align: center; border: 1px solid #eaeaea; border-radius: 7.5px; } main .card-info .deadline ul li p { color: rgba(135, 135, 135, 1); } main .card-info .deadline ul li h4 { font-size: 28px; color: #2DA2B1; } /* Card Info 02 */ main .card-info-2 { margin: 25px auto; } main .card-info-2 table tr .total-price ul { display: inline-block; padding: 15px 30px; text-align: center; border: 1px solid #eaeaea; border-radius: 7.5px; } main .card-info-2 .total-price ul li p { color: rgba(135, 135, 135, 1); } main .card-info-2 .total-price ul li h4 { font-size: 24px; color: #2DA2B1; } /* Card Info 03 */ main .card-info-3 ul { padding: 25px; border: 1px solid #eaeaea; border-radius: 7.5px; } main .card-info-3 td:nth-child(1) { padding-right: 15px; } /* List Items */ main .list-items, main .payment-details { margin: 25px auto; border: 1px solid #eaeaea; border-radius: 7.5px; } main .list-items thead tr { background: #f6f6f6; } main .list-items thead th { color: #111111; padding: 10px 15px; letter-spacing: 1.25px; font-size: 11.5px; text-transform: uppercase; } main .list-items tbody tr { border-bottom: 1px solid #eaeaea; } main .list-items tbody tr:last-child { border-bottom: none; } main .list-items tbody tr td:nth-child(1) b { font-size: 11.5px; } main .list-items tbody td { padding: 10px 15px; } /* Payment Details */ main .payment-details { /*background: #f0faff;*/ } main .payment-details tr td { padding: 10px 15px; } /* Detail Items */ main .detail-items h4 { margin: 25px auto 17.5px auto; } main .detail-items .item ul { border: 1px solid #eaeaea; border-radius: 7.5px; padding: 7.5px 15px; margin: 7.5px 0; } main .item td ul li p { padding: 5px 25px; text-align: justify; } /* Observation */ main .observation ul { border: 1px solid #eaeaea; border-radius: 7.5px; padding: 7.5px 15px; } main .observation td ul li p { padding: 5px 25px; text-align: justify; } .payment-details .payment-summary { list-style: none; padding: 0; margin: 0; display: flex; justify-content: flex-end; gap: 30px; } .payment-details .summary th, .payment-details .summary td{ font-size:12px; line-height:1.25; padding:2px 0; letter-spacing:0; } .payment-details .summary th{ text-align:left; font-weight:600; } .payment-details .summary td{ text-align:right; white-space:nowrap; } </style>
</head>
<body>
<header>
    <table>
        <tr>
            <td class="col-6">
                <ul>
                    <li>
                        <b>Código de orçamento:</b>
                        <span>{{ "#$budget_number" }}</span>
                    </li>
                    <li>
                        <b>Data de criação:</b>
                        <span>{{ $create_date }}</span>
                    </li>
                </ul>
            </td>
            <td class="col-6" style="text-align:right">
                @if(!empty($logo))
                    <img src="{{ $logo }}" alt="Logo" style="width:auto;height:60px;object-fit:contain">
                @endif
            </td>
        </tr>
    </table>
</header>
<main>
    <!-- Card Info 01 -->
    <div class="card-info">
        <table>
            <tr>
                <td class="col-6 deadline">
                   @if(($deadline ?? 0) > 0)
                        <ul>
                            <li><p>Entrega em</p></li>
                            <li><h4>{{ $deadline }} Dias</h4></li>
                        </ul>
                    @endif
                </td>
                <td class="col-6 text-right">
                    <ul>
                        <li><b>{{ strtoupper($org['name'] ?? '') }}</b></li>
                        <li><p>{{ $org['document'] ?? null }}</p></li>
                        <li><p>
                                {{ ($org['city'] ?? '') }}
                                @if(!empty($org['city'] ?? null) && !empty($org['state']))
                                    /
                                @endif
                                {{ $org['state'] ?? '' }}
                            </p></li>
                        <li><p>{{ $org['email'] ?? null }}</p></li>
                    </ul>
                </td>
            </tr>
        </table>
    </div>

    <!-- Card Info 02 -->
    <div class="card-info-2">
        <table>
            <tr>
                <td class="col-4" style="width: 40%;">
                    <ul>
                        <li><b>CLIENTE</b></li>
                        <li style="width: 85%;"><b>{{ $customer }}</b></li>
                        <li><p>
                                @if(!empty($cnpj) && !in_array($cnpj, ['00.000.000/0000-00','000.000.000-00','000.000.000/00']))
                                    {{ $cnpj }}
                                @endif
                            </p></li>
                        <li><p>{{ trim(($state ?? '').(($uf ?? '') ? ', '.$uf : '')) }}</p></li>
                        @if(!empty($phone))
                            <li><p>{{ $phone }}</p></li>
                        @endif
                    </ul>
                </td>

                <td class="col-4">
                    <ul>
                        @if(!empty($representative['name']) || !empty($representative['email']) || !empty($representative['phone']) || !empty($representative['city']) || !empty($representative['state']))
                            <li><b>REPRESENTANTE</b></li>
                        @endif
                        @if(!empty($representative['name']))
                        <li><b>{{ ucwords($representative['name']) ?? '' }}</b></li>
                            @endif
                        @if(!empty($representative['email']))
                                <li><p>{{ $representative['email'] }}</p></li>
                            @endif
                        <li><p>
                                {{ ($representative['city'] ?? '') }}
                                @if(!empty($representative['city'] ?? null) && !empty($representative['state']))
                                    /
                                @endif
                                {{ $representative['state'] ?? '' }}
                            </p></li>
                        @if(!empty($representative['phone']))
                            <li><p>{{ $representative['phone'] }}</p></li>
                        @endif
                    </ul>
                </td>

                <td class="col-4 total-price text-right">
                    <ul>
                        <li><p>TOTAL ORÇAMENTO</p></li>
                        <li><h4>{{ brlPrice($totals['grand'] ?? 0) }}</h4></li>
                    </ul>
                </td>
            </tr>
        </table>
    </div>

    <!-- Card Info 03 -->
    <div class="card-info-3">
        <table>
            <tr>
                @if($services_txt)
                    <td class="col-6">
                        <ul>
                            <li><b>NOSSOS SERVIÇOS</b></li>
                            <li>
                                <p>{!! $services_txt !!}</p>
                            </li>
                        </ul>
                    </td>
                @endif

                @if($payment_txt)
                    <td class="col-6">
                        <ul>
                            <li><b>PAGAMENTO</b></li>
                            <li>
                                <p>{!! $payment_txt !!}</p>
                            </li>
                        </ul>
                    </td>
                @endif
            </tr>
        </table>
    </div>

    <!-- List Items -->
    <div class="list-items">
        <table>
            <thead>
            <tr>
                <th class="text-left">Serviço</th>
                <th>Valor</th>
                <th>Recorrência</th>
                <th>Desconto</th>
                <th>Total</th>
            </tr>
            </thead>
            <tbody>
            @foreach($items as $item)
                @php
                    $valor = $item->price ?: $item->item_price;
                    $total = $item->price_with_discount ?: $valor;
                    $type  = $item->type ?? ($item->service->type ?? 'payment_unique');
                @endphp

                <tr>
                    <td style="width:30%;"><b>{{ ucwords($item->service->name) }}</b></td>
                    <td class="text-center" style="width:20%"><span>{{ brlPrice($valor) }}</span></td>

                    <td class="text-center" style="width:20%">
                        @if($type === 'payment_unique')
                            <span style="color:#cecece;">ÚNICA</span>
                        @elseif($type === 'monthly')
                            <span style="color:#cecece;">MENSAL</span>
                        @else
                            <span style="color:#cecece;">ANUAL</span>
                        @endif
                    </td>
                    <td class="text-center" style="width:10%"><span>{{ $item->discount_price }}%</span></td>
                    <td class="text-center" style="width:20%"><span>{{ brlPrice($total) }}</span></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="payment-details" style="page-break-after: always;">
        <table>
            <tr>
                <td class="col-6">
                    <ul>
                        <li><b>CONDIÇÕES DE PAGAMENTO</b></li>
                        <li>
                            @if(($percent_signal ?? 0) > 0)
                                <b>Sinal ({{ (int)$percent_signal }}%):</b>
                                <span>{{ brlPrice($signal_price ?? 0) }}</span>
                            @else
                                <b>Sem sinal de entrada</b>
                            @endif
                        </li>
                        <li>
                            @if(($percent_signal ?? 0) > 0)
                                <b>*</b> <span>Não será preciso o pagamento de sinal de entrada!</span>
                            @else
                                <b>*</b> <span>O projeto será iniciado após o pagamento do sinal.</span>
                            @endif
                        </li>
                        <li style="margin:7.5px 0;border-bottom:1px solid #eaeaea;width:100px;"></li>

                        @if(($remaining_price ?? 0) > 0)
                            <li>
                                <b>Na entrega ({{ (int)($percent_remaining ?? 0) }}%):</b>
                                <span>{{ brlPrice($remaining_price ?? 0) }}</span>
                            </li>
                        @endif

                        <li>
                            <b>* </b>
                            @if(($percent_signal ?? 0) > 0)
                                <span>O valor deve ser pago na entrega do serviço.</span>
                            @else
                                <span>O restante do valor deve ser pago na entrega do projeto.</span>
                            @endif
                            <br>
                            <span>Para serviços com pagamento recorrente o dia será escolhido pelo cliente.</span>
                        </li>
                    </ul>
                </td>

                <td class="col-6">
                    @php
                        $discountSum = $items->sum(function($i){
                            $base = $i->price ?: $i->item_price;
                            return ($i->discount_price ?? 0) > 0 ? $base * (($i->discount_price)/100) : 0;
                        });
                        $subItems   = $items->sum(fn($i)=> $i->price ?: $i->item_price);
                        $totalItems = $items->sum('price_with_discount');
                    @endphp

                    <table class="summary">
                        <tr>
                            <th>SUBTOTAL:</th>
                            <td>{{ brlPrice($subItems) }}</td>
                        </tr>
                        @if($discountSum > 0)
                            <tr>
                                <th>DESCONTO:</th>
                                <td style="text-decoration:line-through;">{{ brlPrice($discountSum) }}</td>
                            </tr>
                        @endif
                        <tr>
                            <th>TOTAL:</th>
                            <td>{{ brlPrice($totalItems) }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    {{-- <!-- Cronograma de pagamento (projeções + parcelas) -->--}} {{-- @if(!empty($schedule) && count($schedule))--}} {{-- <div class="list-items">--}} {{-- <table>--}} {{-- <thead>--}} {{-- <tr>--}} {{-- <th class="text-left">Data</th>--}} {{-- <th class="text-center">Descrição</th>--}} {{-- <th class="text-center">Valor</th>--}} {{-- </tr>--}} {{-- </thead>--}} {{-- <tbody>--}} {{-- @foreach($schedule as $row)--}} {{-- <tr>--}} {{-- <td style="width: 30%;"><b>{{ $row['date'] }}</b></td>--}} {{-- <td class="text-center" style="width:20%"><span>{{ $row['label'] }}</span></td>--}} {{-- <td class="text-center" style="width:20%"><span>{{ brlPrice($row['amount']) }}</span></td>--}} {{-- </tr>--}} {{-- @endforeach--}} {{-- </tbody>--}} {{-- </table>--}} {{-- </div>--}} {{-- @endif--}}

    <div class="detail-items">
        <h4 class="text-center">DESCRIÇÃO DOS SERVIÇOS PRESTADOS</h4>

        @foreach($items as $item)
            <div class="item">
                <table>
                    <tr>
                        <td>
                            <ul>
                                <li><b>{{ $item->service->name }}</b></li>
                                <li><p>{!! $item->service->description !!}</p></li>
                            </ul>
                        </td>
                    </tr>
                </table>
            </div>
        @endforeach
    </div>
</main>
</body>
</html>
