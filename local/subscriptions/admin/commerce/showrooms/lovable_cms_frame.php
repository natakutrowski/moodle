<?php
// Persistent CMS draft frame: authenticated, opaque-origin, never public.
declare(strict_types=1);
require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\commerce\showroom\bridge\CommerceShowroomLovableDraftService as Draft;
use local_subscriptions\commerce\showroom\bridge\CommerceShowroomBridgePreviewService as Preview;
use local_subscriptions\commerce\showroom\bridge\CommerceShowroomLovableCommerceLink as Link;
use local_subscriptions\commerce\showroom\CommerceShowroomCurrencyResolver as Currency;

require_login();
// This standalone iframe endpoint has no Moodle page header: initialise the
// page context explicitly before Commerce formats product names.
$context = context_system::instance();
$PAGE->set_context($context);
require_capability('local/subscriptions:manage_showrooms', $context);
$id = required_param('id', PARAM_INT);
$lang = optional_param('lang', 'ru', PARAM_ALPHA);
[$record, $meta] = Draft::require_draft($DB, $id);
$currency = strtoupper(optional_param('currency', 'EUR', PARAM_ALPHA));
$active = Currency::active_currencies($DB);
if (!in_array($currency, $active, true)) {
    $currency = (string)($active[0] ?? 'EUR');
}
$review = Link::read_only_review($DB, $id, $currency);
// Read-only, role-keyed Commerce data. Do not transmit cartaction, sesskey or endpoints.
$commerce = ['currency' => $currency, 'availablecurrencies' => array_values($active),
    'offers' => array_values($review['offers'])];
$file = Draft::require_archive($id, $meta);
$path = Draft::materialise_archive($file, $meta);
try {
    $html = Preview::render_frame($path, (string)$meta['designid'], Draft::displayed_page($meta, $lang), $commerce);
} finally {
    @unlink($path);
}
$language = in_array($lang, ['ru', 'fr', 'en'], true) ? $lang : 'ru';
$html = str_replace('<html lang="ru">', '<html lang="' . $language . '">', $html);
// V4.2: Preview-only video from Moodle File API. No external embeds, no extra iframe privilege.
// Prefer the video attached to this CMS draft; for the dedicated #7 preview only,
// reuse the old #6 presentation video when #7 has not yet received a new upload.
require_once($CFG->dirroot . '/local/subscriptions/classes/commerce/showroom/bridge/CommerceShowroomBridgeVideoService.php');
$videoservice = new \local_subscriptions\commerce\showroom\bridge\CommerceShowroomBridgeVideoService(
    context_system::instance()
);
$videoobject = $videoservice->get_url((int)$id);
if ($videoobject === null && (int)$id === 7) {
    $videoobject = $videoservice->get_url(6);
}
$videourl = $videoobject?->out(false);
if ($videourl !== null) {
    $expected = rtrim((string)$CFG->wwwroot, '/') . '/pluginfile.php/';
    if (!str_starts_with($videourl, $expected)) {
        throw new invalid_parameter_exception('Media de previsualisation non autorise.');
    }
}
$replacecount = 0;
$html = preg_replace_callback(
    '~(<script id="campusfr-preview-payload" type="application/json">)(.*?)(</script>)~s',
    static function(array $match) use ($videourl): string {
        $payload = json_decode($match[2], true, 64, JSON_THROW_ON_ERROR);
        if (!is_array($payload) || ($payload['api'] ?? '') !== 'campusfr-showroom-bridge/v1') {
            throw new invalid_parameter_exception('Payload CMS inattendu.');
        }
        $payload['media'] = ['presentationVideoUrl' => $videourl];
        return $match[1] . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR)
            . $match[3];
    }, $html, 1, $replacecount
);
if (!is_string($html) || $replacecount !== 1) {
    throw new invalid_parameter_exception('Insertion du media CMS impossible.');
}

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: private, no-store, max-age=0');
header('Referrer-Policy: no-referrer');
header('X-Content-Type-Options: nosniff');
header('X-Robots-Tag: noindex, nofollow, noarchive');
header("Content-Security-Policy: sandbox allow-scripts; default-src 'none'; "
    . "script-src data:; style-src data: 'unsafe-inline' https://fonts.googleapis.com; img-src data:; "
    . "font-src data: https://fonts.gstatic.com; media-src data: " . rtrim((string)$CFG->wwwroot, "/") . "; object-src 'none'; "
    . "connect-src 'none'; form-action 'none'; frame-src 'none'; "
    . "worker-src 'none'; base-uri 'none'; frame-ancestors 'self';");
echo $html;
