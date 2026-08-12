<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../config.php');

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\CommerceShowroomCurrencyResolver;
use local_subscriptions\commerce\showroom\CommerceShowroomPresenter;
use local_subscriptions\commerce\showroom\CommerceShowroomProductResolver;
use local_subscriptions\commerce\showroom\CommerceShowroomRegistry;
use local_subscriptions\commerce\showroom\CommerceShowroomSeoService;
use local_subscriptions\commerce\showroom\CommerceShowroomUrl;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomRuntimeBlockSet;
use local_subscriptions\currency\CommerceCurrencyLabelFormatter;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockConfigurationPresenter;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomExerciseExplorerPresenter;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomPublishedDefinitionResolver;
use local_subscriptions\commerce\storefront\seo\CommerceStorefrontSeoHeadRegistry;
use local_subscriptions\support\Region;
use local_subscriptions\commerce\order\invoice\CommerceInvoiceProfileResolver;
use local_subscriptions\commerce\personaloffer\service\CommercePersonalOfferShoppingContextService;

$adminpreview = $GLOBALS['local_subscriptions_showroom_admin_preview'] ?? null;
$isadminpreview = is_array($adminpreview)
    && isset(
        $adminpreview['definition'],
        $adminpreview['runtimeblocks'],
        $adminpreview['pageurl'],
        $adminpreview['currencyendpoint']
    );

if (!$isadminpreview) {
    \local_subscriptions\subscription_config::guard_public_access();
}

if ($isadminpreview) {
    $definition = $adminpreview['definition'];
    $showroomkey = $definition->get_key();
} else {
    $showroomkey = optional_param(
        'showroomkey',
        CommerceShowroomRegistry::THIRD_GROUP_VERBS,
        PARAM_ALPHANUMEXT
    );
    $definition = (new CommerceShowroomPublishedDefinitionResolver($DB))
        ->require($showroomkey);
}

$requestedcurrency = strtoupper(optional_param('currency', '', PARAM_ALPHA));
$availablecurrencies = CommerceShowroomCurrencyResolver::active_currencies($DB);
if (!$isadminpreview) {
    foreach ($definition->get_products() as $personalsku) {
        $personaloffercurrencies = CommercePersonalOfferShoppingContextService::create($DB)
            ->available_currencies((string)$personalsku);
        if ($personaloffercurrencies !== null) {
            if ($personaloffercurrencies !== []) {
                $availablecurrencies = array_values(array_intersect($availablecurrencies, $personaloffercurrencies));
            }
            break;
        }
    }
}
$storedcurrency = strtoupper((string)(
    $SESSION->local_subscriptions_showroom_currency
        ?? $SESSION->local_subscriptions_storefront_currency
        ?? ''
));
$currency = CommerceShowroomCurrencyResolver::resolve(
    $availablecurrencies,
    $requestedcurrency,
    $storedcurrency
);
$SESSION->local_subscriptions_showroom_currency = $currency;
$SESSION->local_subscriptions_storefront_currency = $currency;

$pageurl = $isadminpreview
    ? new moodle_url($adminpreview['pageurl'], ['currency' => $currency])
    : CommerceShowroomUrl::make($definition, ['currency' => $currency]);
$PAGE->set_context(context_system::instance());
$PAGE->set_url($pageurl);
$PAGE->set_pagelayout('showroom');
$PAGE->add_body_class('commerce-showroom-page');
if ($isadminpreview) {
    $PAGE->add_body_class('commerce-showroom-admin-preview');
}

$offers = CommerceShowroomProductResolver::create($DB)->resolve(
    $definition,
    current_language(),
    $currency
);

$seoservice = new CommerceShowroomSeoService();
$seo = $seoservice->present($definition, $offers);
$PAGE->set_title($seo['title']);
$PAGE->set_heading($seo['title']);
if (method_exists($PAGE, 'set_description')) {
    $PAGE->set_description($seo['description']);
}
$seoheadhtml = $seoservice->head_html($seo);
if ($isadminpreview) {
    $seoheadhtml .= "\n"
        . '<meta name="robots" content="noindex,nofollow,noarchive">';
}

$PAGE->requires->css(new moodle_url('/local/subscriptions/styles/showroom.css'));
$PAGE->requires->js_call_amd('local_subscriptions/showroom', 'init');

$data = (new CommerceShowroomPresenter())->present($definition, $offers, $currency);
$runtimeblocks = $isadminpreview
    ? $adminpreview['runtimeblocks']
    : CommerceShowroomRuntimeBlockSet::load($DB, $definition->get_key());
$data = array_replace($data, $runtimeblocks->to_template_data());
$data = (new CommerceShowroomBlockConfigurationPresenter())->apply($data, $runtimeblocks);
$data = (new CommerceShowroomExerciseExplorerPresenter(context_system::instance()))->apply(
    $data,
    $runtimeblocks->config('exercise_explorer'),
    $runtimeblocks->block_id('exercise_explorer'),
    current_language()
);
$data['currencyendpoint'] = $isadminpreview
    ? $adminpreview['currencyendpoint']->out(false)
    : (new moodle_url('/local/subscriptions/ajax/showroom_prices.php'))->out(false);
$data['currencyerrormessage'] = get_string('commerce_showroom_currency_update_error', 'local_subscriptions');
$data['currencies'] = array_map(
    static function(string $candidate) use ($currency): array {
        return [
            'value' => $candidate,
            'label' => CommerceCurrencyLabelFormatter::format($candidate),
            'selected' => $candidate === $currency,
        ];
    },
    $availablecurrencies
);

// J16P3 — legal footer: same regional legal URL resolution as Commerce policies.
$legalurls = Region::policyUrls();
$legalprofile = (new CommerceInvoiceProfileResolver())->resolve($currency, null);

// J16P6 — expose each invoice profile field independently.
$finallegalprofilefields = [
    'name' => trim((string)($legalprofile['name'] ?? '')),
    'address' => trim((string)($legalprofile['address'] ?? '')),
    'legal' => trim((string)($legalprofile['legal'] ?? '')),
    'email' => trim((string)($legalprofile['email'] ?? '')),
    'phone' => trim((string)($legalprofile['phone'] ?? '')),
    'website' => trim((string)($legalprofile['website'] ?? '')),
    'taxnotice' => trim((string)($legalprofile['taxnotice'] ?? '')),
    'footer' => trim((string)($legalprofile['footer'] ?? '')),
];

foreach ($finallegalprofilefields as $field => $value) {
    $data['finallegal' . $field] = $value;
    $data['hasfinallegal' . $field] = $value !== '';
}

$data['finallegalprivacyurl'] = (string)$legalurls['policy'];
$data['finallegaltermsurl'] = (string)$legalurls['terms'];
$data['finallegalofferurl'] = (string)$legalurls['offer'];
$data['finallegalprivacylabel'] = get_string('privacy_policy', 'local_subscriptions');
$data['finallegaltermslabel'] = get_string('terms_cgu', 'local_subscriptions');
$data['finallegalofferlabel'] = get_string('terms_cgv', 'local_subscriptions');
$data['finallegalnavlabel'] = implode(' · ', [
    $data['finallegalprivacylabel'],
    $data['finallegaltermslabel'],
    $data['finallegalofferlabel'],
]);

// Preload a single likely LCP image without competing with the rest of the catalogue artwork.
$preloadattributes = null;
if (!empty($data['hasherobackground']) && trim((string)($data['herobackgroundurl'] ?? '')) !== '') {
    $preloadattributes = [
        'rel' => 'preload',
        'as' => 'image',
        'href' => (string)$data['herobackgroundurl'],
        'fetchpriority' => 'high',
    ];
} else {
    foreach ((array)($data['offers'] ?? []) as $offer) {
        if ((string)($offer['role'] ?? '') !== 'bundle' || empty($offer['hascover'])) {
            continue;
        }
        $preloadattributes = [
            'rel' => 'preload',
            'as' => 'image',
            'href' => (string)$offer['coverurl'],
            'fetchpriority' => 'high',
        ];
        if (!empty($offer['coverresponsive']) && trim((string)($offer['coversrcset'] ?? '')) !== '') {
            $preloadattributes['imagesrcset'] = (string)$offer['coversrcset'];
            $preloadattributes['imagesizes'] = '(max-width: 767px) calc(100vw - 48px), (max-width: 1199px) 42vw, 360px';
        }
        break;
    }
}
if ($preloadattributes !== null) {
    $seoheadhtml .= "\n" . html_writer::empty_tag('link', $preloadattributes);
}
CommerceStorefrontSeoHeadRegistry::set($seoheadhtml);

$template = $definition->get_template();

echo $OUTPUT->header();
echo $OUTPUT->render_from_template($template, $data);
echo $OUTPUT->footer();
