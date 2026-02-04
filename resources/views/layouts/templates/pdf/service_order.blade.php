<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>OS {{ $os->order_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #0f172a; }
        .muted { color: #64748b; }
        .box { border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px; margin-bottom: 12px; }
        .row { width: 100%; }
        .col { vertical-align: top; }
        h1 { font-size: 18px; margin: 0 0 6px 0; }
        h2 { font-size: 13px; margin: 0 0 8px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; border-bottom: 1px solid #e2e8f0; }
        th { background: #f1f5f9; text-align: left; font-size: 11px; }
        .right { text-align: right; }
        .badge { display:inline-block; padding: 4px 10px; border-radius: 999px; font-size: 10px; background:#e2e8f0; }

        /* ===== HEADER GRADIENT (PDF) ===== */
        .hero{
            border-radius: 18px;
            padding: 16px 18px;
            margin-bottom: 16px;
            color:#fff;

            /* gradiente via SVG (funciona no dompdf) */
            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='1200' height='220' viewBox='0 0 1200 220' preserveAspectRatio='none'><defs><linearGradient id='g' x1='0' y1='0' x2='1' y2='1'><stop offset='0%25' stop-color='%2338bdf8'/><stop offset='55%25' stop-color='%232563eb'/><stop offset='100%25' stop-color='%233730a3'/></linearGradient></defs><rect width='1200' height='220' fill='url(%23g)'/></svg>");
            background-size: 100% 100%;
            background-repeat: no-repeat;
            background-color:#2563eb; /* fallback */
        }

        .hero-top {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 999px;
            background: rgba(255,255,255,.16);
            font-size: 10px;
            letter-spacing: .3px;
        }

        .hero-title {
            font-size: 20px;
            font-weight: 700;
            margin: 10px 0 2px 0;
        }
        .hero-sub {
            font-size: 12px;
            color: rgba(255,255,255,.85);
            margin: 0;
        }
        .hero-status {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            background: rgba(255,255,255,.20);
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .totals-grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 10px;
        }

        .totals-grid td {
            border: none !important;
        }

        .total-card {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 12px 14px;
            background: #f8fafc;
        }

        .total-card .label {
            font-size: 11px;
            color: #64748b;
            margin-bottom: 4px;
        }

        .total-card .value {
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
        }

        /* destaque total geral */
        .total-card-dark {
            background: #0f172a;
            color: #ffffff;
            border-radius: 16px;
            padding: 14px 16px;
        }

        .total-card-dark .label {
            font-size: 11px;
            color: #c7d2fe;
            margin-bottom: 4px;
        }

        .total-card-dark .value {
            font-size: 20px;
            font-weight: 800;
            color: #ffffff;
        }
    </style>
</head>
<body>

@php
    $clienteNome = optional($os->secondaryCustomer)->name ?? $os->client_name ?? '-';
    $clienteDoc  = optional($os->secondaryCustomer)->cpfCnpj ?? $os->cpfCnpj ?? '-';
    $clienteEmail = optional($os->secondaryCustomer)->email ?? $os->requester_email ?? '-';

    $dt = $os->order_date ?? $os->created_at;
    $dtFmt = $dt ? \Carbon\Carbon::parse($dt)->format('d/m/Y - H:i') : '-';

    $status = $os->status_label ?? $os->status ?? 'rascunho';
@endphp

<div class="hero">
    <table class="row">
        <tr>
            <td class="col" style="width:70%">
                <span class="hero-top">OS • #{{ $os->order_number }}</span>
                <div class="hero-title">Ordem de Serviço n° {{ $os->order_number }}</div>
                <p class="hero-sub">Data: {{ $dtFmt }}</p>
            </td>
            <td class="col right" style="width:30%; vertical-align: middle;">
                <span class="hero-status">{{ mb_strtoupper($status) }}</span>
            </td>
        </tr>
    </table>
</div>

<div class="box">
    <h2>Cliente</h2>
    <table>
        <tr>
            <td><strong>Nome:</strong> {{ $clienteNome }}</td>
            <td><strong>CPF/CNPJ:</strong> {{ $clienteDoc }}</td>
        </tr>
        <tr>
            <td><strong>E-mail:</strong> {{ $clienteEmail }}</td>
            <td><strong>Telefone:</strong> {{ $os->requester_phone ?? '-' }}</td>
        </tr>
        <tr>
            <td colspan="2">
                <strong>Endereço:</strong>
                {{ $os->address_line1 ?? '-' }}
                @if($os->address_line2) - {{ $os->address_line2 }} @endif
                @if($os->city) - {{ $os->city }} @endif
                @if($os->state) / {{ $os->state }} @endif
            </td>
        </tr>
    </table>
</div>

<div class="box">
    <h2>Equipamentos atendidos</h2>
    <table>
        <thead>
        <tr>
            <th>Equipamento</th>
            <th>Nº Série</th>
            <th>Localização</th>
            <th>Serviço executado</th>
        </tr>
        </thead>
        <tbody>
        @forelse($os->equipments as $e)
            <tr>
                <td>{{ optional($e->equipment)->name ?? $e->equipment_description ?? '-' }}</td>
                <td>{{ $e->serial_number ?? '-' }}</td>
                <td>{{ $e->location ?? '-' }}</td>
                <td>{{ $e->notes ?? '-' }}</td>
            </tr>
        @empty
            <tr><td colspan="4" class="muted">Nenhum equipamento informado.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="box">
    <h2>Serviços</h2>
    <table>
        <thead>
        <tr>
            <th>Descrição</th>
            <th class="right">Qtd</th>
            <th class="right">Valor</th>
            <th class="right">Total</th>
        </tr>
        </thead>
        <tbody>
        @forelse($os->serviceItems as $s)
            <tr>
                <td>{{ $s->description ?? optional($s->serviceItem)->name ?? '-' }}</td>
                <td class="right">{{ number_format((float)$s->quantity, 2, ',', '.') }}</td>
                <td class="right">R$ {{ number_format((float)$s->unit_price, 2, ',', '.') }}</td>
                <td class="right">R$ {{ number_format((float)$s->total, 2, ',', '.') }}</td>
            </tr>
        @empty
            <tr><td colspan="4" class="muted">Nenhum serviço informado.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="box">
    <h2>Peças</h2>
    <table>
        <thead>
        <tr>
            <th>Descrição</th>
            <th class="right">Qtd</th>
            <th class="right">Valor</th>
            <th class="right">Total</th>
        </tr>
        </thead>
        <tbody>
        @forelse($os->partItems as $p)
            <tr>
                <td>{{ $p->description ?? optional($p->part)->name ?? '-' }}</td>
                <td class="right">{{ number_format((float)$p->quantity, 2, ',', '.') }}</td>
                <td class="right">R$ {{ number_format((float)$p->unit_price, 2, ',', '.') }}</td>
                <td class="right">R$ {{ number_format((float)$p->total, 2, ',', '.') }}</td>
            </tr>
        @empty
            <tr><td colspan="4" class="muted">Nenhuma peça informada.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="box">
    <h2>Mão de obra</h2>
    <table>
        <thead>
        <tr>
            <th>Técnico</th>
            <th>Início</th>
            <th>Fim</th>
            <th class="right">Horas</th>
            <th class="right">Valor/h</th>
            <th class="right">Total</th>
        </tr>
        </thead>
        <tbody>
        @forelse($os->laborEntries as $l)
            <tr>
                <td>{{ optional($l->employee)->full_name ?? '-' }}</td>
                <td>{{ $l->started_at ? \Carbon\Carbon::parse($l->started_at)->format('d/m/Y H:i') : '-' }}</td>
                <td>{{ $l->ended_at ? \Carbon\Carbon::parse($l->ended_at)->format('d/m/Y H:i') : '-' }}</td>
                <td class="right">{{ number_format((float)$l->hours, 2, ',', '.') }}</td>
                <td class="right">R$ {{ number_format((float)$l->rate, 2, ',', '.') }}</td>
                <td class="right">R$ {{ number_format((float)$l->total, 2, ',', '.') }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="muted">Nenhuma hora registrada.</td></tr>
        @endforelse
        </tbody>
    </table>

    <div style="margin-top:10px" class="muted">
        <strong>Condição:</strong> {{ $os->payment_condition ?? '-' }}<br>
        <strong>Observações:</strong> {{ $os->notes ?? '-' }}
    </div>
</div>

<div class="">
    <table class="totals-grid">
        <tr>
            <td style="width:25%">
                <div class="total-card">
                    <div class="label">Serviços</div>
                    <div class="value">
                        R$ {{ number_format((float)$os->services_subtotal, 2, ',', '.') }}
                    </div>
                </div>
            </td>

            <td style="width:25%">
                <div class="total-card">
                    <div class="label">Peças</div>
                    <div class="value">
                        R$ {{ number_format((float)$os->parts_subtotal, 2, ',', '.') }}
                    </div>
                </div>
            </td>

            <td style="width:25%">
                <div class="total-card">
                    <div class="label">Mão de obra</div>
                    <div class="value">
                        R$ {{ number_format((float)$os->labor_total_amount, 2, ',', '.') }}
                    </div>
                </div>
            </td>

            <td style="width:25%">
                <div class="total-card-dark">
                    <div class="label">Total geral</div>
                    <div class="value">
                        R$ {{ number_format((float)$os->grand_total, 2, ',', '.') }}
                    </div>
                </div>
            </td>
        </tr>
    </table>
</div>

</body>
</html>
