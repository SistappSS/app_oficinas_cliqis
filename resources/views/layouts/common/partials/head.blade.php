<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

    <title>Cliqis</title>

    <script>
        (function () {
            const doc = document.documentElement;
            // mata transições no 1º paint
            doc.classList.add('preload');
            // se usuário deixou oculto, já inicia oculto no SSR/1º paint
            if (localStorage.getItem('headerHidden') === 'true') {
                doc.classList.add('header-hidden');
            }
            // preloader ligado até DOM pronto
            doc.classList.add('loading');
        })();
    </script>

    <script src="https://cdn.tailwindcss.com"></script>
{{--    <link rel="stylesheet" href="{{asset('assets/css/tailwind.css')}}"/>--}}
{{--    <script src="{{asset('assets/css/tailwind.js')}}"></script>--}}

    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"/>


    <style>
        .no-scrollbar{
            scrollbar-width:none;
        }
        .no-scrollbar::-webkit-scrollbar{
            display:none;
        }

        html {
            scrollbar-gutter: stable !important;
        }

    /*    preloader */
        /* evita pulo do scroll */
        html { scrollbar-gutter: stable; }

        /* desliga transições no 1º paint */
        html.preload * { transition: none !important; }

        /* estado inicial sem flash */
        html.header-hidden #header-collapsible { height: 0 !important; overflow: hidden; }
        html.header-hidden #header-inner { opacity: 0; transform: translateY(-12px); }

        /* navbar central: só aparece quando header está oculto */
        /* padrão: navbar central invisível e sem clique */
        #mini-shortcuts{ opacity:0; pointer-events:none; }

        /* quando header oculto (classe no <html>): mostra e habilita clique */
        @media (min-width:768px){
            html.header-hidden #mini-shortcuts{ display:flex; opacity:1; pointer-events:auto; }
            html:not(.header-hidden) #mini-shortcuts{ display:none; }
        }


        /* preloader fino no topo (visível enquanto html.loading) */
        #route-preloader {
            position: fixed; left:0; top:0; right:0; height:3px;
            background: #2563eb; transform-origin: left;
            transform: scaleX(0); opacity: 0; z-index: 60;
        }
        html.loading #route-preloader { opacity: 1; transform: scaleX(.35); }

        html { font-size: 90%; }


    </style>

    @stack('styles')
</head>
