document.addEventListener("DOMContentLoaded", function () {
    const cnpjCpfInput = document.getElementById("cpfCnpj");
    const phoneInput = document.getElementById("mobilePhone");
    const cepInput = document.getElementById("postalCode");

    if (cnpjCpfInput) {
        cnpjCpfInput.addEventListener("input", function () {
            let value = this.value.replace(/\D/g, '');
            const isCnpj = value.length > 11;

            value = isCnpj ?
                value.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2}).*/, '$1.$2.$3/$4-$5') :
                value.replace(/^(\d{3})(\d{3})(\d{3})(\d{2}).*/, '$1.$2.$3-$4');

            this.value = value;
        });
    }

    if (phoneInput) {
        phoneInput.addEventListener("input", function () {
            let value = this.value.replace(/\D/g, '');
            value = value.replace(/^(\d{2})(\d{5})(\d{4}).*/, '($1) $2-$3');
            this.value = value;
        });
    }

    if(cepInput) {
        cepInput.addEventListener("input", function () {
            let value = this.value.replace(/\D/g, '');
            value = value.replace(/^(\d{5})(\d{3}).*/, '$1-$2');
            this.value = value;
        });
    }

    // ==== MÁSCARA BRL ====
    const formatBRL = (digits) => {
        // digits: string só com números (centavos no fim)
        if (!digits) return '';
        // garante pelo menos 3 chars para ter duas casas decimais
        digits = digits.replace(/^0+/, '');
        if (digits.length < 3) digits = digits.padStart(3, '0');
        const intVal = parseInt(digits, 10);
        const n = intVal / 100;
        return n.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    };

    const unmaskBRL = (str) => {
        if (!str) return 0;
        // remove tudo exceto dígitos, interpreta como centavos
        const d = String(str).replace(/\D/g, '');
        return d ? parseInt(d, 10) : 0; // em centavos
    };

    const applyMoneyMask = (el) => {
        // formata no load se vier valor pré-preenchido
        if (el.value && /\d/.test(el.value)) {
            el.value = formatBRL(el.value.replace(/\D/g, ''));
        }

        el.addEventListener('input', function () {
            const digits = this.value.replace(/\D/g, '');
            this.value = formatBRL(digits);
            // opcional: manter valor "limpo" em data attr (centavos)
            this.dataset.cents = String(unmaskBRL(this.value));
        });

        el.addEventListener('blur', function () {
            // força 0,00 se vazio
            if (!this.value.trim()) this.value = '';
        });

        // opcional: impedir entrada de letras
        el.addEventListener('keypress', function (e) {
            const char = String.fromCharCode(e.which || e.keyCode);
            if (!/[0-9]/.test(char)) e.preventDefault();
        });

        // opcional: ao focar, não selecionar tudo
        el.addEventListener('focus', function () {
            // nada especial; já está formatado
        });
    };

    // selecione por classe ou data-attr
    document.querySelectorAll('.money-brl, [data-mask="money-brl"]').forEach(applyMoneyMask);

    // helper global (se precisar ler valor em centavos no submit)
    window.getBrlCents = function (idOrEl) {
        const el = (typeof idOrEl === 'string') ? document.getElementById(idOrEl) : idOrEl;
        return el ? unmaskBRL(el.value) : 0;
    };
});
