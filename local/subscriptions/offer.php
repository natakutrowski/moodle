<?php

require_once(__DIR__ . '/../../config.php');

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\personaloffer\service\CommercePersonalOfferCheckoutService;
use local_subscriptions\support\Region;
use local_subscriptions\url\UrlFactory;

\local_subscriptions\subscription_config::guard_public_access();

global $USER, $SESSION;

$token = trim((string)required_param('token', PARAM_RAW_TRIMMED));
$requestedcurrency = strtoupper(optional_param('currency', '', PARAM_ALPHA));
$fallbackcurrency = in_array(Region::detect_country(), ['RU', 'BY'], true) ? 'RUB' : 'EUR';

try {
    $personaloffers = CommercePersonalOfferCheckoutService::create();
    $currency = in_array($requestedcurrency, ['EUR', 'RUB'], true)
        ? $requestedcurrency
        : $personaloffers->choose_currency($token, $fallbackcurrency);
    $userid = isloggedin() && !isguestuser() ? (int)$USER->id : null;
    $email = $userid !== null ? (string)$USER->email : null;
    $prepared = $personaloffers->prepare(
        $token,
        $currency,
        $userid,
        $email,
        current_language()
    );
    $SESSION->local_subscriptions_personal_offer_token = $token;
    $SESSION->local_subscriptions_personal_offer_uuid = $prepared['offer']->get_offer_uuid();

    redirect(new moodle_url('/local/subscriptions/commerce_checkout.php', [
        'currency' => $currency,
        'flow' => 'direct',
        'source' => 'personaloffer',
        'originreturn' => '/local/subscriptions/offer.php?token=' . rawurlencode($token) . '&currency=' . rawurlencode($currency),
    ]));
} catch (Throwable $exception) {
    // Do not turn every checkout/runtime failure into "invalid offer link".
    // That message is reserved for genuine offer availability/identity failures.
    $knownunavailable = $exception instanceof \moodle_exception && in_array($exception->errorcode, [
        'commerce_personal_offer_link_unavailable',
        'commerce_personal_offer_target_unavailable',
        'commerce_personal_offer_currency_unavailable',
        'commerce_personal_offer_identity_mismatch',
        'commerce_personal_offer_not_redeemable',
    ], true);

    if (!$knownunavailable) {
        // Preserve the real exception in developer/debug environments and logs.
        debugging(
            'Personal Offer entry failed: ' . $exception->getMessage(),
            DEBUG_DEVELOPER
        );
    }

    $PAGE->set_context(context_system::instance());
    $PAGE->set_url(new moodle_url('/local/subscriptions/offer.php'));
    $PAGE->set_pagelayout('standard');
    $PAGE->set_title(get_string('commerce_personal_offer_page_title', 'local_subscriptions'));
    $PAGE->set_heading(get_string('commerce_personal_offer_page_title', 'local_subscriptions'));
    echo $OUTPUT->header();
    echo $OUTPUT->notification(
        $knownunavailable
            ? get_string('commerce_personal_offer_link_unavailable', 'local_subscriptions')
            : get_string('commerce_personal_offer_checkout_temporary_error', 'local_subscriptions'),
        \core\output\notification::NOTIFY_ERROR
    );
    if (!$knownunavailable && debugging('', DEBUG_DEVELOPER)) {
        echo html_writer::div(
            s(get_class($exception) . ': ' . $exception->getMessage()),
            'alert alert-warning small'
        );
    }
    echo html_writer::link(
        UrlFactory::digital_catalog(),
        get_string('commerce_personal_offer_back_store', 'local_subscriptions'),
        ['class' => 'btn btn-primary']
    );
    echo $OUTPUT->footer();
}
