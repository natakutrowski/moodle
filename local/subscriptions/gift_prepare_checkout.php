<?php
// G5B2: checkout handoff is private DEV-only and rejects all unbound form input.
require_once(__DIR__ . '/../../config.php');
defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\gift\CommerceGiftDevE2EGate;
use local_subscriptions\commerce\gift\CommerceGiftCommercialDevGate;
use local_subscriptions\commerce\gift\CommerceGiftCommercialRuntimeGate;
use local_subscriptions\commerce\gift\CommerceGiftPaymentProviderReadiness;
use local_subscriptions\commerce\gift\CommerceGiftDevDraftAuthority;
use local_subscriptions\commerce\gift\CommerceGiftCheckoutIntentBinder;
use local_subscriptions\commerce\gift\CommerceGiftPreparedCheckoutService;
use local_subscriptions\commerce\cart\service\CommerceCartRuntimeFactory;
use local_subscriptions\commerce\checkout\flow\CommerceDirectPurchaseSession;
use local_subscriptions\commerce\catalog\repository\CommerceProductPriceRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;
use local_subscriptions\commerce\catalog\persistence\CommerceCatalogHydrator;

require_login();
if (!CommerceGiftCommercialRuntimeGate::can_operate()) {
    throw new moodle_exception('nopermissions');
}
CommerceGiftCommercialRuntimeGate::assert_buyer($DB, (int)$USER->id, (string)$USER->email);
if (empty($USER->confirmed) || !empty($USER->suspended) || is_siteadmin((int)$USER->id)) {
    throw new moodle_exception('nopermissions');
}
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    throw new moodle_exception('nopermissions');
}
require_sesskey();
header('Cache-Control: private, no-store, max-age=0');
header('Referrer-Policy: no-referrer');
$preview = $SESSION->cfr_g5b2_preview ?? null;
unset($SESSION->cfr_g5b2_preview); // One-time server-owned form context.
if (!is_array($preview) || (int)($preview['buyerid'] ?? 0) !== (int)$USER->id
    || (int)($preview['at'] ?? 0) < time() - 900
    || (int)($preview['at'] ?? 0) > time() + 60
    || !is_array($preview['values'] ?? null)
    || !(new \local_subscriptions\commerce\gift\CommerceGiftFeatureToggle())->can_issue()) {
    throw new moodle_exception('nopermissions');
}
$values = $preview['values'];
$scheduledat = (int)($preview['scheduledat'] ?? 0);
$checked = \local_subscriptions\commerce\gift\CommerceGiftPrepareInputValidator::validate($values, time());
if ($checked['errors'] || (int)($checked['scheduledat'] ?? 0) !== $scheduledat) {
    throw new moodle_exception('nopermissions');
}
if ($values['deliverymode'] !== 'email') {
    throw new moodle_exception('nopermissions');
}
// Recheck the current buyer-bound showroom choice, not the posted currency.
$context = \local_subscriptions\commerce\gift\CommerceGiftShowroomCurrencyContext::resolve(
    $SESSION, (int)$USER->id, time());
if ($context['currency'] !== $values['currency']
    || !$context['fromshowroom']) {
    throw new moodle_exception('nopermissions');
}
$currency = $context['currency'];
$hydrator = new CommerceCatalogHydrator();
$products = new CommerceProductRepository($DB, $hydrator);
$prices = new CommerceProductPriceRepository($DB, $hydrator, $products);
$price = CommerceGiftPaymentProviderReadiness::resolve_price($prices, $currency);
if (!$price->get_id()) {
    throw new \DomainException('DEV gift product or price is not eligible for checkout.');
}
$key = trim((string)get_config('local_subscriptions', 'commerce_gifts_intent_key_hex'));
if (!preg_match('/\A[a-f0-9]{64}\z/D', $key)) {
    throw new \DomainException('Gift signing key is not configured.');
}
$record = (new CommerceGiftPreparedCheckoutService($DB))->prepare(
    (int)$USER->id, (string)$USER->email, $values, $scheduledat,
    (int)$price->get_id(), time()
);
$draft = [
    'lineuuid' => (string)$record->lineuuid,
    'deliverymode' => (string)$record->deliverymode,
    'recipientemail' => (string)$record->recipientemail,
    'scheduledat' => $record->scheduledat === null ? null : (int)$record->scheduledat,
    'expiresat' => (int)$record->expiresat,
];
$metadata = [
    CommerceGiftCheckoutIntentBinder::DRAFT_FIELD => $draft,
    CommerceGiftDevDraftAuthority::PROOF_FIELD => CommerceGiftDevDraftAuthority::proof(
        $draft, (string)$USER->email, $currency, 'AVENT_2026', (int)$price->get_id(), $key
    ),
];
// Completely separate Buy Now cart; the customer's ordinary cart is not edited.
$result = CommerceCartRuntimeFactory::create()->prepare_direct_product(
    (int)$USER->id, $currency, current_language(), 'AVENT_2026',
    (int)$price->get_id(), 1, $metadata
);
if (!$result->has_changed() || count($result->get_cart()->get_items()) !== 1) {
    // Restrict diagnostics to immutable internal message codes. Never log any
    // recipient address, cart metadata, proof or token.
    $codes = [];
    foreach ($result->get_messages() as $message) {
        $code = (string)$message->get_code();
        if (preg_match('/\A[a-z0-9_]{1,80}\z/D', $code)) {
            $codes[] = $code;
        }
    }
    error_log('CampusFR gift direct-cart refused: ' . ($codes ? implode(',', $codes) : 'cart_not_prepared'));
    throw new \DomainException('Gift direct cart could not be prepared.');
}
$items = $result->get_cart()->get_items();
$item = reset($items);
if (!$item || !isset($item->get_metadata()[CommerceGiftCheckoutIntentBinder::DRAFT_FIELD])) {
    throw new \DomainException('Gift direct cart lost its server-controlled intent.');
}
CommerceDirectPurchaseSession::store(
    $currency, 'AVENT_2026', (int)$price->get_id(), 1,
    $item->get_metadata(), $result->get_cart()->get_uuid()
);
redirect(new moodle_url('/local/subscriptions/commerce_checkout.php',
    ['currency' => $currency, 'flow' => 'direct', 'source' => 'gift_prepare']));
