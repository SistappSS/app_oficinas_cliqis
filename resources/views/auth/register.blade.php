@extends('layouts.templates.guest')

@section('guest-content')
    <main class="relative z-10 mx-auto max-w-7xl px-4">
        <section class="h-full grid place-items-center">
            <form method="post" action="{{ route('register.post') }}" novalidate
                  class="w-full max-w-xl rounded-2xl bg-white p-6 sm:p-8 shadow-xl border border-slate-200">
                @csrf

                <h1 class="mb-6 text-center text-2xl font-bold sm:text-3xl">
                    Você pode criar uma conta em segundos!
                </h1>

                <button type="button" id="btn-google" onclick="location.href='{{ route('google.redirect') }}'"
                        class="mb-4 inline-flex w-full items-center justify-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-slate-700 transition hover:bg-slate-50 active:scale-[.98]">
                    <!-- Google icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" class="h-5 w-5">
                        <path fill="#FFC107"
                              d="M43.6 20.5H42V20H24v8h11.3C33.6 32.9 29.2 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3 0 5.7 1.1 7.7 3l5.7-5.7C34 6.1 29.3 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.3-.1-2.5-.4-3.5z"/>
                        <path fill="#FF3D00"
                              d="M6.3 14.7l6.6 4.8C14.8 16.1 19 14 24 14c3 0 5.7 1.1 7.7 3l5.7-5.7C34 6.1 29.3 4 24 4 16.1 4 9.3 8.5 6.3 14.7z"/>
                        <path fill="#4CAF50"
                              d="M24 44c5.1 0 9.8-1.9 13.3-5.1l-6.1-5.1C29.2 36 26.7 37 24 37c-5.2 0-9.6-3.1-11.3-7.5l-6.5 5C9.3 39.5 16.1 44 24 44z"/>
                        <path fill="#1976D2"
                              d="M43.6 20.5H42V20H24v8h11.3c-1.3 3.1-4.8 5.5-11.3 5.5-6.6 0-12-5.4-12-12S17.4 9.5 24 9.5c3 0 5.7 1.1 7.7 3l5.7-5.7C34 6.1 29.3 4 24 4 16.1 4 9.3 8.5 6.3 14.7z"/>
                    </svg>
                    <span class="font-medium">Continuar com o Google</span>
                </button>

                <div class="relative my-4">
                    <hr class="border-slate-200">
                    <span class="absolute inset-0 m-auto w-10 bg-white text-center text-xs text-slate-400">OU</span>
                </div>

                {{-- Nome --}}
                <div class="mb-4">
                    <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Nome completo</label>
                    <div class="relative group">
                        <input
                            id="name"
                            name="name"
                            type="text"
                            required
                            value="{{ old('name') }}"
                            class="peer w-full rounded-xl bg-white px-10 py-2.5 outline-none transition placeholder:text-slate-400 hover:border-slate-400 focus:ring-2
                                {{ $errors->has('name')
                                    ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-200 border'
                                    : 'border border-slate-300 focus:border-blue-600 focus:ring-blue-200' }}"
                            placeholder="John Doe"
                        />
                        <svg
                            class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 transition-colors duration-200
                                   {{ $errors->has('name')
                                        ? 'text-rose-500'
                                        : 'text-slate-400 group-hover:text-blue-600 peer-focus:text-blue-600 peer-hover:text-blue-600' }}"
                            xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="1.6">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5Zm-7 9a7 7 0 0 1 14 0Z"/>
                        </svg>
                    </div>
                    @error('name')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Email --}}
                <div class="mb-4">
                    <label for="email" class="mb-1 block text-sm font-medium text-slate-700">E-mail para acesso</label>
                    <div class="relative group">
                        <input
                            id="email"
                            name="email"
                            type="email"
                            required
                            autocomplete="email"
                            value="{{ old('email') }}"
                            class="peer w-full rounded-xl bg-white px-10 py-2.5 outline-none transition placeholder:text-slate-400 hover:border-slate-400 focus:ring-2
                                {{ $errors->has('email')
                                    ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-200 border'
                                    : 'border border-slate-300 focus:border-blue-600 focus:ring-blue-200' }}"
                            placeholder="example@site.com"
                        />
                        <svg
                            class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 transition-colors duration-200
                                   {{ $errors->has('email')
                                        ? 'text-rose-500'
                                        : 'text-slate-400 group-hover:text-blue-600 peer-focus:text-blue-600 peer-hover:text-blue-600' }}"
                            xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="1.6">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M3 8l9 6 9-6M3 8l9 6 9-6v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                        </svg>
                    </div>
                    @error('email')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Senha --}}
                <div>
                    <div class="flex items-center justify-between">
                        <label for="password" class="mb-1 block text-sm font-medium text-slate-700">
                            Escolha a senha
                        </label>
                        <button type="button" id="s-toggle" class="text-sm text-blue-700 hover:underline">
                            Mostrar
                        </button>
                    </div>

                    <div class="relative group">
                        <input
                            id="password"
                            name="password"
                            type="password"
                            required
                            minlength="8"
                            class="peer w-full rounded-xl bg-white pl-10 py-2.5 outline-none transition placeholder:text-slate-400 hover:border-slate-400 focus:ring-2
                                {{ $errors->has('password')
                                    ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-200 border'
                                    : 'border border-slate-300 focus:border-blue-600 focus:ring-blue-200' }}"
                            placeholder="Mínimo de 8 caracteres"
                        />
                        <svg
                            class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 transition-colors duration-200
                                   {{ $errors->has('password')
                                        ? 'text-rose-500'
                                        : 'text-slate-400 group-hover:text-blue-600 peer-focus:text-blue-600 peer-hover:text-blue-600' }}"
                            xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="1.6">
                            <rect x="4" y="11" width="16" height="9" rx="2"/>
                            <path d="M8 11V8a4 4 0 1 1 8 0v3"/>
                        </svg>
                    </div>

                    @error('password')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror

                    {{-- Confirmar senha --}}
                    <div class="flex items-center justify-between mt-4">
                        <label for="password_confirmation" class="mb-1 block text-sm font-medium text-slate-700">
                            Confirmar senha
                        </label>
                    </div>

                    <div class="relative group">
                        <input
                            id="password_confirmation"
                            name="password_confirmation"
                            type="password"
                            required
                            minlength="8"
                            class="peer w-full rounded-xl bg-white pl-10 py-2.5 outline-none transition placeholder:text-slate-400 hover:border-slate-400 focus:ring-2
                                {{ $errors->has('password')
                                    ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-200 border'
                                    : 'border border-slate-300 focus:border-blue-600 focus:ring-blue-200' }}"
                            placeholder="Mínimo de 8 caracteres"
                        />
                        <svg
                            class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 transition-colors duration-200
                                   {{ $errors->has('password')
                                        ? 'text-rose-500'
                                        : 'text-slate-400 group-hover:text-blue-600 peer-focus:text-blue-600 peer-hover:text-blue-600' }}"
                            xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="1.6">
                            <rect x="4" y="11" width="16" height="9" rx="2"/>
                            <path d="M8 11V8a4 4 0 1 1 8 0v3"/>
                        </svg>
                    </div>

                    {{-- erro de confirmação vem como password.confirmed,
                         então já cobrimos junto com @error('password') acima --}}

                    <div class="mt-2 flex items-center gap-2 text-xs text-slate-500">
                        <span id="meter-label">Fraca</span>
                        <div class="h-1.5 w-28 rounded-full bg-slate-200 overflow-hidden">
                            <div id="meter-bar" class="h-full w-[8%] bg-blue-600 transition-all"></div>
                        </div>
                    </div>
                </div>

                <button type="submit"
                        class="mt-6 inline-flex w-full items-center justify-center rounded-xl bg-blue-700 px-4 py-3 font-medium text-white shadow-lg transition hover:bg-blue-800 active:scale-[.98]">
                    Criar conta com E-mail
                </button>

                <p class="mt-6 text-center text-sm text-slate-600 sm:hidden">
                    Já tem uma conta?
                    <a href="{{('login')}}" class="font-medium text-blue-700 hover:underline">Entrar</a>
                </p>
            </form>
        </section>
    </main>

    @push('scripts')
        <script>
            const pass = document.getElementById("password");
            const meterBar = document.getElementById("meter-bar");
            const meterLabel = document.getElementById("meter-label");
            const toggle = document.getElementById("s-toggle");

            toggle.addEventListener("click", ()=>{
                pass.type = pass.type === "password" ? "text" : "password";
                toggle.textContent = pass.type === "password" ? "Mostrar" : "Ocultar";
            });

            function strengthLevel(v){
                let raw = 0;
                if (v.length >= 8) raw++;
                if (/[A-Z]/.test(v)) raw++;
                if (/[a-z]/.test(v)) raw++;
                if (/\d/.test(v)) raw++;
                if (/[^\w\s]/.test(v)) raw++;
                if (v.length >= 12) raw++;

                if (raw <= 1) return 0;
                if (raw === 2) return 1;
                if (raw === 3) return 2;
                if (raw === 4) return 3;
                return 4;
            }

            function renderMeter(level){
                const pct   = [8, 28, 52, 76, 100][level];
                const label = ["Muito fraca","Fraca","Ok","Forte","Excelente"][level];
                meterBar.style.width = pct + "%";
                meterLabel.textContent = label;
            }

            pass.addEventListener("input", ()=> renderMeter(strengthLevel(pass.value)));
            renderMeter(0);
        </script>
    @endpush
@endsection
