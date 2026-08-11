<?php
require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\subscription_config;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\personaloffer\admin\CommercePersonalOfferAdminService;
use local_subscriptions\commerce\personaloffer\admin\CommercePersonalOfferCrmPresentation;
use local_subscriptions\commerce\personaloffer\domain\CommercePersonalOffer;
use local_subscriptions\commerce\personaloffer\repository\MoodleCommercePersonalOfferRepository;
use local_subscriptions\commerce\mail\CommerceMailIdempotencyKey;
use local_subscriptions\commerce\mail\CommerceMailQueueRepository;
use local_subscriptions\commerce\mail\admin\CommerceMailAdminPresentation;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::VIEW_PAYMENTS);
$id = required_param('id', PARAM_INT);
$repo = new MoodleCommercePersonalOfferRepository($DB);
$offer = $repo->get_by_id($id) ?? throw new moodle_exception('commerce_personal_offer_not_found', 'local_subscriptions');
$url = new moodle_url('/local/subscriptions/admin/commerce/personal-offers/view.php', ['id' => $id]);
CrmPageConfigurator::configure($PAGE, $context, $url, get_string('commerce_personal_offer_detail_title', 'local_subscriptions'), 'local-subscriptions-commerce-personal-offer-detail');
$admin = new CommercePersonalOfferAdminService($DB);
$secureurl = $admin->secure_url($offer);
$canmanage = has_capability(Capabilities::MANAGE_CRM_ADMIN_TOOLS, $context);
$effective = $offer->get_effective_status(time());
$mailrecord = (new CommerceMailQueueRepository())->find_by_idempotency_key(CommerceMailIdempotencyKey::normalise('personal-offer:offer:' . $id));
$sourcepurchase = $offer->get_source_purchase_id() ? $DB->get_record('local_subscriptions_commerce_purchase', ['id' => $offer->get_source_purchase_id()], 'id,reference,timecreated,customerjson', IGNORE_MISSING) : null;
$redeemedpurchase = $offer->get_redeemed_purchase_id() ? $DB->get_record('local_subscriptions_commerce_purchase', ['id' => $offer->get_redeemed_purchase_id()], 'id,reference,timecreated,customerjson', IGNORE_MISSING) : null;
$beneficiaryuser = $offer->get_beneficiary_user_id() ? $DB->get_record('user', ['id' => $offer->get_beneficiary_user_id(), 'deleted' => 0], 'id,firstname,lastname,email', IGNORE_MISSING) : null;
$beneficiarylabel = CommercePersonalOfferCrmPresentation::beneficiary_label($offer->get_beneficiary_email(), $beneficiaryuser, $sourcepurchase);
$targetproduct = $DB->get_record('local_subs_commerce_product', ['id' => $offer->get_target_product_id()], 'id,name,sku', IGNORE_MISSING);
$metadata = $offer->get_metadata();
$ownershipsource = (string)($metadata['ownershipsource'] ?? '');
$eligibilitymode = (string)($metadata['eligibilitymode'] ?? '');

$purchasehtml = static function(?\stdClass $purchase): string {
    if ($purchase === null) {
        return '—';
    }
    $internal = trim((string)($purchase->reference ?? ''));
    $full = CommercePersonalOfferCrmPresentation::purchase_reference($purchase);
    $public = $full;
    if ($internal !== '') {
        $suffix = ' · ' . $internal;
        if (str_ends_with($full, $suffix)) {
            $public = substr($full, 0, -strlen($suffix));
        }
    }
    $link = html_writer::link(
        new moodle_url('/local/subscriptions/admin/commerce/purchases/view.php', ['id' => (int)$purchase->id]),
        s($public),
        ['class' => 'fw-semibold']
    );
    if ($internal !== '') {
        $link .= ' ' . html_writer::span('(' . s($internal) . ')', 'text-muted small');
    }
    return $link;
};

$campaign = null;
if ($offer->get_campaign_key()) {
    $campaign = $DB->get_record(
        'local_subs_commerce_offer_campaign',
        ['campaignkey' => $offer->get_campaign_key()],
        'id,campaignkey,name,audiencetype,sourceproductsku',
        IGNORE_MISSING
    );
}

$ownershipproduct = null;
if (!empty($metadata['ownershipproductid'])) {
    $ownershipproduct = $DB->get_record(
        'local_subs_commerce_product',
        ['id' => (int)$metadata['ownershipproductid']],
        'id,name,sku',
        IGNORE_MISSING
    );
} else if (!empty($metadata['ownershipproductsku'])) {
    $ownershipproduct = $DB->get_record(
        'local_subs_commerce_product',
        ['sku' => strtoupper((string)$metadata['ownershipproductsku'])],
        'id,name,sku',
        IGNORE_MISSING
    );
} else if ($campaign && !empty($campaign->sourceproductsku)) {
    $ownershipproduct = $DB->get_record(
        'local_subs_commerce_product',
        ['sku' => strtoupper((string)$campaign->sourceproductsku)],
        'id,name,sku',
        IGNORE_MISSING
    );
}


// Recover the concrete product/purchase used as evidence whenever possible.
// New offers persist ownershipproductid/sku. Older offers are reconstructed conservatively
// from the source purchase or from a unique matching Legacy digital purchase.
$evidencenativepurchase = $sourcepurchase;
$evidencelegacypurchase = null;

if ($ownershipproduct === null && $sourcepurchase) {
    $sourceitem = $DB->get_record_sql(
        "SELECT i.itemreference
           FROM {local_subscriptions_commerce_purchase_item} i
          WHERE i.purchaseid = :purchaseid
       ORDER BY i.position ASC, i.id ASC",
        ['purchaseid' => (int)$sourcepurchase->id],
        IGNORE_MULTIPLE
    );
    if ($sourceitem && trim((string)$sourceitem->itemreference) !== '') {
        $ownershipproduct = $DB->get_record(
            'local_subs_commerce_product',
            ['sku' => strtoupper(trim((string)$sourceitem->itemreference))],
            'id,name,sku',
            IGNORE_MISSING
        );
    }
}

if ($ownershipsource === 'legacy_digital_purchase' && $beneficiaryuser) {
    $legacyparams = [
        'userid' => (int)$beneficiaryuser->id,
        'paid' => 'paid',
        'completed' => 'completed',
        'succeeded' => 'succeeded',
    ];
    $legacywhere = "d.userid = :userid AND d.status IN (:paid,:completed,:succeeded)";
    if ($ownershipproduct) {
        $mapping = $DB->get_record('local_subs_commerce_prod_map', [
            'productid' => (int)$ownershipproduct->id,
            'legacytable' => 'subscription_digital_product',
        ], 'legacyid', IGNORE_MISSING);
        if ($mapping) {
            $legacywhere .= " AND d.productid = :legacyproductid";
            $legacyparams['legacyproductid'] = (int)$mapping->legacyid;
        }
    }
    $legacyrows = $DB->get_records_sql(
        "SELECT d.id, d.productid, d.transactionid, d.payment_date, d.creation_date,
                p.name AS legacyproductname,
                m.productid AS nativeproductid
           FROM {subscription_digital_payment_request} d
           JOIN {subscription_digital_product} p ON p.id = d.productid
      LEFT JOIN {local_subs_commerce_prod_map} m
             ON m.legacytable = :legacytable
            AND m.legacyid = d.productid
          WHERE {$legacywhere}
       ORDER BY COALESCE(d.payment_date,d.creation_date) DESC, d.id DESC",
        ['legacytable' => 'subscription_digital_product'] + $legacyparams,
        0,
        20
    );
    if ($legacyrows) {
        if ($ownershipproduct) {
            $evidencelegacypurchase = reset($legacyrows);
        } else {
            $byproduct = [];
            foreach ($legacyrows as $row) {
                $byproduct[(int)$row->productid][] = $row;
            }
            // Only infer an old offer's missing source product when the evidence is unambiguous.
            if (count($byproduct) === 1) {
                $rowsforproduct = reset($byproduct);
                $evidencelegacypurchase = reset($rowsforproduct);
                if (!empty($evidencelegacypurchase->nativeproductid)) {
                    $ownershipproduct = $DB->get_record(
                        'local_subs_commerce_product',
                        ['id' => (int)$evidencelegacypurchase->nativeproductid],
                        'id,name,sku',
                        IGNORE_MISSING
                    );
                }
            }
        }
    }
}

if ($evidencenativepurchase === null && $evidencelegacypurchase) {
    $migrated = $DB->get_record('local_subscriptions_commerce_purchase', [
        'legacyfamily' => 'digital',
        'legacyid' => (int)$evidencelegacypurchase->id,
    ], 'id,reference,timecreated,customerjson', IGNORE_MISSING);
    if ($migrated) {
        $evidencenativepurchase = $migrated;
    }
}

$pricing = $offer->get_terms()->get_data()['pricing'] ?? [];
$strategy = (string)($pricing['strategy'] ?? '');
$strategylabels = [
    'fixed_price' => get_string('commerce_personal_offer_terms_fixed_price_label', 'local_subscriptions'),
    'fixed_discount' => get_string('commerce_personal_offer_terms_fixed_discount_label', 'local_subscriptions'),
    'percentage_discount' => get_string('commerce_personal_offer_terms_percentage_label', 'local_subscriptions'),
];
$pricinglabel = $strategylabels[$strategy] ?? $strategy;
$pricingvalues = [];
if (isset($pricing['amounts']) && is_array($pricing['amounts'])) {
    $symbols = ['EUR' => '€', 'RUB' => '₽', 'USD' => '$'];
    foreach ($pricing['amounts'] as $currency => $minor) {
        $major = ((int)$minor) / 100;
        $formatted = ((int)$minor % 100 === 0)
            ? format_float($major, 0)
            : format_float($major, 2);
        $pricingvalues[] = $formatted . ' ' . ($symbols[$currency] ?? $currency);
    }
} else if (isset($pricing['basispoints'])) {
    $pricingvalues[] = format_float(((int)$pricing['basispoints']) / 100, 2) . ' %';
}
$pricingdisplay = trim($pricinglabel . ($pricingvalues ? ' — ' . implode(' / ', $pricingvalues) : ''));

$ownershiplabels = [
    'native_entitlement' => get_string('commerce_personal_offer_ownership_native_entitlement', 'local_subscriptions'),
    'native_purchase' => get_string('commerce_personal_offer_ownership_native_purchase', 'local_subscriptions'),
    'native_purchase_email' => get_string('commerce_personal_offer_ownership_native_purchase', 'local_subscriptions'),
    'bundle_components' => get_string('commerce_personal_offer_ownership_bundle', 'local_subscriptions'),
    'legacy_digital_purchase' => get_string('commerce_personal_offer_ownership_legacy_digital', 'local_subscriptions'),
    'legacy_plan' => get_string('commerce_personal_offer_ownership_legacy_plan', 'local_subscriptions'),
];

$eligibilitytype = get_string('commerce_personal_offer_eligibility_free', 'local_subscriptions');
$eligibilitydetailhtml = html_writer::span(
    s(get_string('commerce_personal_offer_eligibility_free_help', 'local_subscriptions')),
    'text-muted'
);

if ($eligibilitymode === 'product_ownership' || $ownershipsource !== '') {
    $eligibilitytype = get_string('commerce_personal_offer_eligibility_product', 'local_subscriptions');

    $productlabel = $ownershipproduct
        ? CommercePersonalOfferCrmPresentation::product_label($DB, (int)$ownershipproduct->id)
        : get_string('commerce_personal_offer_product_evidence_missing', 'local_subscriptions');

    $sourcelabel = $ownershiplabels[$ownershipsource] ?? ($ownershipsource !== '' ? $ownershipsource : '—');

    $detailparts = [];
    $detailparts[] = html_writer::div(
        html_writer::tag('span', s(get_string('commerce_personal_offer_owned_product', 'local_subscriptions') . ': '), ['class' => 'text-muted'])
        . html_writer::tag('strong', s($productlabel))
    );
    $detailparts[] = html_writer::div(
        html_writer::tag('span', s(get_string('commerce_personal_offer_ownership_source', 'local_subscriptions') . ': '), ['class' => 'text-muted'])
        . html_writer::tag('strong', s($sourcelabel))
    );

    if ($evidencenativepurchase) {
        $purchaselink = $purchasehtml($evidencenativepurchase);
        $detailparts[] = html_writer::div(
            html_writer::tag('span', s(get_string('commerce_personal_offer_evidence_purchase', 'local_subscriptions') . ': '), ['class' => 'text-muted'])
            . $purchaselink
        );
    } else if ($evidencelegacypurchase) {
        $legacylabel = get_string('commerce_personal_offer_legacy_purchase_reference', 'local_subscriptions', (int)$evidencelegacypurchase->id);
        if (!empty($evidencelegacypurchase->transactionid)) {
            $legacylabel .= ' · ' . (string)$evidencelegacypurchase->transactionid;
        }
        $legacylink = html_writer::link(
            new moodle_url(subscription_config::digital_purchase_view_admin_page(), ['id' => (int)$evidencelegacypurchase->id]),
            s($legacylabel)
        );
        $detailparts[] = html_writer::div(
            html_writer::tag('span', s(get_string('commerce_personal_offer_evidence_purchase', 'local_subscriptions') . ': '), ['class' => 'text-muted'])
            . $legacylink
        );
    }

    $eligibilitydetailhtml = implode('', $detailparts);
} else if ($eligibilitymode === 'source_purchase' || $sourcepurchase) {
    $eligibilitytype = get_string('commerce_personal_offer_eligibility_purchase', 'local_subscriptions');
    if ($sourcepurchase) {
        $eligibilitydetailhtml = $purchasehtml($sourcepurchase);
    } else {
        $eligibilitydetailhtml = '—';
    }
} else if ($eligibilitymode === 'campaign' || ($campaign && !empty($campaign->sourceproductsku))) {
    $eligibilitytype = get_string('commerce_personal_offer_eligibility_campaign', 'local_subscriptions');
    $productlabel = $ownershipproduct
        ? CommercePersonalOfferCrmPresentation::product_label($DB, (int)$ownershipproduct->id)
        : ($campaign && !empty($campaign->sourceproductsku) ? (string)$campaign->sourceproductsku : '—');
    $eligibilitydetailhtml = html_writer::div(
        html_writer::tag('span', s(get_string('commerce_personal_offer_campaign_criteria_source', 'local_subscriptions') . ': '), ['class' => 'text-muted'])
        . html_writer::tag('strong', s($productlabel))
    );
}

$campaignlabel = $campaign
    ? $campaign->name . ' (' . $campaign->campaignkey . ')'
    : ($offer->get_campaign_key() ?: get_string('commerce_personal_offer_no_campaign', 'local_subscriptions'));

$statuspresentation = static function(string $status): array {
    return match ($status) {
        CommercePersonalOffer::STATUS_ISSUED => [
            get_string('commerce_personal_offer_status_issued', 'local_subscriptions'),
            'badge bg-primary',
        ],
        CommercePersonalOffer::STATUS_REDEEMED => [
            get_string('commerce_personal_offer_status_redeemed', 'local_subscriptions'),
            'badge bg-success',
        ],
        CommercePersonalOffer::STATUS_REVOKED => [
            get_string('commerce_personal_offer_status_revoked', 'local_subscriptions'),
            'badge bg-danger',
        ],
        CommercePersonalOffer::EFFECTIVE_EXPIRED => [
            get_string('commerce_personal_offer_status_expired', 'local_subscriptions'),
            'badge bg-secondary',
        ],
        default => [s($status), 'badge bg-secondary'],
    };
};
[$effectivelabel, $effectiveclass] = $statuspresentation($effective);


$rows = [
    get_string('commerce_personal_offer_id', 'local_subscriptions') => '#' . $offer->get_id() . ' / ' . $offer->get_offer_uuid(),
    get_string('commerce_personal_offer_campaign', 'local_subscriptions') => $offer->get_campaign_key() ?? '—',
    get_string('commerce_personal_offer_beneficiary', 'local_subscriptions') => $beneficiarylabel . ($offer->get_beneficiary_user_id() ? ' / user #' . $offer->get_beneficiary_user_id() : ''),
    get_string('commerce_personal_offer_target', 'local_subscriptions') => CommercePersonalOfferCrmPresentation::product_label($DB, $offer->get_target_product_id()),
    get_string('commerce_personal_offer_source_purchase', 'local_subscriptions') => CommercePersonalOfferCrmPresentation::purchase_reference($sourcepurchase),
    get_string('commerce_personal_offer_status', 'local_subscriptions') => $effective,
    get_string('commerce_personal_offer_created', 'local_subscriptions') => $offer->get_time_created() ? userdate($offer->get_time_created()) : '—',
    get_string('commerce_personal_offer_validity', 'local_subscriptions') => ($offer->get_valid_from() ? userdate($offer->get_valid_from()) : '—') . ' → ' . ($offer->get_expires_at() ? userdate($offer->get_expires_at()) : '—'),
    get_string('commerce_personal_offer_redeemed_purchase', 'local_subscriptions') => $redeemedpurchase ? CommercePersonalOfferCrmPresentation::purchase_reference($redeemedpurchase) . ' / ' . userdate((int)$offer->get_redeemed_at()) : '—',
    get_string('commerce_personal_offer_revocation', 'local_subscriptions') => $offer->get_revoked_at() ? userdate($offer->get_revoked_at()) . ' / ' . ($offer->get_revoke_reason() ?? '—') : '—',
    get_string('commerce_personal_offer_mail_status', 'local_subscriptions') => $mailrecord ? CommerceMailAdminPresentation::status_label((string)$mailrecord->status) : get_string('commerce_personal_offer_mail_notqueued', 'local_subscriptions'),
];

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    ['label' => get_string('crm_commerce_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php')],
    ['label' => get_string('commerce_personal_offers_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/personal-offers/index.php')],
    ['label' => get_string('commerce_personal_offer_detail_title', 'local_subscriptions'), 'url' => null],
]);
echo CrmPageHeader::render(get_string('commerce_personal_offer_detail_title', 'local_subscriptions'), get_string('commerce_personal_offer_detail_help', 'local_subscriptions'), HelpContext::COMMERCE);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::PERSONAL_OFFERS, $context);
// Business summary.
echo html_writer::start_div('row g-3 mb-4');

echo html_writer::start_div('col-12 col-xl-6');
echo html_writer::start_div('card h-100 border-0 shadow-sm');
echo html_writer::start_div('card-body');
echo html_writer::tag('h3', get_string('commerce_personal_offer_summary_title', 'local_subscriptions'), ['class' => 'h5 mb-3']);
echo html_writer::tag('div', s($beneficiarylabel), ['class' => 'fw-semibold fs-5']);
echo html_writer::tag('div', s($offer->get_beneficiary_email()), ['class' => 'text-muted mb-3']);
echo html_writer::tag('div',
    html_writer::tag('span', s(get_string('commerce_personal_offer_target', 'local_subscriptions') . ': '), ['class' => 'text-muted'])
    . html_writer::tag('span', s(CommercePersonalOfferCrmPresentation::product_label($DB, $offer->get_target_product_id())), ['class' => 'fw-semibold'])
);
echo html_writer::tag('div',
    html_writer::tag('span', s(get_string('commerce_personal_offer_pricing', 'local_subscriptions') . ': '), ['class' => 'text-muted'])
    . html_writer::tag('span', s($pricingdisplay), ['class' => 'fw-semibold'])
);
echo html_writer::tag('div',
    html_writer::tag('span', s(get_string('commerce_personal_offer_status', 'local_subscriptions') . ': '), ['class' => 'text-muted'])
    . html_writer::tag('span', s($effectivelabel), ['class' => $effectiveclass])
);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('col-12 col-xl-6');
echo html_writer::start_div('card h-100 border-0 shadow-sm');
echo html_writer::start_div('card-body');
echo html_writer::tag('h3', get_string('commerce_personal_offer_eligibility_title', 'local_subscriptions'), ['class' => 'h5 mb-3']);
echo html_writer::tag('div', s($eligibilitytype), ['class' => 'fw-semibold mb-1']);
echo html_writer::div($eligibilitydetailhtml, 'mt-1');
if ($campaign) {
    echo html_writer::tag('div',
        html_writer::tag('span', s(get_string('commerce_personal_offer_campaign', 'local_subscriptions') . ': '), ['class' => 'text-muted'])
        . html_writer::tag('span', s($campaignlabel), ['class' => 'fw-semibold']),
        ['class' => 'mt-3']
    );
}
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_div();

// Validity and lifecycle.
echo html_writer::start_div('card border-0 shadow-sm mb-4');
echo html_writer::start_div('card-body');
echo html_writer::tag('h3', get_string('commerce_personal_offer_lifecycle_title', 'local_subscriptions'), ['class' => 'h5 mb-3']);
$lifecycle = new html_table();
$lifecycle->attributes['class'] = 'table table-sm mb-0';
$lifecycle->data[] = [
    s(get_string('commerce_personal_offer_created', 'local_subscriptions')),
    s($offer->get_time_created() ? userdate($offer->get_time_created()) : '—')
];
$lifecycle->data[] = [
    s(get_string('commerce_personal_offer_validity', 'local_subscriptions')),
    s(($offer->get_valid_from()
        ? userdate($offer->get_valid_from(), get_string('strftimedate', 'langconfig'), 'UTC')
        : '—')
        . ' → '
        . ($offer->get_expires_at()
            ? userdate($offer->get_expires_at(), get_string('strftimedate', 'langconfig'), 'UTC')
            : '—'))
];
$lifecycle->data[] = [
    s(get_string('commerce_personal_offer_mail_status', 'local_subscriptions')),
    s($mailrecord ? CommerceMailAdminPresentation::status_label((string)$mailrecord->status) : get_string('commerce_personal_offer_mail_notqueued', 'local_subscriptions'))
];
$lifecycle->data[] = [
    s(get_string('commerce_personal_offer_redeemed_purchase', 'local_subscriptions')),
    $redeemedpurchase
        ? $purchasehtml($redeemedpurchase) . ' ' . html_writer::span('· ' . s(userdate((int)$offer->get_redeemed_at())), 'text-muted small')
        : '—'
];
$lifecycle->data[] = [
    s(get_string('commerce_personal_offer_revocation', 'local_subscriptions')),
    s($offer->get_revoked_at() ? userdate($offer->get_revoked_at()) . ' / ' . ($offer->get_revoke_reason() ?? '—') : '—')
];
echo html_writer::table($lifecycle);
echo html_writer::end_div();
echo html_writer::end_div();

// Technical identifiers remain available, but no longer dominate the page.
echo html_writer::start_div('card border-0 bg-light mb-4');
echo html_writer::start_div('card-body');
echo html_writer::tag('h3', get_string('commerce_personal_offer_technical_title', 'local_subscriptions'), ['class' => 'h6 mb-3']);
$technical = new html_table();
$technical->attributes['class'] = 'table table-sm mb-0';
$technical->data[] = [s(get_string('commerce_personal_offer_id', 'local_subscriptions')), s('#' . $offer->get_id() . ' / ' . $offer->get_offer_uuid())];
$technical->data[] = [s(get_string('commerce_personal_offer_campaign', 'local_subscriptions')), s($campaignlabel)];
$technical->data[] = [
    s(get_string('commerce_personal_offer_source_purchase', 'local_subscriptions')),
    $purchasehtml($sourcepurchase),
];
if ($ownershipsource !== '') {
    $technical->data[] = [s(get_string('commerce_personal_offer_ownership_source', 'local_subscriptions')), s($ownershiplabels[$ownershipsource] ?? $ownershipsource)];
}
echo html_writer::table($technical);
echo html_writer::end_div();
echo html_writer::end_div();
if ($canmanage) {
    $actions = [];

    if ($effective === CommercePersonalOffer::STATUS_ISSUED) {
        $actions[] = html_writer::link(
            new moodle_url('/local/subscriptions/admin/commerce/personal-offers/edit.php', ['id' => $id]),
            get_string('commerce_personal_offer_edit', 'local_subscriptions'),
            ['class' => 'btn btn-outline-primary']
        );

        $revokeform = html_writer::start_tag('form', [
            'method' => 'post',
            'action' => (new moodle_url('/local/subscriptions/admin/commerce/personal-offers/action.php'))->out(false),
            'class' => 'd-inline',
            'onsubmit' => "return confirm('" . addslashes_js(get_string('commerce_personal_offer_revoke_confirm', 'local_subscriptions')) . "');",
        ]);
        $revokeform .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        $revokeform .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $id]);
        $revokeform .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'revoke']);
        $revokeform .= html_writer::tag(
            'button',
            get_string('commerce_personal_offer_revoke', 'local_subscriptions'),
            ['class' => 'btn btn-outline-warning', 'type' => 'submit']
        );
        $revokeform .= html_writer::end_tag('form');
        $actions[] = $revokeform;
    }

    if ($admin->can_delete($offer)) {
        $deleteurl = new moodle_url('/local/subscriptions/admin/commerce/personal-offers/action.php', [
            'id' => $id,
            'action' => 'delete',
            'sesskey' => sesskey(),
        ]);
        $actions[] = html_writer::link(
            $deleteurl,
            get_string('commerce_personal_offer_delete', 'local_subscriptions'),
            [
                'class' => 'btn btn-outline-danger',
                'onclick' => "return confirm('" . addslashes_js(get_string('commerce_personal_offer_delete_confirm', 'local_subscriptions')) . "');",
            ]
        );
    }

    if ($actions) {
        echo html_writer::div(implode(' ', $actions), 'd-flex gap-2 align-items-center mb-4');
    }
}


echo $OUTPUT->heading(get_string('commerce_personal_offer_secure_link', 'local_subscriptions'), 3);
if ($secureurl !== null && $offer->get_status() === CommercePersonalOffer::STATUS_ISSUED && !$offer->is_expired(time())) {
    echo html_writer::div(html_writer::tag('code', s($secureurl->out(false))), 'alert alert-light border');
}
if ($canmanage && $effective === CommercePersonalOffer::STATUS_ISSUED) {
    echo html_writer::start_tag('form', ['method' => 'post', 'action' => (new moodle_url('/local/subscriptions/admin/commerce/personal-offers/action.php'))->out(false), 'class' => 'mb-3']);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $id]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'sendmail']);
    if ($mailrecord && (string)$mailrecord->status === 'sent') {
        echo html_writer::link(
            new moodle_url('/local/subscriptions/admin/commerce/mail/action.php', [
                'id' => (int)$mailrecord->id,
                'action' => 'resend',
                'sesskey' => sesskey(),
            ]),
            get_string('commerce_mail_resend', 'local_subscriptions'),
            [
                'class' => 'btn btn-dark',
                'onclick' => "return confirm('" . addslashes_js(
                    get_string('commerce_mail_resend_confirm', 'local_subscriptions')
                ) . "');",
            ]
        );
    } else {
        echo html_writer::tag(
            'button',
            get_string('commerce_personal_offer_mail_queue_single', 'local_subscriptions'),
            ['class' => 'btn btn-dark', 'type' => 'submit']
        );
    }
    echo ' ' . html_writer::link(
        new moodle_url('/local/subscriptions/admin/commerce/mail/index.php', [
            'mailtype' => 'personal_offer',
            'q' => $offer->get_beneficiary_email(),
        ]),
        get_string('commerce_personal_offer_mail_log', 'local_subscriptions'),
        ['class' => 'btn btn-outline-secondary']
    );
    echo html_writer::end_tag('form');
    if ($mailrecord && !empty($mailrecord->lasterror)) {
        echo html_writer::div(s((string)$mailrecord->lasterror), 'alert alert-danger');
    }
}
if ($canmanage && ($effective === CommercePersonalOffer::EFFECTIVE_EXPIRED || $offer->get_status() === CommercePersonalOffer::STATUS_REVOKED)) {
    echo html_writer::start_tag('form', ['method' => 'post', 'action' => (new moodle_url('/local/subscriptions/admin/commerce/personal-offers/action.php'))->out(false), 'class' => 'mt-3']);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $id]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'reissue']);
    echo html_writer::tag('label', get_string('commerce_personal_offer_validity_days', 'local_subscriptions'), ['class' => 'form-label']);
    echo html_writer::empty_tag('input', ['type' => 'number', 'name' => 'validitydays', 'value' => 30, 'min' => 1, 'max' => 3650, 'class' => 'form-control mb-2']);
    echo html_writer::tag('button', get_string('commerce_personal_offer_reissue', 'local_subscriptions'), ['class' => 'btn btn-primary', 'type' => 'submit']);
    echo html_writer::end_tag('form');
}

echo $OUTPUT->heading(get_string('commerce_personal_offer_metadata_technical', 'local_subscriptions'), 3);
echo html_writer::tag('pre', s(json_encode($offer->get_metadata(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)));
echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();