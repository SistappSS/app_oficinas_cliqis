<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Erro</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- Se já tiver seu app.css, pode trocar o CDN abaixo --}}
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-[#050509] text-slate-50 flex items-center justify-center">

@php
    $status = isset($exception) ? $exception->getStatusCode() : 403;
@endphp

<div class="w-full max-w-md px-6 text-center">
    {{-- Ícone cadeado --}}
    <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-slate-900/80 border border-slate-700/70">
        <svg class="h-8 w-8 text-slate-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M8 11V8a4 4 0 1 1 8 0v3M7 11h10a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1v-7a1 1 0 0 1 1-1z" />
        </svg>
    </div>

    <h1 class="text-2xl font-semibold mb-2">
        Acesso bloqueado
    </h1>

    <p class="text-sm text-slate-400 max-w-sm mx-auto">
        Este recurso não faz do seu módulo atual.
        Para usar, desbloqueie abaixo.
    </p>

    <div class="mt-6">
        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">
            Código do erro
        </p>
        <p class="mt-1 text-lg font-semibold text-slate-100">
            {{ $status }}
        </p>
    </div>

    <a href="{{ route('billing.index', auth()->id()) }}"
       class="mt-8 inline-flex items-center justify-center rounded-full bg-emerald-500 px-8 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-500/30 transition hover:bg-emerald-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400">
        Desbloquear
    </a>

    <a href="{{ url()->previous() }}"
       class="mt-4 block text-xs text-slate-500 hover:text-slate-300">
        Voltar para a página anterior
    </a>
</div>

</body>
</html>
