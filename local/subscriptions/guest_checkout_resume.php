<?php

require_once(__DIR__ . '/../../config.php');

use local_subscriptions\url\UrlFactory;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\currency\CommerceCurrencyRegistry;
use local_subscriptions\commerce\cart\service\CommerceCartRuntimeFactory;
use local_subscriptions\commerce\checkout\guest\CommerceGuestCartTransferService;
use local_subscriptions\commerce\checkout\guest\CommerceGuestCheckoutSessionRepository;
use local_subscriptions\commerce\checkout\guest\CommerceAuthenticatedCartReconciliationService;

\local_subscriptions\subscription_config::guard_public_access();
require_login();

$from = optional_param('from', '', PARAM_ALPHANUMEXT);

$currencyregistry = new CommerceCurrencyRegistry();
$currency = strtoupper(optional_param('currency', '', PARAM_ALPHA));
if (!$currencyregistry->is_enabled($currency)) {
    $currency = $currencyregistry->enabled()[0] ?? 'EUR';
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
    'authenticated_resume_source' =>
        $from === 'verified_identity_login'
            ? 'verified_identity_login'
            : 'guest_checkout_resume',
]);

if ($transferred !== null) {
    $metadata['cart_uuid'] = $transferred->get_uuid();
    $metadata['cart_item_count'] = count($transferred->get_items());
}

// H12.9-A5.7.6: transfer preserves Guest intent, then authenticated
// reconciliation replays the merged cart through the canonical Cart admission
// rules. This reuses effective Native + Legacy ownership, bundle and upgrade
// eligibility instead of inventing a checkout-specific ownership definition.
$reconciliation =
    (new CommerceAuthenticatedCartReconciliationService(
        CommerceCartRuntimeFactory::create()
    ))->reconcile(
        (int)$USER->id,
        $currency,
        current_language()
    );

$metadata['authenticated_cart_reconciled_at'] = time();
$metadata['authenticated_cart_items_before'] =
    $reconciliation['before'];
$metadata['authenticated_cart_items_after'] =
    $reconciliation['after'];
$metadata['authenticated_cart_items_removed'] =
    $reconciliation['removed'];
$metadata['authenticated_cart_removed_skus'] =
    $reconciliation['removedskus'];

// Once the authenticated cart has been successfully reconciled, the original
// anonymous snapshot is no longer authoritative. Keeping it would cause the
// generic GuestCartRecoveryService to merge already-owned items back into the
// user's cart on the very next checkout request.
unset($metadata['guest_cart_snapshot']);
$metadata['guest_cart_snapshot_consumed_at'] = time();

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
    $message =
        $reconciliation['removed'] > 0
            ? get_string(
                'commerce_guest_cart_all_already_owned',
                'local_subscriptions'
            )
            : get_string(
                'commerce_cart_empty_text',
                'local_subscriptions'
            );

    redirect(
        $carturl,
        $message,
        null,
        \core\output\notification::NOTIFY_INFO
    );
}

if ($reconciliation['removed'] > 0) {
    redirect(
        $checkouturl,
        get_string(
            'commerce_guest_cart_owned_items_removed',
            'local_subscriptions',
            $reconciliation['removed']
        ),
        null,
        \core\output\notification::NOTIFY_INFO
    );
}

redirect($checkouturl);
