<?php

require_once(__DIR__ . '/../../config.php');

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\checkout\flow\CommercePurchaseFlow;
use local_subscriptions\commerce\checkout\guest\CommerceCheckoutIdentityResolver;
use local_subscriptions\commerce\checkout\guest\CommerceGuestCartRecoveryService;
use local_subscriptions\commerce\checkout\guest\CommerceGuestCheckoutService;
use local_subscriptions\commerce\checkout\guest\CommerceGuestCheckoutSessionRepository;
use local_subscriptions\commerce\checkout\express\CommerceCheckoutExpressService;
use local_subscriptions\commerce\checkout\unified\CommerceCheckoutContext;
use local_subscriptions\commerce\checkout\unified\CommerceCheckoutRuntimeFactory;
use local_subscriptions\commerce\purchase\CommerceCustomer;
use local_subscriptions\commerce\personaloffer\service\CommercePersonalOfferCheckoutService;
use local_subscriptions\support\Region;
use local_subscriptions\url\UrlFactory;

\local_subscriptions\subscription_config::guard_public_access();
require_sesskey();

global $DB, $SESSION;

$currency = strtoupper(required_param('currency', PARAM_ALPHA));
$provider = strtolower(required_param('provider', PARAM_ALPHANUMEXT));
$flow = CommercePurchaseFlow::normalise(optional_param('flow', CommercePurchaseFlow::CART, PARAM_ALPHA));
$source = strtolower(optional_param('source', '', PARAM_ALPHANUMEXT));
$showroom = strtolower(optional_param('showroom', '', PARAM_ALPHANUMEXT));
$showroomoffer = strtolower(optional_param('showroomoffer', '', PARAM_ALPHANUMEXT));
$originreturn = optional_param('originreturn', '', PARAM_LOCALURL);
$acceptterms = optional_param('accept_terms', 0, PARAM_BOOL);

if (!in_array($currency, ['EUR', 'RUB'], true)) {
    $currency = in_array(Region::detect_country(), ['RU', 'BY'], true) ? 'RUB' : 'EUR';
}

$checkoutparams = [
    'currency' => $currency,
    'provider' => $provider,
    'flow' => $flow,
];
foreach (['source' => $source, 'showroom' => $showroom, 'showroomoffer' => $showroomoffer, 'originreturn' => $originreturn] as $key => $value) {
    if ($value !== '') {
        $checkoutparams[$key] = $value;
    }
}

if (!$acceptterms) {
    redirect(
        new moodle_url('/local/subscriptions/commerce_checkout.php', $checkoutparams),
        get_string('commerce_checkout_terms_required', 'local_subscriptions'),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}

try {
    $personalofferidentity = null;
    if ($source === 'personaloffer' && (!isloggedin() || isguestuser())) {
        $personaloffers = CommercePersonalOfferCheckoutService::create($DB);
        $cartoffer = $personaloffers->get_cart_offer(0, $currency);
        if ($cartoffer === null || !$cartoffer->is_available_at(time())) {
            throw new moodle_exception('commerce_personal_offer_not_redeemable', 'local_subscriptions');
        }
        $personalofferidentity = $personaloffers->get_beneficiary_identity($cartoffer);
    }

    if (!isloggedin() || isguestuser()) {
        $token = trim((string)($SESSION->local_subscriptions_guest_checkout_token ?? ''));
        $sessions = new CommerceGuestCheckoutSessionRepository($DB);
        $guestsession = $token !== '' ? $sessions->find_by_token($token) : null;
        if ($guestsession === null || $guestsession->is_expired() || $guestsession->get_currency() !== $currency) {
            $guestsession = CommerceGuestCheckoutService::create()->start($currency, [
                'entrypoint' => 'commerce_checkout_action.php',
                'purchase_flow' => $flow,
                'checkout_source' => $source,
                'showroom' => $showroom,
                'showroom_offer' => $showroomoffer,
            'origin_return' => $originreturn,
            ]);
            $SESSION->local_subscriptions_guest_checkout_token = $guestsession->get_token();
        }

        if (!in_array($guestsession->get_status(), ['provisional', 'payment_pending'], true)) {
            if ($personalofferidentity !== null) {
                // Identity attached to a Personal Offer is authoritative server-side.
                // Only genuinely missing name fields may be supplied by the customer.
                $email = (string)$personalofferidentity['email'];
                $firstname = trim((string)$personalofferidentity['firstname']);
                $lastname = trim((string)$personalofferidentity['lastname']);
                if ($firstname === '') {
                    $firstname = (string)required_param('firstname', PARAM_RAW_TRIMMED);
                }
                if ($lastname === '') {
                    $lastname = (string)required_param('lastname', PARAM_RAW_TRIMMED);
                }
            } else {
                $email = (string)required_param('email', PARAM_RAW_TRIMMED);
                $firstname = (string)required_param('firstname', PARAM_RAW_TRIMMED);
                $lastname = (string)required_param('lastname', PARAM_RAW_TRIMMED);
            }
            $guestsession = CommerceGuestCheckoutService::create()->identify(
                $guestsession,
                $email,
                $firstname,
                $lastname
            );
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
    (new CommerceCheckoutExpressService())->record_legal_acceptance($identity->userid);

    $returnurl = (new moodle_url('/local/subscriptions/payment/return.php'))->out(false);
    $carturl = (UrlFactory::cart(['currency' => $currency]))->out(false);
    $cancelurl = $flow === CommercePurchaseFlow::DIRECT && $originreturn !== ''
        ? (new moodle_url($originreturn))->out(false)
        : $carturl;

    $context = new CommerceCheckoutContext(
        $identity->userid,
        $currency,
        current_language(),
        $provider,
        $returnurl,
        $cancelurl,
        true,
        [
            'checkout_entrypoint' => 'commerce_checkout_action.php',
            'checkout_phase' => 'J14B',
            'purchase_flow' => $flow,
            'checkout_source' => $source,
            'showroom' => $showroom,
            'showroom_offer' => $showroomoffer,
        ]
    );
    $customer = new CommerceCustomer(
        $identity->userid,
        $identity->email,
        $identity->firstname,
        $identity->lastname,
        ['language' => current_language(), 'guest_checkout' => $identity->is_guest_checkout()]
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
        'currency' => $currency,
        'provider' => $provider,
        'flow' => $flow,
        'chain' => $chain,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

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
