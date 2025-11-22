<!doctype html>
<html lang="pt-BR">
<body style="font-family:Arial,Helvetica,sans-serif;background:#f8fafc;padding:24px">
<table width="100%" cellpadding="0" cellspacing="0" style="max-width:640px;margin:0 auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:12px">
    <tr>
        <td style="padding:20px">
            <h2 style="margin:0 0 8px 0;color:#0f172a">Lembrete de vencimento</h2>

            @if(!empty($body ?? null))
                <p style="margin:0;color:#475569;white-space:pre-line;">
                    {{ $body }}
                </p>
            @else
                <p style="margin:0;color:#475569">
                    Olá, sua fatura <strong>{{ $invoice->number }}</strong> vence em {{ $invoice->due_date?->format('d/m/Y') }}.
                </p>
            @endif

            <div style="margin-top:16px;padding:12px;border:1px solid #e2e8f0;border-radius:8px">
                <p style="margin:0 0 6px 0">Valor:
                    <strong>R$ {{ number_format($invoice->amount,2,',','.') }}</strong>
                </p>
                <p style="margin:0 0 6px 0">Vencimento:
                    <strong>{{ $invoice->due_date?->format('d/m/Y') }}</strong>
                </p>
                <p style="margin:0">Cliente:
                    <strong>{{ $invoice->customer->name ?? '—' }}</strong>
                </p>
            </div>

            <p style="margin-top:16px">Caso já tenha efetuado o pagamento, desconsidere este aviso.</p>

            <p style="margin-top:24px;color:#64748b;font-size:12px">© {{ date('Y') }} Cliqis</p>
        </td>
    </tr>
</table>
</body>
</html>
