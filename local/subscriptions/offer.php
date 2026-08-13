<?php

require_once(__DIR__ . '/../../config.php');

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\personaloffer\campaign\CommercePersonalOfferCampaignEmailService;
use local_subscriptions\commerce\personaloffer\service\CommercePersonalOfferCheckoutService;
use local_subscriptions\commerce\personaloffer\service\CommercePersonalOfferIdentityConflictException;
use local_subscriptions\commerce\personaloffer\service\CommercePersonalOfferIdentityConflictPresenter;
use local_subscriptions\commerce\personaloffer\service\CommercePersonalOfferDestinationResolver;
use local_subscriptions\commerce\personaloffer\service\CommercePersonalOfferSessionService;
use local_subscriptions\commerce\showroom\CommerceShowroomUrl;
use local_subscriptions\support\Region;
use local_subscriptions\url\UrlFactory;

\local_subscriptions\subscription_config::guard_public_access();

global $USER, $SESSION;

$token = trim((string)required_param('token', PARAM_RAW_TRIMMED));
$requestedcurrency = strtoupper(optional_param('currency', '', PARAM_ALPHA));
$requesteddestination = strtolower(optional_param('destination', '', PARAM_ALPHA));
if (!in_array($requesteddestination, ['', 'checkout'], true)) {
    throw new invalid_parameter_exception('Unsupported Personal Offer destination override.');
}
$requestedanchor = strtolower(optional_param('anchor', '', PARAM_ALPHANUMEXT));
if (!in_array($requestedanchor, ['', 'showroom-offers'], true)) {
    throw new invalid_parameter_exception('Unsupported Personal Offer return anchor.');
}
$fallbackcurrency = in_array(Region::detect_country(), ['RU', 'BY'], true) ? 'RUB' : 'EUR';

try {
    $personaloffers = CommercePersonalOfferCheckoutService::create();
    $currency = in_array($requestedcurrency, ['EUR', 'RUB'], true)
        ? $requestedcurrency
        : $personaloffers->choose_currency($token, $fallbackcurrency);
    $userid = isloggedin() && !isguestuser() ? (int)$USER->id : null;
    $email = $userid !== null ? (string)$USER->email : null;
    // Validate the signed offer and authoritative currency before resolving any destination.
    // This does not mutate the cart, which is important for Showroom-first campaigns.
    $validated = $personaloffers->validate_entry($token, $currency, $userid, $email);
    $destination = CommercePersonalOfferDestinationResolver::create()->resolve($validated['offer']);
    $campaigndestination = $destination;
    if ($requesteddestination === CommercePersonalOfferCampaignEmailService::DESTINATION_CHECKOUT) {
        // Direct checkout is a safe one-way override: it never changes offer/product/price
        // and can never force a Showroom that the Campaign did not authorise.
        $destination = [
            'destination' => CommercePersonalOfferCampaignEmailService::DESTINATION_CHECKOUT,
            'campaignid' => $destination['campaignid'] ?? null,
            'showroomid' => null,
            'showroomkey' => null,
            'definition' => null,
        ];
    }

    (new CommercePersonalOfferSessionService())->initialise(
        $token,
        $validated['offer'],
        $validated['sku'],
        $currency,
        $destination
    );

    if ($destination['destination'] === CommercePersonalOfferCampaignEmailService::DESTINATION_SHOWROOM) {
        if ($destination['definition'] === null) {
            throw new moodle_exception('commerce_personal_offer_link_unavailable', 'local_subscriptions');
        }
        $SESSION->local_subscriptions_showroom_currency = $currency;
        $SESSION->local_subscriptions_storefront_currency = $currency;
        $showroomurl = CommerceShowroomUrl::make(
            $destination['definition'],
            ['currency' => $currency],
            current_language()
        );
        $showroomtarget = $showroomurl->out(false);
        if ($requestedanchor === 'showroom-offers') {
            // Append the fragment explicitly to the final Location target. This avoids
            // losing the anchor during redirect URL normalisation while keeping the
            // anchor itself server allow-listed above.
            $showroomtarget .= '#showroom-offers';
        }
        redirect($showroomtarget);
    }

    // Historical/direct-checkout campaigns retain the exact existing cart preparation path.
    $personaloffers->prepare($token, $currency, $userid, $email, current_language());

    // If this is a Showroom-first campaign and the customer deliberately chose direct
    // checkout, "Back to offer" must return to the Showroom rather than re-entering the
    // checkout override and looping on the same page.
    $originreturn = '';
    if (
        ($campaigndestination['destination'] ?? '') === CommercePersonalOfferCampaignEmailService::DESTINATION_SHOWROOM
        && ($campaigndestination['definition'] ?? null) !== null
    ) {
        // Re-enter through the signed Personal Offer boundary so the Showroom session
        // is revalidated/reinitialised before displaying the personalised prices again.
        $originreturn = (new moodle_url('/local/subscriptions/offer.php', [
            'token' => $token,
            'currency' => $currency,
            'anchor' => 'showroom-offers',
        ]))->out(false);
    }

    $checkoutparams = [
        'currency' => $currency,
        'flow' => 'direct',
        'source' => 'personaloffer',
    ];
    if ($originreturn !== '') {
        $checkoutparams['originreturn'] = $originreturn;
    }
    redirect(new moodle_url('/local/subscriptions/commerce_checkout.php', $checkoutparams));
} catch (CommercePersonalOfferIdentityConflictException $exception) {
    // The signed offer itself is valid; only the currently authenticated Moodle
    // identity conflicts with its beneficiary. Keep the security boundary intact,
    // but offer a clear, reversible logout-and-retry path.
    $PAGE->set_context(context_system::instance());
    $PAGE->set_url(new moodle_url('/local/subscriptions/offer.php'));
    $PAGE->set_pagelayout('standard');
    $PAGE->set_title(get_string('commerce_personal_offer_identity_conflict_title', 'local_subscriptions'));
    $PAGE->set_heading(get_string('commerce_personal_offer_identity_conflict_title', 'local_subscriptions'));

    $beneficiarymasked = CommercePersonalOfferIdentityConflictPresenter::mask_email($exception->beneficiaryemail);
    $currentmasked = CommercePersonalOfferIdentityConflictPresenter::mask_email($exception->currentemail);

    echo $OUTPUT->header();
    echo $OUTPUT->notification(
        get_string(
            'commerce_personal_offer_identity_conflict_message',
            'local_subscriptions',
            (object)['offeremail' => $beneficiarymasked, 'currentemail' => $currentmasked]
        ),
        \core\output\notification::NOTIFY_WARNING
    );
    echo html_writer::start_tag('form', [
        'method' => 'post',
        'action' => (new moodle_url('/local/subscriptions/personal_offer_identity_continue.php'))->out(false),
        'class' => 'mb-3',
    ]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'token', 'value' => $token]);
    if ($requestedcurrency !== '') {
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'currency', 'value' => $requestedcurrency]);
    }
    if ($requesteddestination !== '') {
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'destination', 'value' => $requesteddestination]);
    }
    if ($requestedanchor !== '') {
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'anchor', 'value' => $requestedanchor]);
    }
    echo html_writer::tag(
        'button',
        get_string('commerce_personal_offer_identity_conflict_continue', 'local_subscriptions'),
        ['type' => 'submit', 'class' => 'btn btn-primary']
    );
    echo html_writer::end_tag('form');
    echo html_writer::link(
        UrlFactory::digital_catalog(),
        get_string('commerce_personal_offer_identity_conflict_cancel', 'local_subscriptions'),
        ['class' => 'btn btn-outline-secondary']
    );
    echo $OUTPUT->footer();
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
