<?php

require_once(__DIR__ . '/../../config.php');

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\checkout\flow\CommercePurchaseFlow;
use local_subscriptions\commerce\checkout\guest\CommerceCheckoutIdentityResolver;
use local_subscriptions\commerce\checkout\guest\CommerceGuestCartRecoveryService;
use local_subscriptions\commerce\checkout\guest\CommerceGuestCheckoutService;
use local_subscriptions\commerce\checkout\guest\CommerceGuestCheckoutSessionRepository;
use local_subscriptions\commerce\checkout\unified\CommerceCheckoutContext;
use local_subscriptions\commerce\checkout\unified\CommerceCheckoutRuntimeFactory;
use local_subscriptions\commerce\checkout\unified\presentation\CommerceCheckoutPresenter;
use local_subscriptions\commerce\purchase\CommerceCustomer;
use local_subscriptions\commerce\personaloffer\service\CommercePersonalOfferCheckoutService;
use local_subscriptions\commerce\runtime\CommerceRuntimeFactory;
use local_subscriptions\support\Region;
use local_subscriptions\url\UrlFactory;

\local_subscriptions\subscription_config::guard_public_access();

global $DB, $SESSION, $USER;

$currency = strtoupper(optional_param('currency', '', PARAM_ALPHA));
if (!in_array($currency, ['EUR', 'RUB'], true)) {
    $currency = in_array(Region::detect_country(), ['RU', 'BY'], true) ? 'RUB' : 'EUR';
}

$flow = CommercePurchaseFlow::normalise(optional_param('flow', CommercePurchaseFlow::CART, PARAM_ALPHA));
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
            'checkout_source' => $source,
            'showroom' => $showroom,
            'showroom_offer' => $showroomoffer,
            'origin_return' => $originreturn,
        ]);
        $SESSION->local_subscriptions_guest_checkout_token = $guestsession->get_token();
    }

    if (optional_param('resetidentity', 0, PARAM_BOOL)) {
        require_sesskey();
        $metadata = $guestsession->get_metadata();
        unset($metadata['identity_resolution']);
        $guestsession = $guestrepository->transition($guestsession, 'identity_pending', [
            'userid' => null,
            'email' => null,
            'firstname' => null,
            'lastname' => null,
            'metadatajson' => $metadata,
        ]);
    }
}

$providers = CommerceRuntimeFactory::create()->payment_providers()->all();
$availablekeys = [];
foreach ($providers as $provider) {
    if ($provider->is_available()) {
        $availablekeys[] = $provider->get_key();
    }
}

$requestedprovider = strtolower(optional_param('provider', '', PARAM_ALPHANUMEXT));
$defaultprovider = $currency === 'RUB' ? 'alfa' : 'stripe';
$selectedprovider = in_array($requestedprovider, $availablekeys, true)
    ? $requestedprovider
    : (in_array($defaultprovider, $availablekeys, true) ? $defaultprovider : ($availablekeys[0] ?? $defaultprovider));

$pageparams = [
    'currency' => $currency,
    'provider' => $selectedprovider,
    'flow' => $flow,
];
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
$PAGE->requires->js_call_amd('local_subscriptions/guest_checkout_security', 'init');
$PAGE->requires->js_call_amd('local_subscriptions/payment_provider_transition', 'init');

$identity = null;
if (!$isguestcheckout) {
    $identity = CommerceCheckoutIdentityResolver::create()->resolve($currency);
    CommerceGuestCartRecoveryService::create()->recover_current($identity->userid, $currency);
} else if ($guestsession !== null && in_array($guestsession->get_status(), ['provisional', 'payment_pending'], true)) {
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
        'checkout_source' => $source,
        'showroom' => $showroom,
        'showroom_offer' => $showroomoffer,
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
    $data = CommerceCheckoutPresenter::present($snapshot, $providers, $selectedprovider, current_language());
} catch (Throwable $exception) {
    $reference = substr(hash('sha256', implode('|', [
        (string)$customerid,
        $currency,
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

        // Personal Offer identity is already authoritative. Resolve it now, before the
        // customer reaches the Pay button, so an existing Moodle account becomes an
        // explicit authentication prerequisite instead of a late checkout error.
        if ($isguestcheckout && $guestsession !== null
                && !in_array($guestsession->get_status(), ['provisional', 'payment_pending'], true)) {
            $guestsession = CommerceGuestCheckoutService::create()->identify(
                $guestsession,
                (string)$personalofferidentity['email'],
                (string)$personalofferidentity['firstname'],
                (string)$personalofferidentity['lastname'],
                true
            );
        }

        foreach ($personaloffers->get_available_currencies($personaloffer) as $currencyoption) {
            $currencyoption['selected'] = $currencyoption['currency'] === $currency;
            $currencyoption['url'] = (new moodle_url('/local/subscriptions/offer_currency.php', [
                'currency' => $currencyoption['currency'],
            ]))->out(false);
            $personaloffercurrencies[] = $currencyoption;
        }
        // Personal Offers never ask the customer to re-enter identity data we already own.
        $showguestidentity = false;
    }
}

// Personal Offer resolution above may have transitioned the Guest session to existing_account.
$existingaccount = $isguestcheckout && $guestsession?->get_status() === 'existing_account';
$launchdisabled = $existingaccount;

$resumeurl = new moodle_url('/local/subscriptions/guest_checkout_resume.php', [
    'currency' => $currency,
]);
$loginurl = new moodle_url('/login/index.php');
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
        ];
    }
}
$privacyurl = new moodle_url('/privacy');
$termsurl = new moodle_url('/terms');
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
        $termsurl,
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
    'subtitle' => get_string('commerce_checkout_subtitle', 'local_subscriptions'),
    'stepslabel' => get_string('commerce_checkout_steps_label', 'local_subscriptions'),
    'steps' => CommercePurchaseFlow::checkout_steps($flow, $carturl),
    'stepcart' => get_string('commerce_checkout_step_cart', 'local_subscriptions'),
    'stepreview' => get_string('commerce_checkout_step_review', 'local_subscriptions'),
    'steppayment' => get_string('commerce_checkout_step_payment', 'local_subscriptions'),
    'stepconfirmation' => get_string('commerce_checkout_step_confirmation', 'local_subscriptions'),
    'ordersummarytitle' => get_string('commerce_checkout_order_summary', 'local_subscriptions'),
    'paymenttitle' => get_string('commerce_checkout_payment_title', 'local_subscriptions'),
    'paymentdescription' => get_string('commerce_checkout_payment_description', 'local_subscriptions'),
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
    'providerlabel' => get_string('commerce_checkout_provider_label', 'local_subscriptions'),
    'paylabel' => get_string('commerce_checkout_continue_payment', 'local_subscriptions'),
    'backlabel' => get_string(
        $flow === CommercePurchaseFlow::DIRECT ? 'commerce_checkout_back_offer' : 'commerce_checkout_back_cart',
        'local_subscriptions'
    ),
    'backurl' => $cancelurl,
    'actionurl' => (new moodle_url('/local/subscriptions/commerce_checkout_action.php'))->out(false),
    'sesskey' => sesskey(),
    'launchdisabled' => $launchdisabled,
    'launchhint' => $existingaccount ? get_string('commerce_guest_checkout_existing_account', 'local_subscriptions') : '',
    'paymentsecurelabel' => get_string('commerce_cart_payment_secure', 'local_subscriptions'),
    'instantaccesslabel' => get_string('commerce_cart_instant_access', 'local_subscriptions'),
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
    'personaloffercurrencytitle' => get_string('commerce_personal_offer_checkout_currency_title', 'local_subscriptions'),
    'personaloffercurrencyhelp' => get_string('commerce_personal_offer_checkout_currency_help', 'local_subscriptions'),
    'personaloffercurrencies' => $personaloffercurrencies,
    'personalofferhasmultiplecurrencies' => count($personaloffercurrencies) > 1,
    'existingaccount' => $existingaccount,
    'guestidentitytitle' => get_string('commerce_guest_checkout_identity_title', 'local_subscriptions'),
    'guestidentitydescription' => get_string('commerce_guest_checkout_identity_checkout_description', 'local_subscriptions'),
    'email' => $guestsession?->get_email() ?? '',
    'firstname' => $guestsession?->get_first_name() ?? '',
    'lastname' => $guestsession?->get_last_name() ?? '',
    'emailvalidlabel' => get_string('commerce_guest_checkout_email_valid', 'local_subscriptions'),
    'emailinvalidlabel' => get_string('commerce_guest_checkout_email_invalid_live', 'local_subscriptions'),
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
    'embeddedloginalternativelabel' => get_string('commerce_checkout_existing_account_login_alternative', 'local_subscriptions'),
    'loginurl' => $loginurl->out(false),
    'loginlabel' => get_string('login'),
    'otheremailurl' => $otheremailurl->out(false),
    'otheremaillabel' => get_string('commerce_guest_checkout_other_email', 'local_subscriptions'),
    'legalacceptlabel' => get_string('i_accept_all_terms', 'local_subscriptions', $legallinks),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_subscriptions/checkout/page', $data);
echo $OUTPUT->footer();
