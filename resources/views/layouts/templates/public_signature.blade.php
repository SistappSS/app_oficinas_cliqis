<!doctype html>
<html lang="pt-BR" class="h-full">
@include('layouts.common.guest.head')
<body class="min-h-screen bg-slate-50 text-slate-900 overflow-x-hidden">
<div aria-hidden="true" class="pointer-events-none fixed inset-0 -z-10">
    <div class="absolute right-[-120px] top-[-120px] h-80 w-80 rounded-full bg-blue-200 blur-3xl opacity-60"></div>
    <svg class="absolute inset-x-0 bottom-0 h-[300px] w-full" viewBox="0 0 1440 320" preserveAspectRatio="none">
        <defs>
            <linearGradient id="gsu" x1="0" x2="1" y1="1" y2="0">
                <stop offset="0%" stop-color="#1d4ed8"></stop>
                <stop offset="60%" stop-color="#2563eb"></stop>
            </linearGradient>
        </defs>
        <path fill="url(#gsu)" fill-opacity=".08"
              d="M0,288L80,250.7C160,213,320,139,480,122.7C640,107,800,149,960,165.3C1120,181,1280,171,1360,165.3L1440,160L1440,320L1360,320C1280,320,1120,320,960,320C800,320,640,320,480,320C320,320,160,320,80,320L0,320Z"></path>
    </svg>
</div>

<div class="mx-auto w-full max-w-4xl px-3 sm:px-6 pt-4">
    @include('layouts.common.guest.topbar')
</div>

@yield('content')

@stack('scripts')
</body>
</html>
