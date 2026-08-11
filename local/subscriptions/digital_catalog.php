<?php

require_once(__DIR__ . '/../../config.php');

$campuslib = $CFG->dirroot . '/local/campus/lib.php';
if (is_readable($campuslib)) {
    require_once($campuslib);
}

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\storefront\presentation\CommerceStorefrontPresenter;
use local_subscriptions\commerce\cart\presentation\CommerceCartPresenter;
use local_subscriptions\commerce\cart\service\CommerceCartRuntimeFactory;
use local_subscriptions\commerce\storefront\readmodel\CommerceStorefrontListFilter;
use local_subscriptions\commerce\storefront\repository\CommerceStorefrontRepository;
use local_subscriptions\support\Region;
use local_subscriptions\url\UrlFactory;
use local_subscriptions\commerce\trial\CommerceTrialCartPricingService;
use local_subscriptions\commerce\pricing\CommerceStorefrontCommercialPricingPresenter;

\local_subscriptions\subscription_config::guard_public_access();

$query = optional_param('q', '', PARAM_RAW_TRIMMED);
$type = optional_param('type', '', PARAM_ALPHANUMEXT);
$requestedcurrency = strtoupper(optional_param('currency', '', PARAM_ALPHA));
$currency = $requestedcurrency;
$page = max(0, optional_param('page', 0, PARAM_INT));
$customerid = isloggedin() && !isguestuser() ? (int)$USER->id : 0;
$hideowned = optional_param(
    'hideowned',
    $customerid > 0 ? 1 : 0,
    PARAM_BOOL
);

$availablecurrencies = $DB->get_fieldset_sql("
    SELECT DISTINCT UPPER(currency)
      FROM {local_subs_commerce_prod_price}
     WHERE active = 1
  ORDER BY UPPER(currency)
");
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

if ($requestedcurrency !== '' && in_array($requestedcurrency, $availablecurrencies, true)) {
    $currency = $requestedcurrency;
} else if ($storedcurrency !== '' && in_array($storedcurrency, $availablecurrencies, true)) {
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
if (!in_array($type, ['', 'course_access', 'digital_download', 'bundle'], true)) {
    $type = '';
}

$context = context_system::instance();
$pageurl = UrlFactory::digital_catalog([
    'q' => $query,
    'type' => $type,
    'currency' => $currency,
    'hideowned' => $hideowned ? 1 : 0,
]);
$PAGE->set_context($context);
$PAGE->set_url($pageurl);
$PAGE->set_pagelayout('standard');
$PAGE->add_body_class('commerce-chromeless-page');
$PAGE->set_title(get_string('commerce_storefront_title', 'local_subscriptions'));
$PAGE->set_heading(get_string('commerce_storefront_title', 'local_subscriptions'));
$PAGE->requires->css(new moodle_url('/local/subscriptions/styles/storefront.css'));

$filter = new CommerceStorefrontListFilter(
    current_language(),
    $currency,
    $type !== '' ? $type : null,
    $query
);
$result = CommerceStorefrontRepository::create($DB)->search($filter, $page, 24);
$cards = array_map(
    static fn($product): array => CommerceStorefrontPresenter::card($product, $currency),
    $result->get_products()
);
$productmodels = [];
foreach ($result->get_products() as $productmodel) {
    $productmodels[strtoupper($productmodel->get_sku())] = $productmodel;
}
$cartsnapshot = CommerceCartRuntimeFactory::create()->snapshot(
    $customerid,
    $currency,
    current_language()
);
$cartdata = CommerceCartPresenter::present($cartsnapshot);
$cartkeys = [];
foreach ($cartdata['items'] as $cartitem) {
    $cartkeys[strtoupper((string)$cartitem['productsku']) . ':' . (int)$cartitem['priceid']] = true;
}
$carturl = (UrlFactory::cart(['currency' => $currency]))->out(false);
$cartaction = (new moodle_url('/local/subscriptions/cart_action.php'))->out(false);
foreach ($cards as &$card) {
    if (!empty($card['detailsurl'])) {
        $detailsurl = new moodle_url((string)$card['detailsurl']);
        $detailsurl->param('from', 'shop');
        $card['detailsurl'] = $detailsurl->out(false);
    }
    $card['istrialconversion'] = false;
    $card['trialconversionlabel'] = get_string(
        'commerce_trial_storefront_badge',
        'local_subscriptions'
    );
    $card['trialexplanation'] = get_string(
        'commerce_trial_storefront_explanation',
        'local_subscriptions'
    );

    $model = $productmodels[strtoupper((string)$card['sku'])] ?? null;

    if ($customerid > 0 && $model !== null) {
        foreach ($card['prices'] as &$candidateprice) {
            if (
                (string)$candidateprice['currency'] !== $currency
                || empty($candidateprice['id'])
            ) {
                continue;
            }

            $modelprice = null;
            foreach ($model->get_prices() as $availableprice) {
                if (
                    $availableprice->get_currency() === $currency
                    && (int)$availableprice->get_id()
                        === (int)$candidateprice['id']
                ) {
                    $modelprice = $availableprice;
                    break;
                }
            }

            if ($modelprice === null) {
                continue;
            }

            // The Storefront amount already represents the active product
            // promotion price. Trial is therefore applied afterwards, which
            // gives an honest cumulative reduction.
            $resolvedtrialprice =
                CommerceTrialCartPricingService::create()->resolve(
                    $customerid,
                    (string)$card['sku'],
                    $currency,
                    $modelprice->get_amount_minor()
                );

            if ($resolvedtrialprice === null) {
                continue;
            }

            $card['istrialconversion'] = true;
            $candidateprice['istrialconversion'] = true;
            $candidateprice['trialdiscountpercent'] =
                $resolvedtrialprice->get_discount_percent();
            $candidateprice['trialformatted'] =
                \local_subscriptions\commerce\purchase\presentation\CommercePurchasePresentation::money(
                        $resolvedtrialprice->get_total_minor(),
                        $currency
                    );
            $candidateprice['trialdiscountformatted'] =
                \local_subscriptions\commerce\purchase\presentation\CommercePurchasePresentation::money(
                        $resolvedtrialprice->get_discount_minor(),
                        $currency
                    );

            // Without a product promotion, the normal price is the Trial
            // comparison price. With a product promotion, keep both stages:
            // catalogue price -> promotion price -> Trial price.
            $candidateprice['trialbaseformatted'] =
                !empty($candidateprice['haspromotion'])
                    ? $candidateprice['compareformatted']
                    : $candidateprice['formatted'];
            $candidateprice['hasproductpromotionbeforetrial'] =
                !empty($candidateprice['haspromotion']);
            $candidateprice['productpromotionformatted'] =
                !empty($candidateprice['haspromotion'])
                    ? $candidateprice['formatted']
                    : null;
            $candidateprice['triallabel'] = get_string(
                'commerce_trial_storefront_discount',
                'local_subscriptions',
                $resolvedtrialprice->get_discount_percent()
            );
            $candidateprice['productpromotionlabel'] =
                get_string(
                    'commerce_pricing_initial_promotion',
                    'local_subscriptions'
                );
            $candidateprice['trialpricelabel'] =
                get_string(
                    'commerce_trial_storefront_final_price',
                    'local_subscriptions'
                );
        }
        unset($candidateprice);
    }

    if (!empty($card['hasupgrade']) && $model !== null) {
        $card = array_replace(
            $card,
            CommerceStorefrontCommercialPricingPresenter::upgrade(
                $model,
                $customerid,
                $currency
            )
        );
    }

    $card['cartaction'] = $cartaction;
    $card['cartreturnurl'] = $pageurl->out(false);
    $card['cartcurrency'] = $currency;
    $card['cartsesskey'] = sesskey();
    $card['buy_now_label'] = get_string(
        'commerce_cart_buy_now',
        'local_subscriptions'
    );
    $card['standardpricelabel'] = get_string(
        'commerce_storefront_price_standard',
        'local_subscriptions'
    );
    $card['promotionpricelabel'] = get_string(
        'commerce_storefront_price_promotional',
        'local_subscriptions'
    );
    $card['trialpricelabelcard'] = get_string(
        'commerce_storefront_price_trial',
        'local_subscriptions'
    );
    $card['upgradepricelabelcard'] = get_string(
        'commerce_storefront_price_upgrade',
        'local_subscriptions'
    );

    if (!empty($card['hascommercialupgradepricing'])) {
        $initialminor = (int)(
            $model?->get_prices()[0]?->get_compare_amount_minor()
            ?? $model?->get_prices()[0]?->get_amount_minor()
            ?? 0
        );
        $finalminor = 0;
        if (!empty($card['upgradepriceformatted'])
            && $model?->get_upgrade() !== null) {
            $finalminor = $model->get_upgrade()->get_amount_minor();
        }
        $card['commercialdiscountpercentage'] =
            $initialminor > 0 && $finalminor < $initialminor
                ? (int)round(
                    (($initialminor - $finalminor) * 100) / $initialminor
                )
                : null;
        $card['hascommercialdiscountpercentage'] =
            !empty($card['commercialdiscountpercentage']);
    }

    foreach ($card['prices'] as &$price) {
        $key = strtoupper((string)$card['sku']) . ':' . (int)$price['id'];
        $price['incart'] = isset($cartkeys[$key]);
        $price['toggleaction'] = $price['incart'] ? 'remove' : 'add';
        $price['togglelabel'] = $price['incart']
            ? get_string('commerce_cart_remove_from_cart', 'local_subscriptions')
            : get_string('commerce_cart_add', 'local_subscriptions');
    }
    unset($price);
    if (!empty($card['upgradepriceid'])) {
        $upgradekey = strtoupper((string)$card['sku']) . ':' . (int)$card['upgradepriceid'];
        $card['upgradeincart'] = isset($cartkeys[$upgradekey]);
        $card['upgradetoggleaction'] = $card['upgradeincart'] ? 'remove' : 'add';
        $card['upgradetogglelabel'] = $card['upgradeincart']
            ? get_string('commerce_cart_remove_from_cart', 'local_subscriptions')
            : $card['upgradeactionlabel'];
    }
}
unset($card);

if ($customerid > 0 && $hideowned) {
    $cards = array_values(array_filter(
        $cards,
        static fn(array $card): bool => empty($card['owned'])
    ));
}

$groups = [];
$grouporder = ['courses', 'resources', 'bundles'];
foreach ($grouporder as $groupkey) {
    $groupcards = array_values(array_filter(
        $cards,
        static fn(array $card): bool => ($card['group'] ?? '') === $groupkey
    ));
    if ($groupcards === []) {
        continue;
    }
    $groups[] = [
        'key' => $groupkey,
        'title' => CommerceStorefrontPresenter::group_label($groupkey),
        'intro' => CommerceStorefrontPresenter::group_intro($groupkey),
        'products' => $groupcards,
    ];
}

$baseparams = ['currency' => $currency];
$contextdata = [
    'title' => get_string('commerce_storefront_title', 'local_subscriptions'),
    'intro' => get_string('commerce_storefront_intro', 'local_subscriptions'),
    'filteraction' => UrlFactory::digital_catalog()->out(false),
    'query' => $query,
    'currency' => $currency,
    'currencylabel' => get_string('currency'),
    'currencyselectlabel' => get_string('commerce_storefront_currency_displayed', 'local_subscriptions'),
    'currencyselectid' => 'storefront-currency',
    'currenttype' => $type,
    'searchlabel' => get_string('search'),
    'searchplaceholder' => get_string('commerce_storefront_search_placeholder', 'local_subscriptions'),
    'filterlabel' => get_string('filter'),
    'filterstogglelabel' => get_string('commerce_storefront_filters_toggle', 'local_subscriptions'),
    'reseturl' => UrlFactory::digital_catalog(['currency' => $currency])->out(false),
    'resetlabel' => get_string('reset'),
    'currencies' => array_map(
        static function(string $code) use ($currency): array {
            return [
                'value' => $code,
                'label' => \local_subscriptions\currency\CommerceCurrencyLabelFormatter::format($code),
                'selected' => $currency === $code,
            ];
        },
        $availablecurrencies
    ),
    'types' => [
        ['value' => '', 'label' => get_string('all'), 'selected' => $type === ''],
        ['value' => 'course_access', 'label' => get_string('commerce_purchase_type_subscription', 'local_subscriptions'), 'selected' => $type === 'course_access'],
        ['value' => 'digital_download', 'label' => get_string('commerce_product_type_digital_download', 'local_subscriptions'), 'selected' => $type === 'digital_download'],
        ['value' => 'bundle', 'label' => get_string('commerce_product_type_bundle', 'local_subscriptions'), 'selected' => $type === 'bundle'],
    ],
    'typelabel' => get_string(
        'commerce_storefront_filter_type',
        'local_subscriptions'
    ),
    'showownedfilter' => $customerid > 0,
    'hideowned' => (bool)$hideowned,
    'hideownedlabel' => get_string(
        'commerce_storefront_hide_owned',
        'local_subscriptions'
    ),
    'hideownedhelp' => get_string(
        'commerce_storefront_hide_owned_help',
        'local_subscriptions'
    ),
    'groups' => $groups,
    'products' => $cards,
    'hasproducts' => $cards !== [],
    'emptytitle' => get_string('commerce_storefront_empty_title', 'local_subscriptions'),
    'emptytext' => get_string('commerce_storefront_empty', 'local_subscriptions'),
    'resultcount' => get_string(
        'commerce_storefront_result_count',
        'local_subscriptions',
        count($cards)
    ),
    'carturl' => $carturl,
    'cartlabel' => get_string('commerce_cart_view', 'local_subscriptions'),
    'cartlinecount' => $cartdata['linecount'],
    'carttotalformatted' => $cartdata['totalformatted'],
];

$params = array_filter([
    'q' => $query,
    'type' => $type,
    'currency' => $currency,
    'hideowned' => $hideowned ? 1 : 0,
], static fn($value): bool => $value !== '');

ob_start();
echo $OUTPUT->paging_bar(
    $result->get_total(),
    $result->get_page(),
    $result->get_per_page(),
    new moodle_url(UrlFactory::digital_catalog(), $params)
);
$contextdata['pagination'] = ob_get_clean();

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
$cartsku = strtoupper(optional_param('cartsku', '', PARAM_RAW_TRIMMED));
$contextdata['showaddedmodal'] = $cartchanged && $cartactionresult === 'add';
$contextdata['addedmodaltitle'] = get_string('commerce_cart_added_modal_title', 'local_subscriptions');
$contextdata['addedmodaltext'] = $cartnotice === 'bundle_partial_owned'
    ? get_string('commerce_cart_message_bundle_partial_owned', 'local_subscriptions')
    : get_string('commerce_cart_added_modal_text', 'local_subscriptions');
$contextdata['continueurl'] = $pageurl->out(false);
$contextdata['continuelabel'] = get_string('commerce_cart_continue_shopping', 'local_subscriptions');
$contextdata['viewcartlabel'] = get_string('commerce_cart_view', 'local_subscriptions');
$contextdata['addedproductname'] = '';
foreach ($cards as $card) {
    if (strtoupper((string)$card['sku']) === $cartsku) {
        $contextdata['addedproductname'] = $card['name'];
        $contextdata['addedproductcoverurl'] = $card['coverurl'];
        $contextdata['addedproducthascover'] = $card['hascover'];
        break;
    }
}


$contextdata['trialbannerhtml'] = '';
$contextdata['hastrialbanner'] = false;

if (function_exists('local_campus_render_trial_discount_banner')) {
    // Capture the shared countdown so the template can place it precisely
    // between the Boutique introduction and the search/filter controls.
    ob_start();
    local_campus_render_trial_discount_banner(false);
    $contextdata['trialbannerhtml'] = trim((string)ob_get_clean());
    $contextdata['hastrialbanner'] =
        $contextdata['trialbannerhtml'] !== '';
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template(
    'local_subscriptions/storefront/catalog',
    $contextdata
);
echo $OUTPUT->footer();
