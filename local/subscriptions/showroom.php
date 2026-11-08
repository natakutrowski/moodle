<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../config.php');

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\bridge\CommerceShowroomBridgePackageRegistry;
use local_subscriptions\commerce\showroom\bridge\CommerceShowroomBridgePresenter;
use local_subscriptions\commerce\showroom\CommerceShowroomCurrencyResolver;
use local_subscriptions\commerce\currency\selection\CommerceCurrencySurfaceSelectionService;
use local_subscriptions\currency\Currency;
use local_subscriptions\support\Region;
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
use local_subscriptions\commerce\order\invoice\CommerceInvoiceProfileResolver;
use local_subscriptions\commerce\showroom\legal\CommerceShowroomLegalFooterPresenter;
use local_subscriptions\commerce\personaloffer\service\CommercePersonalOfferShoppingContextService;

$adminpreview = $GLOBALS['local_subscriptions_showroom_admin_preview'] ?? null;
$isadminpreview = is_array($adminpreview)
    && isset(
        $adminpreview['definition'],
        $adminpreview['runtimeblocks'],
        $adminpreview['pageurl'],
        $adminpreview['currencyendpoint']
    );
// The admin preview *remains a draft* even when explicitly testing its cart.
$checkouttest = $isadminpreview && !empty($adminpreview['checkouttest']);

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

// P2L: customer-account tests on published #8, never on PROD/other showrooms.
// Keep the administrator preview mode unchanged.
require_once(__DIR__ . '/classes/commerce/showroom/bridge/CommerceShowroomDevCheckoutTestGate.php');
$isdevpublishedpilot = false;
if (!$isadminpreview
    && rtrim((string)$CFG->wwwroot, '/') === \local_subscriptions\commerce\showroom\bridge\CommerceShowroomDevCheckoutTestGate::DEV_ROOT
    && $definition->get_key() === \local_subscriptions\commerce\showroom\bridge\CommerceShowroomDevCheckoutTestGate::KEY) {
    $pilotrecord = (new \local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository($DB))
        ->get_by_key($definition->get_key());
    if (!$pilotrecord) {
        throw new \moodle_exception('commerce_showroom_not_found', 'local_subscriptions');
    }
    $pilotsettings = \local_subscriptions\commerce\showroom\bridge\CommerceShowroomBridgeSettings
        ::from_json((string)$pilotrecord->settingsjson);
    $pilotmanifest = CommerceShowroomBridgePackageRegistry::require_manifest($pilotsettings['id']);
    $isdevpublishedpilot = \local_subscriptions\commerce\showroom\bridge\CommerceShowroomDevCheckoutTestGate
        ::eligible_published_pilot(
            (int)$pilotrecord->id,
            (string)$pilotrecord->status,
            (string)$pilotrecord->showroomkey,
            (string)$pilotrecord->template,
            (string)$CFG->wwwroot,
            (string)$pilotsettings['id'],
            (string)$pilotmanifest['version'],
            [
                'slugfr' => (string)$pilotrecord->slugfr,
                'slugen' => (string)$pilotrecord->slugen,
                'slugru' => (string)$pilotrecord->slugru,
            ]
        );
    if (!$isdevpublishedpilot) {
        throw new \moodle_exception('commerce_showroom_not_found', 'local_subscriptions');
    }
    // Require a real login, not an admin permission. Ordinary test accounts qualify.
    \local_subscriptions\commerce\showroom\bridge\CommerceShowroomDevCheckoutTestGate::require_sandbox();
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
$usercurrency = isloggedin() && !isguestuser()
    ? Currency::sanitize((string)get_user_preferences(
        'local_subscriptions_storefront_currency',
        '',
        (int)$USER->id
    ))
    : '';
$sessioncurrency = Currency::sanitize((string)(
    $SESSION->local_subscriptions_showroom_currency
        ?? $SESSION->local_subscriptions_storefront_currency
        ?? ''
));

$currencyselection = (new CommerceCurrencySurfaceSelectionService())->resolve(
    $availablecurrencies,
    $requestedcurrency,
    $usercurrency,
    $sessioncurrency
);
$currency = $currencyselection->get_currency();

$SESSION->local_subscriptions_showroom_currency = $currency;
$SESSION->local_subscriptions_storefront_currency = $currency;
if (isloggedin() && !isguestuser()) {
    set_user_preference(
        'local_subscriptions_storefront_currency',
        $currency,
        (int)$USER->id
    );
}

$pageurl = $isadminpreview
    ? new moodle_url($adminpreview['pageurl'], ['currency' => $currency])
    : CommerceShowroomUrl::make($definition, ['currency' => $currency]);
$PAGE->set_context(context_system::instance());
$PAGE->set_url($pageurl);
if ($isdevpublishedpilot) {
    // P2O (DEV only): this already-published, tightly gated sandbox showroom
    // accepts anonymous visitors. Commerce owns Guest Cart, identity OTP and
    // payment access. Do not force authentication before the cart POST.
    header('Cache-Control: private, no-store, max-age=0');
}
$PAGE->set_pagelayout('showroom');
$isbridgerender = $definition->get_template() === CommerceShowroomBridgePackageRegistry::TEMPLATE;
if ($isbridgerender) {
    $PAGE->add_body_class('commerce-showroom-bridge-page');
}
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
if ($isadminpreview || $isdevpublishedpilot) {
    $seoheadhtml .= "\n"
        . '<meta name="robots" content="noindex,nofollow,noarchive">';
}

// 7.98E.8.4.3 — A1 handwritten notes use the Caveat webfont used by the Lovable reference.
// Load it only for the A1 showroom (including admin preview) so other showrooms keep their
// existing font payload unchanged. The CSS itself keeps a cursive fallback if Google Fonts
// is unavailable for any reason.
if ($isbridgerender) {
    // Match Lovable's own global typography for the new renderer only.
    $seoheadhtml .= "\n" . html_writer::empty_tag('link', [
        'rel' => 'preconnect', 'href' => 'https://fonts.googleapis.com',
    ]);
    $seoheadhtml .= "\n" . html_writer::empty_tag('link', [
        'rel' => 'preconnect', 'href' => 'https://fonts.gstatic.com', 'crossorigin' => 'anonymous',
    ]);
    $seoheadhtml .= "\n" . html_writer::empty_tag('link', [
        'rel' => 'stylesheet',
        'href' => 'https://fonts.googleapis.com/css2?family=Caveat:wght@500&family=Nunito:wght@400;600;700;800;900&display=swap',
    ]);
}

if ($definition->get_key() === 'a1-course') {
    $seoheadhtml .= "\n" . html_writer::empty_tag('link', [
        'rel' => 'preconnect',
        'href' => 'https://fonts.googleapis.com',
    ]);
    $seoheadhtml .= "\n" . html_writer::empty_tag('link', [
        'rel' => 'preconnect',
        'href' => 'https://fonts.gstatic.com',
        'crossorigin' => 'anonymous',
    ]);
    $seoheadhtml .= "\n" . html_writer::empty_tag('link', [
        'rel' => 'stylesheet',
        'href' => 'https://fonts.googleapis.com/css2?family=Caveat:wght@400;500;600;700&display=swap',
    ]);
}

if ($isbridgerender) {
    $PAGE->requires->css(new moodle_url('/local/subscriptions/styles/showroom_bridge_shell.css'));
}
// Phase 2N: the trusted Bridge 0.4.11 implements the native Moodle Commerce
// POST in React itself; no DEV-only adapter JS is injected here. Normal admin
// preview remains read-only and the server controls payment/entitlements.
if (!$isbridgerender) {
    $PAGE->requires->css(new moodle_url('/local/subscriptions/styles/showroom.css'));
    $PAGE->requires->js_call_amd('local_subscriptions/showroom', 'init');
}
if ($definition->get_key() === 'a1-course') {
    $PAGE->requires->js_call_amd('local_subscriptions/anchor_nav_sticky', 'init');
}

$data = (new CommerceShowroomPresenter())->present($definition, $offers, $currency);
// Make the authoritative currency options available to both Mustache and the Bridge.
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
$runtimeblocks = $isadminpreview
    ? $adminpreview['runtimeblocks']
    : CommerceShowroomRuntimeBlockSet::load($DB, $definition->get_key());
if ($isbridgerender) {
    $data = array_replace($data, (new CommerceShowroomBridgePresenter())->present(
        $definition, $DB, $data, $isadminpreview ? (int)$adminpreview['id'] : null,
        $checkouttest
    ));
} else {
    $data = array_replace($data, $runtimeblocks->to_template_data());
    $data = (new CommerceShowroomBlockConfigurationPresenter())->apply($data, $runtimeblocks);
    $data = (new CommerceShowroomExerciseExplorerPresenter(context_system::instance()))->apply(
        $data,
        $runtimeblocks->config('exercise_explorer'),
        $runtimeblocks->block_id('exercise_explorer'),
        current_language()
    );
}
$data['currencyendpoint'] = $isadminpreview
    ? $adminpreview['currencyendpoint']->out(false)
    : (new moodle_url('/local/subscriptions/ajax/showroom_prices.php'))->out(false);
$data['currencyerrormessage'] = get_string('commerce_showroom_currency_update_error', 'local_subscriptions');
// The Articles / Alf footer belongs to its trusted compiled DESIGN, not to a
// DEV-only CMS key, showroom database ID or preview/publication state.
// Commerce still owns invoice display fields, legal links and merchant policy.
$isnativelegalbridge = $isbridgerender
    && (string)($data['bridgeid'] ?? '') === 'articles_alf_reimport';


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

if ($isnativelegalbridge) {
    $nativelegal = new CommerceShowroomLegalFooterPresenter();
    // Same invoice_eur_*/invoice_rub_* display profile as existing showrooms #4/#6.
    // This is PRESENTATION ONLY: checkout merchant remains market-driven and untouched.
    // Document links use the existing Region::policyUrls() routing, as in #4/#6.
    $data = array_replace($data, $nativelegal->present([
        'fields' => $finallegalprofilefields,
        'urls' => [
            'privacy' => (string)$legalurls['policy'],
            'terms' => (string)$legalurls['terms'],
            'offer' => (string)$legalurls['offer'],
        ],
    ]));
    $PAGE->requires->css(new moodle_url('/local/subscriptions/styles/showroom_bridge_legal_footer.css', ['rev' => 'p2i-blackglass-1']));
}


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
    $coveroffers = array_values(array_filter(
        (array)($data['offers'] ?? []),
        static fn(array $offer): bool => !empty($offer['hascover'])
            && trim((string)($offer['coverurl'] ?? '')) !== ''
    ));
    usort(
        $coveroffers,
        static fn(array $left, array $right): int => ((int)!empty($right['isfeatured']))
            <=> ((int)!empty($left['isfeatured']))
    );

    $offer = $coveroffers[0] ?? null;
    if (is_array($offer)) {
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
    }
}
if ($preloadattributes !== null) {
    $seoheadhtml .= "\n" . html_writer::empty_tag('link', $preloadattributes);
}
CommerceStorefrontSeoHeadRegistry::set($seoheadhtml);

$template = $definition->get_template();

echo $OUTPUT->header();
// The public DEV #8 has the final storefront appearance, with zero test banner.
// The administrator's special checkout preview still warns that it is sandbox-only.
if ($checkouttest) {
    $lang = strtolower(explode('_', str_replace('-', '_', current_language()))[0]);
    $label = match ($lang) {
        'ru' => 'DEV · Тест корзины Commerce · Доступ только для администратора · Только тестовые платежи',
        'en' => 'DEV · Commerce checkout test · Administrator only · Test payments only',
        default => 'DEV · Test du panier Commerce · Administrateur uniquement · Paiements de test uniquement',
    };
    echo \html_writer::div(s($label), 'campusfr-dev-commerce-test-banner');
}
echo $OUTPUT->render_from_template($template, $data);
echo $OUTPUT->footer();
