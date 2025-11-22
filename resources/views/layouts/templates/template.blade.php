<!doctype html>
<html lang="pt-BR">

<!-- Include:head -->
@include('layouts.common.partials.head')

<body class="min-h-screen bg-gradient-to-b from-slate-50 to-white text-slate-900">

<!-- Include:topbar -->
@include('layouts.common.partials.topbar')

<main>
    <div id="header-collapsible" class="overflow-hidden transition-[height] duration-500 ease-in-out will-change-[height] mb-6">
        <div id="header-inner" class="transition-transform duration-500 ease-in-out">
            @include('layouts.common.partials.header')
        </div>
    </div>

    <div id="route-preloader"></div>
    <div id="content-loading" class="hidden mx-auto max-w-7xl px-4 sm:px-6 pb-10">
        <div class="animate-pulse grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="h-28 rounded-2xl bg-slate-200/60"></div>
            <div class="h-28 rounded-2xl bg-slate-200/60"></div>
            <div class="h-28 rounded-2xl bg-slate-200/60"></div>
            <div class="h-28 rounded-2xl bg-slate-200/60"></div>
        </div>
    </div>

    @yield('content')
</main>

<!-- Include:scripts -->
@include('layouts.common.partials.scripts')

</body>
</html>
