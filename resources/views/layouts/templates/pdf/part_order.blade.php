{{--<!doctype html>--}}
{{--<html lang="pt-br">--}}
{{--<head>--}}
{{--    <meta charset="utf-8">--}}
{{--    <title>Proposta {{ $order->order_number }}</title>--}}

{{--    <style>--}}
{{--        * { font-family: DejaVu Sans, Arial, sans-serif; }--}}

{{--        /* Se quiser manter retrato, remova "size: A4 landscape" */--}}
{{--        @page { margin: 18px 18px; size: A4 landscape; }--}}

{{--        body { font-size: 10.5px; color: #0f172a; }--}}
{{--        .muted { color: #64748b; }--}}

{{--        .h1 { font-size: 18px; font-weight: 700; margin: 0 0 4px; }--}}

{{--        .box { border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px; }--}}

{{--        /* Header sem flex (mais estável no Dompdf) */--}}
{{--        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }--}}
{{--        .header-table td { vertical-align: top; }--}}
{{--        .badge {--}}
{{--            display:inline-block; padding: 4px 8px; border-radius: 999px;--}}
{{--            background:#ffe4e6; color:#9f1239; font-weight:700; font-size:10px;--}}
{{--        }--}}

{{--        /* Card da tabela (border-radius funciona melhor no wrapper) */--}}
{{--        .table-card {--}}
{{--            border: 1px solid #e2e8f0;--}}
{{--            border-radius: 12px;--}}
{{--            overflow: hidden;--}}
{{--        }--}}

{{--        table { width: 100%; border-collapse: collapse; table-layout: fixed; }--}}
{{--        thead { display: table-header-group; }--}}
{{--        tfoot { display: table-footer-group; }--}}

{{--        th, td {--}}
{{--            border-bottom: 1px solid #e2e8f0;--}}
{{--            padding: 7px 6px;--}}
{{--            vertical-align: top;--}}
{{--        }--}}

{{--        th {--}}
{{--            background: #f8fafc;--}}
{{--            font-weight: 700;--}}
{{--            color: #334155;--}}
{{--            font-size: 10px;--}}
{{--            border-bottom: 1px solid #cbd5e1;--}}
{{--        }--}}

{{--        /* Zebra (leve) */--}}
{{--        tbody tr:nth-child(even) td { background: #fbfdff; }--}}

{{--        /* Evita quebrar linha em números */--}}
{{--        .num { text-align: right; white-space: nowrap; }--}}
{{--        .center { text-align: center; white-space: nowrap; }--}}
{{--        .desc { white-space: normal; word-break: break-word; }--}}

{{--        /* Evita “quebrar linha no meio” do item em troca de página */--}}
{{--        tr { page-break-inside: avoid; }--}}

{{--        /* Totais */--}}
{{--        .totals div { display:flex; justify-content: space-between; padding: 4px 0; }--}}
{{--        .totals .big {--}}
{{--            border-top: 1px solid #e2e8f0;--}}
{{--            margin-top: 6px; padding-top: 8px;--}}
{{--            font-weight: 800; font-size: 14px;--}}
{{--        }--}}
{{--    </style>--}}
{{--</head>--}}
{{--<body>--}}
{{--@php--}}
{{--    // NBSP entre "R$" e o número (não quebra linha)--}}
{{--    $money = fn($v) => 'R$' . "\u{00A0}" . number_format((float)($v ?? 0), 2, ',', '.');--}}
{{--    $date  = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('d/m/Y') : '—';--}}
{{--@endphp--}}

{{--<table class="header-table">--}}
{{--    <tr>--}}
{{--        <td>--}}
{{--            <div class="h1">Proposta • {{ $order->order_number ?? '—' }}</div>--}}
{{--            <div class="muted">Data: {{ $date($order->order_date) }} • CNPJ: {{ $order->billing_cnpj ?? '—' }}</div>--}}
{{--            <div class="muted">--}}
{{--                Fornecedor: {{ $order->supplier?->name ?? '—' }}--}}
{{--                @if($order->supplier?->email) • {{ $order->supplier->email }} @endif--}}
{{--            </div>--}}

{{--            @if(($order->status ?? '') === 'draft')--}}
{{--                <div style="margin-top:8px;"><span class="badge">Rascunho</span></div>--}}
{{--            @endif--}}
{{--        </td>--}}

{{--        <td style="text-align:right; width: 260px;">--}}
{{--            <div style="font-weight:700;">Cliqis</div>--}}
{{--            <div class="muted" style="font-size:11px;">Proposta gerada automaticamente</div>--}}
{{--        </td>--}}
{{--    </tr>--}}
{{--</table>--}}

{{--<div class="table-card">--}}
{{--    <table>--}}
{{--        <thead>--}}
{{--        <tr>--}}
{{--            <th style="width:85px;">Código</th>--}}
{{--            <th>Descrição</th>--}}
{{--            <th style="width:85px;">NCM</th>--}}
{{--            <th style="width:90px;" class="num">Valor item</th>--}}
{{--            <th style="width:55px;" class="num">IPI %</th>--}}
{{--            <th style="width:55px;" class="num">Qtd</th>--}}
{{--            <th style="width:95px;" class="num">Valor c/ IPI</th>--}}
{{--            <th style="width:60px;" class="num">Desc. %</th>--}}
{{--            <th style="width:95px;" class="num">Valor final</th>--}}
{{--        </tr>--}}
{{--        </thead>--}}

{{--        <tbody>--}}
{{--        @foreach(($order->items ?? []) as $it)--}}
{{--            @php--}}
{{--                $unit = (float)($it->unit_price ?? 0);--}}
{{--                $qty  = (float)($it->quantity ?? 0);--}}
{{--                $ipi  = (float)($it->ipi_rate ?? 0);--}}
{{--                $disc = (float)($it->discount_rate ?? 0);--}}

{{--                $base = $unit * $qty;--}}
{{--                $valIpi = $base * ($ipi / 100);--}}
{{--                $withIpi = $base + $valIpi;--}}
{{--                $valDesc = $withIpi * ($disc / 100);--}}
{{--                $final = $withIpi - $valDesc;--}}

{{--                $fmtPct = fn($v) => rtrim(rtrim(number_format((float)$v, 2, ',', '.'), '0'), ',') . '%';--}}
{{--                $fmtNum = fn($v) => rtrim(rtrim(number_format((float)$v, 2, ',', '.'), '0'), ',');--}}
{{--            @endphp--}}

{{--            <tr>--}}
{{--                <td class="center">{{ $it->code }}</td>--}}
{{--                <td class="desc">{{ $it->description }}</td>--}}
{{--                <td class="center">{{ $it->ncm }}</td>--}}
{{--                <td class="num">{{ $money($unit) }}</td>--}}
{{--                <td class="num">{{ $fmtPct($ipi) }}</td>--}}
{{--                <td class="num">{{ $fmtNum($qty) }}</td>--}}
{{--                <td class="num">{{ $money($withIpi) }}</td>--}}
{{--                <td class="num">{{ $fmtPct($disc) }}</td>--}}
{{--                <td class="num">{{ $money($final) }}</td>--}}
{{--            </tr>--}}
{{--        @endforeach--}}
{{--        </tbody>--}}
{{--    </table>--}}
{{--</div>--}}

{{--<table style="width:100%; border-collapse:collapse; margin-top:12px;">--}}
{{--    <tr>--}}
{{--        <td style="width:55%; vertical-align:top;">--}}
{{--            <div class="box">--}}
{{--                <div style="font-weight:700; margin-bottom:6px;">Observações</div>--}}
{{--                <div class="muted">Prazo de entrega e condições serão definidos após confirmação do pedido.</div>--}}
{{--            </div>--}}
{{--        </td>--}}

{{--        <td style="width:45%; vertical-align:top; padding-left:12px;">--}}
{{--            <div class="box">--}}
{{--                @php--}}
{{--                    $rate = (float)($order->icms_rate ?? 0);--}}
{{--                    $uf = $order->billing_uf ?? '—';--}}
{{--                    $fmtPct2 = fn($v) => rtrim(rtrim(number_format((float)$v, 2, ',', '.'), '0'), ',') . '%';--}}
{{--                @endphp--}}

{{--                <div class="totals">--}}
{{--                    <div><span>Subtotal:</span><span style="font-weight:700">{{ $money($order->subtotal) }}</span></div>--}}
{{--                    <div><span>IPI:</span><span style="font-weight:700">{{ $money($order->ipi_total) }}</span></div>--}}
{{--                    <div>--}}
{{--                        <span>ICMS ({{ $uf }} — {{ $fmtPct2($rate) }}):</span>--}}
{{--                        <span style="font-weight:700">{{ $money($order->icms_total) }}</span>--}}
{{--                    </div>--}}
{{--                    <div><span>Descontos:</span><span style="font-weight:700; color:#047857">- {{ $money($order->discount_total) }}</span></div>--}}
{{--                    <div class="big"><span>Total:</span><span>{{ $money($order->grand_total) }}</span></div>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </td>--}}
{{--    </tr>--}}
{{--</table>--}}

{{--</body>--}}
{{--</html>--}}

    <!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Proposta {{ $order->order_number }}</title>

    <style>
        * { font-family: DejaVu Sans, Arial, sans-serif; }

        /* Retrato + mais área útil */
        @page { margin: 10mm 8mm; size: A4 portrait; }

        body { font-size: 8.2px; color:#0f172a; }

        .muted { color:#64748b; }
        .h1 { font-size: 14px; font-weight: 700; margin: 0 0 2px; }

        /* Caixas (observações/totais) */
        .box { border: 1px solid #cbd5e1; padding: 8px; }
        .box-title { font-weight:700; margin-bottom:5px; }

        /* Header sem flex (mais estável no Dompdf) */
        .header-table { width:100%; border-collapse: collapse; margin-bottom: 8px; }
        .header-table td { vertical-align: top; }

        .badge {
            display:inline-block;
            padding: 3px 6px;
            border-radius: 999px;
            background:#fee2e2;
            color:#991b1b;
            font-weight:700;
            font-size:7.8px;
        }

        /* TABELA estilo planilha */
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        thead { display: table-header-group; }

        th, td{
            border: 1px solid #cbd5e1;
            padding: 2.2px 3px;
            vertical-align: middle;
            line-height: 1.15;
        }

        th{
            background: #0f172a;   /* header escuro */
            color: #ffffff;
            font-weight: 700;
            font-size: 8px;
        }

        tbody td{ background:#fff; }
        tbody tr:nth-child(even) td { background:#f8fafc; }

        .center { text-align:center; white-space:nowrap; }
        .num    { text-align:right;  white-space:nowrap; }
        .desc   { white-space: normal; word-break: break-word; }

        /* Evita quebrar uma linha em duas páginas */
        tr { page-break-inside: avoid; }

        /* Larguras travadas em mm (pra caber em A4 retrato) */
        .w-code { width: 16mm; }
        .w-desc { width: 66mm; }
        .w-ncm  { width: 18mm; }
        .w-unit { width: 20mm; }
        .w-ipi  { width: 10mm; }
        .w-qty  { width: 14mm; }
        .w-pre  { width: 20mm; }
        .w-dis  { width: 12mm; }
        .w-fin  { width: 20mm; }

        /* Totais */
        .totals { width:100%; border-collapse: collapse; }
        .totals td { border: 0; padding: 2px 0; }
        .totals .label { color:#334155; }
        .totals .value { text-align:right; font-weight:700; white-space:nowrap; }
        .totals .big td { padding-top: 6px; border-top: 1px solid #cbd5e1; font-size: 10.5px; font-weight: 800; }
        .neg { color:#047857; }
    </style>
</head>

<body>
@php
    // NBSP entre "R$" e número (não quebra)
    $money = fn($v) => 'R$' . "\u{00A0}" . number_format((float)($v ?? 0), 2, ',', '.');
    $date  = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('d/m/Y') : '—';

    $fmtPct = fn($v) => rtrim(rtrim(number_format((float)($v ?? 0), 2, ',', '.'), '0'), ',') . '%';
    $fmtNum = fn($v) => rtrim(rtrim(number_format((float)($v ?? 0), 2, ',', '.'), '0'), ',');
@endphp

    <!-- HEADER -->
<table class="header-table">
    <tr>
        <td>
            <div class="h1">Proposta • {{ $order->order_number ?? '—' }}</div>
            <div class="muted">
                Data: {{ $date($order->order_date) }}
                &nbsp;•&nbsp; CNPJ: {{ $order->billing_cnpj ?? '—' }}
            </div>
            <div class="muted">
                Fornecedor: {{ $order->supplier?->name ?? '—' }}
                @if($order->supplier?->email) &nbsp;•&nbsp; {{ $order->supplier->email }} @endif
            </div>

            @if(($order->status ?? '') === 'draft')
                <div style="margin-top:6px;"><span class="badge">Rascunho</span></div>
            @endif
        </td>

        <td style="text-align:right; width: 260px;">
            <div style="font-weight:700;">Cliqis</div>
            <div class="muted" style="font-size:8px;">Proposta gerada automaticamente</div>
        </td>
    </tr>
</table>

<!-- TABELA -->
<table>
    <thead>
    <tr>
        <th class="w-code center">Código</th>
        <th class="w-desc">Descrição</th>
        <th class="w-ncm center">NCM</th>
        <th class="w-unit num">Valor item</th>
        <th class="w-ipi num">IPI %</th>
        <th class="w-qty num">Qtd</th>
        <th class="w-pre num">Valor c/ IPI</th>
        <th class="w-dis num">Desc. %</th>
        <th class="w-fin num">Valor final</th>
    </tr>
    </thead>

    <tbody>
    @foreach(($order->items ?? []) as $it)
        @php
            $unit = (float)($it->unit_price ?? 0);
            $qty  = (float)($it->quantity ?? 0);
            $ipi  = (float)($it->ipi_rate ?? 0);
            $disc = (float)($it->discount_rate ?? 0);

            $base    = $unit * $qty;
            $valIpi  = $base * ($ipi / 100);
            $withIpi = $base + $valIpi;

            $valDesc = $withIpi * ($disc / 100);
            $final   = $withIpi - $valDesc;
        @endphp

        <tr>
            <td class="center">{{ $it->code }}</td>
            <td class="desc">{{ $it->description }}</td>
            <td class="center">{{ $it->ncm }}</td>
            <td class="num">{{ $money($unit) }}</td>
            <td class="num">{{ $fmtPct($ipi) }}</td>
            <td class="num">{{ $fmtNum($qty) }}</td>
            <td class="num">{{ $money($withIpi) }}</td>
            <td class="num">{{ $fmtPct($disc) }}</td>
            <td class="num">{{ $money($final) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<!-- OBS + TOTAIS -->
<table style="width:100%; border-collapse:collapse; margin-top: 8px;">
    <tr>
        <td style="width:55%; vertical-align:top;">
            <div class="box">
                <div class="box-title">Observações</div>
                <div class="muted">
                    Prazo de entrega e condições serão definidos após confirmação do pedido.
                </div>
            </div>
        </td>

        <td style="width:45%; vertical-align:top; padding-left: 8px;">
            <div class="box">
                @php
                    $rate = (float)($order->icms_rate ?? 0);
                    $uf   = $order->billing_uf ?? '—';
                @endphp

                <table class="totals">
                    <tr>
                        <td class="label">Subtotal:</td>
                        <td class="value">{{ $money($order->subtotal) }}</td>
                    </tr>
                    <tr>
                        <td class="label">IPI:</td>
                        <td class="value">{{ $money($order->ipi_total) }}</td>
                    </tr>
                    <tr>
                        <td class="label">ICMS ({{ $uf }} — {{ $fmtPct($rate) }}):</td>
                        <td class="value">{{ $money($order->icms_total) }}</td>
                    </tr>
                    <tr>
                        <td class="label">Descontos:</td>
                        <td class="value neg">- {{ $money($order->discount_total) }}</td>
                    </tr>
                    <tr class="big">
                        <td>Total:</td>
                        <td style="text-align:right;">{{ $money($order->grand_total) }}</td>
                    </tr>
                </table>
            </div>
        </td>
    </tr>
</table>

</body>
</html>

