<?php
require_once(__DIR__ . '/../../config.php');

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\digital\product_manager;
use local_subscriptions\url\UrlFactory;

\local_subscriptions\subscription_config::guard_public_access();

$slug = required_param('p', PARAM_ALPHANUMEXT);

$product = product_manager::get_localized_product_by_slug($slug, current_language(), true);

if (!$product) {
    redirect(
        UrlFactory::digital_catalog(['missingproduct' => 1]),
        get_string('digital_product_not_found_redirect', 'local_subscriptions'),
        0,
        \core\output\notification::NOTIFY_WARNING
    );
}

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/subscriptions/digital_product.php', ['p' => $slug]));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(format_string($product->localized_title));
$PAGE->set_heading(format_string($product->localized_title));

echo $OUTPUT->header();

echo html_writer::start_div('container mt-2 mb-5');
echo html_writer::start_div('row justify-content-center');
echo html_writer::start_div('col-lg-10');

echo html_writer::start_div('card border-0 shadow-sm overflow-hidden');

echo html_writer::start_div('card-body p-0');

// HERO.
echo html_writer::start_div('row g-0 align-items-center');

echo html_writer::start_div('col-lg-6 text-center p-2 p-md-3');

if (!empty($product->coverimage)) {
    $coverpath = $CFG->dirroot . '/local/subscriptions/pix/cover/' . $product->coverimage;

    if (file_exists($coverpath)) {
        $coverurl = $CFG->wwwroot . '/local/subscriptions/pix/cover/' . rawurlencode($product->coverimage);

        echo html_writer::link(
            '#',
            html_writer::empty_tag('img', [
                'src' => $coverurl,
                'alt' => '',
                'style' => '
                    width:100%;
                    max-width:700px;
                    border-radius:18px;
                    box-shadow:0 12px 20px rgba(0,0,0,0.15);
                    cursor:pointer;
                    display:block;
                    margin:0 auto;
                ',
            ]),
            [
                'data-bs-toggle' => 'modal',
                'data-bs-target' => '#digitalCoverModal',
                'role' => 'button',
                'style' => 'display:block;text-decoration:none;',
            ]
        );

        echo html_writer::tag(
            'p',
            get_string('digital_cover_zoom_hint', 'local_subscriptions'),
            [
                'class' => 'text-muted mt-2 mb-0 text-center',
                'style' => '
                    font-size:0.72rem;
                    opacity:0.75;
                ',
            ]
        );
    }
}

echo html_writer::end_div();

echo html_writer::start_div('col-lg-6 p-3 p-md-4');

echo html_writer::tag('div',
    get_string('digital_pdf_badge', 'local_subscriptions'),
    ['class' => 'badge bg-primary mb-3']
);

echo html_writer::tag(
    'h2',
    format_text($product->localized_title, FORMAT_HTML, [
        'trusted' => false,
        'noclean' => false,
        'para' => false,
    ]),
    ['class' => 'display-8 fw-bold mb-3']
);

echo html_writer::tag('p',
    format_text($product->sales_intro, FORMAT_PLAIN),
    ['class' => 'lead mb-4']
);


echo html_writer::tag('p',
    !empty($product->access_note)
    ? format_text($product->access_note, FORMAT_HTML, ['para' => false])
    : get_string('digital_sales_lifetime_access', 'local_subscriptions'),
    ['class' => 'text-success fw-semibold mb-0']
);

echo html_writer::end_div();

echo html_writer::end_div();

// CONTENT.
echo html_writer::start_div('p-4 p-md-5');

echo html_writer::start_div('row g-4');

// LEFT COLUMN.
echo html_writer::start_div('col-lg-7');

echo html_writer::tag('h4',
    !empty($product->content_title)
    ? format_text($product->content_title, FORMAT_HTML, ['para' => false])
    : get_string('digital_sales_content_title', 'local_subscriptions'),
    ['class' => 'h5 mb-3']
);

echo html_writer::start_tag('ul', ['class' => 'mb-5']);

foreach (product_manager::lines_from_text($product->content_items) as $item) {
    echo html_writer::tag('li', s($item), ['class' => 'mb-2']);
}

echo html_writer::end_tag('ul');

echo html_writer::tag('h4',
    !empty($product->forwho_title)
    ? format_text($product->forwho_title, FORMAT_HTML, ['para' => false])
    : get_string('digital_sales_forwho_title', 'local_subscriptions'),
    ['class' => 'h5 mb-3']
);

echo html_writer::start_tag('ul');

foreach (product_manager::lines_from_text($product->forwho_items) as $item) {
    echo html_writer::tag('li', s($item), ['class' => 'mb-2']);
}

echo html_writer::end_tag('ul');

echo html_writer::end_div();

// RIGHT COLUMN.
echo html_writer::start_div('col-lg-5');

echo html_writer::start_div('border rounded-4 p-4 bg-light');

echo html_writer::tag('h2',
    !empty($product->buy_title)
    ? format_text($product->buy_title, FORMAT_HTML, ['para' => false])
    : get_string('digital_pdf_buy_title', 'local_subscriptions'),
    ['class' => 'h3 mb-4']
);

echo html_writer::start_tag('form', [
    'method' => 'post',
    'id' => 'digital-product-checkout-form',
    'action' => UrlFactory::digital_checkout()->out(false),
]);

echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'sesskey',
    'value' => sesskey(),
]);

echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'slug',
    'value' => $product->slug,
]);

echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'uilang',
    'value' => current_language(),
]);

echo html_writer::tag('label',
    get_string('digital_pdf_firstname', 'local_subscriptions'),
    ['class' => 'form-label fw-semibold']
);

echo html_writer::empty_tag('input', [
    'type' => 'text',
    'name' => 'firstname',
    'id' => 'digital-firstname',
    'class' => 'form-control mb-3',
    'required' => 'required',
]);

echo html_writer::tag('label',
    get_string('digital_pdf_lastname', 'local_subscriptions'),
    ['class' => 'form-label fw-semibold']
);

echo html_writer::empty_tag('input', [
    'type' => 'text',
    'name' => 'lastname',
    'id' => 'digital-lastname',
    'class' => 'form-control mb-3',
    'required' => 'required',
]);

echo html_writer::tag('label',
    get_string('digital_pdf_email', 'local_subscriptions'),
    ['class' => 'form-label fw-semibold']
);

echo html_writer::empty_tag('input', [
    'type' => 'email',
    'name' => 'email',
    'id' => 'digital-email',
    'class' => 'form-control mb-2',
    'required' => 'required',
]);

echo html_writer::tag('div',
    get_string('digital_pdf_email_help', 'local_subscriptions'),
    ['class' => 'form-text mb-4']
);

echo html_writer::tag('button',
    get_string('digital_pdf_buy_eur', 'local_subscriptions', [
        'price' => number_format((float)$product->price_eur, 2, ',', ' '),
    ]),
    [
        'type' => 'submit',
        'name' => 'currency',
        'id' => 'digital-buy-eur',
        'value' => 'EUR',
        'class' => 'btn btn-primary btn-lg w-100 mb-3',
        'disabled' => 'disabled',
    ]
);

$rubconfirm = get_string('digital_rub_confirm_vpn', 'local_subscriptions');

echo html_writer::tag('button',
    get_string('digital_pdf_buy_rub', 'local_subscriptions', [
        'price' => number_format((float)$product->price_rub, 0, ',', ' '),
    ]),
    [
        'type' => 'submit',
        'name' => 'currency',
        'value' => 'RUB',
        'id' => 'digital-buy-rub',
        'class' => 'btn btn-outline-primary btn-lg w-100',
        'disabled' => 'disabled',
        'onclick' => "return confirm(" . json_encode($rubconfirm, JSON_UNESCAPED_UNICODE) . ");",
    ]
);

echo html_writer::end_tag('form');

echo html_writer::start_div('digital-checkout-reassurance mt-4');

$checks = [
    get_string('digital_reassurance_instant', 'local_subscriptions'),
    get_string('digital_reassurance_versions', 'local_subscriptions'),
    get_string('digital_reassurance_email', 'local_subscriptions'),
    get_string('digital_reassurance_nocampus', 'local_subscriptions'),
];

foreach ($checks as $check) {
    echo html_writer::tag('div', '✔ ' . $check, [
        'class' => 'small mb-2',
        'style' => 'color:#198754;font-weight:500;',
    ]);
}

echo html_writer::tag('hr', '', ['class' => 'my-3']);

echo html_writer::tag(
    'div',
    get_string('digital_reassurance_secure', 'local_subscriptions'),
    ['class' => 'small text-muted mb-2']
);

echo html_writer::start_div('d-flex align-items-center gap-2 flex-wrap');

$paymenticons = [
    'stripe' => 'stripe.png',
    'alfa' => 'alfa.png',
    'visa' => 'visa.png',
    'mastercard' => 'mastercard.png',
];

foreach ($paymenticons as $label => $file) {
    $path = $CFG->dirroot . '/local/subscriptions/pix/email/' . $file;

    if (file_exists($path)) {
        echo html_writer::empty_tag('img', [
            'src' => $CFG->wwwroot . '/local/subscriptions/pix/email/' . $file,
            'alt' => $label,
            'title' => $label,
            'style' => 'height:26px;width:auto;object-fit:contain;',
        ]);
    } else {
        echo html_writer::tag('span', strtoupper($label), [
            'class' => 'badge bg-light text-muted border',
        ]);
    }
}

echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_div();

echo html_writer::end_div();

echo html_writer::end_div();

echo html_writer::end_div();

echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

if (!empty($coverurl)) {

    echo html_writer::start_div('modal fade', [
        'id' => 'digitalCoverModal',
        'tabindex' => '-1',
        'aria-hidden' => 'true',
    ]);

    echo html_writer::start_div('modal-dialog modal-dialog-centered m-0', [
        'style' => '
            width:auto;
            max-width:none;
            display:flex;
            align-items:center;
            justify-content:center;
            min-height:100vh;
            margin:auto;
            pointer-events:none;
        ',
    ]);

    echo html_writer::start_div('modal-content border-0', [
        'style' => '
            width:auto;
            background:#f8f9fb;
            border-radius:24px;
            box-shadow:0 20px 80px rgba(0,0,0,0.18);
            pointer-events:auto;
        ',
    ]);

    echo html_writer::start_div('modal-body text-center position-relative', [
        'style' => '
            padding:20px;
        ',
    ]);

    echo html_writer::tag('button', '×', [
        'type' => 'button',
        'class' => 'btn btn-light position-absolute top-0 end-0 m-3 rounded-circle',
        'data-bs-dismiss' => 'modal',
        'aria-label' => get_string('close', 'local_subscriptions'),
        'style' => '
            z-index:10;
            width:46px;
            height:46px;
            font-size:30px;
            line-height:20px;
            box-shadow:0 4px 20px rgba(0,0,0,0.12);
        ',
    ]);

    echo html_writer::empty_tag('img', [
        'src' => $coverurl,
        'alt' => '',
        'style' => '
            display:block;

            width:auto;
            height:auto;

            max-width:min(95vw, 1400px);
            max-height:95vh;

            border-radius:18px;

            background:white;
        ',
    ]);

    echo html_writer::end_div();

    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();
}

echo html_writer::script("
(function () {
    var firstname = document.getElementById('digital-firstname');
    var lastname = document.getElementById('digital-lastname');
    var email = document.getElementById('digital-email');
    var buyEur = document.getElementById('digital-buy-eur');
    var buyRub = document.getElementById('digital-buy-rub');

    if (!firstname || !lastname || !email || !buyEur || !buyRub) {
        return;
    }

    function isValidEmail(value) {
        return /^[^\\s@]+@[^\\s@]+\\.[^\\s@]+$/.test(value);
    }

    function updateButtons() {
        var ok = firstname.value.trim() !== ''
            && lastname.value.trim() !== ''
            && isValidEmail(email.value.trim());

        buyEur.disabled = !ok;
        buyRub.disabled = !ok;
    }

    firstname.addEventListener('input', updateButtons);
    lastname.addEventListener('input', updateButtons);
    email.addEventListener('input', updateButtons);
    email.addEventListener('change', updateButtons);

    updateButtons();
})();
");

echo html_writer::script("
(function () {

    const fields = [
        'digital-firstname',
        'digital-lastname',
        'digital-email'
    ];

    fields.forEach(function(id) {

        const el = document.getElementById(id);

        if (!el) {
            return;
        }

        const key = 'campusfr_' + id;

        const saved = localStorage.getItem(key);

        if (saved && el.value === '') {
            el.value = saved;
        }

        el.addEventListener('input', function() {
            localStorage.setItem(key, el.value);
        });
    });

})();
");

echo html_writer::script("
(function () {

    const form = document.getElementById('digital-product-checkout-form');

    if (!form) {
        return;
    }

    form.addEventListener('submit', function() {

        const overlay = document.createElement('div');

        overlay.innerHTML = `
            <div style=\"
                position:fixed;
                inset:0;
                background:rgba(255,255,255,0.92);
                z-index:99999;
                display:flex;
                align-items:center;
                justify-content:center;
                flex-direction:column;
                font-family:inherit;
            \">
                <div class=\"spinner-border text-primary mb-4\"></div>

                <div style=\"
                    font-size:1.2rem;
                    font-weight:600;
                    margin-bottom:10px;
                \">
                    " . addslashes(get_string('digital_redirecting_payment', 'local_subscriptions')) . "
                </div>

                <div style=\"
                    color:#666;
                    text-align:center;
                    max-width:420px;
                \">
                    " . addslashes(get_string('digital_redirecting_payment_desc', 'local_subscriptions')) . "
                </div>
            </div>
        `;

        document.body.appendChild(overlay);
    });

})();
");

echo $OUTPUT->footer();