<?php
require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\subscription_config;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\personaloffer\admin\CommercePersonalOfferAdminService;
use local_subscriptions\commerce\personaloffer\admin\CommercePersonalOfferCrmPresentation;
use local_subscriptions\commerce\personaloffer\domain\CommercePersonalOffer;
use local_subscriptions\commerce\personaloffer\repository\MoodleCommercePersonalOfferRepository;
use local_subscriptions\commerce\personaloffer\mail\CommercePersonalOfferMailService;
use local_subscriptions\commerce\mail\CommerceMailIdempotencyKey;
use local_subscriptions\commerce\mail\CommerceMailQueueRepository;
use local_subscriptions\commerce\mail\admin\CommerceMailAdminPresentation;
use local_subscriptions\commerce\mail\admin\CommerceMailAdminService;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\commerce\rendering\CommerceOffersAccessNavigationRenderer;
use local_subscriptions\crm\commerce\rendering\CommerceOffersAccessDetailRenderer;
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
$mailpreview = null;
$mailpreviewerror = '';
if ($effective === CommercePersonalOffer::STATUS_ISSUED) {
    try {
        if ($mailrecord) {
            $preview = (new CommerceMailAdminService())->preview(
                (int)$mailrecord->id
            );
            $mailpreview = (object)[
                'subject' => (string)$preview['subject'],
                'html' => (string)$preview['html'],
            ];
        } else {
            $message = CommercePersonalOfferMailService::create($DB)
                ->preview_offer($id);
            $mailpreview = (object)[
                'subject' => $message->get_subject(),
                'html' => $message->get_html(),
            ];
        }
    } catch (\Throwable $e) {
        $mailpreviewerror = $e->getMessage();
    }
}
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
        ? CommercePersonalOfferCrmPresentation::business_product_label($DB, (int)$ownershipproduct->id)
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
        ? CommercePersonalOfferCrmPresentation::business_product_label($DB, (int)$ownershipproduct->id)
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
    get_string('commerce_personal_offer_target', 'local_subscriptions') => CommercePersonalOfferCrmPresentation::business_product_label($DB, $offer->get_target_product_id()),
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
    [
        'label' => get_string('crm_commerce_title', 'local_subscriptions'),
        'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php'),
    ],
    [
        'label' => get_string('commerce_offers_access_title', 'local_subscriptions'),
        'url' => new moodle_url(
            '/local/subscriptions/admin/commerce/offers-access/index.php'
        ),
    ],
    [
        'label' => get_string('commerce_personal_offers_title', 'local_subscriptions'),
        'url' => new moodle_url(
            '/local/subscriptions/admin/commerce/personal-offers/index.php'
        ),
    ],
    [
        'label' => get_string('commerce_personal_offer_detail_title', 'local_subscriptions'),
        'url' => null,
    ],
]);
echo CrmPageHeader::render(
    get_string('commerce_personal_offer_detail_title', 'local_subscriptions'),
    get_string('commerce_offers_access_offer_detail_help', 'local_subscriptions'),
    HelpContext::COMMERCE
);
echo CommerceSectionNavigationRenderer::render(
    CommerceSectionNavigationRenderer::OFFERS_ACCESS,
    $context
);
echo CommerceOffersAccessNavigationRenderer::render(
    CommerceOffersAccessNavigationRenderer::OFFERS
);

$productlabel = CommercePersonalOfferCrmPresentation::business_product_label(
    $DB,
    $offer->get_target_product_id()
);
$producthtml = $targetproduct
    ? html_writer::link(
        new moodle_url(
            '/local/subscriptions/admin/commerce/products/view.php',
            ['id' => (int)$targetproduct->id]
        ),
        s($productlabel),
        ['class' => 'crm-offers-access-detail-link']
    )
    : s($productlabel);

$beneficiaryhtml = s($beneficiarylabel);
if ($offer->get_beneficiary_user_id()) {
    $beneficiaryhtml = html_writer::link(
        new moodle_url(
            subscription_config::admin_user_view_page(),
            ['id' => (int)$offer->get_beneficiary_user_id()]
        ),
        s($beneficiarylabel),
        ['class' => 'crm-offers-access-detail-link']
    );
}

$statusclass = match ($effective) {
    CommercePersonalOffer::STATUS_REDEEMED => 'is-success',
    CommercePersonalOffer::STATUS_REVOKED => 'is-error',
    CommercePersonalOffer::EFFECTIVE_EXPIRED => 'is-warning',
    default => 'is-offer',
};

$validitydisplay = ($offer->get_valid_from()
    ? userdate(
        $offer->get_valid_from(),
        get_string('strftimedate', 'langconfig')
    )
    : get_string('commerce_offers_access_now', 'local_subscriptions'))
    . ' → '
    . ($offer->get_expires_at()
        ? userdate(
            $offer->get_expires_at(),
            get_string('strftimedate', 'langconfig')
        )
        : get_string('commerce_offers_access_no_expiry', 'local_subscriptions'));

echo CommerceOffersAccessDetailRenderer::hero(
    'offer',
    $beneficiarylabel,
    $offer->get_beneficiary_email(),
    $effectivelabel,
    $statusclass,
    [
        [
            'label' => get_string('commerce_offers_access_config_product', 'local_subscriptions'),
            'value' => $producthtml,
            'html' => true,
        ],
        [
            'label' => get_string('commerce_personal_offer_pricing', 'local_subscriptions'),
            'value' => $pricingdisplay,
        ],
        [
            'label' => get_string('commerce_offers_access_config_validity', 'local_subscriptions'),
            'value' => $validitydisplay,
        ],
        [
            'label' => get_string('commerce_personal_offer_created', 'local_subscriptions'),
            'value' => $offer->get_time_created()
                ? userdate(
                    $offer->get_time_created(),
                    get_string('strftimedatetimeshort', 'langconfig')
                )
                : '—',
        ],
    ]
);

$navigationactions = [];
if ($offer->get_beneficiary_user_id()) {
    $navigationactions[] = [
        'label' => get_string('commerce_offers_access_config_open_user360', 'local_subscriptions'),
        'url' => new moodle_url(
            subscription_config::admin_user_view_page(),
            ['id' => (int)$offer->get_beneficiary_user_id()]
        ),
        'icon' => 'fa-user',
        'class' => 'btn btn-outline-secondary crm-offers-access-action is-client',
    ];
}
$navigationactions[] = [
    'label' => get_string('commerce_offers_access_offer_mail_journal', 'local_subscriptions'),
    'url' => new moodle_url(
        '/local/subscriptions/admin/commerce/mail/index.php',
        [
            'mailtype' => 'personal_offer',
            'q' => $offer->get_beneficiary_email(),
        ]
    ),
    'icon' => 'fa-envelope-o',
        'class' => 'btn btn-outline-primary crm-offers-access-action is-communication',
];
if ($sourcepurchase) {
    $navigationactions[] = [
        'label' => get_string('commerce_offers_access_open_source_sale', 'local_subscriptions'),
        'url' => new moodle_url(
            '/local/subscriptions/admin/commerce/purchases/view.php',
            ['id' => (int)$sourcepurchase->id]
        ),
        'icon' => 'fa-shopping-cart',
        'class' => 'btn btn-outline-primary crm-offers-access-action is-sale',
    ];
}
if ($redeemedpurchase) {
    $navigationactions[] = [
        'label' => get_string('commerce_offers_access_open_redeemed_sale', 'local_subscriptions'),
        'url' => new moodle_url(
            '/local/subscriptions/admin/commerce/purchases/view.php',
            ['id' => (int)$redeemedpurchase->id]
        ),
        'icon' => 'fa-check-circle',
        'class' => 'btn btn-outline-success crm-offers-access-action is-success',
    ];
}
if ($campaign) {
    $navigationactions[] = [
        'label' => get_string('commerce_offers_access_open_campaign', 'local_subscriptions'),
        'url' => new moodle_url(
            '/local/subscriptions/admin/commerce/personal-offers/campaign_view.php',
            ['id' => (int)$campaign->id]
        ),
        'icon' => 'fa-users',
        'class' => 'btn btn-outline-primary crm-offers-access-action is-campaign',
    ];
}
echo CommerceOffersAccessDetailRenderer::actions($navigationactions);

echo html_writer::start_div('crm-offers-access-detail-grid');

$eligibilitycontent = CommerceOffersAccessDetailRenderer::rows([
    [
        'label' => get_string('commerce_personal_offer_eligibility_title', 'local_subscriptions'),
        'value' => $eligibilitytype,
    ],
    [
        'label' => get_string('commerce_personal_offer_eligibility_detail', 'local_subscriptions'),
        'value' => $eligibilitydetailhtml,
        'html' => true,
    ],
]);
if ($campaign) {
    $eligibilitycontent .= CommerceOffersAccessDetailRenderer::rows([
        [
            'label' => get_string('commerce_personal_offer_campaign', 'local_subscriptions'),
            'value' => s((string)$campaign->name),
            'html' => true,
        ],
    ]);
}
echo CommerceOffersAccessDetailRenderer::panel(
    get_string('commerce_offers_access_offer_context', 'local_subscriptions'),
    $eligibilitycontent,
    'fa-check-circle-o'
);

$mailstatus = $mailrecord
    ? CommerceMailAdminPresentation::status_label((string)$mailrecord->status)
    : get_string('commerce_personal_offer_mail_notqueued', 'local_subscriptions');

$communication = CommerceOffersAccessDetailRenderer::rows([
    [
        'label' => get_string('commerce_personal_offer_mail_status', 'local_subscriptions'),
        'value' => $mailstatus,
    ],
]);
if ($mailrecord && !empty($mailrecord->timesent)) {
    $communication .= CommerceOffersAccessDetailRenderer::rows([
        [
            'label' => get_string('commerce_offers_access_last_mail_sent', 'local_subscriptions'),
            'value' => userdate(
                (int)$mailrecord->timesent,
                get_string('strftimedatetimeshort', 'langconfig')
            ),
        ],
    ]);
}

$communicationactions = html_writer::link(
    $url,
    html_writer::tag('i', '', [
        'class' => 'fa fa-refresh me-1',
        'aria-hidden' => 'true',
    ]) . get_string(
        'commerce_personal_offer_mail_refresh_status',
        'local_subscriptions'
    ),
    ['class' => 'btn btn-sm btn-outline-secondary']
);

if ($mailrecord && (string)$mailrecord->status === 'queued' && $canmanage) {
    $communicationactions .= html_writer::link(
        new moodle_url(
            '/local/subscriptions/admin/commerce/personal-offers/action.php',
            [
                'id' => $id,
                'action' => 'sendmailnow',
                'sesskey' => sesskey(),
            ]
        ),
        html_writer::tag('i', '', [
            'class' => 'fa fa-paper-plane me-1',
            'aria-hidden' => 'true',
        ]) . get_string(
            'commerce_mail_send_now',
            'local_subscriptions'
        ),
        [
            'class' => 'btn btn-sm btn-primary',
            'data-action' => 'personal-offer-send-now',
        ]
    );
}

$communication .= html_writer::div(
    $communicationactions,
    'crm-offers-access-communication-actions'
);

echo CommerceOffersAccessDetailRenderer::panel(
    get_string('commerce_offers_access_campaign_communication_title', 'local_subscriptions'),
    $communication,
    'fa-envelope-o'
);

if ($mailpreview !== null) {
    $previewcontent = html_writer::div(
        html_writer::tag(
            'strong',
            s((string)$mailpreview->subject),
            ['class' => 'crm-offers-access-mail-preview-subject']
        ),
        'crm-offers-access-mail-preview-heading'
    );
    $previewcontent .= html_writer::tag(
        'iframe',
        '',
        [
            'class' => 'crm-offers-access-mail-preview-frame',
            'srcdoc' => (string)$mailpreview->html,
            'sandbox' => '',
            'title' => get_string(
                'commerce_personal_offer_mail_preview',
                'local_subscriptions'
            ),
        ]
    );
    echo CommerceOffersAccessDetailRenderer::panel(
        get_string(
            'commerce_personal_offer_mail_preview',
            'local_subscriptions'
        ),
        $previewcontent,
        'fa-eye',
        'crm-offers-access-mail-preview-panel'
    );
} else if ($mailpreviewerror !== '') {
    echo html_writer::div(
        get_string(
            'commerce_personal_offer_mail_preview_unavailable',
            'local_subscriptions'
        ),
        'alert alert-light border'
    );
}

$lifecyclerows = [
    [
        'label' => get_string('commerce_personal_offer_created', 'local_subscriptions'),
        'value' => $offer->get_time_created()
            ? userdate($offer->get_time_created())
            : '—',
    ],
    [
        'label' => get_string('commerce_personal_offer_validity', 'local_subscriptions'),
        'value' => $validitydisplay,
    ],
];
if ($redeemedpurchase) {
    $lifecyclerows[] = [
        'label' => get_string('commerce_personal_offer_redeemed_purchase', 'local_subscriptions'),
        'value' => $purchasehtml($redeemedpurchase),
        'html' => true,
    ];
}
if ($offer->get_revoked_at()) {
    $lifecyclerows[] = [
        'label' => get_string('commerce_personal_offer_revocation', 'local_subscriptions'),
        'value' => userdate($offer->get_revoked_at())
            . ' · '
            . ($offer->get_revoke_reason() ?? '—'),
    ];
}
echo CommerceOffersAccessDetailRenderer::panel(
    get_string('commerce_personal_offer_lifecycle_title', 'local_subscriptions'),
    CommerceOffersAccessDetailRenderer::rows($lifecyclerows),
    'fa-history'
);

if ($canmanage) {
    $management = '';

    if ($effective === CommercePersonalOffer::STATUS_ISSUED) {
        $management .= html_writer::link(
            new moodle_url(
                '/local/subscriptions/admin/commerce/personal-offers/edit.php',
                ['id' => $id]
            ),
            html_writer::tag('i', '', [
                'class' => 'fa fa-pencil me-1',
                'aria-hidden' => 'true',
            ])
            . get_string('commerce_personal_offer_edit', 'local_subscriptions'),
            ['class' => 'btn btn-outline-primary']
        );

        $management .= html_writer::start_tag('form', [
            'method' => 'post',
            'action' => (new moodle_url(
                '/local/subscriptions/admin/commerce/personal-offers/action.php'
            ))->out(false),
            'class' => 'd-inline',
            'onsubmit' => "return confirm('"
                . addslashes_js(
                    get_string(
                        'commerce_personal_offer_revoke_confirm',
                        'local_subscriptions'
                    )
                )
                . "');",
        ]);
        $management .= html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'sesskey',
            'value' => sesskey(),
        ]);
        $management .= html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'id',
            'value' => $id,
        ]);
        $management .= html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'action',
            'value' => 'revoke',
        ]);
        $management .= html_writer::tag(
            'button',
            html_writer::tag('i', '', [
                'class' => 'fa fa-ban me-1',
                'aria-hidden' => 'true',
            ])
            . get_string('commerce_personal_offer_revoke', 'local_subscriptions'),
            ['class' => 'btn btn-outline-warning', 'type' => 'submit']
        );
        $management .= html_writer::end_tag('form');

        $management .= html_writer::start_tag('form', [
            'method' => 'post',
            'action' => (new moodle_url(
                '/local/subscriptions/admin/commerce/personal-offers/action.php'
            ))->out(false),
            'class' => 'd-inline',
        ]);
        $management .= html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'sesskey',
            'value' => sesskey(),
        ]);
        $management .= html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'id',
            'value' => $id,
        ]);
        $management .= html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'action',
            'value' => 'sendmail',
        ]);

        if ($mailrecord && (string)$mailrecord->status === 'sent') {
            $management .= html_writer::link(
                new moodle_url(
                    '/local/subscriptions/admin/commerce/mail/action.php',
                    [
                        'id' => (int)$mailrecord->id,
                        'action' => 'resend',
                        'sesskey' => sesskey(),
                    ]
                ),
                html_writer::tag('i', '', [
                    'class' => 'fa fa-envelope me-1',
                    'aria-hidden' => 'true',
                ])
                . get_string('commerce_mail_resend', 'local_subscriptions'),
                ['class' => 'btn btn-dark']
            );
        } else if (!$mailrecord || (string)$mailrecord->status !== 'queued') {
            $management .= html_writer::tag(
                'button',
                html_writer::tag('i', '', [
                    'class' => 'fa fa-clock-o me-1',
                    'aria-hidden' => 'true',
                ])
                . get_string(
                    'commerce_personal_offer_mail_queue_single',
                    'local_subscriptions'
                ),
                [
                    'class' => 'btn btn-outline-primary '
                        . 'crm-offers-access-action is-communication',
                    'type' => 'submit',
                ]
            );
        }
        $management .= html_writer::end_tag('form');
    }

    if (
        $effective === CommercePersonalOffer::EFFECTIVE_EXPIRED
        || $offer->get_status() === CommercePersonalOffer::STATUS_REVOKED
    ) {
        $management .= html_writer::start_tag('form', [
            'method' => 'post',
            'action' => (new moodle_url(
                '/local/subscriptions/admin/commerce/personal-offers/action.php'
            ))->out(false),
            'class' => 'crm-offers-access-reissue-form',
        ]);
        $management .= html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'sesskey',
            'value' => sesskey(),
        ]);
        $management .= html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'id',
            'value' => $id,
        ]);
        $management .= html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'action',
            'value' => 'reissue',
        ]);
        $management .= html_writer::tag(
            'label',
            get_string(
                'commerce_personal_offer_validity_days',
                'local_subscriptions'
            ),
            ['for' => 'reissue-validity-days', 'class' => 'form-label']
        );
        $management .= html_writer::empty_tag('input', [
            'id' => 'reissue-validity-days',
            'type' => 'number',
            'name' => 'validitydays',
            'value' => 30,
            'min' => 1,
            'max' => 3650,
            'class' => 'form-control',
        ]);
        $management .= html_writer::tag(
            'button',
            get_string('commerce_personal_offer_reissue', 'local_subscriptions'),
            ['class' => 'btn btn-primary', 'type' => 'submit']
        );
        $management .= html_writer::end_tag('form');
    }

    if ($admin->can_delete($offer)) {
        $management .= html_writer::link(
            new moodle_url(
                '/local/subscriptions/admin/commerce/personal-offers/action.php',
                [
                    'id' => $id,
                    'action' => 'delete',
                    'sesskey' => sesskey(),
                ]
            ),
            html_writer::tag('i', '', [
                'class' => 'fa fa-trash me-1',
                'aria-hidden' => 'true',
            ])
            . get_string('commerce_personal_offer_delete', 'local_subscriptions'),
            [
                'class' => 'btn btn-outline-danger',
                'onclick' => "return confirm('"
                    . addslashes_js(
                        get_string(
                            'commerce_personal_offer_delete_confirm',
                            'local_subscriptions'
                        )
                    )
                    . "');",
            ]
        );
    }

    if ($management !== '') {
        echo CommerceOffersAccessDetailRenderer::panel(
            get_string('commerce_offers_access_management', 'local_subscriptions'),
            html_writer::div(
                $management,
                'crm-offers-access-detail-management'
            ),
            'fa-cog'
        );
    }
}

echo html_writer::end_div();

if ($mailrecord && !empty($mailrecord->lasterror)) {
    echo html_writer::div(
        s((string)$mailrecord->lasterror),
        'alert alert-danger mt-3'
    );
}

if (
    $secureurl !== null
    && $offer->get_status() === CommercePersonalOffer::STATUS_ISSUED
    && !$offer->is_expired(time())
) {
    $securevalue = $secureurl->out(false);
    $securecontent = html_writer::div(
        html_writer::tag(
            'code',
            s($securevalue),
            [
                'class' => 'crm-offers-access-secure-link',
                'id' => 'personal-offer-secure-link',
            ]
        )
        . html_writer::tag(
            'button',
            html_writer::tag('i', '', [
                'class' => 'fa fa-copy me-1',
                'aria-hidden' => 'true',
            ])
            . get_string(
                'commerce_personal_offer_secure_link_copy',
                'local_subscriptions'
            ),
            [
                'type' => 'button',
                'class' => 'btn btn-sm btn-outline-primary',
                'data-copy-text' => $securevalue,
                'id' => 'personal-offer-copy-link',
            ]
        ),
        'crm-offers-access-secure-link-row'
    );

    echo CommerceOffersAccessDetailRenderer::technical(
        get_string(
            'commerce_personal_offer_secure_link',
            'local_subscriptions'
        ),
        $securecontent,
        true
    );
}

$technicalrows = [
    [
        'label' => get_string('commerce_personal_offer_id', 'local_subscriptions'),
        'value' => '#' . $offer->get_id() . ' / ' . $offer->get_offer_uuid(),
    ],
    [
        'label' => get_string('commerce_personal_offer_campaign', 'local_subscriptions'),
        'value' => $campaignlabel,
    ],
    [
        'label' => get_string('commerce_personal_offer_source_purchase', 'local_subscriptions'),
        'value' => $purchasehtml($sourcepurchase),
        'html' => true,
    ],
];
if ($ownershipsource !== '') {
    $technicalrows[] = [
        'label' => get_string('commerce_personal_offer_ownership_source', 'local_subscriptions'),
        'value' => $ownershiplabels[$ownershipsource] ?? $ownershipsource,
    ];
}
$technicalcontent = CommerceOffersAccessDetailRenderer::rows($technicalrows);
$technicalcontent .= html_writer::tag(
    'pre',
    s(json_encode(
        $offer->get_metadata(),
        JSON_PRETTY_PRINT
        | JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
    )),
    ['class' => 'crm-offers-access-detail-metadata']
);

echo CommerceOffersAccessDetailRenderer::technical(
    get_string('commerce_personal_offer_technical_title', 'local_subscriptions'),
    $technicalcontent
);

$copiedlabel = json_encode(
    get_string(
        'commerce_personal_offer_secure_link_copied',
        'local_subscriptions'
    ),
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);
$PAGE->requires->js_init_code(<<<JS
(function() {
    var button = document.getElementById('personal-offer-copy-link');
    if (!button) {
        return;
    }

    var original = button.innerHTML;
    button.addEventListener('click', function() {
        var value = button.getAttribute('data-copy-text') || '';
        if (!value || !navigator.clipboard) {
            return;
        }

        navigator.clipboard.writeText(value).then(function() {
            button.innerHTML =
                '<i class="fa fa-check me-1" aria-hidden="true"></i>'
                + {$copiedlabel};
            window.setTimeout(function() {
                button.innerHTML = original;
            }, 1600);
        });
    });
})();
JS);

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
