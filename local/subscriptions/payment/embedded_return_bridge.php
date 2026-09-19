<?php

declare(strict_types=1);

/*
 * H12.5.6 lightweight embedded-return bridge.
 *
 * This file intentionally does not bootstrap Moodle. It never decides whether
 * payment succeeded. It only paints a same-origin splash immediately, then
 * forwards Alfa's query parameters to the fixed Moodle return endpoint where
 * the authoritative reconciliation still happens.
 */

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$target = isset($_GET['target'])
    ? (string)$_GET['target']
    : 'return.php?embedded=1';

unset($_GET['target']);

$parts = parse_url($target);
$path = (string)($parts['path'] ?? '');

if (!str_ends_with($path, '/local/subscriptions/payment/return.php')) {
    $target = 'return.php?embedded=1';
}

$query = http_build_query(
    $_GET,
    '',
    '&',
    PHP_QUERY_RFC3986
);

if ($query !== '') {
    $target .= (
        str_contains($target, '?')
            ? '&'
            : '?'
    ) . $query;
}

$targetjson = json_encode(
    $target,
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
);

?><!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Payment</title>
    <style>
        html,body{margin:0;min-height:100%;background:#fff;font-family:Arial,sans-serif}
        .s{position:fixed;inset:0;display:flex;align-items:center;justify-content:center;padding:2rem;text-align:center}
        .sp{width:2.25rem;height:2.25rem;margin:0 auto 1rem;border:.22rem solid #e9ecef;border-top-color:#e83e8c;border-radius:50%;animation:r .75s linear infinite}
        .s h1{margin:0 0 .45rem;font-size:1.25rem}.s p{margin:0;color:#6c757d}
        @keyframes r{to{transform:rotate(360deg)}}
    </style>
</head>
<body>
<div class="s">
    <div>
        <div class="sp" aria-hidden="true"></div>
        <h1 id="t">Payment confirmed</h1>
        <p id="m">Finalizing your access…</p>
    </div>
</div>
<script>
(function() {
    const target = <?= $targetjson ?>;
    const copy = {
        fr: ['Paiement confirmé', 'Nous finalisons votre accès…'],
        ru: ['Оплата подтверждена', 'Завершаем предоставление доступа…'],
        en: ['Payment confirmed', 'We are finalizing your access…']
    };

    let lang = 'en';

    try {
        const parentLang = String(
            window.top.document.documentElement.lang || ''
        ).toLowerCase();

        if (parentLang.startsWith('fr')) {
            lang = 'fr';
        } else if (parentLang.startsWith('ru')) {
            lang = 'ru';
        }
    } catch (e) {}

    document.getElementById('t').textContent = copy[lang][0];
    document.getElementById('m').textContent = copy[lang][1];

    try {
        if (window.top && window.top !== window) {
            const d = window.top.document;
            let overlay = d.getElementById(
                'campus-payment-finalizing-overlay'
            );

            if (!overlay) {
                overlay = d.createElement('div');
                overlay.id = 'campus-payment-finalizing-overlay';
                overlay.setAttribute('aria-live', 'polite');
                overlay.style.cssText =
                    'position:fixed;inset:0;z-index:2147483647;' +
                    'display:flex;align-items:center;justify-content:center;' +
                    'padding:2rem;background:#fff;color:#212529;' +
                    'text-align:center;font-family:inherit';

                const style = d.createElement('style');
                style.textContent =
                    '@keyframes campusPaymentSpin{to{transform:rotate(360deg)}}';
                d.head.appendChild(style);

                const box = d.createElement('div');
                const spinner = d.createElement('div');
                const title = d.createElement('h2');
                const message = d.createElement('p');

                spinner.style.cssText =
                    'width:2.25rem;height:2.25rem;margin:0 auto 1rem;' +
                    'border:.22rem solid #e9ecef;border-top-color:#e83e8c;' +
                    'border-radius:50%;animation:campusPaymentSpin .75s linear infinite';

                title.textContent = copy[lang][0];
                title.style.cssText =
                    'margin:0 0 .45rem;font-size:1.25rem';

                message.textContent = copy[lang][1];
                message.style.cssText =
                    'margin:0;color:#6c757d';

                box.appendChild(spinner);
                box.appendChild(title);
                box.appendChild(message);
                overlay.appendChild(box);
                d.body.appendChild(overlay);
            }
        }
    } catch (e) {}

    // Keep the parent checkout and its splash in place. Only this iframe
    // proceeds to the authoritative Moodle reconciliation endpoint.
    window.location.replace(target);
})();
</script>
</body>
</html>
