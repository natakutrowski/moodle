<?php

declare(strict_types=1);

require_once dirname(__DIR__, 4) . '/config.php';

require_login();
require_capability('moodle/site:config', \context_system::instance());

global $OUTPUT, $PAGE, $SITE;

$mode = optional_param('mode', 'return', PARAM_ALPHA);
if (!in_array($mode, ['return', 'outbound'], true)) {
    $mode = 'return';
}

$PAGE->set_context(\context_system::instance());
$PAGE->set_url(new \moodle_url(
    '/local/subscriptions/admin/commerce/payment-splash-preview.php',
    ['mode' => $mode]
));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('commerce_payment_splash_preview_title', 'local_subscriptions'));
$PAGE->set_heading(format_string($SITE->fullname));

if ($mode === 'return') {
    $PAGE->requires->css(
        new \moodle_url('/local/subscriptions/styles/alfa_payment_confirmation.css')
    );
} else {
    $PAGE->requires->css(
        new \moodle_url('/local/subscriptions/styles/payment_provider_transition.css')
    );
}

echo $OUTPUT->header();

echo \html_writer::div(
    \html_writer::link(
        new \moodle_url(
            '/local/subscriptions/admin/commerce/payment-splash-preview.php',
            ['mode' => 'outbound']
        ),
        get_string('commerce_payment_splash_preview_outbound', 'local_subscriptions'),
        ['class' => 'btn btn-outline-primary me-2']
    ) .
    \html_writer::link(
        new \moodle_url(
            '/local/subscriptions/admin/commerce/payment-splash-preview.php',
            ['mode' => 'return']
        ),
        get_string('commerce_payment_splash_preview_return', 'local_subscriptions'),
        ['class' => 'btn btn-outline-primary']
    ),
    'mb-4'
);

if ($mode === 'outbound') {
    echo \html_writer::start_div(
        'payment-provider-transition is-visible',
        [
            'style' => 'position:relative;min-height:72vh;',
            'aria-hidden' => 'false',
        ]
    );
    echo \html_writer::start_div('payment-provider-transition__glass');
    echo \html_writer::start_div('payment-provider-transition__hourglass-wrap');
    echo \html_writer::div('', 'payment-provider-transition__orbit');
    echo \html_writer::tag(
        'div',
        \html_writer::tag('i', '', ['class' => 'fa-solid fa-hourglass-half']),
        ['class' => 'payment-provider-transition__hourglass']
    );
    echo \html_writer::end_div();

    echo \html_writer::tag(
        'h2',
        get_string('commerce_provider_transition_title', 'local_subscriptions'),
        ['class' => 'payment-provider-transition__title']
    );
    echo \html_writer::tag(
        'p',
        get_string('commerce_provider_transition_message', 'local_subscriptions'),
        ['class' => 'payment-provider-transition__message']
    );
    echo \html_writer::tag(
        'div',
        \html_writer::tag('i', '', ['class' => 'fa-solid fa-shield-halved'])
            . ' '
            . get_string('commerce_provider_transition_alfa', 'local_subscriptions'),
        ['class' => 'payment-provider-transition__provider']
    );
    echo \html_writer::start_div('payment-provider-transition__security');
    echo \html_writer::tag(
        'span',
        \html_writer::tag('i', '', ['class' => 'fa-solid fa-lock']),
        ['class' => 'payment-provider-transition__security-icon']
    );
    echo \html_writer::tag(
        'span',
        \html_writer::tag(
            'strong',
            get_string('commerce_provider_transition_security_title', 'local_subscriptions')
        ) .
        \html_writer::tag(
            'small',
            get_string('commerce_provider_transition_security_message', 'local_subscriptions')
        )
    );
    echo \html_writer::end_div();
    echo \html_writer::end_div();
    echo \html_writer::end_div();
} else {
    echo \html_writer::start_div(
        'alfa-payment-confirmation',
        [
            'style' => 'position:relative;min-height:72vh;',
            'aria-hidden' => 'false',
        ]
    );
    echo \html_writer::start_div('alfa-payment-confirmation__glass');
    echo \html_writer::start_div('alfa-payment-confirmation__hourglass-wrap');
    echo \html_writer::div('', 'alfa-payment-confirmation__orbit');
    echo \html_writer::tag(
        'div',
        \html_writer::tag('i', '', ['class' => 'fa-solid fa-hourglass-half']),
        ['class' => 'alfa-payment-confirmation__hourglass']
    );
    echo \html_writer::end_div();
    echo \html_writer::tag(
        'h1',
        get_string('commerce_alfa_confirmation_title', 'local_subscriptions'),
        ['class' => 'alfa-payment-confirmation__title']
    );
    echo \html_writer::tag(
        'p',
        get_string('commerce_alfa_confirmation_message', 'local_subscriptions'),
        ['class' => 'alfa-payment-confirmation__message']
    );
    echo \html_writer::div(
        '',
        'alfa-payment-confirmation__progress',
        ['style' => '--alfa-progress:58%;']
    );
    echo \html_writer::start_div('alfa-payment-confirmation__security');
    echo \html_writer::tag(
        'span',
        \html_writer::tag('i', '', ['class' => 'fa-solid fa-lock']),
        ['class' => 'alfa-payment-confirmation__security-icon']
    );
    echo \html_writer::tag(
        'span',
        \html_writer::tag(
            'strong',
            get_string('commerce_alfa_confirmation_security_title', 'local_subscriptions')
        )
        . \html_writer::tag(
            'span',
            get_string('commerce_alfa_confirmation_security_message', 'local_subscriptions')
        )
    );
    echo \html_writer::end_div();
    echo \html_writer::end_div();
    echo \html_writer::end_div();
}

echo $OUTPUT->footer();
