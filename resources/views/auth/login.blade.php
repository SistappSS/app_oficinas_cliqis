@extends('layouts.templates.guest')

@section('guest-content')
    <main class="relative z-10 mx-auto max-w-7xl px-4 pb-32">
        <section class="mx-auto grid place-items-center py-10 sm:py-16">
            <form method="POST" action="{{ route('login.post') }}" id="login-form" novalidate class="w-full max-w-lg rounded-2xl bg-white p-6 sm:p-8 shadow-xl border border-slate-200">
                @csrf

                <h1 class="mb-6 text-center text-2xl font-bold sm:text-3xl">Bem-vindo de volta!</h1>

                <!-- Google -->
                <button type="button" id="btn-google" onclick="location.href='{{ route('google.redirect') }}'"
                        class="mb-4 inline-flex w-full items-center justify-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-slate-700 transition hover:bg-slate-50 active:scale-[.98]">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" class="h-5 w-5">
                        <path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.6 32.9 29.2 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3 0 5.7 1.1 7.7 3l5.7-5.7C34 6.1 29.3 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.3-.1-2.5-.4-3.5z"/><path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.8 16.1 19 14 24 14c3 0 5.7 1.1 7.7 3l5.7-5.7C34 6.1 29.3 4 24 4 16.1 4 9.3 8.5 6.3 14.7z"/><path fill="#4CAF50" d="M24 44c5.1 0 9.8-1.9 13.3-5.1l-6.1-5.1C29.2 36 26.7 37 24 37c-5.2 0-9.6-3.1-11.3-7.5l-6.5 5C9.3 39.5 16.1 44 24 44z"/><path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-1.3 3.1-4.8 5.5-11.3 5.5-6.6 0-12-5.4-12-12S17.4 9.5 24 9.5c3 0 5.7 1.1 7.7 3l5.7-5.7C34 6.1 29.3 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.3-.1-2.5-.4-3.5z"/>
                    </svg>
                    <span class="font-medium">Continuar com o Google</span>
                </button>

                <!-- Divider -->
                <div class="relative my-4">
                    <hr class="border-slate-200">
                    <span class="absolute inset-0 m-auto w-10 bg-white text-center text-xs text-slate-400">OU</span>
                </div>

                <!-- Email -->
                <div class="mb-4">
                    <label for="email" class="mb-1 block text-sm font-medium text-slate-700">E-mail profissional</label>
                    <div class="relative">
                        <input id="email" name="email" type="email" autocomplete="email" required value="{{old('email')}}"
                               class="peer w-full rounded-xl border border-slate-300 bg-white px-10 py-2.5 outline-none transition placeholder:text-slate-400 hover:border-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-200"
                               placeholder="email@cliqis.com" />
                        <svg class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400 peer-focus:text-blue-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l9 6 9-6M3 8l9 6 9-6v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                    </div>
                    @if(session('error'))
                        <p class="mt-1 text-xs text-rose-600">
                            {{ session('error') }}
                            <a href="{{route('module.index')}}" class="font-weight-bold">Renove agora!</a>
                        </p>
                    @endif

                    @error('email')
                    <p class="mt-1 text-xs text-rose-600">
                        {{ $message }}
                    </p>
                    @enderror
                </div>

                <!-- Password -->
                <div>
                    <div class="flex items-center justify-between">
                        <label for="password" class="mb-1 block text-sm font-medium text-slate-700">Senha</label>
                        <a href="{{route('forgot-password')}}" class="text-sm font-medium text-blue-700 hover:underline">esqueceu a senha?</a>
                    </div>
                    <div class="relative">
                        <input id="password" name="password" type="password" autocomplete="current-password" required minlength="8"
                               class="peer w-full rounded-xl border border-slate-300 bg-white pl-10 pr-12 py-2.5 outline-none transition placeholder:text-slate-400 hover:border-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-200"
                               placeholder="Insira a senha" />
                        <svg class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400 peer-focus:text-blue-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="4" y="11" width="16" height="9" rx="2"/><path d="M8 11V8a4 4 0 1 1 8 0v3"/></svg>



                        <button type="button" id="toggle-pass" aria-label="Mostrar senha" class="absolute right-1.5 top-1/2 -translate-y-1/2 rounded-lg p-2 text-slate-500 hover:bg-slate-100">
                            <svg id="icon-eye" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>

                    @error('password')
                    <small class="mt-1 text-xs text-rose-600">{{$message}}</small>
                    @enderror
                </div>

                <!-- Submit -->
                <button id="btn-submit" type="submit"
                        class="mt-6 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-blue-700 px-4 py-3 font-medium text-white shadow-lg transition hover:bg-blue-800 active:scale-[.98] focus:outline-none focus:ring-2 focus:ring-blue-200">
                    <span>Entrar</span>
                    <svg id="spinner" class="hidden h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"></path>
                    </svg>
                </button>

                <div class="mt-6 text-center text-sm text-slate-600 sm:hidden">
                    Não tem uma conta?
                    <a class="font-medium text-blue-700 hover:underline" href="{{route('register')}}">Criar conta</a>
                </div>
            </form>
        </section>
    </main>

    {{-- Toast global --}}
    <div id="toast" class="pointer-events-none fixed bottom-5 right-5 z-50 hidden">
        <div class="rounded-xl bg-slate-900/90 px-4 py-3 text-sm font-medium text-white shadow-lg">Mensagem</div>
    </div>

    {{-- Helper do toast (reutilizável) --}}
    <script>
        window.showToast = function (msg) {
            const toast = document.getElementById('toast');
            if (!toast) return;
            const box = toast.querySelector('div');
            if (box) box.textContent = msg;
            toast.classList.remove('hidden');
            clearTimeout(toast._t);
            toast._t = setTimeout(() => toast.classList.add('hidden'), 3000);
        };
    </script>

    {{-- Dispara o toast ao abrir a tela de login, se a senha foi atualizada --}}
    @if(session('password_updated'))
        <script>
            document.addEventListener('DOMContentLoaded', () => showToast('Senha atualizada!'));
        </script>
    @endif

@endsection
