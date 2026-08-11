<?php

require_once(__DIR__ . '/../../config.php');

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\presentation\CommerceCartPresenter;
use local_subscriptions\commerce\cart\service\CommerceCartRuntimeFactory;
use local_subscriptions\support\Region;
use local_subscriptions\url\UrlFactory;

\local_subscriptions\subscription_config::guard_public_access();

$pageurl = new moodle_url('/local/subscriptions/cart.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_url($pageurl);
$PAGE->set_pagelayout('standard');
$PAGE->add_body_class('commerce-chromeless-page');

$currency = strtoupper(optional_param('currency', '', PARAM_ALPHA));
if (!in_array($currency, ['EUR', 'RUB'], true)) {
    $currency = in_array(Region::detect_country(), ['RU', 'BY'], true) ? 'RUB' : 'EUR';
}
$availablecurrencies = \local_subscriptions\commerce\showroom\CommerceShowroomCurrencyResolver::active_currencies($DB);

$customerid = isloggedin() && !isguestuser() ? (int)$USER->id : 0;
if ($customerid > 0) {
    $guesttoken = (string)($SESSION->local_subscriptions_guest_checkout_token ?? '');
    if ($guesttoken !== '') {
        $guestsessions = new \local_subscriptions\commerce\checkout\guest\CommerceGuestCheckoutSessionRepository($DB);
        $guestsession = $guestsessions->find_by_token($guesttoken);
        if ($guestsession !== null
                && !$guestsession->is_expired()
                && $guestsession->get_status() === 'existing_account'
                && $guestsession->get_user_id() === $customerid) {
            $transferred = \local_subscriptions\commerce\checkout\guest\CommerceGuestCartTransferService::create()
                ->transfer(
                    $customerid,
                    $currency,
                    is_array($guestsession->get_metadata()['guest_cart_snapshot'] ?? null)
                        ? $guestsession->get_metadata()['guest_cart_snapshot']
                        : null
                );
            if ($transferred !== null) {
                $guestsessions->transition($guestsession, 'active', [
                    'metadatajson' => array_replace($guestsession->get_metadata(), [
                        'cart_transferred' => true,
                        'cart_uuid' => $transferred->get_uuid(),
                        'cart_item_count' => count($transferred->get_items()),
                        'authenticated_at' => time(),
                    ]),
                ]);
                redirect(new moodle_url('/local/subscriptions/commerce_checkout.php', [
                    'currency' => $currency,
                ]));
            }
        }
    }
}
$snapshot = CommerceCartRuntimeFactory::create()->snapshot($customerid, $currency, current_language());
$data = CommerceCartPresenter::present($snapshot, current_language());

$pageurl->param('currency', $currency);
$PAGE->set_url($pageurl);
$PAGE->set_title(get_string('commerce_cart_title', 'local_subscriptions'));
$PAGE->set_heading(get_string('commerce_cart_title', 'local_subscriptions'));
$PAGE->requires->css(new moodle_url('/local/subscriptions/styles/storefront.css'));


$steps = [
    [
        'number' => 1,
        'label' => get_string('commerce_checkout_step_cart', 'local_subscriptions'),
        'state' => 'is-current',
        'current' => true,
        'clickable' => false,
        'url' => '',
    ],
    [
        'number' => 2,
        'label' => get_string('commerce_checkout_step_review', 'local_subscriptions'),
        'state' => '',
        'current' => false,
        'clickable' => false,
        'url' => '',
    ],
    [
        'number' => 3,
        'label' => get_string('commerce_checkout_step_payment', 'local_subscriptions'),
        'state' => '',
        'current' => false,
        'clickable' => false,
        'url' => '',
    ],
    [
        'number' => 4,
        'label' => get_string('commerce_checkout_step_confirmation', 'local_subscriptions'),
        'state' => '',
        'current' => false,
        'clickable' => false,
        'url' => '',
    ],
];

$switchreport = (array)($SESSION->local_subscriptions_cart_currency_switch_report ?? []);
unset($SESSION->local_subscriptions_cart_currency_switch_report);
$removedlabels = array_values(array_filter(array_map('strval', (array)($switchreport['removedlabels'] ?? []))));
$currencyreport = [
    'visible' => $switchreport !== [],
    'hasremoved' => $removedlabels !== [],
    'removedtext' => $removedlabels !== [] ? get_string('commerce_cart_currency_removed_items', 'local_subscriptions', implode(', ', $removedlabels)) : '',
    'promotionremoved' => !empty($switchreport['promotionremoved']),
    'promotionremovedtext' => get_string('commerce_cart_currency_promotion_removed', 'local_subscriptions'),
    'success' => $switchreport !== [],
    'successtext' => get_string('commerce_cart_currency_switched', 'local_subscriptions', $currency),
];

$data += [
    'title' => get_string('commerce_cart_title', 'local_subscriptions'),
    'currencyreport' => $currencyreport,
    'currencyswitchlabel' => get_string('commerce_cart_currency_switch', 'local_subscriptions'),
    'currencyswitchhelp' => get_string('commerce_cart_currency_switch_help', 'local_subscriptions'),
    'currencyswitchaction' => (new moodle_url('/local/subscriptions/cart_action.php'))->out(false),
    'currencies' => array_map(static function(string $code) use ($currency): array {
        return ['value' => $code, 'label' => \local_subscriptions\currency\CommerceCurrencyLabelFormatter::format($code), 'selected' => $currency === $code];
    }, $availablecurrencies),
    'stepslabel' => get_string('commerce_checkout_steps_label', 'local_subscriptions'),
    'steps' => $steps,
    'emptytitle' => get_string('commerce_cart_empty_title', 'local_subscriptions'),
    'emptytext' => get_string('commerce_cart_empty_text', 'local_subscriptions'),
    'continueshoppingurl' => UrlFactory::digital_catalog(['currency' => $currency])->out(false),
    'continueshoppinglabel' => get_string('commerce_cart_continue_shopping', 'local_subscriptions'),
    'clearaction' => (new moodle_url('/local/subscriptions/cart_action.php'))->out(false),
    'clearlabel' => get_string('commerce_cart_clear', 'local_subscriptions'),
    'detailedcartprintlabel' => get_string(
        'commerce_cart_print_detailed',
        'local_subscriptions'
    ),
    'detailedcartprinturl' => (new moodle_url(
        '/local/subscriptions/cart_print.php',
        ['currency' => $currency, 'return' => 'cart']
    ))->out(false),
    'clearconfirm' => get_string('commerce_cart_clear_confirm', 'local_subscriptions'),
    'clearcancellabel' => get_string('cancel'),
    'clearconfirmlabel' => get_string('commerce_cart_clear_confirm_action', 'local_subscriptions'),
    'checkoutlabel' => get_string('commerce_cart_checkout', 'local_subscriptions'),
    'checkouturl' => (new moodle_url('/local/subscriptions/commerce_checkout.php', [
        'currency' => $currency,
        'flow' => \local_subscriptions\commerce\checkout\flow\CommercePurchaseFlow::CART,
    ]))->out(false),
    'checkoutdisabled' => !$data['hasitems'],
    'removeaction' => (new moodle_url('/local/subscriptions/cart_action.php'))->out(false),
    'returnurl' => $pageurl->out(false),
    'sesskey' => sesskey(),
    'quantitylabel' => get_string('commerce_cart_quantity', 'local_subscriptions'),
    'unitpricelabel' => get_string('commerce_cart_unit_price', 'local_subscriptions'),
    'subtotalLabel' => get_string('commerce_cart_subtotal', 'local_subscriptions'),
    'listtotallabel' => get_string('commerce_cart_list_total', 'local_subscriptions'),
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
    'discountlabel' => get_string('commerce_cart_discount', 'local_subscriptions'),
    'totallabel' => get_string('commerce_cart_total_ttc', 'local_subscriptions'),
    'viewproductlabel' => get_string('commerce_cart_view_product', 'local_subscriptions'),
    'paymentsecurelabel' => get_string('commerce_cart_payment_secure', 'local_subscriptions'),
    'instantaccesslabel' => get_string('commerce_cart_instant_access', 'local_subscriptions'),
    'stripeiconurl' => (new moodle_url('/local/subscriptions/pix/email/stripe.png'))->out(false),
    'alfaiconurl' => (new moodle_url('/local/subscriptions/pix/email/alfa.png'))->out(false),
    'visaiconurl' => (new moodle_url('/local/subscriptions/pix/email/visa.png'))->out(false),
    'mastercardiconurl' => (new moodle_url('/local/subscriptions/pix/email/mastercard.png'))->out(false),
    'removelabel' => get_string('commerce_cart_remove', 'local_subscriptions'),
    'updatelabel' => get_string('commerce_cart_update', 'local_subscriptions'),
    'promocodelabel' => get_string('commerce_cart_promo_code', 'local_subscriptions'),
    'promocodeplaceholder' => get_string('commerce_cart_promo_placeholder', 'local_subscriptions'),
    'promoapplylabel' => get_string('commerce_cart_promo_apply', 'local_subscriptions'),
    'promoremovelabel' => get_string('commerce_cart_promo_remove', 'local_subscriptions'),
];

if ($notice = optional_param('cartnotice', '', PARAM_ALPHANUMEXT)) {
    $stringkey = 'commerce_cart_message_' . $notice;
    if (get_string_manager()->string_exists($stringkey, 'local_subscriptions')) {
        $message = get_string($stringkey, 'local_subscriptions');
        if ($notice === 'error') {
            \core\notification::error($message);
        } else if (in_array($notice, ['item_not_found', 'unchanged', 'promotion_not_found', 'promotion_expired', 'promotion_inactive', 'promotion_not_started', 'promotion_currency_mismatch', 'promotion_minimum_cart_not_reached', 'promotion_no_eligible_product', 'promotion_global_usage_limit_reached', 'promotion_user_usage_limit_reached', 'promotion_code_required'], true)) {
            \core\notification::warning($message);
        } else {
            \core\notification::success($message);
        }
    }
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_subscriptions/cart/page', $data);
echo $OUTPUT->footer();
