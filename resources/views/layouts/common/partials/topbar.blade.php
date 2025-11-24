<header class="sticky top-0 z-30 border-b border-slate-200 bg-white/80 backdrop-blur">
    <div class="relative overflow-visible mx-auto max-w-7xl px-4 sm:px-6 py-3 flex items-center justify-between">
        <!-- ESQUERDA: logo -->
        <div class="flex items-center gap-3 flex-none">
      <span class="grid h-9 w-9 place-items-center rounded-xl bg-blue-700 text-white shadow">
        <!-- logo -->
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
          <path d="M7 13h4v8l6-12h-4V1L7 13Z"/>
        </svg>
      </span>
            <span class="text-lg font-semibold">Cliqis</span>
        </div>

        <nav id="mini-shortcuts"
             class="hidden md:flex items-center gap-6 ml-8 opacity-0 pointer-events-none transition-opacity duration-200">

            <a href="{{route('dashboard')}}" class="text-sm text-slate-600 hover:text-slate-900">Dashboard</a>

            @can('entitie_customer_view')
                <a href="{{route('customer.view')}}" class="text-sm text-slate-600 hover:text-slate-900">Clientes</a>
            @endcan

            <div class="relative">
                <button id="mini-more" type="button"
                        class="flex items-center gap-1 text-sm text-slate-600 hover:text-slate-900"
                        aria-expanded="false" aria-haspopup="menu">
                    Mais
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m6 9 6 6 6-6"/>
                    </svg>
                </button>

                <div id="mini-menu" role="menu"
                     class="hidden absolute left-0 top-full mt-2 min-w-[220px] rounded-xl border border-slate-200 bg-white p-2 shadow-lg z-50">
                    @role('admin')
                    <a href="{{route('roles.index')}}"
                       class="block rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">Permissões</a>
                    <a href="{{route('module.index')}}"
                       class="block rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">Módulos</a>
                    @endrole
                </div>
            </div>
        </nav>

        <nav class="hidden md:flex items-center gap-6 flex-none">
            <a class="text-sm text-slate-600 hover:text-slate-900"
               href="https://wa.me/5511988313151?text=Ajuda%20no%20Cliqis" target="_blank" rel="noopener">Ajuda</a>
            <a class="text-sm text-slate-600 hover:text-slate-900"
               href="https://wa.me/5511988313151?text=Contato%20Cliqis" target="_blank" rel="noopener">Contato</a>


            <div class="relative">
                <button id="user-btn"
                        class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-2.5 py-1.5 shadow hover:bg-slate-50">

                    {{-- Avatar --}}
                    @if(auth()->user()->image)
                        <img src="{{ 'data:image/png;base64,' . auth()->user()->image }}" alt="Profile Picture"
                             class="h-7 w-7 rounded-full object-cover">
                    @else
                        <span
                            class="grid h-7 w-7 place-items-center rounded-full bg-blue-100 text-blue-700 text-xs font-semibold">{!! getUserInitials(auth()->user()->name) !!}</span>
                    @endif

                    <span id="user-name" class="text-sm font-medium">{{ Auth::user()->name }}</span>
                    <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2">
                        <path d="m6 9 6 6 6-6"/>
                    </svg>
                </button>

                <div id="user-menu"
                     class="hidden absolute right-0 mt-2 w-48 rounded-xl border border-slate-200 bg-white p-2 shadow-lg">
                    <a href="{{ route('my-account.index')}}"
                       class="block rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">Meu perfil</a>
                    <a href="{{ route('logout') }}"
                       class="block rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">Sair</a>
                </div>
            </div>
        </nav>
    </div>
</header>
