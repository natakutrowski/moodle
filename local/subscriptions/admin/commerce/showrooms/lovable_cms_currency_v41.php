<?php
// CampusFR V4.1: authenticated read-only currency quote for one Lovable CMS draft.
// No cart actions, payment tokens, product mutations or session currency writes.
declare(strict_types=1);
require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\commerce\showroom\bridge\CommerceShowroomLovableDraftService as Draft;
use local_subscriptions\commerce\showroom\bridge\CommerceShowroomLovableCommerceLink as Link;
use local_subscriptions\commerce\showroom\CommerceShowroomCurrencyResolver as Currency;

require_login();
$context = context_system::instance();
$PAGE->set_context($context);
require_capability('local/subscriptions:manage_showrooms', $context);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    throw new invalid_parameter_exception('Only GET is allowed.');
}
$id = required_param('id', PARAM_INT);
$currency = strtoupper(required_param('currency', PARAM_ALPHA));
[$record, $meta] = Draft::require_draft($DB, $id);
if ((string)($meta['designid'] ?? '') !== 'articles_alf_reimport'
        || empty($meta['commerce_preview_v3']) || (string)$record->status !== 'draft') {
    throw new invalid_parameter_exception('Aperçu V3 non autorisé.');
}
if (!in_array($currency, Currency::active_currencies($DB), true)) {
    throw new invalid_parameter_exception('Devise inactive.');
}
// The native Commerce resolver is the ONLY source of prices.
$review = Link::read_only_review($DB, $id, $currency);
$offers = array_values($review['offers']);
if (count($offers) > 3) {
    throw new invalid_parameter_exception('Nombre d’offres inattendu.');
}
$payload = ['ok' => true, 'currency' => $currency, 'offers' => $offers];
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, no-store, max-age=0');
header('X-Content-Type-Options: nosniff');
header('X-Robots-Tag: noindex, nofollow, noarchive');
header('Referrer-Policy: no-referrer');
echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
