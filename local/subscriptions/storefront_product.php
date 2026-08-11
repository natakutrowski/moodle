<?php

require_once(__DIR__ . '/../../config.php');

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\storefront\page\CommerceStorefrontPagePresenter;
use local_subscriptions\commerce\cart\presentation\CommerceCartPresenter;
use local_subscriptions\commerce\cart\service\CommerceCartRuntimeFactory;
use local_subscriptions\commerce\storefront\recommendation\CommerceStorefrontRecommendationResolver;
use local_subscriptions\commerce\storefront\recommendation\CommerceStorefrontRecommendationService;
use local_subscriptions\commerce\storefront\page\CommerceStorefrontPageResolver;
use local_subscriptions\commerce\storefront\page\CommerceStorefrontLayoutContract;
use local_subscriptions\commerce\storefront\seo\CommerceStorefrontSeoPresenter;
use local_subscriptions\commerce\storefront\seo\CommerceStorefrontSeoHeadRegistry;
use local_subscriptions\commerce\storefront\repository\CommerceStorefrontRepository;
use local_subscriptions\commerce\trial\CommerceTrialConversionBridge;
use local_subscriptions\commerce\trial\CommerceTrialCartPricingService;
use local_subscriptions\commerce\purchase\presentation\CommercePurchasePresentation;
use local_subscriptions\commerce\pricing\CommerceStorefrontCommercialPricingPresenter;
use local_subscriptions\commerce\digital\library\CommerceDigitalLibraryService;
use local_subscriptions\support\Region;
use local_subscriptions\url\UrlFactory;
use local_subscriptions\commerce\showroom\CommerceShowroomProductLinkService;

\local_subscriptions\subscription_config::guard_public_access();

$sku = required_param('sku', PARAM_RAW_TRIMMED);
$requestedcurrency = strtoupper(optional_param('currency', '', PARAM_ALPHA));

$availablecurrencies = $DB->get_fieldset_sql(
    "SELECT DISTINCT UPPER(pp.currency)
       FROM {local_subs_commerce_prod_price} pp
       JOIN {local_subs_commerce_product} p ON p.id = pp.productid
      WHERE UPPER(p.sku) = :sku
        AND pp.active = 1
   ORDER BY UPPER(pp.currency)",
    ['sku' => strtoupper(trim($sku))]
);
$availablecurrencies = array_values(array_unique(array_filter(array_map(
    static fn(mixed $value): string => strtoupper(trim((string)$value)),
    $availablecurrencies
))));
if ($availablecurrencies === []) {
    $availablecurrencies = ['EUR', 'RUB'];
}

$storedcurrency = isloggedin() && !isguestuser()
    ? strtoupper((string)get_user_preferences(
        'local_subscriptions_storefront_currency',
        '',
        (int)$USER->id
    ))
    : '';
if ($storedcurrency === '') {
    $storedcurrency = strtoupper((string)(
        $SESSION->local_subscriptions_storefront_currency ?? ''
    ));
}

if (
    $requestedcurrency !== ''
    && in_array($requestedcurrency, $availablecurrencies, true)
) {
    $currency = $requestedcurrency;
} else if (
    $storedcurrency !== ''
    && in_array($storedcurrency, $availablecurrencies, true)
) {
    $currency = $storedcurrency;
} else {
    $country = strtoupper(Region::detect_country());
    $geocandidate = in_array($country, ['RU', 'BY'], true) ? 'RUB' : 'EUR';
    $currency = in_array($geocandidate, $availablecurrencies, true)
        ? $geocandidate
        : (in_array('EUR', $availablecurrencies, true)
            ? 'EUR'
            : $availablecurrencies[0]);
}

$SESSION->local_subscriptions_storefront_currency = $currency;
if (isloggedin() && !isguestuser()) {
    set_user_preference(
        'local_subscriptions_storefront_currency',
        $currency,
        (int)$USER->id
    );
}

$repository = CommerceStorefrontRepository::create($DB);
$product = $repository->find_by_sku(
    $sku,
    current_language(),
    $currency,
    true
);
if ($product === null) {
    throw new moodle_exception('commerce_storefront_product_not_found', 'local_subscriptions');
}

$context = context_system::instance();
$pageurlparams = [
    'sku' => $sku,
    'currency' => $currency,
];
if (optional_param('from', '', PARAM_ALPHANUMEXT) === 'shop') {
    $pageurlparams['from'] = 'shop';
}
$pageurl = new moodle_url('/local/subscriptions/storefront_product.php', $pageurlparams);
$PAGE->set_context($context);
$PAGE->set_url($pageurl);
$definition = (new CommerceStorefrontPageResolver())->resolve($product);
$PAGE->set_pagelayout(
    CommerceStorefrontLayoutContract::moodle_page_layout(
        $definition->get_shell_mode()
    )
);
if (!$definition->show_header()) {
    $PAGE->add_body_class('commerce-storefront-hide-header');
}
if (!$definition->show_footer()) {
    $PAGE->add_body_class('commerce-storefront-hide-footer');
}
$seopresenter = new CommerceStorefrontSeoPresenter();
$seo = $seopresenter->present(
    $product,
    $pageurl->out(false),
    current_language()
);
$PAGE->set_title($seo['title']);
$PAGE->set_heading($product->get_name());
if (method_exists($PAGE, 'set_description')) {
    $PAGE->set_description($seo['description']);
}
CommerceStorefrontSeoHeadRegistry::set(
    $seopresenter->head_html($seo)
);
$PAGE->requires->css(
    new moodle_url('/local/subscriptions/styles/storefront.css')
);
$PAGE->requires->js_call_amd('local_subscriptions/storefront_premium', 'init');

$data = (new CommerceStorefrontPagePresenter())->present(
    $product,
    $definition,
    $currency,
    UrlFactory::digital_catalog(['currency' => $currency])->out(false)
);
$data['producturl'] = (new moodle_url('/local/subscriptions/storefront_product.php'))->out(false);
$data['currencyselectlabel'] = get_string(
    'commerce_storefront_currency_displayed',
    'local_subscriptions'
);
$data['currencyselectid'] = 'storefront-product-currency';
$data['currencies'] = array_map(
    static function(string $code) use ($currency): array {
        return [
            'value' => $code,
            'label' => \local_subscriptions\currency\CommerceCurrencyLabelFormatter::format($code),
            'selected' => $currency === $code,
        ];
    },
    $availablecurrencies
);
$from = optional_param('from', '', PARAM_ALPHANUMEXT);
$data['showbacktoshop'] = $from === 'shop';
$customerid = isloggedin() && !isguestuser() ? (int)$USER->id : 0;
$cartdata = CommerceCartPresenter::present(
    CommerceCartRuntimeFactory::create()->snapshot($customerid, $currency, current_language())
);
$cartkeys = [];
foreach ($cartdata['items'] as $cartitem) {
    $cartkeys[strtoupper((string)$cartitem['productsku']) . ':' . (int)$cartitem['priceid']] = true;
}
foreach ($data['prices'] as &$price) {
    $key = strtoupper((string)$data['sku']) . ':' . (int)$price['id'];
    $price['incart'] = isset($cartkeys[$key]);
    $price['toggleaction'] = $price['incart'] ? 'remove' : 'add';
    $price['togglelabel'] = $price['incart']
        ? get_string('commerce_cart_remove_from_cart', 'local_subscriptions')
        : get_string('commerce_cart_add', 'local_subscriptions');
}
unset($price);
if (!empty($data['upgradepriceid'])) {
    $upgradekey = strtoupper((string)$data['sku']) . ':' . (int)$data['upgradepriceid'];
    $data['upgradeincart'] = isset($cartkeys[$upgradekey]);
    $data['upgradetoggleaction'] = $data['upgradeincart'] ? 'remove' : 'add';
    $data['upgradetogglelabel'] = $data['upgradeincart']
        ? get_string('commerce_cart_remove_from_cart', 'local_subscriptions')
        : $data['upgradeactionlabel'];
}
$data['buy_now_label'] = get_string('commerce_cart_buy_now', 'local_subscriptions');
$data['standardpricelabel'] = get_string(
    'commerce_storefront_price_standard',
    'local_subscriptions'
);
$data['promotionpricelabel'] = get_string(
    'commerce_storefront_price_promotional',
    'local_subscriptions'
);
$data['trialpricelabelcard'] = get_string(
    'commerce_storefront_price_trial',
    'local_subscriptions'
);
$data['upgradepricelabelcard'] = get_string(
    'commerce_storefront_price_upgrade',
    'local_subscriptions'
);
$data['cartaction'] = (new moodle_url('/local/subscriptions/cart_action.php'))->out(false);
$data['cartreturnurl'] = $pageurl->out(false);
$data['cartcurrency'] = $currency;
$data['cartsesskey'] = sesskey();
$data['carturl'] = (UrlFactory::cart(['currency' => $currency]))->out(false);
$data['cartlabel'] = get_string('commerce_cart_view', 'local_subscriptions');
$data['cartlinecount'] = $cartdata['linecount'];
$data['carttotalformatted'] = $cartdata['totalformatted'];
$cartnotice = optional_param('cartnotice', '', PARAM_ALPHANUMEXT);
if ($cartnotice !== '') {
    $stringkey = 'commerce_cart_message_' . $cartnotice;
    if (get_string_manager()->string_exists($stringkey, 'local_subscriptions')) {
        $message = get_string($stringkey, 'local_subscriptions');
        if ($cartnotice === 'error' || $cartnotice === 'bundle_all_owned') {
            \core\notification::warning($message);
        }
    }
}
$cartactionresult = optional_param('cartactionresult', '', PARAM_ALPHA);
$cartchanged = optional_param('cartchanged', 0, PARAM_BOOL);
$data['showaddedmodal'] = $cartchanged && $cartactionresult === 'add';
$data['addedmodaltitle'] = get_string('commerce_cart_added_modal_title', 'local_subscriptions');
$data['addedmodaltext'] = $cartnotice === 'bundle_partial_owned'
    ? get_string('commerce_cart_message_bundle_partial_owned', 'local_subscriptions')
    : get_string('commerce_cart_added_modal_text', 'local_subscriptions');
$data['continueurl'] = $pageurl->out(false);
$data['continuelabel'] = get_string('commerce_cart_continue_shopping', 'local_subscriptions');
$data['viewcartlabel'] = get_string('commerce_cart_view', 'local_subscriptions');
$data['addedproductname'] = $data['name'];
$data['addedproductcoverurl'] = $data['coverurl'] ?? null;
$data['addedproducthascover'] = !empty($data['coverurl']);
$data['owneddownloads'] = [];
$data['hasowneddownloads'] = false;

if (
    $customerid > 0
    && !empty($data['owned'])
    && !empty($data['ownedisdigital'])
) {
    $library = CommerceDigitalLibraryService::create()->get_for_customer(
        $customerid,
        (string)($USER->email ?? '')
    );

    foreach ($library->get_resources() as $resource) {
        $resourcesku = strtoupper(trim((string)(
            $resource->metadata['sku'] ?? ''
        )));
        if ($resourcesku !== strtoupper(trim($product->get_sku()))) {
            continue;
        }

        foreach ($resource->export()['downloads'] as $download) {
            $ismobile = !empty($download['ismobile']);
            $download['buttonicon'] = $ismobile
                ? 'fa-solid fa-mobile-screen-button'
                : 'fa-solid fa-file-arrow-down';
            $download['buttonclass'] = $ismobile
                ? 'btn-outline-primary'
                : 'btn-primary';
            $data['owneddownloads'][] = $download;
        }
        break;
    }

    $data['hasowneddownloads'] = $data['owneddownloads'] !== [];
}

echo $OUTPUT->header();

$data['istrialconversion'] = false;
$data['trialconversionlabel'] = get_string(
    'commerce_trial_storefront_badge',
    'local_subscriptions'
);
$data['trialexplanation'] = get_string(
    'commerce_trial_storefront_explanation',
    'local_subscriptions'
);
$data['trialconversionexpires'] = null;

if (!empty($data['hasupgrade'])) {
    $data = array_replace(
        $data,
        CommerceStorefrontCommercialPricingPresenter::upgrade(
            $product,
            $customerid,
            $currency
        )
    );

    $initialminor = 0;
    $finalminor = 0;
    foreach ($product->get_prices() as $candidateprice) {
        if ($candidateprice->get_currency() !== $currency) {
            continue;
        }

        $initialminor = (int)(
            $candidateprice->get_compare_amount_minor()
            ?? $candidateprice->get_amount_minor()
        );
        break;
    }

    if ($product->get_upgrade() !== null) {
        $finalminor = $product->get_upgrade()->get_amount_minor();
    }

    $data['commercialdiscountpercentage'] =
        $initialminor > 0 && $finalminor >= 0 && $finalminor < $initialminor
            ? (int)round(
                (($initialminor - $finalminor) * 100) / $initialminor
            )
            : null;
    $data['hascommercialdiscountpercentage'] =
        !empty($data['commercialdiscountpercentage']);
}

if ($customerid > 0) {
    $trialoffer = CommerceTrialConversionBridge::create()->resolve_for_user(
        $customerid,
        $currency,
        $product->get_sku()
    );

    if (
        $trialoffer !== null
        && $trialoffer->targets_product()
        && $trialoffer->get_product_sku() === strtoupper($product->get_sku())
    ) {
        $data['trialconversionexpires'] = userdate(
            $trialoffer->get_expires_at(),
            get_string('strftimedatetimeshort', 'langconfig')
        );

        foreach ($data['prices'] as &$presentedprice) {
            $modelprice = null;

            foreach ($product->get_prices() as $candidateprice) {
                if (
                    $candidateprice->get_currency() === $currency
                    && (int)$candidateprice->get_id()
                        === (int)$presentedprice['id']
                ) {
                    $modelprice = $candidateprice;
                    break;
                }
            }

            if ($modelprice === null) {
                continue;
            }

            // The Storefront model amount already includes the active product
            // promotion. The Trial discount is applied afterwards.
            $trialprice = CommerceTrialCartPricingService::create()->resolve(
                $customerid,
                $product->get_sku(),
                $modelprice->get_currency(),
                $modelprice->get_amount_minor()
            );

            if ($trialprice === null) {
                continue;
            }

            $data['istrialconversion'] = true;
            $presentedprice['istrialconversion'] = true;
            $presentedprice['trialdiscountpercent'] =
                $trialprice->get_discount_percent();
            $presentedprice['trialformatted'] =
                CommercePurchasePresentation::money(
                        $trialprice->get_total_minor(),
                        $trialprice->get_currency()
                    );
            $presentedprice['trialdiscountformatted'] =
                CommercePurchasePresentation::money(
                        $trialprice->get_discount_minor(),
                        $trialprice->get_currency()
                    );

            // Preserve both commercial stages when a product promotion exists:
            // comparison price -> promoted price -> Trial price.
            $presentedprice['trialbaseformatted'] =
                !empty($presentedprice['haspromotion'])
                    ? $presentedprice['compareformatted']
                    : $presentedprice['formatted'];
            $presentedprice['hasproductpromotionbeforetrial'] =
                !empty($presentedprice['haspromotion']);
            $presentedprice['productpromotionformatted'] =
                !empty($presentedprice['haspromotion'])
                    ? $presentedprice['formatted']
                    : null;
            $presentedprice['triallabel'] = get_string(
                'commerce_trial_storefront_discount',
                'local_subscriptions',
                $trialprice->get_discount_percent()
            );
            $presentedprice['productpromotionlabel'] = get_string(
                'commerce_pricing_initial_promotion',
                'local_subscriptions'
            );
            $presentedprice['initialpricelabel'] = get_string(
                'commerce_trial_storefront_initial_price',
                'local_subscriptions'
            );
            $presentedprice['trialpricelabel'] = get_string(
                'commerce_trial_storefront_final_price',
                'local_subscriptions'
            );
            $presentedprice['trialdeadline'] = get_string(
                'commerce_trial_storefront_deadline',
                'local_subscriptions',
                $data['trialconversionexpires']
            );
        }
        unset($presentedprice);
    }
}

$recommendationskus = (new CommerceStorefrontRecommendationResolver())->resolve($product->get_metadata());
$recommendations = (new CommerceStorefrontRecommendationService($repository))->cards($recommendationskus, current_language(), $currency);
$data['recommendations'] = $recommendations;
$data['hasrecommendations'] = $recommendations !== [];
$data['recommendationstitle'] = get_string('commerce_storefront_recommendations_title', 'local_subscriptions');
$data = array_replace(
    $data,
    (new CommerceShowroomProductLinkService())->present(
        $product->get_metadata(),
        strtolower(explode('_', str_replace('-', '_', current_language()))[0])
    )
);

echo $OUTPUT->render_from_template($definition->get_template(), $data);
echo $OUTPUT->footer();
