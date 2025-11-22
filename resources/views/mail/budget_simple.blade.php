<!doctype html>
<html lang="pt-BR">
<body style="font-family:Arial, Helvetica, sans-serif; color:#0f172a;">
<p>Olá {{ optional($budget->customer)->name }},</p>
<p>Segue em anexo o orçamento <strong>#{{ $budget->code ?? $budget->id }}</strong>.</p>
<p>Qualquer dúvida, estamos à disposição.</p>
<p>Atenciosamente,<br>Equipe Cliqis</p>
</body>
</html>
