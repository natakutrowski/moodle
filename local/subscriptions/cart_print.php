<?php

require_once(__DIR__ . '/../../config.php');

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\presentation\CommerceCartPresenter;
use local_subscriptions\commerce\cart\service\CommerceCartRuntimeFactory;
use local_subscriptions\support\Region;

\local_subscriptions\subscription_config::guard_public_access();

$currency = strtoupper(optional_param('currency', '', PARAM_ALPHA));
if (!in_array($currency, ['EUR', 'RUB'], true)) {
    $currency = in_array(Region::detect_country(), ['RU', 'BY'], true)
        ? 'RUB'
        : 'EUR';
}
$return = optional_param('return', 'cart', PARAM_ALPHA);
$customerid = isloggedin() && !isguestuser() ? (int)$USER->id : 0;

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/subscriptions/cart_print.php', [
    'currency' => $currency,
    'return' => $return,
]));
$PAGE->set_pagelayout('print');
$PAGE->set_title(get_string(
    'commerce_cart_print_detailed',
    'local_subscriptions'
));
$PAGE->set_heading(get_string(
    'commerce_cart_print_detailed',
    'local_subscriptions'
));
$PAGE->requires->css(new moodle_url(
    '/local/subscriptions/styles/storefront.css'
));

$snapshot = CommerceCartRuntimeFactory::create()->snapshot(
    $customerid,
    $currency,
    current_language()
);
$data = CommerceCartPresenter::present(
    $snapshot,
    current_language()
);

$backurl = $return === 'checkout'
    ? new moodle_url('/local/subscriptions/commerce_checkout.php', [
        'currency' => $currency,
    ])
    : new moodle_url('/local/subscriptions/cart.php', [
        'currency' => $currency,
    ]);

$data += [
    'title' => get_string(
        'commerce_cart_print_detailed',
        'local_subscriptions'
    ),
    'subtitle' => get_string(
        'commerce_cart_print_detailed_subtitle',
        'local_subscriptions'
    ),
    'generatedlabel' => get_string(
        'commerce_cart_print_generated',
        'local_subscriptions',
        userdate(time())
    ),
    'printlabel' => get_string(
        'commerce_checkout_print_summary',
        'local_subscriptions'
    ),
    'backlabel' => get_string('back'),
    'backurl' => $backurl->out(false),
    'listtotallabel' => get_string(
        'commerce_cart_list_total',
        'local_subscriptions'
    ),
    'productpromotiontotallabel' => get_string(
        'commerce_cart_product_promotions_total',
        'local_subscriptions'
    ),
    'trialdiscounttotallabel' => get_string(
        'commerce_cart_trial_discount_total',
        'local_subscriptions'
    ),
    'upgradecredittotallabel' => get_string(
        'commerce_cart_upgrade_credit_total',
        'local_subscriptions'
    ),
    'totalreductionslabel' => get_string(
        'commerce_cart_total_reductions',
        'local_subscriptions'
    ),
    'totallabel' => get_string(
        'commerce_cart_total_ttc',
        'local_subscriptions'
    ),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template(
    'local_subscriptions/cart/print',
    $data
);
echo $OUTPUT->footer();
