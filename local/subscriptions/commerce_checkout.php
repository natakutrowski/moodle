<?php

require_once(__DIR__ . '/../../config.php');

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\legal\document\CommerceLegalDocumentResolver;
use local_subscriptions\commerce\catalog\currency\CommerceCurrencyRegistry;
use local_subscriptions\commerce\checkout\flow\CommercePurchaseFlow;
use local_subscriptions\commerce\checkout\flow\CommercePurchaseOrigin;
use local_subscriptions\commerce\checkout\flow\CommerceDirectPurchaseSession;
use local_subscriptions\commerce\checkout\guest\CommerceCheckoutIdentityResolver;
use local_subscriptions\commerce\checkout\guest\CommerceGuestCartRecoveryService;
use local_subscriptions\commerce\checkout\guest\CommerceGuestCheckoutService;
use local_subscriptions\commerce\checkout\guest\CommerceGuestIdentityVerificationService;
use local_subscriptions\commerce\checkout\guest\CommerceGuestIdentityVerificationState;
use local_subscriptions\commerce\checkout\guest\CommerceGuestPaymentGate;
use local_subscriptions\commerce\checkout\guest\CommerceGuestCheckoutSessionRepository;
use local_subscriptions\commerce\checkout\guest\CommerceUnfinishedGuestCheckoutRecoveryService;
use local_subscriptions\commerce\checkout\execution\CommerceCheckoutExecutionPolicy;
use local_subscriptions\commerce\checkout\execution\CommerceCheckoutPaymentOrchestrator;
use local_subscriptions\commerce\checkout\execution\CommerceCheckoutPaymentPresentationPlanner;
use local_subscriptions\commerce\checkout\execution\CommerceCheckoutPaymentRoute;
use local_subscriptions\commerce\checkout\unified\CommerceCheckoutContext;
use local_subscriptions\commerce\checkout\unified\CommerceCheckoutRuntimeFactory;
use local_subscriptions\commerce\checkout\unified\presentation\CommerceCheckoutPresenter;
use local_subscriptions\commerce\checkout\unified\presentation\CommerceCheckoutPaymentMethodPresenter;
use local_subscriptions\commerce\cart\presentation\CommerceCartSeatReservationPresenter;
use local_subscriptions\commerce\education\capacity\CommercePedagogicalCapacityService;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservationException;
use local_subscriptions\commerce\payment\availability\CommercePaymentAvailabilityResolver;
use local_subscriptions\commerce\payment\method\CommercePaymentMethod;
use local_subscriptions\commerce\payment\provider\paypal\PayPalGatewayConfiguration;
use local_subscriptions\commerce\payment\policy\CommercePaymentPolicyResolver;
use local_subscriptions\commerce\purchase\CommerceCustomer;
use local_subscriptions\commerce\personaloffer\service\CommercePersonalOfferCheckoutService;
use local_subscriptions\commerce\runtime\CommerceRuntimeFactory;
use local_subscriptions\support\Region;
use local_subscriptions\url\UrlFactory;
use local_subscriptions\payment\stripe\StripeConfiguration;
use local_subscriptions\commerce\payment\policy\CommercePaymentPresentationPolicy;
use local_subscriptions\commerce\payment\availability\CommercePaymentMethodMarketEligibility;
use local_subscriptions\commerce\currency\selection\CommerceCurrencySurfaceSelectionService;
use local_subscriptions\commerce\currency\selection\CommerceCurrencyJourneyStateResolver;
use local_subscriptions\currency\Currency;

\local_subscriptions\subscription_config::guard_public_access();

global $DB, $SESSION, $USER;

$currencyregistry =
    new CommerceCurrencyRegistry();
$availablecurrencies =
    $currencyregistry->enabled();
$requestedcurrency =
    Currency::sanitize(
        optional_param(
            'currency',
            '',
            PARAM_ALPHA
        )
    );

$usercurrency =
    isloggedin()
    && !isguestuser()
        ? Currency::sanitize(
            (string)get_user_preferences(
                'local_subscriptions_storefront_currency',
                '',
                (int)$USER->id
            )
        )
        : '';
$sessioncurrency =
    Currency::sanitize(
        (string)(
            $SESSION->local_subscriptions_storefront_currency
            ?? ''
        )
    );

$journeystate =
    CommerceCurrencyJourneyStateResolver::create();
$activecartcurrency =
    $journeystate->active_cart_currency(
        $availablecurrencies
    );
$activeguestcurrency =
    $journeystate
        ->active_guest_checkout_currency();

$currencyselection =
    (
        new CommerceCurrencySurfaceSelectionService()
    )->resolve(
        $availablecurrencies,
        $requestedcurrency,
        $usercurrency,
        $sessioncurrency,
        'EUR',
        $activecartcurrency,
        $activeguestcurrency
    );
$currency =
    $currencyselection->get_currency();

$SESSION->local_subscriptions_storefront_currency =
    $currency;
if (
    isloggedin()
    && !isguestuser()
) {
    set_user_preference(
        'local_subscriptions_storefront_currency',
        $currency,
        (int)$USER->id
    );
}

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
$isguestcheckout = !isloggedin() || isguestuser();

$guestsession = null;
$guestrepository = new CommerceGuestCheckoutSessionRepository($DB);
if ($isguestcheckout) {
    $token = trim((string)($SESSION->local_subscriptions_guest_checkout_token ?? ''));
    $guestsession = $token !== '' ? $guestrepository->find_by_token($token) : null;

    if ($guestsession !== null
            && !$guestsession->is_expired()
            && $guestsession->get_currency() !== $currency
            && $guestsession->get_status() === 'provisional'
            && $guestsession->get_user_id() !== null) {
        // A Personal Offer currency switch has already prepared the requested
        // anonymous cart. Keep the same provisional account and transfer that
        // cart instead of creating a new session that would see checkout_* as
        // an unrelated "existing account".
        $guestsession = CommerceGuestCheckoutService::create()->switch_provisional_currency(
            $guestsession,
            $currency
        );
    } else if ($guestsession === null
            || $guestsession->is_expired()
            || $guestsession->get_currency() !== $currency) {
        $guestsession = CommerceGuestCheckoutService::create()->start($currency, [
            'entrypoint' => 'commerce_checkout.php',
            'purchase_flow' => $flow,
            'direct_purchase' => $directpurchase,
            'checkout_source' => $source,
            'showroom' => $showroom,
            'showroom_offer' => $showroomoffer,
            'origin_return' => $originreturn,
        ]);
        $SESSION->local_subscriptions_guest_checkout_token = $guestsession->get_token();
    }

    // M9: heal an interrupted Guest Checkout before the page decides to
    // render the normal existing-account password gate.
    if ($guestsession !== null && $guestsession->get_status() === 'existing_account') {
        $guestsession = (new CommerceUnfinishedGuestCheckoutRecoveryService(
            $DB,
            $guestrepository
        ))->recover_session_if_possible($guestsession);
    }

    if (optional_param('resetidentity', 0, PARAM_BOOL)) {
        require_sesskey();
        $guestsession =
            (new CommerceGuestIdentityVerificationService($guestrepository))
                ->reset_identity($guestsession);
    }
}

$guestpaymentlocked =
    $isguestcheckout
    && !CommerceGuestPaymentGate::is_ready(
        $guestsession
    );

$providerregistry = CommerceRuntimeFactory::create()->payment_providers();
$providers = $providerregistry->all();
$availabilityresolver = new CommercePaymentAvailabilityResolver(
    $providerregistry
);
$paymentcountry = Region::detect_country();
$paymentmarketcountry =
    $paymentcountry === 'ZZ'
        ? null
        : $paymentcountry;
$paymentorchestrator = new CommerceCheckoutPaymentOrchestrator(
    $availabilityresolver
);

// Availability + executable capability are authoritative. H13.2 recommendation
// is then applied once, before methods are split into Express and standard UI.
$availablemethods = $availabilityresolver->available(
    $currency,
    $paymentmarketcountry
);
$availablemethods = array_values(
    array_filter(
        $availablemethods,
        static fn($availability): bool =>
            CommerceCheckoutExecutionPolicy::is_executable_now(
                $availability->get_method()
            )
    )
);

$paymentroutes = $paymentorchestrator->routes(
    $currency,
    $paymentmarketcountry
);
$presentationplan =
    (new CommerceCheckoutPaymentPresentationPlanner())->plan(
        $paymentcountry,
        $currency,
        $availablemethods,
        $paymentroutes
    );
$paymentpolicy =
    $presentationplan['policy'];
$orderedallmethods =
    $presentationplan['orderedmethods'];
$paymentroutes =
    $presentationplan['orderedroutes'];

$expressroutes = array_values(
    array_filter(
        $paymentroutes,
        static fn(CommerceCheckoutPaymentRoute $route): bool =>
            $route->is_express()
    )
);
$expresspaymentmethods = array_values(
    array_map(
        static fn(CommerceCheckoutPaymentRoute $route): string =>
            $route->get_method(),
        $expressroutes
    )
);
$inlineroutes = array_values(
    array_filter(
        $paymentroutes,
        static fn(CommerceCheckoutPaymentRoute $route): bool =>
            $route->is_inline()
    )
);
$inlinepaymentmethods = array_values(
    array_map(
        static fn(CommerceCheckoutPaymentRoute $route): string =>
            $route->get_method(),
        array_values(
            array_filter(
                $inlineroutes,
                static fn(CommerceCheckoutPaymentRoute $route): bool =>
                    $route->get_provider() === 'stripe'
            )
        )
    )
);
$hostedsplashmethods = array_values(
    array_map(
        static fn(CommerceCheckoutPaymentRoute $route): string =>
            $route->get_method(),
        array_values(
            array_filter(
                $paymentroutes,
                static fn(CommerceCheckoutPaymentRoute $route): bool =>
                    CommerceCheckoutExecutionPolicy::mode_for_route(
                        $route->get_method(),
                        $route->get_provider()
                    ) ===
                    \local_subscriptions\commerce\checkout\execution\CommerceCheckoutExecutionMode::PROVIDER_HOSTED
            )
        )
    )
);

$haspaypalcandidate = count(
    array_filter(
        $paymentroutes,
        static fn(CommerceCheckoutPaymentRoute $route): bool =>
            $route->get_provider() === 'paypal'
            && $route->get_method() === CommercePaymentMethod::PAYPAL
    )
) > 0;

$paypalconfiguration =
    new PayPalGatewayConfiguration();

$paypalclientid =
    $haspaypalcandidate
        ? trim((string)($paypalconfiguration->get_client_id() ?? ''))
        : '';

$paypalsdkurl =
    $haspaypalcandidate
        ? $paypalconfiguration->get_web_sdk_url()
        : '';

$hasalfawidgetcandidate = count(
    array_filter(
        $inlineroutes,
        static fn(CommerceCheckoutPaymentRoute $route): bool =>
            $route->get_provider() === 'alfa'
            && $route->get_method() === CommercePaymentMethod::CARD
    )
) > 0;

$hassbpcandidate = count(
    array_filter(
        $paymentroutes,
        static fn(CommerceCheckoutPaymentRoute $route): bool =>
            $route->get_provider() === 'alfa'
            && $route->get_method() === CommercePaymentMethod::SBP
    )
) > 0;

// Express methods keep their dedicated Stripe surface, while classic methods
// preserve exactly the same H13.2 recommendation order.
$orderedmethods = array_values(
    array_filter(
        $orderedallmethods,
        static fn($availability): bool =>
            !in_array(
                $availability->get_method(),
                $expresspaymentmethods,
                true
            )
    )
);

$requestedmethod = strtolower(
    optional_param('paymentmethod', '', PARAM_ALPHANUMEXT)
);
$availablemethodkeys = array_map(
    static fn($availability): string =>
        $availability->get_method(),
    $orderedmethods
);

// Explicit customer choice is always preserved when the method is genuinely
// available. Policy only chooses the default and presentation order.
// H12.7.3: recommendation is presentation advice only. A payment method
// becomes selected exclusively after an explicit customer action.
$selectedmethod = in_array(
    $requestedmethod,
    $availablemethodkeys,
    true
)
    ? $requestedmethod
    : '';

$selectedavailability = $selectedmethod !== ''
    ? $availabilityresolver->method(
        $currency,
        $selectedmethod,
        $paymentmarketcountry
    )
    : null;

// H12.7.3.1: provider availability and customer selection are independent.
// With no selected method yet, the checkout remains usable whenever at least
// one executable payment method exists.
$hasavailablepaymentmethod =
    $orderedmethods !== [];

$contextprovideravailability =
    $selectedavailability
    ?? (
        $orderedmethods !== []
            ? reset($orderedmethods)
            : null
    );

// CommerceCheckoutContext always requires a technical provider identity.
// This fallback does NOT select a payment method; it only supplies the
// execution context with a valid provider until the customer expresses intent.
$selectedprovider =
    $contextprovideravailability?->get_preferred_provider_key()
    ?? 'stripe';

$hascompatibleprovider =
    $selectedmethod === ''
        ? $hasavailablepaymentmethod
        : (
            $selectedavailability !== null
            && $selectedavailability->is_available()
        );

$pageparams = [
    'currency' => $currency,
    'flow' => $flow,
];
if ($selectedmethod !== '') {
    $pageparams['paymentmethod'] = $selectedmethod;
}
foreach (['source' => $source, 'showroom' => $showroom, 'showroomoffer' => $showroomoffer, 'originreturn' => $originreturn] as $key => $value) {
    if ($value !== '') {
        $pageparams[$key] = $value;
    }
}
$pageurl = new moodle_url('/local/subscriptions/commerce_checkout.php', $pageparams);
$returnurl = (new moodle_url('/local/subscriptions/payment/return.php'))->out(false);
$carturl = (UrlFactory::cart(['currency' => $currency]))->out(false);
$cancelurl = $flow === CommercePurchaseFlow::DIRECT && $originreturn !== ''
    ? (new moodle_url($originreturn))->out(false)
    : $carturl;

$PAGE->set_context(context_system::instance());
$PAGE->set_url($pageurl);
$PAGE->set_pagelayout('standard');
$PAGE->add_body_class('commerce-chromeless-page');
$PAGE->set_title(get_string('commerce_checkout_title', 'local_subscriptions'));
$PAGE->set_heading(get_string('commerce_checkout_title', 'local_subscriptions'));
$PAGE->requires->css(new moodle_url('/local/subscriptions/styles/storefront.css'));
$PAGE->requires->css(new moodle_url('/local/subscriptions/styles/guest_checkout.css'));
$PAGE->requires->css(new moodle_url('/local/subscriptions/styles/payment_provider_transition.css'));
$PAGE->requires->css(new moodle_url('/local/subscriptions/styles/checkout_express_wallets.css'));
$PAGE->requires->js_call_amd('local_subscriptions/guest_checkout_security', 'init');
$PAGE->requires->js_call_amd('local_subscriptions/checkout_payment_intent', 'init');
$PAGE->requires->js_call_amd('local_subscriptions/checkout_submission_state', 'init');
$PAGE->requires->js_call_amd('local_subscriptions/cart_seat_reservation', 'init');
if ($haspaypalcandidate && $paypalclientid !== '') {
    $PAGE->requires->js_call_amd(
        'local_subscriptions/checkout_paypal_embedded',
        'init'
    );
}
$PAGE->requires->js_call_amd(
    'local_subscriptions/checkout_inline_card',
    'init',
    [[
        // H12.3.1: keep AMD bootstrap arguments technical and compact.
        // Localised UI copy is rendered as data-* attributes in the template.
        // This avoids Moodle's 1024-character js_call_amd argument warning,
        // notably with longer RU translations.
        'locale' => current_language(),
        'inlineMethods' => $inlinepaymentmethods,
        'actionUrl' => (
            new moodle_url(
                '/local/subscriptions/commerce_checkout_action.php'
            )
        )->out(false),
        'returnUrl' => (
            new moodle_url(
                '/local/subscriptions/payment/return.php'
            )
        )->out(false),
    ]]
);

// H10.10: keep the Alfa diagnostic trace available to developers through
// window.__campusAlfaWidgetDebug, but never render a customer-facing debug
// surface or emit the orchestration trace to the browser console.
$alfawidgetdebugenabled =
    debugging('', DEBUG_DEVELOPER);

$alfawidgetserverdiagnostics = [
    'currency' => $currency,
    'country' => $paymentcountry,
    'selected_method' => $selectedmethod,
    'selected_provider' => $selectedprovider,
    'candidate' => $hasalfawidgetcandidate,
    'environment' =>
        \local_subscriptions\commerce\payment\provider\alfa\AlfaWidgetConfiguration::environment(),
    'widget_enabled' =>
        \local_subscriptions\commerce\payment\provider\alfa\AlfaWidgetConfiguration::is_enabled(),
    'widget_available' =>
        \local_subscriptions\commerce\payment\provider\alfa\AlfaWidgetConfiguration::is_available(),
    'script_url' =>
        \local_subscriptions\commerce\payment\provider\alfa\AlfaWidgetConfiguration::script_url(),
    'gateway' =>
        \local_subscriptions\commerce\payment\provider\alfa\AlfaWidgetConfiguration::gateway(),
    'token_present' =>
        \local_subscriptions\commerce\payment\provider\alfa\AlfaWidgetConfiguration::token() !== '',
    'token_length' =>
        strlen(
            \local_subscriptions\commerce\payment\provider\alfa\AlfaWidgetConfiguration::token()
        ),
];

if ($hasalfawidgetcandidate) {
    // H12.3.2: runtime configuration is rendered in the DOM. Keep the AMD
    // bootstrap argument-free so translations and H10 diagnostics can never
    // hit Moodle's 1024-character js_call_amd() limit.
    $PAGE->requires->js_call_amd(
        'local_subscriptions/checkout_alfa_widget',
        'init'
    );
}

if ($hassbpcandidate) {
    // Same DOM-config contract for SBP, preventing locale-dependent AMD
    // payload growth.
    $PAGE->requires->js_call_amd(
        'local_subscriptions/checkout_alfa_sbp',
        'init'
    );
}

$identity = null;
if (!$isguestcheckout) {
    $identity = CommerceCheckoutIdentityResolver::create()->resolve($currency);
    CommerceGuestCartRecoveryService::create()->recover_current($identity->userid, $currency);
} else if ($guestsession !== null && in_array($guestsession->get_status(), ['provisional', 'payment_pending', 'payment_failed'], true)) {
    $identity = CommerceCheckoutIdentityResolver::create()->resolve($currency);
    CommerceGuestCartRecoveryService::create()->recover_current($identity->userid, $currency);
}

$customerid = $identity?->userid ?? 0;
$context = new CommerceCheckoutContext(
    $customerid,
    $currency,
    current_language(),
    $selectedprovider,
    $returnurl,
    $cancelurl,
    true,
    [
        'checkout_entrypoint' => 'commerce_checkout.php',
        'checkout_phase' => 'J14B',
        'checkout_preview' => $identity === null,
        'purchase_flow' => $flow,
        'direct_purchase' => $directpurchase,
        'checkout_source' => $source,
        'showroom' => $showroom,
        'showroom_offer' => $showroomoffer,
        'payment_method' => $selectedmethod,
        'payment_execution_mode' =>
            $selectedmethod !== ''
                ? CommerceCheckoutExecutionPolicy::mode_for_route(
                    $selectedmethod,
                    (string)$selectedprovider
                )
                : '',
        'payment_country' => $paymentcountry,
        'payment_policy_advice' => $paymentpolicy->get_advice_key() ?? '',
        'payment_recommended_method' =>
            $paymentpolicy->get_recommended_method() ?? '',
    ]
);

$customer = $identity !== null
    ? new CommerceCustomer(
        $identity->userid,
        $identity->email,
        $identity->firstname,
        $identity->lastname,
        ['language' => current_language(), 'guest_checkout' => $identity->is_guest_checkout()]
    )
    : new CommerceCustomer(
        null,
        'checkout-preview@invalid.local',
        null,
        null,
        ['language' => current_language(), 'checkout_preview' => true]
    );

try {
    $snapshot = CommerceCheckoutRuntimeFactory::create()->prepare($context, $customer);
    $data = CommerceCheckoutPresenter::present(
        $snapshot,
        $providers,
        $selectedprovider,
        current_language()
    );
    $data = CommerceCartSeatReservationPresenter::create($DB)->decorate(
        $data,
        $snapshot->get_summary()->get_cart_snapshot()->get_cart()->get_uuid(),
        time()
    );
    $data['checkoutseatreservedlabel'] = get_string('commerce_checkout_seat_reserved', 'local_subscriptions');
    $data['checkoutseatreservedhelp'] = get_string('commerce_checkout_seat_reserved_help', 'local_subscriptions');
    $data['checkoutseatexpiredlabel'] = get_string('commerce_checkout_seat_expired', 'local_subscriptions');
    $data['checkoutseatexpiredhelp'] = get_string('commerce_checkout_seat_expired_help', 'local_subscriptions');
    $data += CommerceCheckoutPaymentMethodPresenter::present(
        $orderedmethods,
        $selectedmethod,
        $paymentpolicy->get_recommended_method(),
        $paymentpolicy->get_advice_key()
    );

    $expresswalletmethods =
        $expresspaymentmethods;

    // H9.1: retained H4 diagnostics still need these read-only dependencies.
    // H9 changes the Express method set, not the diagnostic provider/policy
    // contract, so initialise them explicitly before building diagnostics.
    $presentationpolicy =
        new CommercePaymentPresentationPolicy();

    $stripeprovider =
        $providerregistry->has('stripe')
            ? $providerregistry->get('stripe')
            : null;

    $stripeconfiguration = StripeConfiguration::get();
    $stripepublishablekey = trim(
        (string)($stripeconfiguration['publishable_key'] ?? '')
    );
    $hasexpresswalletcandidate =
        $stripepublishablekey !== ''
        && count($expresswalletmethods) > 0;

    $walletdebugenabled =
        debugging('', DEBUG_DEVELOPER)
        || has_capability(
            'local/subscriptions:manageconfiguration',
            context_system::instance()
        );

    $walletserverdiagnostics = [
        'currency' => $currency,
        'country' => $paymentcountry,
        'stripe_registered' =>
            $providerregistry->has('stripe'),
        'stripe_available' =>
            $stripeprovider !== null
                ? $stripeprovider->is_available()
                : false,
        'stripe_admin_allowed' =>
            $presentationpolicy->is_provider_allowed('stripe'),
        'apple_pay_admin_allowed' =>
            $presentationpolicy->is_method_allowed(
                CommercePaymentMethod::APPLE_PAY
            ),
        'google_pay_admin_allowed' =>
            $presentationpolicy->is_method_allowed(
                CommercePaymentMethod::GOOGLE_PAY
            ),
        'stripe_supports_currency' =>
            $stripeprovider !== null
                ? $stripeprovider
                    ->get_capabilities()
                    ->supports_currency($currency)
                : false,
        'stripe_supports_apple_pay' =>
            $stripeprovider !== null
                ? $stripeprovider
                    ->get_capabilities()
                    ->supports_payment_method(
                        CommercePaymentMethod::APPLE_PAY
                    )
                : false,
        'stripe_supports_google_pay' =>
            $stripeprovider !== null
                ? $stripeprovider
                    ->get_capabilities()
                    ->supports_payment_method(
                        CommercePaymentMethod::GOOGLE_PAY
                    )
                : false,
        'apple_pay_market_allowed' =>
            CommercePaymentMethodMarketEligibility::supports(
                CommercePaymentMethod::APPLE_PAY,
                $currency,
                $paymentcountry
            ),
        'google_pay_market_allowed' =>
            CommercePaymentMethodMarketEligibility::supports(
                CommercePaymentMethod::GOOGLE_PAY,
                $currency,
                $paymentcountry
            ),
        'express_methods' => $expresswalletmethods,
        'recommended_method' =>
            $paymentpolicy->get_recommended_method() ?? '',
        'publishable_key_present' =>
            $stripepublishablekey !== '',
        'has_express_wallet_candidate' =>
            $hasexpresswalletcandidate,
    ];

    if ($hasexpresswalletcandidate) {
        $PAGE->requires->js_call_amd(
            'local_subscriptions/checkout_express_wallets',
            'init',
            [[
                'publishableKey' => $stripepublishablekey,
                'locale' => current_language(),
                'currency' => strtolower($currency),
                'amount' => $snapshot->get_total_minor(),
                'methods' => $expresswalletmethods,
                'debug' => false,
                'buildId' => '7.96H4.1.8',
                'serverDiagnostics' =>
                    $walletserverdiagnostics,
                'actionUrl' => (
                    new moodle_url(
                        '/local/subscriptions/commerce_checkout_action.php'
                    )
                )->out(false),
                'returnUrl' => $returnurl,
            ]]
        );
    }
} catch (Throwable $exception) {
    if (
        $exception instanceof CommercePedagogicalSeatReservationException
        && $exception->get_code_key() === CommercePedagogicalCapacityService::SALES_CLOSED
    ) {
        redirect(
            new moodle_url('/local/subscriptions/cart.php', [
                'currency' => $currency,
            ]),
            get_string('commerce_capacity_sales_closed', 'local_subscriptions'),
            null,
            \core\output\notification::NOTIFY_WARNING
        );
    }

    $reference = substr(hash('sha256', implode('|', [
        (string)$customerid,
        $currency,
        $selectedmethod,
        $selectedprovider,
        $exception::class,
        $exception->getMessage(),
        (string)microtime(true),
    ])), 0, 12);
    error_log('[local_subscriptions][checkout_prepare][' . $reference . '] ' . $exception);
    redirect(
        new moodle_url('/local/subscriptions/cart.php', [
            'currency' => $currency,
            'checkouterror' => $reference,
        ]),
        get_string('commerce_checkout_prepare_error_reference', 'local_subscriptions', $reference),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}

$existingaccount = $isguestcheckout && $guestsession?->get_status() === 'existing_account';
$recoveringunfinishedguest = $isguestcheckout
    && $guestsession !== null
    && ($guestsession->get_metadata()['identity_resolution'] ?? '') === 'unfinished_guest_checkout_resume';
$showguestidentity = $isguestcheckout && $identity === null && !$existingaccount;

$ispersonaloffer = $source === 'personaloffer';
$personaloffer = null;
$personalofferidentity = null;
$personaloffercurrencies = [];
if ($ispersonaloffer) {
    $personaloffers = CommercePersonalOfferCheckoutService::create($DB);
    $personaloffer = $personaloffers->get_cart_offer((int)$customerid, $currency);
    if ($personaloffer !== null) {
        $personalofferidentity = $personaloffers->get_beneficiary_identity($personaloffer);

        // H13.1.6.2: the signed Personal Offer link was delivered to the
        // beneficiary mailbox, so it is already our mailbox-possession proof.
        // Bind the reserved identity to this Guest Checkout and resolve it
        // directly when at least one valid name is already known. No OTP is sent.
        if ($isguestcheckout && $guestsession !== null) {
            $reservedemail =
                \core_text::strtolower(
                    trim(
                        (string)$personalofferidentity['email']
                    )
                );
            $metadata =
                array_replace(
                    $guestsession->get_metadata(),
                    [
                        'personal_offer_reserved_email' => $reservedemail,
                        'personal_offer_uuid' => $personaloffer->get_offer_uuid(),
                    ]
                );

            $guestsession =
                $guestrepository->update_identity(
                    $guestsession,
                    $guestsession->get_user_id(),
                    $reservedemail,
                    (string)$personalofferidentity['firstname'],
                    (string)$personalofferidentity['lastname'],
                    $guestsession->get_status(),
                    $metadata
                );

            $firstnamevalid =
                \core_text::strlen(
                    trim((string)$personalofferidentity['firstname'])
                ) >= 2;
            $lastnamevalid =
                \core_text::strlen(
                    trim((string)$personalofferidentity['lastname'])
                ) >= 2;

            if (
                ($firstnamevalid || $lastnamevalid)
                && !in_array(
                    $guestsession->get_status(),
                    ['provisional', 'payment_pending', 'existing_account'],
                    true
                )
            ) {
                $guestsession =
                    CommerceGuestCheckoutService::create()->identify(
                        $guestsession,
                        $reservedemail,
                        (string)$personalofferidentity['firstname'],
                        (string)$personalofferidentity['lastname'],
                        true
                    );

                $metadata =
                    CommerceGuestIdentityVerificationState::locked_metadata(
                        array_replace(
                            $guestsession->get_metadata(),
                            [
                                'identity_proof' => 'personal_offer_signed_link',
                                'personal_offer_mailbox_proof_at' => time(),
                            ]
                        ),
                        $reservedemail,
                        time()
                    );
                $guestsession =
                    $guestrepository->transition(
                        $guestsession,
                        $guestsession->get_status(),
                        ['metadatajson' => $metadata]
                    );
            }
        }

        if ($guestsession !== null) {
            if (
                trim((string)$personalofferidentity['firstname']) === ''
                && trim((string)$guestsession->get_first_name()) !== ''
            ) {
                $personalofferidentity['firstname'] =
                    (string)$guestsession->get_first_name();
            }
            if (
                trim((string)$personalofferidentity['lastname']) === ''
                && trim((string)$guestsession->get_last_name()) !== ''
            ) {
                $personalofferidentity['lastname'] =
                    (string)$guestsession->get_last_name();
            }
        }

        foreach ($personaloffers->get_available_currencies($personaloffer) as $currencyoption) {
            $currencyoption['selected'] = $currencyoption['currency'] === $currency;
            $currencyoption['url'] = (new moodle_url('/local/subscriptions/offer_currency.php', [
                'currency' => $currencyoption['currency'],
            ]))->out(false);
            $personaloffercurrencies[] = $currencyoption;
        }
        // H13.1.6.3: Personal Offers never render the generic Guest Checkout
        // identity/OTP component. The signed offer owns the beneficiary email
        // and the dedicated Personal Offer fields below collect only missing
        // first/last names.
        $showguestidentity = false;
    }
}

$guestpaymentlocked =
    $isguestcheckout
    && !CommerceGuestPaymentGate::is_ready(
        $guestsession
    );

// Personal Offer resolution above may have transitioned the Guest session to
// existing_account. H12.9-A5.7.5: never disclose/render the existing-account
// login gate until mailbox ownership has been proven by OTP.
$guestverificationstate =
    $guestsession !== null
        ? CommerceGuestIdentityVerificationState::from_session(
            $guestsession
        )
        : null;
$existingaccount =
    $isguestcheckout
    && $guestsession?->get_status() === 'existing_account'
    && $guestverificationstate?->is_locked() === true;
$launchdisabled =
    $existingaccount
    || !$hasavailablepaymentmethod;

$resumeurl = new moodle_url('/local/subscriptions/guest_checkout_resume.php', [
    'currency' => $currency,
    'from' => 'verified_identity_login',
]);
$loginurl = new moodle_url('/login/index.php');
$embeddedloginajaxurl = new moodle_url('/local/subscriptions/ajax/guest_existing_account_login.php');
$embeddedlogin = null;
if ($existingaccount && $guestsession?->get_user_id()) {
    $existinguser = $DB->get_record(
        'user',
        ['id' => (int)$guestsession->get_user_id(), 'deleted' => 0],
        'id,username,email,auth',
        IGNORE_MISSING
    );
    if ($existinguser) {
        // Moodle's normal login stack remains authoritative (auth plugins, policies,
        // failed-login handling, etc.). We only render its POST form inside checkout.
        $SESSION->wantsurl = $resumeurl->out(false);
        $embeddedlogin = [
            'username' => (string)$existinguser->username,
            'email' => (string)$existinguser->email,
            'logintoken' => \core\session\manager::get_login_token(),
            'actionurl' => $loginurl->out(false),
            'ajaxurl' => $embeddedloginajaxurl->out(false),
        ];

        $PAGE->requires->js_call_amd(
            'local_subscriptions/guest_existing_account_login',
            'init'
        );
    }
}
$legaldocuments = (new CommerceLegalDocumentResolver())->resolve(null, current_language());
$privacyurl = new moodle_url($legaldocuments->get_privacy_url());
$termsurl = new moodle_url($legaldocuments->get_terms_url());
$offerurl = new moodle_url($legaldocuments->get_offer_url());
$legallinks = (object)[
    'policy' => html_writer::link(
        $privacyurl,
        get_string('privacy_policy', 'local_subscriptions'),
        ['target' => '_blank', 'rel' => 'noopener noreferrer']
    ),
    'terms' => html_writer::link(
        $termsurl,
        get_string('terms_cgu', 'local_subscriptions'),
        ['target' => '_blank', 'rel' => 'noopener noreferrer']
    ),
    'offer' => html_writer::link(
        $offerurl,
        get_string('terms_cgv', 'local_subscriptions'),
        ['target' => '_blank', 'rel' => 'noopener noreferrer']
    ),
];

$otheremailurl = new moodle_url('/local/subscriptions/commerce_checkout.php', array_merge($pageparams, [
    'resetidentity' => 1,
    'sesskey' => sesskey(),
    'focus' => 'email',
]));

$data += [
    'title' => get_string('commerce_checkout_title', 'local_subscriptions'),
    'stepslabel' => get_string('commerce_checkout_steps_label', 'local_subscriptions'),
    'steps' => CommercePurchaseFlow::checkout_steps($flow, $carturl),
    'stepcart' => get_string('commerce_checkout_step_cart', 'local_subscriptions'),
    'stepreview' => get_string('commerce_checkout_step_review', 'local_subscriptions'),
    'steppayment' => get_string('commerce_checkout_step_payment', 'local_subscriptions'),
    'stepconfirmation' => get_string('commerce_checkout_step_confirmation', 'local_subscriptions'),
    'ordersummarytitle' => get_string('commerce_checkout_order_summary', 'local_subscriptions'),
    'paymenttitle' => get_string('commerce_checkout_payment_title', 'local_subscriptions'),
    'subtotalLabel' => get_string('commerce_cart_subtotal', 'local_subscriptions'),
    'listtotallabel' => get_string('commerce_cart_list_total', 'local_subscriptions'),
    'productpromotiontotallabel' => get_string('commerce_cart_product_promotions_total', 'local_subscriptions'),
    'trialdiscounttotallabel' => get_string('commerce_cart_trial_discount_total', 'local_subscriptions'),
    'upgradecredittotallabel' => get_string('commerce_cart_upgrade_credit_total', 'local_subscriptions'),
    'printsummarylabel' => get_string('commerce_checkout_print_summary', 'local_subscriptions'),
    'detailedcartprintlabel' => get_string('commerce_cart_print_detailed', 'local_subscriptions'),
    'detailedcartprinturl' => (new moodle_url('/local/subscriptions/cart_print.php', [
        'currency' => $currency,
        'return' => 'checkout',
    ]))->out(false),
    'totalreductionslabel' => get_string('commerce_cart_total_reductions', 'local_subscriptions'),
    'totallabel' => get_string('commerce_cart_total_ttc', 'local_subscriptions'),
    'hasexpresswalletcandidate' => $hasexpresswalletcandidate ?? false,
    'hostedsplashmethodsjson' => json_encode(
        $hostedsplashmethods,
        JSON_UNESCAPED_SLASHES
    ),
    'hasalfawidgetcandidate' => $hasalfawidgetcandidate,
    'alfaiframeenabled' =>
        \local_subscriptions\commerce\payment\provider\alfa\AlfaIframeConfiguration::is_enabled(),
    'hassbpcandidate' => $hassbpcandidate,
    'alfawidgetscripturl' =>
        $hasalfawidgetcandidate
            ? \local_subscriptions\commerce\payment\provider\alfa\AlfaWidgetConfiguration::script_url()
            : '',
    'expresswalletmethodsjson' => json_encode(
        $expresswalletmethods ?? [],
        JSON_UNESCAPED_SLASHES
    ),
    'walletdebugenabled' => $walletdebugenabled ?? false,
    'walletserverdiagnosticsjson' =>
        json_encode(
            $walletserverdiagnostics ?? [],
            JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE
        ),
    'expresswalletlabel' => get_string(
        'commerce_checkout_express_wallet_title',
        'local_subscriptions'
    ),
    'expresswalletseparator' => get_string(
        'commerce_checkout_express_wallet_separator',
        'local_subscriptions'
    ),
    'expresswallethelp' => get_string(
        'commerce_checkout_express_wallet_help',
        'local_subscriptions'
    ),
    'expresswalletprobing' => get_string(
        'commerce_checkout_express_wallet_probing',
        'local_subscriptions'
    ),
    'checkoutor' => get_string(
        'commerce_checkout_or',
        'local_subscriptions'
    ),
    'paymentmethodlabel' => get_string('commerce_checkout_payment_method_label', 'local_subscriptions'),
    'otherpaymentmethodslabel' => get_string('commerce_checkout_other_payment_methods', 'local_subscriptions'),
    'recommendedlabel' => get_string('commerce_checkout_payment_method_recommended', 'local_subscriptions'),
    'paylabel' => get_string('commerce_checkout_continue_payment', 'local_subscriptions'),
    'cardinlinetitle' => get_string(
        'commerce_checkout_card_inline_title',
        'local_subscriptions'
    ),
    'cardinlinedescription' => get_string(
        'commerce_checkout_card_inline_description',
        'local_subscriptions'
    ),
    'cardinlineerrorlabel' => get_string(
        'commerce_checkout_card_inline_error_label',
        'local_subscriptions'
    ),
    'cardinlineopenlabel' => get_string(
        'commerce_checkout_card_open',
        'local_subscriptions'
    ),
    'cardinlineconfirmlabel' => get_string(
        'commerce_checkout_card_confirm',
        'local_subscriptions'
    ),
    'cardinlineprepareerror' => get_string(
        'commerce_checkout_card_prepare_error',
        'local_subscriptions'
    ),
    'cardinlineconfirmerror' => get_string(
        'commerce_checkout_card_confirm_error',
        'local_subscriptions'
    ),
    'alfawidgettitle' => get_string(
        'commerce_checkout_alfa_widget_title',
        'local_subscriptions'
    ),
    'alfawidgetdescription' => get_string(
        'commerce_checkout_alfa_widget_description',
        'local_subscriptions'
    ),
    'alfaiframetitle' => get_string(
        'commerce_checkout_alfa_iframe_title',
        'local_subscriptions'
    ),
    'alfaiframedescription' => get_string(
        'commerce_checkout_alfa_iframe_description',
        'local_subscriptions'
    ),
    'alfaiframefallbacklabel' => get_string(
        'commerce_checkout_alfa_iframe_fallback',
        'local_subscriptions'
    ),
    'alfaiframepreparingtitle' => get_string(
        'commerce_checkout_alfa_iframe_preparing_title',
        'local_subscriptions'
    ),
    'alfaiframepreparingmessage' => get_string(
        'commerce_checkout_alfa_iframe_preparing_message',
        'local_subscriptions'
    ),
    'alfawidgetactionurl' => (
        new moodle_url('/local/subscriptions/commerce_checkout_action.php')
    )->out(false),
    'alfawidgetprepareerror' => get_string(
        'commerce_checkout_alfa_widget_prepare_error',
        'local_subscriptions'
    ),
    'alfawidgetopenlabel' => get_string(
        'commerce_checkout_alfa_widget_open',
        'local_subscriptions'
    ),
    'alfawidgetreadylabel' => get_string(
        'commerce_checkout_alfa_widget_ready',
        'local_subscriptions'
    ),
    'alfawidgetdefaultlabel' => get_string(
        'commerce_checkout_continue_payment',
        'local_subscriptions'
    ),
    'alfawidgetcardlabel' => get_string(
        'commerce_checkout_alfa_widget_card_cta',
        'local_subscriptions'
    ),
    'alfawidgetcheckoutlanguage' => current_language(),
    'alfawidgetdebug' => $alfawidgetdebugenabled ? '1' : '0',
    'alfawidgetserverdiagnosticsjson' => json_encode(
        $alfawidgetdebugenabled ? $alfawidgetserverdiagnostics : [],
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    ),
    'sbptitle' => get_string(
        'commerce_checkout_sbp_title',
        'local_subscriptions'
    ),
    'sbpdescription' => get_string(
        'commerce_checkout_sbp_description',
        'local_subscriptions'
    ),
    'sbpopenbanklabel' => get_string(
        'commerce_checkout_sbp_open_bank',
        'local_subscriptions'
    ),
    'sbpverifylabel' => get_string(
        'commerce_checkout_sbp_verify',
        'local_subscriptions'
    ),
    'sbpactionurl' => (
        new moodle_url('/local/subscriptions/commerce_checkout_action.php')
    )->out(false),
    'sbpcheckoutlanguage' => current_language(),
    'sbpprepareerror' => get_string(
        'commerce_checkout_sbp_prepare_error',
        'local_subscriptions'
    ),
    'sbpdefaultlabel' => get_string(
        'commerce_checkout_continue_payment',
        'local_subscriptions'
    ),
    'sbppreparingtitle' => get_string(
        'commerce_checkout_sbp_preparing_title',
        'local_subscriptions'
    ),
    'sbppreparingmessage' => get_string(
        'commerce_checkout_sbp_preparing_message',
        'local_subscriptions'
    ),
    'processingpaymentlabel' => get_string('commerce_checkout_processing_payment', 'local_subscriptions'),
    'paymentprocessinglabel' => get_string(
        'commerce_checkout_payment_processing',
        'local_subscriptions'
    ),
    'paymentprocessingmessage' => get_string(
        'commerce_checkout_payment_processing_message',
        'local_subscriptions'
    ),
    'paymentvalidatedlabel' => get_string(
        'commerce_checkout_payment_validated',
        'local_subscriptions'
    ),
    'paymentvalidatedmessage' => get_string(
        'commerce_checkout_payment_validated_message',
        'local_subscriptions'
    ),
    'backlabel' => get_string(
        CommercePurchaseOrigin::checkout_back_string(
            $flow,
            $source
        ),
        'local_subscriptions'
    ),
    'backurl' => $cancelurl,
    'actionurl' => (new moodle_url('/local/subscriptions/commerce_checkout_action.php'))->out(false),
    'sesskey' => sesskey(),
    'launchdisabled' => $launchdisabled,
    'launchhint' => $existingaccount
        ? get_string('commerce_guest_checkout_existing_account', 'local_subscriptions')
        : (!$hasavailablepaymentmethod
            ? get_string('commerce_checkout_no_payment_method_for_currency', 'local_subscriptions', $currency)
            : ''),
    'paymentsecurelabel' => get_string('commerce_cart_payment_secure', 'local_subscriptions'),
    'instantaccesslabel' => get_string('commerce_m62_checkout_access_truth', 'local_subscriptions'),
    'checkoutsecureencrypted' => get_string(
        'commerce_checkout_secure_encrypted',
        'local_subscriptions'
    ),
    'checkoutdataprotected' => get_string(
        'commerce_checkout_data_protected',
        'local_subscriptions'
    ),
    'providertransitiontitle' => get_string('commerce_provider_transition_title', 'local_subscriptions'),
    'providertransitionmessage' => get_string('commerce_provider_transition_message', 'local_subscriptions'),
    'providertransitionsecuritytitle' => get_string('commerce_provider_transition_security_title', 'local_subscriptions'),
    'providertransitionsecuritymessage' => get_string('commerce_provider_transition_security_message', 'local_subscriptions'),
    'providertransitionalfa' => get_string('commerce_provider_transition_alfa', 'local_subscriptions'),
    'providertransitiondefault' => get_string('commerce_provider_transition_default', 'local_subscriptions'),
    'stripeiconurl' => (new moodle_url('/local/subscriptions/pix/email/stripe.png'))->out(false),
    'alfaiconurl' => (new moodle_url('/local/subscriptions/pix/email/alfa.png'))->out(false),
    'visaiconurl' => (new moodle_url('/local/subscriptions/pix/email/visa.png'))->out(false),
    'mastercardiconurl' => (new moodle_url('/local/subscriptions/pix/email/mastercard.png'))->out(false),
    'cardiconurl' => (new moodle_url('/local/subscriptions/pix/providers/card.svg'))->out(false),
    'cardnetworkthirdiconurl' => (new moodle_url(
        $currency === 'RUB'
            ? '/local/subscriptions/pix/providers/mir.svg'
            : '/local/subscriptions/pix/providers/card.svg'
    ))->out(false),
    'cardnetworkthirdlabel' => $currency === 'RUB' ? 'MIR' : 'CB',
    'haspaypalcandidate' => $haspaypalcandidate && $paypalclientid !== '',
    'paypalsdkurl' => $paypalsdkurl,
    'paypalclientid' => $paypalclientid,
    'paypalcurrency' => $currency,
    'paypallocale' => match (strtolower(substr(current_language(), 0, 2))) {
        'fr' => 'fr-FR',
        'ru' => 'ru-RU',
        default => 'en-US',
    },
    'paypalactionurl' => (
        new moodle_url('/local/subscriptions/commerce_checkout_action.php')
    )->out(false),
    'paypalprepareerror' => get_string(
        'commerce_checkout_paypal_prepare_error',
        'local_subscriptions'
    ),
    'paypalpopuperror' => get_string(
        'commerce_checkout_paypal_popup_error',
        'local_subscriptions'
    ),
    'paypalcancelled' => get_string(
        'commerce_checkout_paypal_cancelled',
        'local_subscriptions'
    ),
    'paypallogourl' => (new moodle_url('/local/subscriptions/pix/providers/paypal_logo.png'))->out(false),
    'alfapaylogourl' => (new moodle_url(
        is_file($CFG->dirroot . '/local/subscriptions/pix/providers/alfapay_logo.png')
            ? '/local/subscriptions/pix/providers/alfapay_logo.png'
            : '/local/subscriptions/pix/providers/alfapay.svg'
    ))->out(false),
    'sbplogourl' => (new moodle_url(
        is_file($CFG->dirroot . '/local/subscriptions/pix/providers/sbp_logo.png')
            ? '/local/subscriptions/pix/providers/sbp_logo.png'
            : '/local/subscriptions/pix/providers/sbp.svg'
    ))->out(false),
    'sberpaylogourl' => (new moodle_url(
        is_file($CFG->dirroot . '/local/subscriptions/pix/providers/sberpay_logo.png')
            ? '/local/subscriptions/pix/providers/sberpay_logo.png'
            : '/local/subscriptions/pix/providers/sberpay.svg'
    ))->out(false),
    'mirpaylogourl' => (new moodle_url(
        is_file($CFG->dirroot . '/local/subscriptions/pix/providers/mirpay_logo.png')
            ? '/local/subscriptions/pix/providers/mirpay_logo.png'
            : '/local/subscriptions/pix/providers/mirpay.svg'
    ))->out(false),
    'linklogourl' => (new moodle_url('/local/subscriptions/pix/providers/link_logo.png'))->out(false),
    'klarnalogourl' => (new moodle_url('/local/subscriptions/pix/providers/klarna_logo.png'))->out(false),
    'flow' => $flow,
    'source' => $source,
    'showroom' => $showroom,
    'showroomoffer' => $showroomoffer,
    'originreturn' => $originreturn,
    'showguestidentity' => $showguestidentity,
    'ispersonaloffer' => $ispersonaloffer && $personaloffer !== null,
    'personalofferbadge' => get_string('commerce_personal_offer_checkout_badge', 'local_subscriptions'),
    'personalofferreservedtitle' => get_string('commerce_personal_offer_checkout_reserved_title', 'local_subscriptions'),
    'personalofferreservedfor' => $personalofferidentity
        ? get_string('commerce_personal_offer_checkout_reserved_for', 'local_subscriptions', (object)[
            'name' => trim($personalofferidentity['firstname'] . ' ' . $personalofferidentity['lastname']),
            'email' => $personalofferidentity['email'],
        ])
        : '',
    'personalofferemail' => $personalofferidentity['email'] ?? '',
    'personalofferfirstname' => $personalofferidentity['firstname'] ?? '',
    'personalofferlastname' => $personalofferidentity['lastname'] ?? '',
    'personalofferneedsfirstname' => $ispersonaloffer && $personaloffer !== null && trim((string)($personalofferidentity['firstname'] ?? '')) === '',
    'personalofferneedslastname' => $ispersonaloffer && $personaloffer !== null && trim((string)($personalofferidentity['lastname'] ?? '')) === '',
    'personalofferneedsidentitycompletion' =>
        $ispersonaloffer
        && $personaloffer !== null
        && (
            $guestverificationstate === null
            || $guestverificationstate->is_locked() !== true
        )
        && (
            trim((string)($personalofferidentity['firstname'] ?? '')) === ''
            || trim((string)($personalofferidentity['lastname'] ?? '')) === ''
        ),
    'personalofferidentityconfirmurl' => (
        new moodle_url('/local/subscriptions/ajax/guest_personal_offer_identity_confirm.php')
    )->out(false),
    'guestemailreadonly' => $ispersonaloffer && $personaloffer !== null,
    'guestfirstnamereadonly' => $ispersonaloffer && $personaloffer !== null
        && trim((string)($personalofferidentity['firstname'] ?? '')) !== '',
    'guestlastnamereadonly' => $ispersonaloffer && $personaloffer !== null
        && trim((string)($personalofferidentity['lastname'] ?? '')) !== '',
    'guestallowidentityreset' => !$ispersonaloffer,
    'personaloffercurrencytitle' => get_string('commerce_personal_offer_checkout_currency_title', 'local_subscriptions'),
    'personaloffercurrencyhelp' => get_string('commerce_personal_offer_checkout_currency_help', 'local_subscriptions'),
    'personaloffercurrencies' => $personaloffercurrencies,
    'personalofferhasmultiplecurrencies' => count($personaloffercurrencies) > 1,
    'existingaccount' => $existingaccount,
    'recoveringunfinishedguest' => $recoveringunfinishedguest,
    'unfinishedguestrecoverytitle' => get_string('commerce_guest_unfinished_recovery_title', 'local_subscriptions'),
    'unfinishedguestrecoverymessage' => get_string('commerce_guest_unfinished_recovery_message', 'local_subscriptions'),
    'guestidentitytitle' => get_string('commerce_guest_checkout_identity_title', 'local_subscriptions'),
    'guestidentitydescription' => get_string('commerce_guest_checkout_identity_checkout_description', 'local_subscriptions'),
    'email' => $guestsession?->get_email() ?? '',
    'firstname' => $guestsession?->get_first_name() ?? '',
    'lastname' => $guestsession?->get_last_name() ?? '',
    'emailvalidlabel' => get_string('commerce_guest_checkout_email_valid', 'local_subscriptions'),
    'emailinvalidlabel' => get_string('commerce_guest_checkout_email_invalid_live', 'local_subscriptions'),
    'personalofferbearerproof' => $ispersonaloffer && $personaloffer !== null,
    'personalofferidentityconfirmurl' => (
        new moodle_url('/local/subscriptions/ajax/guest_personal_offer_identity_confirm.php')
    )->out(false),
    'guestotpstarturl' => (new moodle_url('/local/subscriptions/ajax/guest_identity_otp_start.php'))->out(false),
    'guestotpverifyurl' => (new moodle_url('/local/subscriptions/ajax/guest_identity_otp_verify.php'))->out(false),
    'guestotptitle' => get_string('commerce_guest_identity_otp_title', 'local_subscriptions'),
    'guestotpmessage' => get_string('commerce_guest_identity_otp_message', 'local_subscriptions'),
    'guestotpsending' => get_string('commerce_guest_identity_otp_sending', 'local_subscriptions'),
    'guestotpsent' => get_string('commerce_guest_identity_otp_sent', 'local_subscriptions'),
    'guestotpchecking' => get_string('commerce_guest_identity_otp_checking', 'local_subscriptions'),
    'guestotpinvalid' => get_string('commerce_guest_identity_otp_invalid', 'local_subscriptions'),
    'guestotpexpired' => get_string('commerce_guest_identity_otp_expired', 'local_subscriptions'),
    'guestotplimited' => get_string('commerce_guest_identity_otp_limited', 'local_subscriptions'),
    'guestotperror' => get_string('commerce_guest_identity_otp_error', 'local_subscriptions'),
    'guestotpresend' => get_string('commerce_guest_identity_otp_resend', 'local_subscriptions'),
    'guestotpverified' => get_string('commerce_guest_identity_otp_verified', 'local_subscriptions'),
    'guestotpotheremail' => get_string('commerce_guest_checkout_other_email', 'local_subscriptions'),
    'guestidentityconfirm' => get_string('commerce_guest_identity_confirm', 'local_subscriptions'),
    'guestpaymentlocked' => $guestpaymentlocked,
    'guestpaymentgatehint' =>
        $ispersonaloffer
            ? get_string(
                'commerce_personal_offer_guest_details_gate_hint',
                'local_subscriptions'
            )
            : get_string(
                'commerce_guest_payment_gate_hint',
                'local_subscriptions'
            ),
    'existingmessage' => get_string('commerce_guest_checkout_existing_account', 'local_subscriptions'),
    'hasembeddedlogin' => $embeddedlogin !== null,
    'embeddedloginemail' => $embeddedlogin['email'] ?? '',
    'embeddedloginusername' => $embeddedlogin['username'] ?? '',
    'embeddedloginlogintoken' => $embeddedlogin['logintoken'] ?? '',
    'embeddedloginactionurl' => $embeddedlogin['actionurl'] ?? $loginurl->out(false),
    'embeddedlogintitle' => get_string('commerce_checkout_existing_account_login_title', 'local_subscriptions'),
    'embeddedloginhelp' => get_string('commerce_checkout_existing_account_login_help', 'local_subscriptions'),
    'embeddedloginpasswordlabel' => get_string('password'),
    'embeddedloginsubmitlabel' => get_string('commerce_checkout_existing_account_login_submit', 'local_subscriptions'),
    'embeddedloginverifiedlabel' => get_string(
        'commerce_checkout_existing_account_verified_email',
        'local_subscriptions'
    ),
    'embeddedloginajaxurl' => $embeddedlogin['ajaxurl'] ?? '',
    'embeddedlogininvalid' => get_string(
        'commerce_checkout_existing_account_login_invalid',
        'local_subscriptions'
    ),
    'embeddedloginworking' => get_string(
        'commerce_checkout_existing_account_login_working',
        'local_subscriptions'
    ),
    'embeddedloginerror' => get_string(
        'commerce_checkout_existing_account_login_error',
        'local_subscriptions'
    ),
    'embeddedloginalternativelabel' => get_string('commerce_checkout_existing_account_login_alternative', 'local_subscriptions'),
    'loginurl' => $loginurl->out(false),
    'loginlabel' => get_string('login'),
    'otheremailurl' => $otheremailurl->out(false),
    'otheremaillabel' => get_string('commerce_guest_checkout_other_email', 'local_subscriptions'),
    'legalacceptlabel' => get_string('i_accept_all_terms', 'local_subscriptions', $legallinks),
    'termsrequiredlabel' => get_string('commerce_checkout_terms_required', 'local_subscriptions'),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_subscriptions/checkout/page', $data);
echo $OUTPUT->footer();
