<?php

require_once(__DIR__ . '/../../config.php');

use local_subscriptions\url\UrlFactory;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\service\CommerceCartRuntimeFactory;
use local_subscriptions\commerce\checkout\guest\CommerceGuestCartTransferService;
use local_subscriptions\commerce\checkout\guest\CommerceGuestCheckoutSessionRepository;
use local_subscriptions\support\Region;

\local_subscriptions\subscription_config::guard_public_access();
require_login();

$currency = strtoupper(optional_param('currency', '', PARAM_ALPHA));
if (!in_array($currency, ['EUR', 'RUB'], true)) {
    $currency = in_array(Region::detect_country(), ['RU', 'BY'], true) ? 'RUB' : 'EUR';
}

$carturl = UrlFactory::cart(['currency' => $currency]);
$checkouturl = null;
$token = (string)($SESSION->local_subscriptions_guest_checkout_token ?? '');

if ($token === '') {
    redirect($carturl);
}

$repository = new CommerceGuestCheckoutSessionRepository($DB);
$guestsession = $repository->find_by_token($token);

if ($guestsession === null || $guestsession->is_expired() || $guestsession->get_currency() !== $currency) {
    unset($SESSION->local_subscriptions_guest_checkout_token);
    redirect(
        $carturl,
        get_string('commerce_guest_checkout_session_expired', 'local_subscriptions'),
        null,
        \core\output\notification::NOTIFY_WARNING
    );
}

if ($guestsession->get_status() !== 'existing_account'
        || $guestsession->get_user_id() !== (int)$USER->id) {
    redirect(
        $carturl,
        get_string('commerce_guest_checkout_account_mismatch', 'local_subscriptions'),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}

$durablecart = $guestsession->get_metadata()['guest_cart_snapshot'] ?? null;
$transferred = CommerceGuestCartTransferService::create()->transfer(
    (int)$USER->id,
    $currency,
    is_array($durablecart) ? $durablecart : null
);
$metadata = array_replace($guestsession->get_metadata(), [
    'cart_transferred' => $transferred !== null,
    'authenticated_at' => time(),
]);

if ($transferred !== null) {
    $metadata['cart_uuid'] = $transferred->get_uuid();
    $metadata['cart_item_count'] = count($transferred->get_items());
}

$repository->transition($guestsession, 'active', [
    'metadatajson' => $metadata,
]);

$checkoutparams = [
    'currency' => $currency,
    'flow' => (string)($metadata['purchase_flow'] ?? 'cart'),
];
foreach (['checkout_source' => 'source', 'showroom' => 'showroom', 'showroom_offer' => 'showroomoffer', 'origin_return' => 'originreturn'] as $metakey => $paramkey) {
    $value = trim((string)($metadata[$metakey] ?? ''));
    if ($value !== '') {
        $checkoutparams[$paramkey] = $value;
    }
}
$checkouturl = new moodle_url('/local/subscriptions/commerce_checkout.php', $checkoutparams);

$snapshot = CommerceCartRuntimeFactory::create()->snapshot((int)$USER->id, $currency, current_language());
if ($snapshot->get_items() === []) {
    redirect(
        $carturl,
        get_string('commerce_cart_empty_text', 'local_subscriptions'),
        null,
        \core\output\notification::NOTIFY_WARNING
    );
}

redirect($checkouturl);
