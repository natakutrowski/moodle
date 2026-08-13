<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/classes/commerce/tracking/CommerceTrackedActionUrl.php');

use local_subscriptions\commerce\checkout\flow\CommercePurchaseFlow;
use local_subscriptions\commerce\checkout\guest\CommerceGuestCheckoutSessionRepository;
use local_subscriptions\commerce\cart\lifecycle\CommerceCartLifecycleService;
use local_subscriptions\commerce\catalog\visual\CommerceProductVisualAuditService;
use local_subscriptions\commerce\catalog\presentation\CommerceProductDisplayText;
use local_subscriptions\commerce\order\presentation\CommerceBundleComponentResolver;
use local_subscriptions\commerce\order\presentation\CommerceOrderPresentationAccessDeniedException;
use local_subscriptions\commerce\order\presentation\CommerceOrderPresentationService;
use local_subscriptions\commerce\order\presentation\CommercePostPaymentStateResolver;
use local_subscriptions\commerce\order\reference\CommercePublicOrderReference;
use local_subscriptions\commerce\storefront\repository\CommerceStorefrontRepository;
use local_subscriptions\commerce\tracking\CommerceTrackedActionUrl;
use local_subscriptions\url\UrlFactory;

\local_subscriptions\subscription_config::guard_public_access();

global $DB, $OUTPUT, $PAGE, $SITE, $USER, $SESSION;

$reference = required_param('reference', PARAM_ALPHANUMEXT);
$result = optional_param('result', '', PARAM_ALPHA);
$code = optional_param('code', '', PARAM_ALPHANUMEXT);
$accountfinalised = optional_param('accountfinalised', 0, PARAM_BOOL);
$returnpaymentid = optional_param('paymentid', 0, PARAM_INT);
$lang = strtolower(substr(optional_param('lang', '', PARAM_ALPHANUMEXT), 0, 2));
if (in_array($lang, ['fr', 'en', 'ru'], true)) {
    $SESSION->lang = $lang;
    moodle_setlocale();
}

$service = CommerceOrderPresentationService::create();
$order = null;

try {
    if (isloggedin() && !isguestuser()) {
        $order = $service->find_for_user($reference, (int)$USER->id);
    } else {
        $guestsessions = new CommerceGuestCheckoutSessionRepository($DB);
        $guestsession = $guestsessions->find_by_purchase_reference($reference);
        $token = trim((string)($SESSION->local_subscriptions_guest_checkout_token ?? ''));
        if ($guestsession === null || $token === '' || !hash_equals($guestsession->get_token(), $token)) {
            throw new CommerceOrderPresentationAccessDeniedException('Guest Checkout session does not own this order.');
        }
        $order = $service->find_for_user($reference, (int)$guestsession->get_user_id());
    }
} catch (CommerceOrderPresentationAccessDeniedException $exception) {
    throw new moodle_exception('nopermissions', 'error');
}

if ($order === null) {
    throw new moodle_exception('commerce_i2_order_not_found', 'local_subscriptions');
}

$state = (new CommercePostPaymentStateResolver())->resolve($order, $result);

// A successful payment converts exactly the cart that was frozen into this purchase.
// The UUID check deliberately leaves any newer cart untouched.
if ($order->is_paid()) {
    $cartuuid = strtolower(trim((string)($order->metadata['cart_uuid'] ?? '')));
    $cartcustomerid = (int)($order->metadata['cart_customerid'] ?? ($order->userid ?? 0));
    $cartcurrency = strtoupper(trim((string)($order->metadata['cart_currency'] ?? $order->currency)));

    $cartcleared = CommerceCartLifecycleService::create()->clear_converted_cart(
        max(0, $cartcustomerid),
        $cartcurrency,
        $cartuuid
    );

    if (isset($guestsession) && $guestsession !== null) {
        $metadata = $guestsession->get_metadata();
        unset($metadata['guest_cart_snapshot']);
        $metadata['cart_converted'] = true;
        $metadata['cart_converted_at'] = time();
        $metadata['cart_converted_purchase'] = $reference;
        $metadata['cart_cleared_from_session'] = $cartcleared;
        $guestsessions->transition($guestsession, 'active', [
            'metadatajson' => $metadata,
        ]);
    }
}
$publicreference = (new CommercePublicOrderReference())->from_internal($order->reference, $order->timecreated);
$pageurl = UrlFactory::order_result(['reference' => $reference, 'result' => $result, 'code' => $code]);
$PAGE->set_url($pageurl);
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('commerce_i2_title_' . $state->code, 'local_subscriptions'));
$PAGE->set_heading(format_string($SITE->fullname));

$formatmoney = static function(int $minor, string $currency): string {
    return format_float($minor / 100, 2) . ' ' . strtoupper($currency);
};

$retryurl = new moodle_url('/local/subscriptions/cart.php', ['currency' => strtolower($order->currency)]);
$carturl = new moodle_url('/local/subscriptions/cart.php', ['currency' => strtolower($order->currency)]);
$mycampusurl = UrlFactory::my_campus();
$myordersurl = UrlFactory::my_purchases();
$mycoursesurl = UrlFactory::my_courses();
$resourcesurl = UrlFactory::my_digital_products();
$storefronturl = UrlFactory::storefront();
$storefrontproducts = CommerceStorefrontRepository::create($DB);
$supporturl = UrlFactory::support_for_order($reference);
$guestaccountnotice = null;
$requiresaccountfinalisation = false;
$accountactivationurl = null;
if (isset($guestsession) && $guestsession !== null) {
    $guestmetadata = $guestsession->get_metadata();
    $isprovisionalguest = ($guestmetadata['account_origin'] ?? '') === 'guest_checkout';
    $passwordisset = !empty($guestmetadata['password_set_at']);
    if ($isprovisionalguest && !$passwordisset) {
        $requiresaccountfinalisation = true;
        $accountactivationurl = new moodle_url('/local/subscriptions/guest_account_activation_start.php', [
            'reference' => $reference,
        ]);
        $guestaccountnotice = [
            'title' => get_string('commerce_guest_activation_result_title', 'local_subscriptions'),
            'message' => get_string('commerce_guest_activation_result_message', 'local_subscriptions'),
            'url' => $accountactivationurl,
            'label' => get_string('commerce_guest_activation_result_cta', 'local_subscriptions'),
            'icon' => 'fa-key',
            'provisional' => true,
        ];
    } else if (!$isprovisionalguest && !isloggedin()) {
        $guestaccountnotice = [
            'title' => get_string('commerce_guest_existing_account_result_title', 'local_subscriptions'),
            'message' => get_string('commerce_guest_existing_account_result_message', 'local_subscriptions'),
            'url' => new moodle_url('/login/index.php', [
                'wantsurl' => (new moodle_url('/local/subscriptions/order_details.php', [
                    'reference' => $reference,
                ]))->out(false),
            ]),
            'label' => get_string('login'),
            'icon' => 'fa-right-to-bracket',
            'provisional' => false,
        ];
    }
}
$vieworderdestination = new moodle_url('/local/subscriptions/order_details.php', [
    'reference' => $reference,
]);
$vieworderurl = CommerceTrackedActionUrl::build(
    $reference,
    'postpayment_view_order',
    'order_result',
    $vieworderdestination
);

$icons = [
    'success' => '✓',
    'processing' => '…',
    'pending' => '…',
    'failed' => '!',
    'cancelled' => '×',
    'unknown' => '?',
];

$confirmationstate = match ($state->code) {
    'success' => 'is-complete',
    'failed', 'cancelled' => 'is-failed',
    default => 'is-current',
};
$confirmationcurrent = $confirmationstate !== 'is-complete';

$purchaseflow = CommercePurchaseFlow::normalise((string)($order->metadata['purchase_flow'] ?? CommercePurchaseFlow::CART));
$steps = CommercePurchaseFlow::result_steps(
    $purchaseflow,
    $confirmationstate,
    $confirmationcurrent
);

$stepscontext = [
    'stepslabel' => get_string(
        'commerce_checkout_steps_label',
        'local_subscriptions'
    ),
    'steps' => $steps,
];

$showalfaconfirmationsplash = (
    $returnpaymentid > 0
    && strtolower((string)$order->provider) === 'alfa'
    && $state->code === 'pending'
);

if ($showalfaconfirmationsplash) {
    $PAGE->requires->css(
        new moodle_url('/local/subscriptions/styles/alfa_payment_confirmation.css')
    );
    $PAGE->requires->js_call_amd(
        'local_subscriptions/alfa_payment_confirmation',
        'init'
    );
}

$PAGE->requires->css(
    new moodle_url('/local/subscriptions/styles/order_result.css')
);
$PAGE->requires->css(new moodle_url('/local/subscriptions/styles/provisional_account.css'));
$PAGE->requires->js_call_amd('local_subscriptions/guest_checkout_security', 'init');

echo $OUTPUT->header();

if ($showalfaconfirmationsplash) {
    $successurl = UrlFactory::order_result([
        'reference' => $reference,
        'result' => 'success',
        'lang' => $lang,
    ]);
    $failureurl = UrlFactory::order_result([
        'reference' => $reference,
        'result' => 'failure',
        'code' => 'alfa_reconciliation',
        'lang' => $lang,
    ]);

    echo html_writer::start_div(
        'alfa-payment-confirmation',
        [
            'data-alfa-payment-confirmation' => '1',
            'data-endpoint' => (new moodle_url(
                '/local/subscriptions/payment/alfa_return_poll.php'
            ))->out(false),
            'data-paymentid' => (string)$returnpaymentid,
            'data-reference' => $reference,
            'data-sesskey' => sesskey(),
            'data-success-url' => $successurl->out(false),
            'data-failure-url' => $failureurl->out(false),
            'data-fast-attempts' => '12',
            'data-fast-interval' => '1250',
            'data-background-interval' => '5000',
            'data-confirmed-title' => get_string(
                'commerce_alfa_confirmation_confirmed_title',
                'local_subscriptions'
            ),
            'data-confirmed-message' => get_string(
                'commerce_alfa_confirmation_confirmed_message',
                'local_subscriptions'
            ),
            'role' => 'status',
            'aria-live' => 'polite',
            'aria-atomic' => 'true',
        ]
    );

    echo html_writer::start_div('alfa-payment-confirmation__glass');

    echo html_writer::start_div('alfa-payment-confirmation__hourglass-wrap');
    echo html_writer::div('', 'alfa-payment-confirmation__orbit', ['aria-hidden' => 'true']);
    echo html_writer::tag(
        'div',
        html_writer::tag('i', '', [
            'class' => 'fa-solid fa-hourglass-half',
            'aria-hidden' => 'true',
        ]),
        ['class' => 'alfa-payment-confirmation__hourglass']
    );
    echo html_writer::end_div();

    echo html_writer::tag(
        'h1',
        get_string('commerce_alfa_confirmation_title', 'local_subscriptions'),
        [
            'class' => 'alfa-payment-confirmation__title',
            'data-alfa-title' => '1',
        ]
    );
    echo html_writer::tag(
        'p',
        get_string('commerce_alfa_confirmation_message', 'local_subscriptions'),
        [
            'class' => 'alfa-payment-confirmation__message',
            'data-alfa-message' => '1',
        ]
    );

    echo html_writer::div('', 'alfa-payment-confirmation__progress', [
        'data-alfa-progress' => '1',
        'aria-hidden' => 'true',
    ]);

    echo html_writer::start_div('alfa-payment-confirmation__security');
    echo html_writer::tag(
        'span',
        html_writer::tag('i', '', [
            'class' => 'fa-solid fa-lock',
            'aria-hidden' => 'true',
        ]),
        ['class' => 'alfa-payment-confirmation__security-icon']
    );
    echo html_writer::start_div('');
    echo html_writer::tag(
        'strong',
        get_string('commerce_alfa_confirmation_security_title', 'local_subscriptions')
    );
    echo html_writer::tag(
        'span',
        get_string('commerce_alfa_confirmation_security_message', 'local_subscriptions')
    );
    echo html_writer::end_div();
    echo html_writer::end_div();

    echo html_writer::end_div();
    echo html_writer::end_div();
}

echo html_writer::start_div('commerce-order-result container py-4 py-lg-5');

echo $OUTPUT->render_from_template(
    'local_subscriptions/checkout/steps',
    $stepscontext
);

if ($guestaccountnotice !== null && isset($guestsession) && $guestsession !== null
        && ($guestsession->get_metadata()['account_origin'] ?? '') === 'guest_checkout'
        && empty($guestsession->get_metadata()['password_set_at'])
        && !$accountfinalised) {
    echo $OUTPUT->render_from_template(
        'local_subscriptions/commerce/guest_account_dialog',
        [
            'accountactivationurl' => $guestaccountnotice['url']->out(false),
            'autoopen' => '1',
        ]
    );
}

echo html_writer::start_div('commerce-order-hero commerce-order-hero--' . $state->tone);
echo html_writer::div($icons[$state->code] ?? '•', 'commerce-order-hero__icon', ['aria-hidden' => 'true']);
echo html_writer::start_div('commerce-order-hero__content');
echo html_writer::tag('h1', get_string('commerce_i2_title_' . $state->code, 'local_subscriptions'), ['class' => 'commerce-order-hero__title']);
echo html_writer::tag('p', get_string('commerce_i2_message_' . $state->code, 'local_subscriptions'), ['class' => 'commerce-order-hero__message']);
echo html_writer::end_div();
echo html_writer::end_div();

if ($accountfinalised) {
    echo html_writer::start_div('commerce-order-account-ready');
    echo $OUTPUT->notification(
        get_string('commerce_guest_activation_ready_confirmation', 'local_subscriptions'),
        \core\output\notification::NOTIFY_SUCCESS
    );
    echo html_writer::end_div();
}

echo html_writer::start_div('row g-4 mt-1');
echo html_writer::start_div('col-lg-8');
echo html_writer::start_div('card border-0 shadow-sm commerce-order-card');
echo html_writer::start_div('card-body p-4');
echo html_writer::start_div('d-flex flex-wrap justify-content-between gap-3 align-items-start mb-4');
echo html_writer::start_div('');
echo html_writer::tag('div', get_string('commerce_i2_order_label', 'local_subscriptions'), ['class' => 'text-uppercase small text-muted fw-semibold']);
echo html_writer::tag('h2', s($publicreference), ['class' => 'h4 mb-1']);
echo html_writer::tag('div', userdate($order->timecreated), ['class' => 'text-muted']);
echo html_writer::end_div();
echo html_writer::div($formatmoney($order->totalminor, $order->currency), 'commerce-order-total');
echo html_writer::end_div();

$renderaccessactions = static function(array $accesses) use ($reference, $order, $state, $requiresaccountfinalisation): string {
    $accessactions = '';
    foreach ($accesses as $access) {
        if ($access->available && $access->url !== null && $state->showaccesses) {
            if ($access->type === 'course_access') {
                $trackedurl = CommerceTrackedActionUrl::build(
                    $reference,
                    'order_open_course',
                    'order_result',
                    $access->url
                );
                $coursebuttoncontent = html_writer::tag('i', '', [
                    'class' => 'fa-solid fa-graduation-cap me-2',
                    'aria-hidden' => 'true',
                ]) . get_string('commerce_i2_open_course', 'local_subscriptions');
                if ($requiresaccountfinalisation) {
                    $accessactions .= html_writer::tag('button', $coursebuttoncontent, [
                        'type' => 'button',
                        'class' => 'btn btn-primary commerce-order-account-gated-action',
                        'data-requires-account-finalisation' => '1',
                        'aria-haspopup' => 'dialog',
                    ]);
                } else {
                    $accessactions .= html_writer::link(
                        $trackedurl,
                        $coursebuttoncontent,
                        ['class' => 'btn btn-primary']
                    );
                }
                continue;
            }

            if ($access->type === 'digital_download') {
                $hasdesktop = !empty($access->metadata['hasdesktop']);
                $hasmobile = !empty($access->metadata['hasmobile']);
                if ($hasdesktop || !$hasmobile) {
                    $desktopurl = new moodle_url($access->url, ['version' => 'desktop']);
                    $trackeddesktop = CommerceTrackedActionUrl::build(
                        $reference,
                        'order_download_file',
                        'order_result',
                        $desktopurl
                    );
                    $accessactions .= html_writer::link(
                        $trackeddesktop,
                        html_writer::tag('i', '', [
                            'class' => 'fa-solid fa-download me-2',
                            'aria-hidden' => 'true',
                        ]) . get_string('digital_download_classic', 'local_subscriptions'),
                        ['class' => 'btn btn-primary']
                    );
                }
                if ($hasmobile) {
                    $mobileurl = new moodle_url($access->url, ['version' => 'mobile']);
                    $trackedmobile = CommerceTrackedActionUrl::build(
                        $reference,
                        'order_download_file',
                        'order_result',
                        $mobileurl
                    );
                    $accessactions .= html_writer::link(
                        $trackedmobile,
                        html_writer::tag('i', '', [
                            'class' => 'fa-solid fa-mobile-screen-button me-2',
                            'aria-hidden' => 'true',
                        ]) . get_string('digital_download_mobile', 'local_subscriptions'),
                        ['class' => 'btn btn-outline-primary']
                    );
                }
                continue;
            }
        }

        $failed = in_array(strtolower($access->status), ['failed', 'error'], true)
            || in_array(strtolower($order->fulfillmentstatus), ['failed', 'error'], true);
        $label = $failed
            ? get_string('commerce_access_temporarily_unavailable', 'local_subscriptions')
            : get_string('commerce_access_preparing', 'local_subscriptions');
        $accessactions .= html_writer::span(
            $label,
            'commerce-order-access-state commerce-order-access-state--' . ($failed ? 'failed' : 'preparing'),
            ['role' => 'status']
        );
    }
    return $accessactions;
};

$bundlecomponents = new CommerceBundleComponentResolver($DB);

foreach ($order->items as $item) {
    $itemsku = trim((string)(
        $item->metadata['productsku']
        ?? $item->metadata['sku']
        ?? $item->reference
    ));
    $itemproduct = $itemsku === ''
        ? null
        : $storefrontproducts->find_by_sku(
            strtoupper($itemsku),
            current_language(),
            $order->currency,
            true
        );
    $itemtype = $itemproduct?->get_type() ?? match (strtolower($item->type)) {
        'subscription', 'course', 'course_access' => 'course_access',
        'digital', 'digital_download' => 'digital_download',
        'bundle' => 'bundle',
        default => 'unknown',
    };
    $itemcoverurl = $itemproduct?->get_cover_url('checkout');
    $itemplaceholdericon = CommerceProductVisualAuditService::placeholder_icon(
        $itemtype
    );

    echo html_writer::start_div('commerce-order-item-group');

    echo html_writer::start_div('commerce-order-item');
    echo html_writer::start_div('commerce-order-item__identity');
    echo html_writer::start_div(
        'commerce-order-item__visual',
        ['aria-hidden' => 'true']
    );
    if ($itemcoverurl !== null) {
        echo html_writer::empty_tag('img', [
            'src' => $itemcoverurl,
            'alt' => '',
            'class' => 'commerce-order-item__cover',
        ]);
    } else {
        echo html_writer::tag('i', '', [
            'class' => $itemplaceholdericon,
            'aria-hidden' => 'true',
        ]);
    }
    echo html_writer::end_div();
    // CommerceOrderPresentationService already resolves the immutable purchase
    // item to the best available customer-facing translation. Do not re-resolve
    // through Storefront here, otherwise a technical Native name can win again.
    $itemdisplayname = CommerceProductDisplayText::title($item->label);

    echo html_writer::start_div('commerce-order-item__main');
    echo html_writer::tag(
        'h3',
        s($itemdisplayname),
        ['class' => 'h6 mb-1']
    );
    echo html_writer::div(
        get_string(
            'commerce_i2_quantity',
            'local_subscriptions',
            $item->quantity
        ),
        'small text-muted'
    );
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::div(
        $formatmoney($item->netminor, $item->currency),
        'fw-semibold commerce-order-item__price'
    );
    echo html_writer::end_div();

    if ($itemtype === 'bundle') {
        $components = $bundlecomponents->resolve($item);
        if ($components !== []) {
            echo html_writer::start_div('commerce-order-bundle-components');
            foreach ($components as $component) {
                $componentsku = strtoupper(trim((string)$component['sku']));
                $componentaccesses = array_values(array_filter(
                    $item->accesses,
                    static fn($access): bool => strtoupper(trim((string)($access->metadata['productsku'] ?? ''))) === $componentsku
                ));
                $componentproduct = $storefrontproducts->find_by_sku(
                    $componentsku,
                    current_language(),
                    $order->currency,
                    true
                );
                $componentcover = $componentproduct?->get_cover_url('checkout');
                $componenttype = (string)$component['type'];
                $componenticon = CommerceProductVisualAuditService::placeholder_icon($componenttype);

                echo html_writer::start_div('commerce-order-bundle-component');
                echo html_writer::start_div('commerce-order-bundle-component__identity');
                echo html_writer::start_div('commerce-order-bundle-component__visual', ['aria-hidden' => 'true']);
                if ($componentcover !== null) {
                    echo html_writer::empty_tag('img', [
                        'src' => $componentcover,
                        'alt' => '',
                        'class' => 'commerce-order-bundle-component__cover',
                    ]);
                } else {
                    echo html_writer::tag('i', '', [
                        'class' => $componenticon,
                        'aria-hidden' => 'true',
                    ]);
                }
                echo html_writer::end_div();
                echo html_writer::start_div('commerce-order-bundle-component__main');
                echo html_writer::tag('h4', (string)$component['name'], ['class' => 'h6 mb-1']);
                echo html_writer::div(
                    get_string('commerce_i2_quantity', 'local_subscriptions', (int)$component['quantity']),
                    'small text-muted'
                );
                echo html_writer::end_div();
                echo html_writer::end_div();

                $componentactions = $renderaccessactions($componentaccesses);
                if ($componentactions !== '') {
                    echo html_writer::div($componentactions, 'commerce-order-item__actions');
                }
                echo html_writer::end_div();
            }
            echo html_writer::end_div();
        } else {
            $accessactions = $renderaccessactions($item->accesses);
            if ($accessactions !== '') {
                echo html_writer::div($accessactions, 'commerce-order-item__actions');
            }
        }
    } else {
        $accessactions = $renderaccessactions($item->accesses);
        if ($accessactions !== '') {
            echo html_writer::div($accessactions, 'commerce-order-item__actions');
        }
    }

    echo html_writer::end_div();
}

echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('d-flex flex-wrap gap-2 mt-4 commerce-order-secondary-actions');
if ($state->canretry) {
    echo html_writer::link(
        $retryurl,
        get_string('commerce_i2_retry', 'local_subscriptions'),
        ['class' => 'btn btn-primary']
    );
    echo html_writer::link(
        $carturl,
        get_string('commerce_i2_back_cart', 'local_subscriptions'),
        ['class' => 'btn btn-outline-secondary']
    );
}
echo html_writer::link(
    $vieworderurl,
    html_writer::tag('i', '', [
        'class' => 'fa-solid fa-receipt me-2',
        'aria-hidden' => 'true',
    ]) . get_string('commerce_view_order', 'local_subscriptions'),
    ['class' => 'btn btn-outline-primary']
);
echo html_writer::link(
    $supporturl,
    html_writer::tag('i', '', [
        'class' => 'fa-solid fa-headset me-2',
        'aria-hidden' => 'true',
    ]) . get_string('commerce_i2_support', 'local_subscriptions'),
    ['class' => 'btn btn-outline-secondary']
);
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('col-lg-4');
if ($guestaccountnotice !== null) {
    $accountattrs = ['data-account-reminder' => '1'];
    if (!empty($guestaccountnotice['provisional'])) {
        $accountattrs['hidden'] = 'hidden';
    }
    echo html_writer::start_div('card border-0 shadow-sm commerce-order-card commerce-order-account-card mb-4', $accountattrs);
    echo html_writer::start_div('card-body p-4');
    echo html_writer::div('🔐', 'commerce-order-account-card__icon', ['aria-hidden' => 'true']);
    echo html_writer::tag('h2', $guestaccountnotice['title'], ['class' => 'h5']);
    echo html_writer::tag('p', $guestaccountnotice['message'], ['class' => 'text-muted']);
    echo html_writer::link($guestaccountnotice['url'],
        html_writer::tag('i', '', ['class' => 'fa-solid ' . $guestaccountnotice['icon'] . ' me-2', 'aria-hidden' => 'true'])
            . $guestaccountnotice['label'],
        ['class' => 'btn btn-primary w-100']
    );
    echo html_writer::end_div();
    echo html_writer::end_div();
}
if (!$requiresaccountfinalisation) {
echo html_writer::start_div('card border-0 shadow-sm commerce-order-card');
echo html_writer::start_div('card-body p-4');
echo html_writer::tag('h2', get_string('commerce_i2_next_title', 'local_subscriptions'), ['class' => 'h5']);
echo html_writer::start_tag(
    'ul',
    ['class' => 'list-unstyled mb-0 commerce-order-next']
);
echo html_writer::tag(
    'li',
    html_writer::link(
        $mycampusurl,
        html_writer::tag('i', '', [
            'class' => 'fa-solid fa-house',
            'aria-hidden' => 'true',
        ]) . html_writer::span(
            get_string(
                'commerce_order_result_access_contents',
                'local_subscriptions'
            )
        )
    ),
    ['class' => 'commerce-order-next__primary']
);
echo html_writer::tag(
    'li',
    html_writer::link(
        $mycoursesurl,
        html_writer::tag('i', '', [
            'class' => 'fa-solid fa-graduation-cap',
            'aria-hidden' => 'true',
        ]) . html_writer::span(
            get_string(
                'commerce_i2_my_courses',
                'local_subscriptions'
            )
        )
    )
);
echo html_writer::tag(
    'li',
    html_writer::link(
        $resourcesurl,
        html_writer::tag('i', '', [
            'class' => 'fa-solid fa-file-arrow-down',
            'aria-hidden' => 'true',
        ]) . html_writer::span(
            get_string(
                'commerce_i2_my_resources',
                'local_subscriptions'
            )
        )
    )
);
echo html_writer::tag(
    'li',
    html_writer::link(
        $myordersurl,
        html_writer::tag('i', '', [
            'class' => 'fa-solid fa-bag-shopping',
            'aria-hidden' => 'true',
        ]) . html_writer::span(
            get_string(
                'commerce_i2_my_orders',
                'local_subscriptions'
            )
        )
    )
);
echo html_writer::tag(
    'li',
    html_writer::link(
        $storefronturl,
        html_writer::tag('i', '', [
            'class' => 'fa-solid fa-store',
            'aria-hidden' => 'true',
        ]) . html_writer::span(
            get_string(
                'commerce_order_result_discover_store',
                'local_subscriptions'
            )
        )
    ),
    ['class' => 'commerce-order-next__store']
);
echo html_writer::end_tag('ul');
echo html_writer::end_div();
echo html_writer::end_div();
}
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_div();
echo $OUTPUT->footer();
