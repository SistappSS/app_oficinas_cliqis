@php
    $supplier = $order->supplier;
@endphp

<div style="font-family: Arial, sans-serif; line-height: 1.45;">
    <p>{!! nl2br(e($bodyText)) !!}</p>

    <hr>

    <p>
        <strong>Pedido:</strong> {{ $order->order_number }}<br>
        <strong>Título:</strong> {{ $order->title }}<br>
        <strong>Data:</strong> {{ optional($order->order_date)->format('d/m/Y') }}<br>
        <strong>CNPJ:</strong> {{ $order->billing_cnpj }}<br>
        <strong>Fornecedor:</strong> {{ $supplier->name ?? '—' }} ({{ $supplier->email ?? '—' }})
    </p>

    <table cellpadding="6" cellspacing="0" border="1" style="border-collapse: collapse; width: 100%;">
        <thead>
        <tr>
            <th>#</th>
            <th>Código</th>
            <th>Descrição</th>
            <th>NCM</th>
            <th>Qtd</th>
            <th>Unit</th>
            <th>IPI%</th>
            <th>Total</th>
        </tr>
        </thead>
        <tbody>
        @foreach(($order->items ?? []) as $i => $it)
            <tr>
                <td>{{ $i+1 }}</td>
                <td>{{ $it->code }}</td>
                <td>{{ $it->description }}</td>
                <td>{{ $it->ncm }}</td>
                <td>{{ $it->quantity }}</td>
                <td>R$ {{ number_format((float)$it->unit_price, 2, ',', '.') }}</td>
                <td>{{ number_format((float)$it->ipi_rate, 2, ',', '.') }}%</td>
                <td>R$ {{ number_format((float)$it->line_total, 2, ',', '.') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <p style="margin-top: 12px;">
        <strong>Total do pedido:</strong> R$ {{ number_format((float)$order->grand_total, 2, ',', '.') }}
    </p>
</div>
