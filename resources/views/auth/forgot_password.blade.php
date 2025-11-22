@extends('layouts.templates.guest')

@section('guest-content')
    <main class="relative z-10 mx-auto max-w-7xl px-4 pb-32">
        <section class="mx-auto grid place-items-center py-10 sm:py-16">
            <form id="recover-form" class="w-full max-w-xl rounded-2xl bg-white p-6 sm:p-8 shadow-xl border border-slate-200">
                <h1 class="mb-6 text-3xl font-bold">Esqueceu sua senha?</h1>

                <label for="rec-email" class="mb-1 block text-sm font-medium text-slate-700">E-mail</label>
                <div class="relative">
                    <input id="rec-email" type="email" required autocomplete="email"
                           class="w-full rounded-xl border border-slate-300 bg-white px-10 py-2.5 outline-none transition placeholder:text-slate-400 hover:border-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-200"
                           placeholder="insira o seu e-mail" />
                    <svg class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l9 6 9-6M3 8l9 6 9-6v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                </div>
                <p id="rec-error" class="mt-1 hidden text-xs text-rose-600"></p>

                <button type="submit" class="mt-6 inline-flex w-full items-center justify-center rounded-xl bg-blue-700 px-4 py-3 font-medium text-white shadow-lg transition hover:bg-blue-800 active:scale-[.98]">
                    Enviar o link para mim
                </button>

                <p class="mt-4 text-center text-sm">
                    ou <a href="{{route('login')}}" class="font-medium text-blue-700 hover:underline">faça login</a>
                </p>
            </form>
        </section>
    </main>
@endsection


{{--<!doctype html>--}}
{{--<html lang="pt-BR">--}}
{{--<head>--}}
{{--    <meta charset="utf-8" />--}}
{{--    <meta name="viewport" content="width=device-width, initial-scale=1" />--}}
{{--    <title>Cliqis — Recuperar senha</title>--}}
{{--    <script src="https://cdn.tailwindcss.com"></script>--}}
{{--</head>--}}

{{--<body class="min-h-screen bg-slate-50 text-slate-900 relative overflow-x-clip">--}}
{{--<!-- BG -->--}}
{{--<div aria-hidden="true" class="pointer-events-none absolute inset-0 -z-10">--}}
{{--    <div class="absolute left-[-120px] top-[-120px] h-80 w-80 rounded-full bg-blue-200 blur-3xl opacity-60"></div>--}}
{{--    <svg class="absolute inset-x-0 bottom-0 h-[360px] w-full" viewBox="0 0 1440 320" preserveAspectRatio="none">--}}
{{--        <defs>--}}
{{--            <linearGradient id="gr" x1="0" x2="1" y1="1" y2="0"><stop offset="0%" stop-color="#1d4ed8"/><stop offset="60%" stop-color="#2563eb"/><stop offset="100%" stop-color="#38bdf8"/></linearGradient>--}}
{{--            <pattern id="dt" width="16" height="16" patternUnits="userSpaceOnUse"><circle cx="2" cy="2" r="1.2" fill="white" opacity=".3"/></pattern>--}}
{{--        </defs>--}}
{{--        <path fill="url(#gr)" d="M0,256L80,218.7C160,181,320,107,480,96C640,85,800,139,960,165.3C1120,192,1280,192,1360,186.7L1440,181V320H0Z"/>--}}
{{--        <rect x="0" y="0" width="1440" height="320" fill="url(#dt)" opacity=".3"></rect>--}}
{{--    </svg>--}}
{{--</div>--}}

{{--<!-- Topbar -->--}}
{{--<header class="relative z-10">--}}
{{--    <div class="mx-auto max-w-7xl px-4 py-4 flex items-center justify-between">--}}
{{--        <a href="./auth/login.html" class="flex items-center gap-2">--}}
{{--        <span class="grid h-9 w-9 place-items-center rounded-xl bg-blue-700 text-white shadow-lg">--}}
{{--          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M7 13h4v8l6-12h-4V1L7 13Z"/></svg>--}}
{{--        </span>--}}
{{--            <div>--}}
{{--                <div class="text-lg font-semibold leading-none">Cliqis</div>--}}
{{--                <div class="text-xs text-slate-500">O aplicativo completo para o trabalho.</div>--}}
{{--            </div>--}}
{{--        </a>--}}
{{--        <a href="{{route('login')}}" class="hidden sm:inline-flex rounded-xl bg-white px-4 py-2 text-slate-700 shadow border border-slate-200 hover:bg-slate-50">Entrar</a>--}}
{{--    </div>--}}
{{--</header>--}}

{{--<main class="relative z-10 mx-auto max-w-7xl px-4 pb-32">--}}
{{--    <section class="mx-auto grid place-items-center py-10 sm:py-16">--}}
{{--        <form id="recover-form" class="w-full max-w-xl rounded-2xl bg-white p-6 sm:p-8 shadow-xl border border-slate-200">--}}
{{--            <h1 class="mb-6 text-3xl font-bold">Esqueceu sua senha?</h1>--}}

{{--            <label for="rec-email" class="mb-1 block text-sm font-medium text-slate-700">E-mail</label>--}}
{{--            <div class="relative">--}}
{{--                <input id="rec-email" type="email" required autocomplete="email"--}}
{{--                       class="w-full rounded-xl border border-slate-300 bg-white px-10 py-2.5 outline-none transition placeholder:text-slate-400 hover:border-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-200"--}}
{{--                       placeholder="insira o seu e-mail" />--}}
{{--                <svg class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l9 6 9-6M3 8l9 6 9-6v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>--}}
{{--            </div>--}}
{{--            <p id="rec-error" class="mt-1 hidden text-xs text-rose-600"></p>--}}

{{--            <button type="submit" class="mt-6 inline-flex w-full items-center justify-center rounded-xl bg-blue-700 px-4 py-3 font-medium text-white shadow-lg transition hover:bg-blue-800 active:scale-[.98]">--}}
{{--                Enviar o link para mim--}}
{{--            </button>--}}

{{--            <p class="mt-4 text-center text-sm">--}}
{{--                ou <a href="{{route('login')}}" class="font-medium text-blue-700 hover:underline">faça login</a>--}}
{{--            </p>--}}
{{--        </form>--}}
{{--    </section>--}}
{{--</main>--}}

{{--<!-- Rodapé FIXO (branco) -->--}}
{{--<footer class="fixed inset-x-0 bottom-0 z-20 px-4 pb-4 pt-2 text-center text-[11px] text-white drop-shadow">--}}
{{--    Este site é protegido pelo reCAPTCHA e regido pela--}}
{{--    <a class="underline opacity-90 hover:opacity-100" href="https://policies.google.com/privacy?hl=pt-BR">política de privacidade</a>--}}
{{--    e pelos--}}
{{--    <a class="underline opacity-90 hover:opacity-100" href="https://policies.google.com/terms?hl=pt-BR">termos de serviço</a>--}}
{{--    do Google.--}}
{{--</footer>--}}

{{--<script>--}}
{{--    const form = document.getElementById('recover-form');--}}
{{--    const email = document.getElementById('rec-email');--}}
{{--    const err = document.getElementById('rec-error');--}}
{{--    form.addEventListener('submit', (e) => {--}}
{{--        e.preventDefault();--}}
{{--        if(!/^\S+@\S+\.\S+$/.test(email.value)){--}}
{{--            err.textContent = "Informe um e-mail válido.";--}}
{{--            err.classList.remove("hidden");--}}
{{--            email.focus();--}}
{{--            return;--}}
{{--        }--}}
{{--        err.classList.add("hidden");--}}
{{--        form.querySelector('button').disabled = true;--}}
{{--        form.querySelector('button').classList.add("opacity-70","cursor-not-allowed");--}}
{{--        setTimeout(()=>{--}}
{{--            alert("Enviamos um link de recuperação para seu e-mail ✉️");--}}
{{--            window.location.href = "./auth/login.html";--}}
{{--        },900);--}}
{{--    });--}}
{{--</script>--}}
{{--</body>--}}
{{--</html>--}}
