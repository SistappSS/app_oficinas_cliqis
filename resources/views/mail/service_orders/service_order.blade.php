<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Ordem de Serviço #{{ $os->order_number ?? '' }}</title>
</head>
<body style="margin:0;padding:0;background:#f8fafc;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="640" cellspacing="0" cellpadding="0"
                   style="max-width:640px;width:100%;background:#ffffff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;">
                <tr>
                    <td style="padding:18px 18px 14px 18px;background:#0b1220;color:#fff;">
                        <div style="font-size:12px;opacity:.9;">OS • #{{ $os->order_number ?? '-' }}</div>
                        <div style="font-size:20px;font-weight:700;margin-top:6px;">
                            Ordem de Serviço
                        </div>
                        <div style="font-size:12px;opacity:.85;margin-top:4px;">
                            {{ $dtFmt ?? '' }}
                        </div>
                    </td>
                </tr>

                <tr>
                    <td style="padding:18px;">
                        <div style="font-size:14px;font-weight:700;margin-bottom:10px;">Olá!</div>

                        <div style="font-size:13px;line-height:1.5;color:#334155;">
                            Segue a Ordem de Serviço <strong>#{{ $os->order_number ?? '-' }}</strong>.
                            @if(!empty($customerName))
                                Cliente: <strong>{{ $customerName }}</strong>.
                            @endif
                            @if(!empty($statusLabel))
                                Status: <strong>{{ $statusLabel }}</strong>.
                            @endif
                        </div>

                        <div style="margin-top:14px;padding:12px;border:1px solid #e2e8f0;border-radius:12px;background:#f8fafc;">
                            <div style="font-size:12px;color:#64748b;">Total</div>
                            <div style="font-size:18px;font-weight:700;color:#0f172a;">
                                R$ {{ number_format((float)($os->grand_total ?? 0), 2, ',', '.') }}
                            </div>
                        </div>

                        <div style="margin-top:14px;font-size:12px;color:#64748b;line-height:1.5;">
                            O PDF vai em anexo. Se precisar de ajustes, é só responder este e-mail.
                        </div>
                    </td>
                </tr>

                <tr>
                    <td style="padding:14px 18px;background:#f1f5f9;border-top:1px solid #e2e8f0;font-size:11px;color:#64748b;">
                        Cliqis • Sistema de Ordens de Serviço
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
