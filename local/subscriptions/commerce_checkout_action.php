<?php

require_once(__DIR__ . '/../../config.php');

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\currency\CommerceCurrencyRegistry;
use local_subscriptions\commerce\checkout\flow\CommercePurchaseFlow;
use local_subscriptions\commerce\checkout\flow\CommerceDirectPurchaseSession;
use local_subscriptions\commerce\checkout\guest\CommerceCheckoutIdentityResolver;
use local_subscriptions\commerce\checkout\guest\CommerceGuestCartRecoveryService;
use local_subscriptions\commerce\checkout\guest\CommerceGuestCheckoutService;
use local_subscriptions\commerce\checkout\guest\CommerceGuestCheckoutSessionRepository;
use local_subscriptions\commerce\checkout\guest\CommerceGuestPaymentGate;
use local_subscriptions\commerce\checkout\guest\CommerceUnfinishedGuestCheckoutRecoveryService;
use local_subscriptions\commerce\checkout\express\CommerceCheckoutExpressService;
use local_subscriptions\commerce\checkout\execution\CommerceCheckoutExecutionPolicy;
use local_subscriptions\commerce\checkout\execution\CommerceCheckoutPaymentOrchestrator;
use local_subscriptions\commerce\checkout\unified\CommerceCheckoutContext;
use local_subscriptions\commerce\checkout\unified\CommerceCheckoutRuntimeFactory;
use local_subscriptions\commerce\education\capacity\CommercePedagogicalCapacityService;
use local_subscriptions\commerce\education\lifecycle\CommercePedagogicalSaleException;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservationException;
use local_subscriptions\commerce\purchase\CommerceCustomer;
use local_subscriptions\commerce\personaloffer\service\CommercePersonalOfferCheckoutService;
use local_subscriptions\commerce\payment\availability\CommercePaymentAvailabilityResolver;
use local_subscriptions\commerce\payment\method\CommercePaymentMethod;
use local_subscriptions\commerce\payment\repository\CommercePaymentRepository;
use local_subscriptions\commerce\runtime\CommerceRuntimeFactory;
use local_subscriptions\support\Region;
use local_subscriptions\url\UrlFactory;

\local_subscriptions\subscription_config::guard_public_access();
require_sesskey();

global $DB, $SESSION;

$currencyregistry = new CommerceCurrencyRegistry();
$currency = $currencyregistry->require_enabled(
    strtoupper(required_param('currency', PARAM_ALPHA))
);
$paymentmethod = strtolower(
    required_param('paymentmethod', PARAM_ALPHANUMEXT)
);

$paymentcountry = Region::detect_country();
$paymentmarketcountry =
    $paymentcountry === 'ZZ'
        ? null
        : $paymentcountry;

$providerregistry = CommerceRuntimeFactory::create()->payment_providers();
$availability = (new CommercePaymentAvailabilityResolver($providerregistry))
    ->method(
        $currency,
        $paymentmethod,
        $paymentmarketcountry
    );

if (
    !$availability->is_available()
    || !CommerceCheckoutExecutionPolicy::is_executable_now(
        $paymentmethod
    )
) {
    throw new moodle_exception(
        'commerce_checkout_payment_method_unavailable',
        'local_subscriptions',
        '',
        $paymentmethod
    );
}

$paymentroute = (
    new CommerceCheckoutPaymentOrchestrator(
        new CommercePaymentAvailabilityResolver(
            $providerregistry
        )
    )
)->route_for(
    $currency,
    $paymentmethod,
    $paymentmarketcountry
);

if ($paymentroute === null) {
    throw new moodle_exception(
        'commerce_checkout_payment_method_unavailable',
        'local_subscriptions',
        '',
        $paymentmethod
    );
}

$provider = $paymentroute->get_provider();
$flow = CommercePurchaseFlow::normalise(optional_param('flow', CommercePurchaseFlow::CART, PARAM_ALPHA));

$directpurchase =
    CommercePurchaseFlow::is_direct($flow)
        ? CommerceDirectPurchaseSession::current($currency)
        : null;

if (
    CommercePurchaseFlow::is_direct($flow)
    && $directpurchase === null
) {
    throw new moodle_exception(
        'commerce_cart_message_item_not_found',
        'local_subscriptions'
    );
}

$source = strtolower(optional_param('source', '', PARAM_ALPHANUMEXT));
$showroom = strtolower(optional_param('showroom', '', PARAM_ALPHANUMEXT));
$showroomoffer = strtolower(optional_param('showroomoffer', '', PARAM_ALPHANUMEXT));
$originreturn = optional_param('originreturn', '', PARAM_LOCALURL);
$acceptterms = optional_param('accept_terms', 0, PARAM_BOOL);
$ajax = optional_param('ajax', 0, PARAM_BOOL);
$checkoutlanguage = optional_param(
    'checkoutlanguage',
    current_language(),
    PARAM_LANG
);
$checkoutparams = [
    'currency' => $currency,
    'paymentmethod' => $paymentmethod,
    'flow' => $flow,
];
foreach (['source' => $source, 'showroom' => $showroom, 'showroomoffer' => $showroomoffer, 'originreturn' => $originreturn] as $key => $value) {
    if ($value !== '') {
        $checkoutparams[$key] = $value;
    }
}

if (!$acceptterms) {
    if ($ajax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => false,
            'error' => get_string(
                'commerce_checkout_terms_required',
                'local_subscriptions'
            ),
        ]);
        exit;
    }

    redirect(
        new moodle_url('/local/subscriptions/commerce_checkout.php', $checkoutparams),
        get_string('commerce_checkout_terms_required', 'local_subscriptions'),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}

try {
    $isguestcheckout = !isloggedin() || isguestuser();
    $sessions = null;
    $guestsession = null;

    // Resolve an existing Guest Checkout session before validating the Personal
    // Offer. Once identification has transferred the cart, the authoritative
    // Personal Offer line belongs to the provisional userid, not customer 0.
    if ($isguestcheckout) {
        $token = trim((string)($SESSION->local_subscriptions_guest_checkout_token ?? ''));
        $sessions = new CommerceGuestCheckoutSessionRepository($DB);
        $guestsession = $token !== '' ? $sessions->find_by_token($token) : null;
        if ($guestsession !== null
                && ($guestsession->is_expired() || $guestsession->get_currency() !== $currency)) {
            $guestsession = null;
        }
    }

    if (
        $isguestcheckout
        && !CommerceGuestPaymentGate::is_ready(
            $guestsession
        )
    ) {
        if ($ajax) {
            header(
                'Content-Type: application/json; charset=utf-8'
            );
            http_response_code(409);
            echo json_encode([
                'ok' => false,
                'code' => 'guest_identity_verification_required',
                'error' => get_string(
                    'commerce_guest_payment_gate_required',
                    'local_subscriptions'
                ),
            ]);
            exit;
        }

        redirect(
            new moodle_url(
                '/local/subscriptions/commerce_checkout.php',
                $checkoutparams
            ),
            get_string(
                'commerce_guest_payment_gate_required',
                'local_subscriptions'
            ),
            null,
            \core\output\notification::NOTIFY_WARNING
        );
    }

    $personalofferidentity = null;
    if ($source === 'personaloffer' && $isguestcheckout) {
        $personaloffers = CommercePersonalOfferCheckoutService::create($DB);
        $offercustomerid = 0;
        if ($guestsession !== null
                && in_array($guestsession->get_status(), ['provisional', 'payment_pending'], true)
                && $guestsession->get_user_id() !== null) {
            $offercustomerid = $guestsession->get_user_id();
        }

        $cartoffer = $personaloffers->get_cart_offer($offercustomerid, $currency);
        if ($cartoffer === null || !$cartoffer->is_available_at(time())) {
            throw new moodle_exception('commerce_personal_offer_not_redeemable', 'local_subscriptions');
        }
        $personalofferidentity = $personaloffers->get_beneficiary_identity($cartoffer);
    }

    if ($isguestcheckout) {
        if ($sessions === null) {
            $sessions = new CommerceGuestCheckoutSessionRepository($DB);
        }
        if ($guestsession === null) {
            $guestsession = CommerceGuestCheckoutService::create()->start($currency, [
                'entrypoint' => 'commerce_checkout_action.php',
                'purchase_flow' => $flow,
            'direct_purchase' => $directpurchase,
                'checkout_source' => $source,
                'showroom' => $showroom,
                'showroom_offer' => $showroomoffer,
            'origin_return' => $originreturn,
            ]);
            $SESSION->local_subscriptions_guest_checkout_token = $guestsession->get_token();
        }

        if ($guestsession->get_status() === 'existing_account') {
            $guestsession = (new CommerceUnfinishedGuestCheckoutRecoveryService(
                $DB,
                $sessions
            ))->recover_session_if_possible($guestsession);
        }

        if ($guestsession->get_status() === 'existing_account') {
            redirect(new moodle_url('/local/subscriptions/commerce_checkout.php', $checkoutparams));
        }
    }

    $identity = CommerceCheckoutIdentityResolver::create()->resolve($currency);
    CommerceGuestCartRecoveryService::create()->recover_current($identity->userid, $currency);
    CommercePersonalOfferCheckoutService::create($DB)->assert_checkout_identity(
        $identity->userid, $currency, $identity->userid > 0 ? $identity->userid : null, $identity->email
    );
    $legalacceptedat = (new CommerceCheckoutExpressService())->record_legal_acceptance($identity->userid);

    $embeddedmethods = [];

    if ($provider === 'stripe') {
        // H12.9-A6.5.2: this list is the server-side mirror of the deferred
        // Stripe Elements contract used by checkout_express_wallets.js.
        //
        // IMPORTANT: market eligibility is already resolved with a nullable
        // country on the checkout page (ZZ => null). Reusing raw "ZZ" here
        // can silently drop Klarna from the PaymentIntent while the mounted
        // Elements instance still expects it, producing Stripe's 400
        // payment_method_types mismatch.
        $embeddedmarketcountry =
            $paymentcountry === 'ZZ'
                ? null
                : $paymentcountry;

        foreach (
            [
                CommercePaymentMethod::CARD,
                CommercePaymentMethod::APPLE_PAY,
                CommercePaymentMethod::GOOGLE_PAY,
                CommercePaymentMethod::LINK,
                CommercePaymentMethod::KLARNA,
            ]
            as $embeddedmethod
        ) {
            $embeddedavailability =
                (
                    new CommercePaymentAvailabilityResolver(
                        $providerregistry
                    )
                )->method(
                    $currency,
                    $embeddedmethod,
                    $embeddedmarketcountry
                );

            if (
                $embeddedavailability->is_available()
                && $embeddedavailability
                    ->get_preferred_provider_key()
                    === 'stripe'
            ) {
                $embeddedmethods[] =
                    $embeddedmethod;
            }
        }
    }

    $returnurl = (new moodle_url('/local/subscriptions/payment/return.php'))->out(false);
    $carturl = (UrlFactory::cart(['currency' => $currency]))->out(false);
    $cancelurl = $flow === CommercePurchaseFlow::DIRECT && $originreturn !== ''
        ? (new moodle_url($originreturn))->out(false)
        : $carturl;

    $context = new CommerceCheckoutContext(
        $identity->userid,
        $currency,
        $checkoutlanguage,
        $provider,
        $returnurl,
        $cancelurl,
        true,
        [
            'checkout_entrypoint' => 'commerce_checkout_action.php',
            'checkout_phase' => 'J14B',
            'purchase_flow' => $flow,
            'direct_purchase' => $directpurchase,
            'checkout_source' => $source,
            'origin_return' => $originreturn,
            'showroom' => $showroom,
            'showroom_offer' => $showroomoffer,
            'payment_method' => $paymentmethod,
            'payment_execution_mode' =>
                CommerceCheckoutExecutionPolicy::mode_for_route(
                    $paymentmethod,
                    $provider
                ),
            'payment_execution_surface' =>
                $paymentroute->get_surface(),
            'payment_country' => $paymentcountry,
            'embedded_payment_methods' =>
                $embeddedmethods,
            'legal_acceptance' => [
                'accepted' => true,
                'accepted_at' => $legalacceptedat,
                'source' => 'checkout_checkbox',
            ],
            'resume_purchase_reference' => $guestsession !== null
                ? trim((string)($guestsession->get_metadata()['resume_purchase_reference'] ?? ''))
                : '',
        ]
    );
    $customer = new CommerceCustomer(
        $identity->userid,
        $identity->email,
        $identity->firstname,
        $identity->lastname,
        ['language' => $checkoutlanguage, 'guest_checkout' => $identity->is_guest_checkout()]
    );

    $result = CommerceCheckoutRuntimeFactory::create()->launch($context, $customer);
    if ($identity->guestsession !== null) {
        (new CommerceGuestCheckoutSessionRepository($DB))->attach_payment(
            $identity->guestsession,
            $result->get_snapshot()->get_purchase_request()->get_reference(),
            $result->get_snapshot()->get_payment_request()->get_reference()
        );
    }
    $paymentresult = $result->get_initialization()->get_payment_result();
    $action = $paymentresult?->get_action();

    if ($action?->is_embedded()) {
        $parameters = $action->get_parameters();

        if ($ajax) {
            header('Content-Type: application/json; charset=utf-8');

            if (
                $provider === 'alfa'
                && (
                    $parameters['embedded_type']
                    ?? ''
                ) === 'alfa_iframe'
            ) {
                echo json_encode([
                    'ok' => true,
                    'type' => 'alfa_iframe',
                    'provider' => $provider,
                    'paymentmethod' => $paymentmethod,
                    'parameters' => $parameters,
                ], JSON_UNESCAPED_SLASHES);
                exit;
            }

            if (
                $provider === 'alfa'
                && (
                    $parameters['embedded_type']
                    ?? ''
                ) === 'alfa_widget'
            ) {
                echo json_encode([
                    'ok' => true,
                    'type' => 'alfa_widget',
                    'provider' => $provider,
                    'paymentmethod' => $paymentmethod,
                    'parameters' => $parameters,
                ], JSON_UNESCAPED_SLASHES);
                exit;
            }

            if (
                $provider === 'alfa'
                && (
                    $parameters['embedded_type']
                    ?? ''
                ) === 'alfa_sbp'
            ) {
                echo json_encode([
                    'ok' => true,
                    'type' => 'alfa_sbp',
                    'provider' => $provider,
                    'paymentmethod' => $paymentmethod,
                    'parameters' => $parameters,
                ], JSON_UNESCAPED_SLASHES);
                exit;
            }

            echo json_encode([
                'ok' => true,
                'type' => 'embedded',
                'provider' => $provider,
                'paymentmethod' => $paymentmethod,
                'clientSecret' =>
                    (string)($parameters['client_secret'] ?? ''),
                'publishableKey' =>
                    (string)($parameters['publishable_key'] ?? ''),
                'returnUrl' =>
                    (string)($parameters['return_url'] ?? ''),
            ], JSON_UNESCAPED_SLASHES);
            exit;
        }

        if (
            $provider === 'alfa'
            && ($parameters['embedded_type'] ?? '') === 'alfa_sbp'
        ) {
            $PAGE->set_context(context_system::instance());
            $PAGE->set_url(new moodle_url('/local/subscriptions/commerce_checkout_action.php'));
            $PAGE->set_pagelayout('embedded');
            $PAGE->set_title(get_string('commerce_checkout_sbp_title', 'local_subscriptions'));

            echo $OUTPUT->header();
            echo $OUTPUT->render_from_template(
                'local_subscriptions/checkout/alfa_sbp',
                [
                    'title' => get_string('commerce_checkout_sbp_title', 'local_subscriptions'),
                    'description' => get_string('commerce_checkout_sbp_description', 'local_subscriptions'),
                    'qr' => (string)($parameters['rendered_qr'] ?? ''),
                    'payload' => (string)($parameters['payload'] ?? ''),
                    'openbanklabel' => get_string('commerce_checkout_sbp_open_bank', 'local_subscriptions'),
                    'verifylabel' => get_string('commerce_checkout_sbp_verify', 'local_subscriptions'),
                    'returnurl' => (string)($parameters['return_url'] ?? ''),
                ]
            );
            echo $OUTPUT->footer();
            exit;
        }

        $expressmethods = array_values(
            array_intersect(
                $embeddedmethods,
                [
                    CommercePaymentMethod::APPLE_PAY,
                    CommercePaymentMethod::GOOGLE_PAY,
                ]
            )
        );

        $paymentelementmethods = array_values(
            array_intersect(
                $embeddedmethods,
                [
                    CommercePaymentMethod::CARD,
                    CommercePaymentMethod::LINK,
                    CommercePaymentMethod::KLARNA,
                ]
            )
        );

        $clientsecret = trim(
            (string)($parameters['client_secret'] ?? '')
        );
        $publishablekey = trim(
            (string)($parameters['publishable_key'] ?? '')
        );
        $embeddedreturnurl = trim(
            (string)($parameters['return_url'] ?? '')
        );

        if (
            $clientsecret === ''
            || $publishablekey === ''
            || $embeddedreturnurl === ''
        ) {
            throw new RuntimeException(
                'The embedded payment action is incomplete.'
            );
        }

        $PAGE->set_context(
            context_system::instance()
        );
        $PAGE->set_url(
            new moodle_url(
                '/local/subscriptions/commerce_checkout_action.php'
            )
        );
        $PAGE->set_pagelayout('embedded');
        $PAGE->add_body_class(
            'commerce-embedded-payment-page'
        );
        $PAGE->set_title(
            get_string(
                'commerce_embedded_card_title',
                'local_subscriptions'
            )
        );
        $PAGE->requires->css(
            new moodle_url(
                '/local/subscriptions/styles/payment_provider_transition.css'
            )
        );
        $PAGE->requires->css(
            new moodle_url(
                '/local/subscriptions/styles/embedded_card.css'
            )
        );
        $PAGE->requires->js_call_amd(
            'local_subscriptions/stripe_embedded_card',
            'init',
            [[
                'publishableKey' =>
                    $publishablekey,
                'clientSecret' =>
                    $clientsecret,
                'returnUrl' =>
                    $embeddedreturnurl,
                'expressMethods' =>
                    $expressmethods,
                'selectedMethod' =>
                    $paymentmethod,
                'paymentElementMethods' =>
                    $paymentelementmethods,
            ]]
        );

        echo $OUTPUT->header();
        echo $OUTPUT->render_from_template(
            'local_subscriptions/checkout/embedded_card',
            [
                'title' => get_string(
                    'commerce_embedded_card_title',
                    'local_subscriptions'
                ),
                'description' => get_string(
                    'commerce_embedded_card_description',
                    'local_subscriptions'
                ),
                'submitlabel' => get_string(
                    'commerce_embedded_card_submit',
                    'local_subscriptions'
                ),
                'loadingtitle' => get_string(
                    'commerce_payment_splash_preparing_title',
                    'local_subscriptions'
                ),
                'loadingmessage' => get_string(
                    'commerce_payment_splash_preparing_message',
                    'local_subscriptions'
                ),
                'processingtitle' => get_string(
                    'commerce_payment_splash_processing_title',
                    'local_subscriptions'
                ),
                'processingmessage' => get_string(
                    'commerce_payment_splash_processing_message',
                    'local_subscriptions'
                ),
                'securitylabel' => get_string(
                    'commerce_embedded_card_security',
                    'local_subscriptions'
                ),
                'paymentsecurelabel' => get_string(
                    'commerce_embedded_secure_payment_label',
                    'local_subscriptions'
                ),
                'securitytitle' => get_string(
                    'commerce_provider_transition_security_title',
                    'local_subscriptions'
                ),
                'securitymessage' => get_string(
                    'commerce_provider_transition_security_message',
                    'local_subscriptions'
                ),
                'expresslabel' => get_string(
                    'commerce_embedded_express_title',
                    'local_subscriptions'
                ),
                'expressseparator' => get_string(
                    'commerce_embedded_express_separator',
                    'local_subscriptions'
                ),
            ]
        );
        echo $OUTPUT->footer();
        exit;
    }

    if (
        $ajax
        && $provider === 'paypal'
        && $paymentmethod === CommercePaymentMethod::PAYPAL
        && $action?->is_redirect()
        && $action->get_url() !== null
    ) {
        $orderid = trim(
            (string)(
                $paymentresult?->get_provider_payment_id()
                ?? ''
            )
        );

        if ($orderid === '') {
            throw new RuntimeException(
                'PayPal embedded checkout returned no order ID.'
            );
        }

        $paymentrepository =
            new CommercePaymentRepository(
                $DB
            );
        $attempt =
            $paymentrepository
                ->find_by_provider_reference(
                    'paypal',
                    $orderid
                );

        if ($attempt === null) {
            throw new RuntimeException(
                'PayPal embedded checkout could not resolve its native payment attempt.'
            );
        }

        $embeddedreturnurl = (
            new moodle_url(
                '/local/subscriptions/payment/return.php',
                [
                    'paymentid' =>
                        $attempt->get_id(),
                    'provider' =>
                        'paypal',
                    'uilang' =>
                        strtolower(
                            substr(
                                $checkoutlanguage,
                                0,
                                2
                            )
                        ),
                ]
            )
        )->out(false);

        header(
            'Content-Type: application/json; charset=utf-8'
        );
        echo json_encode(
            [
                'ok' => true,
                'type' => 'paypal_order',
                'provider' => 'paypal',
                'paymentmethod' =>
                    CommercePaymentMethod::PAYPAL,
                'orderId' => $orderid,
                'fallbackUrl' =>
                    $action->get_url(),
                'returnUrl' =>
                    $embeddedreturnurl,
            ],
            JSON_UNESCAPED_SLASHES
        );
        exit;
    }

    if ($action?->is_redirect() && $action->get_url() !== null) {
        redirect($action->get_url());
    }

    if ($action?->is_form_post() && $action->get_url() !== null) {
        $PAGE->set_context(context_system::instance());
        $PAGE->set_url(new moodle_url('/local/subscriptions/commerce_checkout_action.php'));
        $PAGE->set_pagelayout('embedded');
        echo $OUTPUT->header();
        echo html_writer::start_tag('form', [
            'id' => 'commerce-provider-post',
            'method' => 'post',
            'action' => $action->get_url(),
        ]);
        foreach ($action->get_parameters() as $name => $value) {
            echo html_writer::empty_tag('input', [
                'type' => 'hidden',
                'name' => (string)$name,
                'value' => (string)$value,
            ]);
        }
        echo html_writer::tag('button', get_string('commerce_checkout_continue_payment', 'local_subscriptions'), [
            'type' => 'submit',
            'class' => 'btn btn-primary',
        ]);
        echo html_writer::end_tag('form');
        echo html_writer::script("document.getElementById('commerce-provider-post').submit();");
        echo $OUTPUT->footer();
        exit;
    }

    throw new RuntimeException('The provider returned no supported checkout action.');
} catch (Throwable $exception) {
    $salesclosedexception = false;
    $promotionjoinpaymentblocked = false;
    $currentexception = $exception;
    while ($currentexception !== null) {
        if (
            (
                $currentexception instanceof CommercePedagogicalSaleException
                || $currentexception instanceof CommercePedagogicalSeatReservationException
            )
            && $currentexception->get_code_key() === CommercePedagogicalCapacityService::SALES_CLOSED
        ) {
            $salesclosedexception = true;
            break;
        }

        if ($currentexception instanceof \moodle_exception) {
            if ($currentexception->errorcode === CommercePedagogicalCapacityService::SALES_CLOSED) {
                $salesclosedexception = true;
                break;
            }
            if ($currentexception->errorcode === 'commerce_promotion_join_payment_pending_fulfillment') {
                $promotionjoinpaymentblocked = true;
                break;
            }
        }

        $currentexception = $currentexception->getPrevious();
    }

    if ($promotionjoinpaymentblocked) {
        $message = get_string(
            'commerce_promotion_join_payment_pending_fulfillment',
            'local_subscriptions'
        );

        if ($ajax) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(409);
            echo json_encode([
                'ok' => false,
                'code' => 'commerce_promotion_join_payment_pending_fulfillment',
                'error' => $message,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            exit;
        }

        $promotionjoinreturn =
            CommercePurchaseFlow::is_direct($flow) && $originreturn !== ''
                ? new moodle_url($originreturn)
                : new moodle_url('/local/subscriptions/commerce_checkout.php', $checkoutparams);

        redirect(
            $promotionjoinreturn,
            $message,
            null,
            \core\output\notification::NOTIFY_WARNING
        );
    }

    if ($salesclosedexception) {
        $message = get_string(
            'commerce_capacity_sales_closed',
            'local_subscriptions'
        );

        if ($ajax) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(409);
            echo json_encode([
                'ok' => false,
                'code' => CommercePedagogicalCapacityService::SALES_CLOSED,
                'error' => $message,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            exit;
        }

        $salesclosedreturn =
            CommercePurchaseFlow::is_direct($flow) && $originreturn !== ''
                ? new moodle_url($originreturn)
                : new moodle_url('/local/subscriptions/cart.php', [
                    'currency' => $currency,
                ]);

        redirect(
            $salesclosedreturn,
            $message,
            null,
            \core\output\notification::NOTIFY_WARNING
        );
    }

    $reference = substr(hash('sha256', implode('|', [
        (string)($identity->userid ?? 0),
        $currency,
        $provider,
        $exception::class,
        $exception->getMessage(),
        (string)microtime(true),
    ])), 0, 12);

    $chain = [];
    $current = $exception;
    while ($current !== null) {
        $chain[] = [
            'class' => $current::class,
            'message' => $current->getMessage(),
            'code' => $current->getCode(),
            'file' => $current->getFile(),
            'line' => $current->getLine(),
        ];
        $current = $current->getPrevious();
    }

    error_log('[local_subscriptions][checkout_provider][' . $reference . '] ' . json_encode([
        'country' => $paymentcountry,
        'currency' => $currency,
        'paymentmethod' => $paymentmethod,
        'provider' => $provider,
        'flow' => $flow,
        'chain' => $chain,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

    if ($ajax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => false,
            'reference' => $reference,
            'error' => get_string(
                'commerce_checkout_launch_error_reference',
                'local_subscriptions',
                $reference
            ),
        ], JSON_UNESCAPED_SLASHES);
        exit;
    }

    $message = get_string_manager()->string_exists('commerce_checkout_launch_error_reference', 'local_subscriptions')
        ? get_string('commerce_checkout_launch_error_reference', 'local_subscriptions', $reference)
        : get_string('commerce_checkout_launch_error', 'local_subscriptions') . ' [' . $reference . ']';

    redirect(
        new moodle_url('/local/subscriptions/commerce_checkout.php', array_merge($checkoutparams, [
            'paymenterror' => $reference,
        ])),
        $message,
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}
