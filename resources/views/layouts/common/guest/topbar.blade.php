<header class="relative z-10">
    <div class="mx-auto max-w-7xl px-4 py-4 flex items-center justify-between">
        <a href="{{route('home')}}" class="flex items-center gap-2">
            <span class="grid h-9 w-9 place-items-center rounded-xl bg-blue-700 text-white shadow-lg">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                   stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round"
                                                                  d="M7 13h4v8l6-12h-4V1L7 13Z"/></svg>
            </span>
            <div>
                <div class="text-lg font-semibold leading-none">Cliqis</div>
                <div class="text-xs text-slate-500">O aplicativo completo para o trabalho.</div>
            </div>
        </a>
        @if(isActiveRoute('register'))
            <div class="hidden sm:flex items-center gap-3">
                <span class="text-sm text-slate-600">Já está explorando a Cliqis?</span>
                <a href="{{route('login')}}"
                   class="inline-flex items-center rounded-xl bg-white px-4 py-2 text-slate-700 shadow border border-slate-200 hover:bg-slate-50">Entrar</a>
            </div>
        @elseif(isActiveRoute('login'))
            <div class="hidden sm:flex items-center gap-3">
                <span class="text-sm text-slate-600">Não tem uma conta?</span>
                <a href="{{route('register')}}"
                   class="inline-flex items-center rounded-xl bg-blue-700 px-4 py-2 text-white shadow-lg transition hover:bg-blue-800 active:scale-[.98]">Criar
                    uma conta</a>
            </div>
        @elseif(isActiveRoute('forgot-password') || isActiveRoute('additional-customer-info.index'))
            <div class="hidden sm:flex items-center gap-6">
                <a class="text-sm text-slate-600 hover:text-slate-900"
                   href="https://wa.me/5511988313151?text=Ol%C3%A1%2C%20preciso%20de%20ajuda%20no%20Cliqis."
                   target="_blank" rel="noopener">Ajuda</a>
                <a class="text-sm text-slate-600 hover:text-slate-900"
                   href="https://wa.me/5511988313151?text=Ol%C3%A1%2C%20gostaria%20de%20falar%20com%20o%20suporte%20Cliqis."
                   target="_blank" rel="noopener">Contato</a>
                <a href="{{route('login')}}"
                   class="hidden sm:inline-flex rounded-xl bg-white px-4 py-2 text-slate-700 shadow border border-slate-200 hover:bg-slate-50">Entrar</a>
            </div>
        @endif
    </div>
</header>
