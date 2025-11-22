export function alert(c, m) {
    new Noty({
        theme: 'semanticui',
        text: m,
        type: c,
        layout: 'topRight',
        timeout: 5000,
        progressBar: true,
    }).show();
}

export function alertError(e) {
    var errors = e;
    for (var field in errors) {
        var message = errors[field][0];
        new Noty({
            theme: 'semanticui',
            text: message,
            type: 'error',
            layout: 'topRight',
            timeout: 5000,
            progressBar: true,
        }).show();
        break;
    }
}
