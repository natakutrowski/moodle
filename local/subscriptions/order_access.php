<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/filelib.php');

use local_subscriptions\commerce\checkout\guest\CommerceGuestCheckoutSessionRepository;
use local_subscriptions\commerce\fulfillment\native\digital\CommerceNativeDigitalDownloadResolver;
use local_subscriptions\commerce\order\presentation\CommerceOrderPresentationAccessDeniedException;
use local_subscriptions\commerce\order\presentation\CommerceOrderPresentationService;
use local_subscriptions\event\digital_file_downloaded;

\local_subscriptions\subscription_config::guard_public_access();

global $DB, $SESSION, $USER;

$reference = required_param('reference', PARAM_ALPHANUMEXT);
$grantreference = required_param('grant', PARAM_ALPHANUMEXT);
$version = optional_param('version', 'desktop', PARAM_ALPHA);
if (!in_array($version, ['desktop', 'mobile'], true)) {
    $version = 'desktop';
}
$service = CommerceOrderPresentationService::create();

try {
    if (isloggedin() && !isguestuser()) {
        $order = $service->find_for_user($reference, (int)$USER->id);
    } else {
        $guestsession = (new CommerceGuestCheckoutSessionRepository($DB))->find_by_purchase_reference($reference);
        $token = trim((string)($SESSION->local_subscriptions_guest_checkout_token ?? ''));
        if ($guestsession === null || $token === '' || !hash_equals($guestsession->get_token(), $token)) {
            throw new CommerceOrderPresentationAccessDeniedException('Guest Checkout session does not own this order.');
        }
        $order = $service->find_for_user($reference, (int)$guestsession->get_user_id());
    }
} catch (CommerceOrderPresentationAccessDeniedException $exception) {
    throw new moodle_exception('commerce_public_access_denied', 'local_subscriptions');
}

if ($order === null) {
    throw new moodle_exception('commerce_i2_order_not_found', 'local_subscriptions');
}

$access = null;
foreach ($order->items as $item) {
    foreach ($item->accesses as $candidate) {
        if ($candidate->grantreference === $grantreference) {
            $access = $candidate;
            break 2;
        }
    }
}
if ($access === null || !$access->available) {
    throw new moodle_exception('commerce_i3_access_unavailable', 'local_subscriptions');
}

if ($access->type === 'course_access') {
    $courseid = (int)($access->metadata['courseid'] ?? 0);
    if ($courseid <= 0 || !$DB->record_exists('course', ['id' => $courseid])) {
        throw new moodle_exception('commerce_i3_access_missing', 'local_subscriptions');
    }
    redirect(new moodle_url('/course/view.php', ['id' => $courseid]));
}

if ($access->type === 'digital_download') {
    $record = $DB->get_record('local_subs_commerce_dig_access', [
        'grantreference' => $grantreference,
        'purchasereference' => $reference,
    ], '*', MUST_EXIST);
    $resolver = new CommerceNativeDigitalDownloadResolver($DB);
    $download = $resolver->resolve((string)$record->downloadtoken, time(), $version);
    $downloadedat = time();
    $resolver->register_download($download['access'], $downloadedat);
    $eventdata = [
        'context' => \context_system::instance(),
        'objectid' => (int)$record->id,
        'other' => [
            'variant' => $version,
            'grantreference' => $grantreference,
            'purchasereference' => $reference,
        ],
    ];
    if ($record->beneficiaryuserid !== null) {
        $eventdata['relateduserid'] = (int)$record->beneficiaryuserid;
    }
    digital_file_downloaded::create($eventdata)->trigger();
    if ($download['storedfile'] instanceof stored_file) {
        send_stored_file($download['storedfile'], 0, 0, true, ['filename' => $download['filename']]);
    }
    send_file($download['filepath'], $download['filename'], 0, 0, false, true, 'application/pdf');
}

throw new moodle_exception('commerce_i3_access_unsupported', 'local_subscriptions');
