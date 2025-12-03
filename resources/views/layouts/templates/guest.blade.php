<!doctype html>
<html lang="pt-BR">

@include('layouts.common.guest.headd')

<body class="h-screen grid grid-rows-[auto_1fr_auto] bg-slate-50 text-slate-900 relative overflow-x-clip">
    <!-- BG -->
    <div aria-hidden="true" class="pointer-events-none absolute inset-0 -z-10">
        <div class="absolute right-[-120px] top-[-120px] h-80 w-80 rounded-full bg-blue-200 blur-3xl opacity-60"></div>
        <svg class="absolute inset-x-0 bottom-0 h-[300px] w-full" viewBox="0 0 1440 320" preserveAspectRatio="none">
            <defs>
                <linearGradient id="gsu" x1="0" x2="1" y1="1" y2="0">
                    <stop offset="0%" stop-color="#1d4ed8"/><stop offset="60%" stop-color="#2563eb"/><stop offset="100%" stop-color="#38bdf8"/>
                </linearGradient>
                <pattern id="dtsu" width="16" height="16" patternUnits="userSpaceOnUse">
                    <circle cx="2" cy="2" r="1.2" fill="white" opacity=".3"/>
                </pattern>
            </defs>
            <path fill="url(#gsu)" d="M0,288L80,250.7C160,213,320,139,480,122.7C640,107,800,149,960,165.3C1120,181,1280,171,1360,165.3L1440,160V320H0Z"/>
            <rect x="0" y="0" width="1440" height="320" fill="url(#dtsu)" opacity=".3"></rect>
        </svg>
    </div>

    <!-- Topbar -->
    @include('layouts.common.guest.topbar')

    <!-- Main (sem scroll) -->
    @yield('guest-content')

    <!-- Rodapé FIXO (branco) -->
    @include('layouts.common.guest.footer')

    @stack('scripts')
</body>
</html>
