<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    var user = @json(auth()->user()->customerLogin);
</script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // FORÇAS DE ROTA
        const FORCE_HEADER_OPEN   = @json(request()->routeIs('dashboard'));
        const FORCE_HEADER_HIDDEN = @json(
        request()->routeIs('my-account*')
        || request()->routeIs('buy-module*')
        || request()->is('module/buy-module/*')
        || request()->routeIs('service-order.create')
    );

        const html  = document.documentElement;
        const box   = document.getElementById('header-collapsible');
        const inner = document.getElementById('header-inner');
        const btn   = document.getElementById('toggle-header');
        const sc    = document.getElementById('mini-shortcuts');
        const bar   = document.getElementById('route-preloader');

        // estado salvo (usado só quando NÃO for rota forçada)
        const savedHidden = (localStorage.getItem('headerHidden') === 'true');

        // aplica classe inicial
        if (FORCE_HEADER_OPEN) {
            html.classList.remove('header-hidden');
        } else if (FORCE_HEADER_HIDDEN) {
            html.classList.add('header-hidden');
        } else {
            html.classList.toggle('header-hidden', savedHidden);
        }

        // libera transições / preloader
        requestAnimationFrame(() => html.classList.remove('preload'));
        requestAnimationFrame(() => { if (bar) bar.style.transform = 'scaleX(1)'; });
        setTimeout(() => {
            html.classList.remove('loading');
            if (bar) bar.style.opacity = '0';
        }, 350);

        // open inicial: dashboard => true | my-account/buy-module => false | demais => !savedHidden
        let open = FORCE_HEADER_OPEN ? true : (FORCE_HEADER_HIDDEN ? false : !savedHidden);

        function shortcutsShow(show){
            if(!sc) return;
            sc.style.opacity = show ? '1' : '0';
            sc.style.pointerEvents = show ? 'auto' : 'none';
        }

        // estado visual inicial
        if (open) {
            box.style.height = inner.offsetHeight + 'px';
            shortcutsShow(false);
        } else {
            box.style.height = '0px';
            shortcutsShow(true);
            if (FORCE_HEADER_HIDDEN) {
                // some de vez nessas rotas
                box.style.display = 'none';
            }
        }

        box.addEventListener('transitionend', (e) => {
            if (e.propertyName === 'height' && open) {
                box.style.height = 'auto';
            }
        });

        function collapse(){
            // bloqueia collapse no dashboard e nas rotas forçadas ocultas
            if (FORCE_HEADER_OPEN || FORCE_HEADER_HIDDEN) return;

            box.style.height = box.offsetHeight + 'px'; void box.offsetHeight;
            inner.style.transform = 'translateY(-12px)';
            inner.style.opacity = '0';
            box.style.height = '0px';
            open = false;
            localStorage.setItem('headerHidden','true');
            html.classList.add('header-hidden');
            shortcutsShow(true);
        }

        function expand(){
            // nas rotas forçadas ocultas não expande
            if (FORCE_HEADER_HIDDEN) return;

            const target = inner.offsetHeight;
            box.style.height = target + 'px';
            inner.style.transform = 'translateY(0)';
            inner.style.opacity = '1';
            open = true;
            localStorage.setItem('headerHidden','false');
            html.classList.remove('header-hidden');
            shortcutsShow(false);
            if (box.style.display === 'none') box.style.display = '';
        }

        // botão toggle
        if (btn) {
            if (FORCE_HEADER_OPEN || FORCE_HEADER_HIDDEN) {
                // some o botão nas rotas forçadas (aberto/oculto)
                btn.remove();
            } else {
                btn.addEventListener('click', () => open ? collapse() : expand());
            }
        }

        // mini menu (inalterado)
        (function miniMenu(){
            const moreBtn  = document.getElementById('mini-more');
            const moreMenu = document.getElementById('mini-menu');
            if (!moreBtn || !moreMenu) return;

            function openMini()  { moreMenu.classList.remove('hidden'); moreBtn.setAttribute('aria-expanded','true'); }
            function closeMini() { moreMenu.classList.add('hidden');   moreBtn.setAttribute('aria-expanded','false'); }

            moreBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                moreMenu.classList.contains('hidden') ? openMini() : closeMini();
            });
            document.addEventListener('click', (e) => {
                if (!moreMenu.classList.contains('hidden')) {
                    if (!moreMenu.contains(e.target) && !moreBtn.contains(e.target)) closeMini();
                }
            });
            document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeMini(); });
            window.__closeMiniShortcutsMenu = closeMini;
        })();

        // recalcula altura quando aberto e resize da janela
        let rAF;
        window.addEventListener('resize', () => {
            if (!open || FORCE_HEADER_HIDDEN) return;
            cancelAnimationFrame(rAF);
            rAF = requestAnimationFrame(() => {
                box.style.height = inner.offsetHeight + 'px';
                setTimeout(() => { if (open) box.style.height = 'auto'; }, 0);
            });
        });
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/dayjs@1.10.6/dayjs.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/dayjs@1.10.6/locale/pt.js"></script>
<script src="https://cdn.jsdelivr.net/npm/dayjs@1.10.6/plugin/relativeTime.js"></script>

<script>
    dayjs.extend(dayjs_plugin_relativeTime);
    dayjs.locale('pt');

    var trialsEndsAt = user && user.trial_ends_at ? new Date(user.trial_ends_at) : null;
    var hasSubscription = user && user.subscription === 1;
    var now = new Date();

    var notifiedTrial = @json(session('notified_trial', false));
    var diff = dayjs(trialsEndsAt).fromNow();

    (function () {
        const userBtn = document.getElementById('user-btn');
        const userMenu = document.getElementById('user-menu');

        userBtn?.addEventListener('click', () => userMenu.classList.toggle('hidden'));

        window.addEventListener('click', (e) => {
            if (!userBtn.contains(e.target)) userMenu.classList.add('hidden');
        });
    })();

    (function () {
        if (!trialsEndsAt) return;

        const end = new Date(trialsEndsAt);
        const start = new Date(end.getTime() - 14 * 24 * 60 * 60 * 1000); // 14 dias antes
        const now = new Date();

        const total = end - start;                 // duração total do teste
        const passed = Math.min(total, Math.max(0, now - start));
        const left = Math.max(0, end - now);       // tempo restante

        const days = Math.ceil(left / (24 * 60 * 60 * 1000));
        const pct = Math.round((passed / total) * 100); // progresso percentual

        const diffText = days > 1
            ? `em ${days} dias`
            : days === 1
                ? "em um dia"
                : "já terminou";

        document.getElementById('trial-text').textContent =
            `Seu teste gratuito termina ${diffText}.`;

        document.getElementById('trial-bar').style.width = pct + "%";
    })();
</script>

@stack('scripts')
