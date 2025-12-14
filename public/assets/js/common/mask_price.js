document.addEventListener("DOMContentLoaded", function() {
    const formatBRL = (digits) => {
        if (!digits) return '';
        digits = digits.replace(/^0+/, '');
        if (digits.length < 3) digits = digits.padStart(3, '0'); // garante 2 casas
        const n = Number(digits) / 100;
        return n.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };

    const unmaskBRL = (str) => (str ? parseInt(String(str).replace(/\D/g, '') || '0', 10) : 0); // centavos

    const allowKey = (e) => {
        const ctrl = e.ctrlKey || e.metaKey;
        const code = e.key;
        const nav  = ['Backspace','Delete','ArrowLeft','ArrowRight','Home','End','Tab'];
        if (nav.includes(code)) return true;
        if (ctrl && ['a','c','v','x'].includes(code.toLowerCase())) return true;
        return /[0-9]/.test(code);
    };

    const applyMoneyMask = (el) => {
        // pré-preenchido
        if (el.value && /\d/.test(el.value)) el.value = formatBRL(el.value.replace(/\D/g, ''));

        el.addEventListener('beforeinput', (e) => {});

        el.addEventListener('keypress', (e) => {
            if (!allowKey(e)) e.preventDefault();
        });

        el.addEventListener('input', function() {
            const digits = this.value.replace(/\D/g, '');
            this.value   = formatBRL(digits);
            this.dataset.cents = String(unmaskBRL(this.value));
        });

        el.addEventListener('paste', function() {
            setTimeout(() => {
                const digits = this.value.replace(/\D/g, '');
                this.value   = formatBRL(digits);
                this.dataset.cents = String(unmaskBRL(this.value));
            }, 0);
        });
    };

    document.querySelectorAll('.money-brl, [data-mask="money-brl"]').forEach(applyMoneyMask);

    window.getBrlCents = function(idOrEl) {
        const el = (typeof idOrEl === 'string') ? document.getElementById(idOrEl) : idOrEl;
        return el ? unmaskBRL(el.value) : 0;
    };
});
