<div style="font-family: Arial, sans-serif; line-height:1.5; color:#0f172a;">
    <h2 style="margin:0 0 8px;">Assinatura da Ordem de Serviço</h2>

    <p style="margin:0 0 12px;">
        Olá{{ !empty($customer_name) ? ', '.$customer_name : '' }}.
        Para assinar sua OS <strong>{{ $order_number }}</strong>, clique no botão abaixo.
    </p>

    <p style="margin:0 0 16px;">
        <a href="{{ $link }}"
           style="display:inline-block; padding:10px 14px; background:#1d4ed8; color:#fff; border-radius:10px; text-decoration:none;">
            Assinar OS
        </a>
    </p>

    <p style="margin:12px 0 0; font-size:12px; color:#64748b;">
        Se o botão não funcionar, copie e cole no navegador:<br>
        <a href="{{ $link }}" style="word-break:break-all;">{{ $link }}</a>
    </p>

    <p style="margin:0; font-size:12px; color:#64748b;">
        Este link expira em {{ $expires_at }}.
    </p>

    <p style="margin:12px 0 0; font-size:12px; color:#64748b;">
        Se você não solicitou isso, ignore este e-mail.
    </p>
</div>
