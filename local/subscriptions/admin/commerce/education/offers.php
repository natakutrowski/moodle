<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\education\admin\CommercePedagogicalPromotionAdminRenderer;
use local_subscriptions\commerce\catalog\persistence\CommerceCatalogHydrator;
use local_subscriptions\commerce\catalog\repository\CommerceProductPriceRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;
use local_subscriptions\commerce\currency\CommerceCurrencyAmount;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionOfferRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionRepository;
use local_subscriptions\commerce\education\promotionjoin\CommercePedagogicalPromotionJoinPriceRepository;
use local_subscriptions\commerce\education\promotionjoin\CommercePedagogicalPromotionJoinPricingService;
use local_subscriptions\commerce\purchase\presentation\CommercePurchasePresentation;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

global $DB, $OUTPUT, $PAGE, $USER;

$context = AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);
$promotionid = required_param('promotionid', PARAM_INT);
$promotion = CommercePedagogicalPromotionRepository::create($DB)->get_by_id($promotionid);
if ($promotion === null) {
    throw new moodle_exception('invalidrecord', 'error');
}

$pageurl = new moodle_url('/local/subscriptions/admin/commerce/education/offers.php', [
    'promotionid' => $promotionid,
]);
$title = get_string('commerce_education_offers_title', 'local_subscriptions', $promotion->get_name());

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $title,
    'local-subscriptions-commerce-education-offers-page'
);

$links = CommercePedagogicalPromotionOfferRepository::create($DB);
$hydrator = new CommerceCatalogHydrator();
$productrepository = new CommerceProductRepository($DB, $hydrator);
$productprices = new CommerceProductPriceRepository($DB, $hydrator, $productrepository);
$joinprices = CommercePedagogicalPromotionJoinPriceRepository::create($DB);
$joinpricing = CommercePedagogicalPromotionJoinPricingService::create($DB);
$products = $productrepository->find_active();

if (data_submitted()) {
    require_sesskey();
    $action = required_param('action', PARAM_ALPHA);
    $productid = required_param('productid', PARAM_INT);

    if ($action === 'link' || $action === 'update') {
        $capacityraw = trim(optional_param('capacity', '', PARAM_RAW_TRIMMED));
        $links->link(
            $promotionid,
            $productid,
            $capacityraw === '' ? null : max(1, (int)$capacityraw),
            (int)$USER->id,
            time()
        );
    } else if ($action === 'joinprice') {
        $link = $links->link_for_promotion_and_product($promotionid, $productid);
        if ($link === null) {
            throw new moodle_exception('invalidrecord', 'error');
        }
        $product = $productrepository->find_by_id($productid);
        if ($product === null) {
            throw new moodle_exception('invalidrecord', 'error');
        }

        $supportedcurrencies = [];
        foreach ($productprices->find_by_product_sku($product->get_sku(), true) as $baseprice) {
            $supportedcurrencies[$baseprice->get_currency()] = true;
        }
        $submitted = optional_param_array('joinprice', [], PARAM_RAW_TRIMMED);
        $now = time();
        foreach (array_keys($supportedcurrencies) as $currency) {
            $raw = trim((string)($submitted[$currency] ?? ''));
            if ($raw === '') {
                $joinpricing->clear($promotionid, $productid, $currency);
                continue;
            }
            try {
                $money = CommerceCurrencyAmount::from_major_input($raw, $currency);
            } catch (\coding_exception) {
                throw new moodle_exception(
                    'commerce_education_offer_owner_price_invalid',
                    'local_subscriptions',
                    '',
                    $currency
                );
            }
            if ($money->get_amount_minor() <= 0) {
                throw new moodle_exception(
                    'commerce_education_offer_owner_price_invalid',
                    'local_subscriptions',
                    '',
                    $currency
                );
            }
            $joinpricing->configure(
                $promotionid,
                $productid,
                $currency,
                $money->get_amount_minor(),
                (int)$USER->id,
                $now
            );
        }
    } else if ($action === 'unlink') {
        $links->unlink($promotionid, $productid);
    }

    redirect($pageurl, get_string('changessaved'), null, \core\output\notification::NOTIFY_SUCCESS);
}

$current = $links->links_for_promotion($promotionid);
$linkedproductids = [];
foreach ($current as $link) {
    $linkedproductids[(int)$link->productid] = true;
}

$participantcounts = [];
foreach ($DB->get_records_sql(
    "SELECT productid, COUNT(1) AS participantcount
       FROM {local_subs_commerce_ped_join}
      WHERE promotionid = :promotionid
        AND state = :state
   GROUP BY productid",
    ['promotionid' => $promotionid, 'state' => 'active']
) as $record) {
    $participantcounts[(int)$record->productid] = (int)$record->participantcount;
}
$totalparticipants = array_sum($participantcounts);
$ownerpricecount = $DB->count_records('local_subs_commerce_ped_jprice', [
    'promotionid' => $promotionid,
]);

$availableproducts = array_values(array_filter(
    $products,
    static fn($product): bool => !isset($linkedproductids[(int)$product->get_id()])
));

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    [
        'label' => get_string('commerce_education_promotions_title', 'local_subscriptions'),
        'url' => new moodle_url('/local/subscriptions/admin/commerce/education/promotions.php'),
    ],
    [
        'label' => $promotion->get_name(),
        'url' => new moodle_url('/local/subscriptions/admin/commerce/education/promotion_edit.php', ['id' => $promotionid]),
    ],
    ['label' => get_string('commerce_education_offers', 'local_subscriptions'), 'url' => null],
]);

echo CrmPageHeader::render(
    $title,
    get_string('commerce_education_m73_offers_description', 'local_subscriptions'),
    HelpContext::COMMERCE
);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::EDUCATION, $context);

echo CommercePedagogicalPromotionAdminRenderer::render(
    $promotion,
    CommercePedagogicalPromotionAdminRenderer::OFFERS,
    $DB
);

// Compact operational summary.
echo html_writer::start_div('commerce-ped-m73-metrics');
foreach ([
    ['fa-tags', get_string('commerce_education_m73_offers_metric_linked', 'local_subscriptions'), count($current)],
    ['fa-users', get_string('commerce_education_m73_offers_metric_participants', 'local_subscriptions'), $totalparticipants],
    ['fa-ticket', get_string('commerce_education_m73_offers_metric_owner_prices', 'local_subscriptions'), $ownerpricecount],
] as [$icon, $label, $value]) {
    echo html_writer::start_div('commerce-ped-m73-metric');
    echo html_writer::tag('i', '', ['class' => 'fa ' . $icon, 'aria-hidden' => 'true']);
    echo html_writer::start_div();
    echo html_writer::div((string)$value, 'commerce-ped-m73-metric-value');
    echo html_writer::div((string)$label, 'commerce-ped-m73-metric-label');
    echo html_writer::end_div();
    echo html_writer::end_div();
}
echo html_writer::end_div();

echo html_writer::start_div('commerce-ped-m73-layout');

// Add an offer.
echo html_writer::start_div('commerce-ped-m73-side');
echo html_writer::start_div('commerce-ped-m73-panel');
echo html_writer::start_div('commerce-ped-m73-panel-header');
echo html_writer::tag('i', '', ['class' => 'fa fa-plus-circle commerce-ped-m73-panel-icon', 'aria-hidden' => 'true']);
echo html_writer::start_div();
echo html_writer::tag('h3', get_string('commerce_education_m73_offers_add_title', 'local_subscriptions'), ['class' => 'commerce-ped-m73-panel-title']);
echo html_writer::div(get_string('commerce_education_m73_offers_add_help', 'local_subscriptions'), 'commerce-ped-m73-panel-description');
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::start_div('commerce-ped-m73-panel-body');

if ($availableproducts === []) {
    echo html_writer::div(
        get_string('commerce_education_m73_offers_all_linked', 'local_subscriptions'),
        'commerce-ped-m73-empty'
    );
} else {
    echo html_writer::start_tag('form', [
        'method' => 'post',
        'action' => $pageurl->out(false),
        'class' => 'commerce-ped-m73-form',
    ]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'link']);

    $options = [];
    foreach ($availableproducts as $product) {
        $options[(int)$product->get_id()] = $product->get_name() . ' [' . $product->get_sku() . ']';
    }
    echo html_writer::start_div('commerce-ped-m73-field');
    echo html_writer::tag('label', get_string('commerce_education_offer_product', 'local_subscriptions'), [
        'class' => 'form-label',
        'for' => 'ped-offer-product',
    ]);
    echo html_writer::select(
        $options,
        'productid',
        '',
        ['' => get_string('choosedots')],
        ['id' => 'ped-offer-product', 'class' => 'form-select', 'required' => 'required']
    );
    echo html_writer::end_div();

    echo html_writer::start_div('commerce-ped-m73-field');
    echo html_writer::tag('label', get_string('commerce_education_offer_capacity', 'local_subscriptions'), [
        'class' => 'form-label',
        'for' => 'ped-offer-capacity',
    ]);
    echo html_writer::empty_tag('input', [
        'id' => 'ped-offer-capacity',
        'type' => 'number',
        'name' => 'capacity',
        'min' => '1',
        'class' => 'form-control',
        'placeholder' => get_string('commerce_education_m73_unlimited', 'local_subscriptions'),
    ]);
    echo html_writer::div(
        get_string('commerce_education_m73_offer_capacity_help', 'local_subscriptions'),
        'form-text'
    );
    echo html_writer::end_div();

    echo html_writer::tag('button', get_string('commerce_education_m73_offers_link_button', 'local_subscriptions'), [
        'type' => 'submit',
        'class' => 'btn btn-primary w-100',
    ]);
    echo html_writer::end_tag('form');
}

echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::div(
    html_writer::tag('i', '', ['class' => 'fa fa-info-circle', 'aria-hidden' => 'true'])
        . html_writer::span(get_string('commerce_education_m73_offers_note', 'local_subscriptions')),
    'commerce-ped-m73-note'
);
echo html_writer::end_div();

// Current offers as operational cards.
echo html_writer::start_div('commerce-ped-m73-main');
echo html_writer::start_div('commerce-ped-m73-section-heading');
echo html_writer::start_div();
echo html_writer::tag('h3', get_string('commerce_education_offers_current', 'local_subscriptions'), ['class' => 'commerce-ped-m73-section-title']);
echo html_writer::div(get_string('commerce_education_m73_offers_current_help', 'local_subscriptions'), 'commerce-ped-m73-section-description');
echo html_writer::end_div();
echo html_writer::span((string)count($current), 'commerce-ped-m73-count');
echo html_writer::end_div();

if ($current === []) {
    echo html_writer::div(get_string('commerce_education_offers_empty', 'local_subscriptions'), 'commerce-ped-m73-empty is-large');
} else {
    echo html_writer::start_div('commerce-ped-offer-list');

    foreach ($current as $link) {
        $productid = (int)$link->productid;
        $participants = $participantcounts[$productid] ?? 0;
        $capacity = $link->capacity !== null ? (int)$link->capacity : null;
        $remaining = $capacity !== null ? max(0, $capacity - $participants) : null;

        $statuslabel = get_string(
            'commerce_product_status_' . (string)$link->productstatus,
            'local_subscriptions'
        );
        $statusclass = (string)$link->productstatus === 'active'
            ? 'is-active'
            : 'is-muted';

        $baseprices = [];
        foreach ($productprices->find_by_product_sku((string)$link->sku, true) as $baseprice) {
            $currency = $baseprice->get_currency();
            if (!isset($baseprices[$currency])) {
                $baseprices[$currency] = $baseprice;
            }
        }
        ksort($baseprices);

        $configuredjoinprices = [];
        foreach ($joinprices->all_for_offer($promotionid, $productid) as $joinprice) {
            $configuredjoinprices[$joinprice->get_currency()] = $joinprice;
        }

        echo html_writer::start_div('commerce-ped-offer-card');
        echo html_writer::start_div('commerce-ped-offer-card-header');
        echo html_writer::start_div('commerce-ped-offer-card-title-wrap');
        echo html_writer::div(s((string)$link->sku), 'commerce-ped-offer-card-sku');
        echo html_writer::tag('h4', s((string)$link->productname), ['class' => 'commerce-ped-offer-card-title']);
        echo html_writer::end_div();
        echo html_writer::span($statuslabel, 'commerce-ped-offer-status ' . $statusclass);
        echo html_writer::end_div();

        echo html_writer::start_div('commerce-ped-offer-card-metrics');
        echo html_writer::start_div('commerce-ped-offer-card-metric');
        echo html_writer::tag('i', '', ['class' => 'fa fa-users', 'aria-hidden' => 'true']);
        echo html_writer::start_div();
        echo html_writer::div((string)$participants, 'commerce-ped-offer-card-metric-value');
        echo html_writer::div(get_string('commerce_education_m73_offer_participants', 'local_subscriptions'), 'commerce-ped-offer-card-metric-label');
        echo html_writer::end_div();
        echo html_writer::end_div();

        echo html_writer::start_div('commerce-ped-offer-card-metric');
        echo html_writer::tag('i', '', ['class' => 'fa fa-ticket', 'aria-hidden' => 'true']);
        echo html_writer::start_div();
        echo html_writer::div(
            $capacity !== null ? (string)$capacity : '∞',
            'commerce-ped-offer-card-metric-value'
        );
        echo html_writer::div(get_string('commerce_education_offer_capacity', 'local_subscriptions'), 'commerce-ped-offer-card-metric-label');
        echo html_writer::end_div();
        echo html_writer::end_div();

        echo html_writer::start_div('commerce-ped-offer-card-metric');
        echo html_writer::tag('i', '', ['class' => 'fa fa-shopping-basket', 'aria-hidden' => 'true']);
        echo html_writer::start_div();
        echo html_writer::div(
            $remaining !== null ? (string)$remaining : '∞',
            'commerce-ped-offer-card-metric-value'
        );
        echo html_writer::div(get_string('commerce_education_m73_offer_remaining', 'local_subscriptions'), 'commerce-ped-offer-card-metric-label');
        echo html_writer::end_div();
        echo html_writer::end_div();
        echo html_writer::end_div();

        echo html_writer::start_div('commerce-ped-offer-card-body');

        // Capacity editor.
        echo html_writer::start_div('commerce-ped-offer-setting');
        echo html_writer::start_div('commerce-ped-offer-setting-heading');
        echo html_writer::tag('h5', get_string('commerce_education_m73_offer_capacity_title', 'local_subscriptions'), ['class' => 'commerce-ped-offer-setting-title']);
        echo html_writer::div(get_string('commerce_education_m73_offer_capacity_description', 'local_subscriptions'), 'commerce-ped-offer-setting-description');
        echo html_writer::end_div();
        echo html_writer::start_tag('form', [
            'method' => 'post',
            'action' => $pageurl->out(false),
            'class' => 'commerce-ped-offer-capacity-form',
        ]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'update']);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'productid', 'value' => (int)$link->productid]);
        echo html_writer::empty_tag('input', [
            'type' => 'number',
            'name' => 'capacity',
            'min' => '1',
            'value' => $link->capacity !== null ? (int)$link->capacity : '',
            'class' => 'form-control form-control-sm',
            'placeholder' => get_string('commerce_education_m73_unlimited', 'local_subscriptions'),
            'aria-label' => get_string('commerce_education_offer_capacity', 'local_subscriptions'),
        ]);
        echo html_writer::tag('button', get_string('savechanges'), [
            'type' => 'submit',
            'class' => 'btn btn-sm btn-outline-primary',
        ]);
        echo html_writer::end_tag('form');
        echo html_writer::end_div();

        // Catalogue prices and owner prices.
        echo html_writer::start_div('commerce-ped-offer-setting');
        echo html_writer::start_div('commerce-ped-offer-setting-heading');
        echo html_writer::tag('h5', get_string('commerce_education_m73_offer_pricing_title', 'local_subscriptions'), ['class' => 'commerce-ped-offer-setting-title']);
        echo html_writer::div(get_string('commerce_education_m73_offer_pricing_description', 'local_subscriptions'), 'commerce-ped-offer-setting-description');
        echo html_writer::end_div();

        if ($baseprices === []) {
            echo html_writer::div(
                get_string('commerce_education_offer_owner_price_no_currency', 'local_subscriptions'),
                'commerce-ped-m73-empty is-compact'
            );
        } else {
            echo html_writer::start_div('commerce-ped-offer-catalogue-prices');
            foreach ($baseprices as $currency => $baseprice) {
                echo html_writer::span(
                    CommercePurchasePresentation::money($baseprice->get_amount_minor(), $currency),
                    'commerce-ped-offer-catalogue-price'
                );
            }
            echo html_writer::end_div();

            echo html_writer::start_tag('form', [
                'method' => 'post',
                'action' => $pageurl->out(false),
                'class' => 'commerce-ped-offer-owner-form',
            ]);
            echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
            echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'joinprice']);
            echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'productid', 'value' => $productid]);

            foreach ($baseprices as $currency => $baseprice) {
                $configured = $configuredjoinprices[$currency] ?? null;
                $fieldid = 'ped-owner-price-' . $productid . '-' . strtolower($currency);
                echo html_writer::start_div('commerce-ped-offer-owner-row');
                echo html_writer::tag('label', $currency, ['for' => $fieldid, 'class' => 'commerce-ped-offer-owner-currency']);
                echo html_writer::empty_tag('input', [
                    'id' => $fieldid,
                    'type' => 'text',
                    'inputmode' => 'decimal',
                    'name' => 'joinprice[' . $currency . ']',
                    'value' => $configured === null
                        ? ''
                        : CommerceCurrencyAmount::major_input_from_minor(
                            $configured->get_amount_minor(),
                            $currency
                        ),
                    'class' => 'form-control form-control-sm',
                    'placeholder' => CommerceCurrencyAmount::major_input_from_minor(
                        $baseprice->get_amount_minor(),
                        $currency
                    ),
                ]);
                echo html_writer::span(
                    get_string(
                        'commerce_education_offer_owner_price_catalogue',
                        'local_subscriptions',
                        CommercePurchasePresentation::money($baseprice->get_amount_minor(), $currency)
                    ),
                    'commerce-ped-offer-owner-reference'
                );
                echo html_writer::end_div();
            }
            echo html_writer::div(
                get_string('commerce_education_offer_owner_pricing_help', 'local_subscriptions'),
                'commerce-ped-offer-owner-help'
            );
            echo html_writer::tag('button', get_string('savechanges'), [
                'type' => 'submit',
                'class' => 'btn btn-sm btn-outline-primary',
            ]);
            echo html_writer::end_tag('form');
        }
        echo html_writer::end_div();
        echo html_writer::end_div();

        echo html_writer::start_div('commerce-ped-offer-card-footer');
        echo html_writer::span(
            get_string('commerce_education_m73_offer_unlink_help', 'local_subscriptions'),
            'commerce-ped-offer-card-footer-help'
        );
        echo html_writer::start_tag('form', [
            'method' => 'post',
            'action' => $pageurl->out(false),
            'class' => 'm-0',
        ]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'unlink']);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'productid', 'value' => $productid]);
        echo html_writer::tag('button', get_string('commerce_education_m73_offer_unlink', 'local_subscriptions'), [
            'type' => 'submit',
            'class' => 'btn btn-sm btn-outline-danger',
        ]);
        echo html_writer::end_tag('form');
        echo html_writer::end_div();

        echo html_writer::end_div();
    }

    echo html_writer::end_div();
}

echo html_writer::end_div();
echo html_writer::end_div();

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
