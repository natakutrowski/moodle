<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\personaloffer\domain\CommercePersonalOffer;
use local_subscriptions\commerce\personaloffer\admin\CommercePersonalOfferCrmPresentation;
use local_subscriptions\commerce\personaloffer\repository\MoodleCommercePersonalOfferRepository;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\commerce\rendering\CommerceOffersAccessNavigationRenderer;
use local_subscriptions\crm\commerce\rendering\CommerceOffersAccessPolishRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::VIEW_PAYMENTS);
$query = trim(optional_param('q', '', PARAM_RAW_TRIMMED));
$status = trim(optional_param('status', '', PARAM_ALPHANUMEXT));
$campaignkey = trim(optional_param('campaignkey', '', PARAM_TEXT));
$productid = max(0, optional_param('productid', 0, PARAM_INT));
$period = trim(optional_param('period', '30', PARAM_ALPHANUMEXT));
$customfrom = trim(optional_param('datefrom', '', PARAM_RAW_TRIMMED));
$customto = trim(optional_param('dateto', '', PARAM_RAW_TRIMMED));
$page = max(0, optional_param('page', 0, PARAM_INT));
$perpage = min(200, max(25, optional_param('perpage', 100, PARAM_INT)));

$allowedstatuses = [
    '',
    CommercePersonalOffer::STATUS_ISSUED,
    CommercePersonalOffer::STATUS_REDEEMED,
    CommercePersonalOffer::STATUS_REVOKED,
];
if (!in_array($status, $allowedstatuses, true)) {
    $status = '';
}

$allowedperiods = ['today', '7', '30', '90', '365', 'all', 'custom'];
if (!in_array($period, $allowedperiods, true)) {
    $period = '30';
}
/** @return int|null */
$parsedate = static function(string $value, bool $endofday = false): ?int {
    $value = trim($value);
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value)) {
        return null;
    }
    try {
        $timezone = \core_date::get_user_timezone_object();
        $date = new \DateTimeImmutable($value, $timezone);
        $date = $endofday
            ? $date->setTime(23, 59, 59)
            : $date->setTime(0, 0, 0);
        return $date->getTimestamp();
    } catch (\Throwable) {
        return null;
    }
};

$now = time();
$timefrom = 0;
$timeto = 0;
if ($period === 'custom') {
    $timefrom = $parsedate($customfrom) ?? 0;
    $timeto = $parsedate($customto, true) ?? 0;
} else if ($period === 'today') {
    $timefrom = usergetmidnight($now);
    $timeto = $now;
} else if ($period !== 'all') {
    $days = (int)$period;
    $days = in_array($days, [7, 30, 90, 365], true) ? $days : 30;
    $timefrom = $now - ($days * DAYSECS);
    $timeto = $now;
    $period = (string)$days;
}

$productoptions = [0 => get_string('all')];
foreach ($DB->get_records(
    'local_subs_commerce_product',
    ['status' => 'active'],
    'name ASC',
    'id,name,sku'
) as $product) {
    $productoptions[(int)$product->id] =
        CommercePersonalOfferCrmPresentation::business_product_label(
            $DB,
            (int)$product->id
        );
}

$filters = array_filter([
    'beneficiaryquery' => $query,
    'status' => $status,
    'campaignkey' => $campaignkey,
    'targetproductid' => $productid,
    'timecreatedfrom' => $timefrom,
    'timecreatedto' => $timeto,
], static fn(mixed $value): bool => $value !== '' && $value !== 0);

$params = [
    'page' => $page,
    'perpage' => $perpage,
    'q' => $query,
    'status' => $status,
    'campaignkey' => $campaignkey,
    'productid' => $productid,
    'period' => $period,
    'datefrom' => $customfrom,
    'dateto' => $customto,
];
$params = array_filter(
    $params,
    static fn(mixed $value): bool => $value !== '' && $value !== 0
);
$pageurl = new moodle_url('/local/subscriptions/admin/commerce/personal-offers/index.php', $params);
$title = get_string('commerce_personal_offers_title', 'local_subscriptions');
CrmPageConfigurator::configure($PAGE, $context, $pageurl, $title, 'local-subscriptions-commerce-personal-offers-page');

$repository = new MoodleCommercePersonalOfferRepository($DB);
$total = $repository->count($filters);
$offers = $repository->find($filters, $perpage, $page * $perpage);
$now = time();

$campaignnames = [];
foreach ($DB->get_records(
    'local_subs_commerce_offer_campaign',
    null,
    '',
    'campaignkey,name'
) as $campaignrecord) {
    $campaignnames[(string)$campaignrecord->campaignkey] = (string)$campaignrecord->name;
}

$statusbadge = static function(CommercePersonalOffer $offer) use ($now): string {
    $effective = $offer->get_effective_status($now);
    $map = [
        CommercePersonalOffer::STATUS_ISSUED => ['commerce_personal_offer_status_issued', 'badge bg-primary'],
        CommercePersonalOffer::STATUS_REDEEMED => ['commerce_personal_offer_status_redeemed', 'badge bg-success'],
        CommercePersonalOffer::STATUS_REVOKED => ['commerce_personal_offer_status_revoked', 'badge bg-secondary'],
        CommercePersonalOffer::EFFECTIVE_EXPIRED => ['commerce_personal_offer_status_expired', 'badge bg-warning text-dark'],
    ];
    [$key, $class] = $map[$effective];
    return html_writer::span(get_string($key, 'local_subscriptions'), $class);
};

$pricinglabel = static function(CommercePersonalOffer $offer): string {
    $terms = $offer->get_terms();
    $strategy = $terms->get_pricing_strategy();
    if ($strategy === \local_subscriptions\commerce\personaloffer\domain\CommercePersonalOfferTerms::STRATEGY_PERCENTAGE_DISCOUNT) {
        return format_float(($terms->get_percentage_basispoints() ?? 0) / 100, 2) . '%';
    }
    $amounts = $terms->get_data()['pricing']['amounts'] ?? [];
    $parts = [];
    foreach ($amounts as $currency => $minor) {
        $parts[] = html_writer::div(
            s((string)$currency) . ' ' . format_float(((int)$minor) / 100, 2),
            'crm-offers-access-condition-price'
        );
    }
    return implode('', $parts);
};

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    ['label' => get_string('crm_commerce_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php')],
    ['label' => $title, 'url' => null],
]);
echo CrmPageHeader::render($title, get_string('commerce_personal_offers_description', 'local_subscriptions'), HelpContext::COMMERCE);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::OFFERS_ACCESS, $context);
echo CommerceOffersAccessNavigationRenderer::render(CommerceOffersAccessNavigationRenderer::OFFERS);

$tools = '';
if (has_capability(Capabilities::MANAGE_CRM_ADMIN_TOOLS, $context)) {
    $tools .= html_writer::link(
        new moodle_url(
            '/local/subscriptions/admin/commerce/offers-access/create.php',
            ['kind' => 'offer']
        ),
        html_writer::tag('i', '', [
            'class' => 'fa fa-plus me-1',
            'aria-hidden' => 'true',
        ]) . get_string(
            'commerce_offers_access_create_offer_action',
            'local_subscriptions'
        ),
        ['class' => 'btn btn-primary']
    );
}
echo html_writer::div($tools, 'crm-offers-access-list-actions');

$periodoptions = [
    'today' => get_string('commerce_sales_period_today', 'local_subscriptions'),
    '7' => get_string('commerce_sales_period_7', 'local_subscriptions'),
    '30' => get_string('commerce_sales_period_30', 'local_subscriptions'),
    '90' => get_string('commerce_sales_period_90', 'local_subscriptions'),
    '365' => get_string('commerce_sales_period_365', 'local_subscriptions'),
    'all' => get_string('commerce_sales_period_all', 'local_subscriptions'),
    'custom' => get_string('commerce_sales_period_custom', 'local_subscriptions'),
];

$offerperiodlabel = static function(
    string $period,
    int $datefrom,
    int $dateto
) use ($periodoptions): string {
    if ($period === 'all') {
        return get_string('commerce_result_scope_period_all', 'local_subscriptions');
    }
    if ($datefrom > 0 && $dateto > 0) {
        $dateformat = get_string('strftimedatetimeshort', 'langconfig');
        return get_string(
            'commerce_result_scope_period_range',
            'local_subscriptions',
            (object)[
                'from' => userdate($datefrom, $dateformat),
                'to' => userdate($dateto, $dateformat),
            ]
        );
    }
    return get_string(
        'commerce_result_scope_period_named',
        'local_subscriptions',
        $periodoptions[$period] ?? $period
    );
};

$filterurl = new moodle_url('/local/subscriptions/admin/commerce/personal-offers/index.php');
$filtersareactive = $query !== ''
    || $productid > 0
    || $status !== ''
    || $period !== '30'
    || $customfrom !== ''
    || $customto !== '';

$filterbody = html_writer::start_tag('form', [
    'method' => 'get',
    'action' => $filterurl->out(false),
    'class' => 'crm-sales-filter-form crm-offers-filter-form',
]);
$filterbody .= html_writer::start_div('crm-sales-filter-grid');
$filterbody .= html_writer::div(
    html_writer::label(
        get_string('search'),
        'offer-query',
        false,
        ['class' => 'form-label']
    )
    . html_writer::div(
        html_writer::empty_tag('input', [
            'id' => 'offer-query',
            'type' => 'search',
            'name' => 'q',
            'value' => $query,
            'class' => 'form-control',
            'placeholder' => get_string(
                'commerce_personal_offer_beneficiary_search_placeholder',
                'local_subscriptions'
            ),
        ]),
        'crm-sales-filter-search-control'
    ),
    'crm-sales-filter-field crm-sales-filter-search'
);
$filterbody .= html_writer::div(
    html_writer::label(
        get_string('commerce_offers_access_period', 'local_subscriptions'),
        'offer-period',
        false,
        ['class' => 'form-label']
    )
    . html_writer::select(
        $periodoptions,
        'period',
        $period,
        false,
        ['id' => 'offer-period', 'class' => 'form-select']
    ),
    'crm-sales-filter-field'
);
$filterbody .= html_writer::div(
    html_writer::label(
        get_string('commerce_offers_access_config_product', 'local_subscriptions'),
        'offer-product',
        false,
        ['class' => 'form-label']
    )
    . html_writer::select(
        $productoptions,
        'productid',
        $productid,
        false,
        ['id' => 'offer-product', 'class' => 'form-select']
    ),
    'crm-sales-filter-field'
);
$statusoptions = ['' => get_string('all')] + [
    CommercePersonalOffer::STATUS_ISSUED => get_string(
        'commerce_personal_offer_status_issued',
        'local_subscriptions'
    ),
    CommercePersonalOffer::STATUS_REDEEMED => get_string(
        'commerce_personal_offer_status_redeemed',
        'local_subscriptions'
    ),
    CommercePersonalOffer::STATUS_REVOKED => get_string(
        'commerce_personal_offer_status_revoked',
        'local_subscriptions'
    ),
];
$filterbody .= html_writer::div(
    html_writer::label(
        get_string('status'),
        'offer-status',
        false,
        ['class' => 'form-label']
    )
    . html_writer::select(
        $statusoptions,
        'status',
        $status,
        false,
        ['id' => 'offer-status', 'class' => 'form-select']
    ),
    'crm-sales-filter-field'
);
$filterbody .= html_writer::end_div();

$customstyle = $period === 'custom' ? '' : 'display:none;';
$filterbody .= html_writer::div(
    html_writer::div(
        html_writer::label(
            get_string('commerce_sales_date_from', 'local_subscriptions'),
            'offer-datefrom',
            false,
            ['class' => 'form-label']
        )
        . html_writer::empty_tag('input', [
            'id' => 'offer-datefrom',
            'name' => 'datefrom',
            'type' => 'date',
            'value' => $customfrom,
            'class' => 'form-control',
        ]),
        'crm-sales-filter-field'
    )
    . html_writer::div(
        html_writer::label(
            get_string('commerce_sales_date_to', 'local_subscriptions'),
            'offer-dateto',
            false,
            ['class' => 'form-label']
        )
        . html_writer::empty_tag('input', [
            'id' => 'offer-dateto',
            'name' => 'dateto',
            'type' => 'date',
            'value' => $customto,
            'class' => 'form-control',
        ]),
        'crm-sales-filter-field'
    ),
    'crm-sales-filter-custom-period',
    ['id' => 'offer-custom-period', 'style' => $customstyle]
);

$filterbody .= html_writer::div(
    html_writer::link(
        $filterurl,
        get_string('reset'),
        ['class' => 'btn btn-outline-secondary']
    )
    . html_writer::tag(
        'button',
        html_writer::tag('i', '', [
            'class' => 'fa fa-filter me-1',
            'aria-hidden' => 'true',
        ])
        . get_string('commerce_filters_apply', 'local_subscriptions'),
        ['type' => 'submit', 'class' => 'btn btn-primary ms-2']
    ),
    'crm-sales-filter-actions'
);
$filterbody .= html_writer::end_tag('form');

$filterpanel = html_writer::tag(
    'details',
    html_writer::tag(
        'summary',
        html_writer::div(
            html_writer::tag('i', '', [
                'class' => 'fa fa-filter',
                'aria-hidden' => 'true',
            ])
            . html_writer::tag(
                'strong',
                get_string('commerce_offers_access_search_filters', 'local_subscriptions')
            )
            . html_writer::span(
                $filtersareactive
                    ? get_string('commerce_sales_filters_active', 'local_subscriptions')
                    : get_string('commerce_sales_filters_collapsed_hint', 'local_subscriptions'),
                'crm-sales-filter-panel-status'
            ),
            'crm-sales-filter-panel-summary-copy'
        )
        . html_writer::tag('i', '', [
            'class' => 'fa fa-chevron-down crm-sales-filter-panel-chevron',
            'aria-hidden' => 'true',
        ]),
        ['class' => 'crm-sales-filter-panel-summary']
    )
    . html_writer::div(
        $filterbody,
        'crm-sales-filter-card crm-sales-filter-card-collapsible'
    ),
    [
        'class' => 'crm-sales-filter-panel crm-offers-filter-panel',
        'open' => $filtersareactive ? 'open' : null,
    ]
);
echo $filterpanel;

$PAGE->requires->js_init_code(<<<JS
(function() {
    var period = document.getElementById('offer-period');
    var custom = document.getElementById('offer-custom-period');
    if (!period || !custom) return;
    function sync() {
        custom.style.display = period.value === 'custom' ? '' : 'none';
    }
    period.addEventListener('change', sync);
    sync();
})();
JS);

$offersremoveurl = static function(
    array $params,
    string $name,
    mixed $reset = null
): moodle_url {
    unset($params['page']);

    if ($reset === null || $reset === '') {
        unset($params[$name]);
    } else {
        $params[$name] = $reset;
    }

    if ($name === 'period') {
        unset($params['datefrom'], $params['dateto']);
    }

    return new moodle_url(
        '/local/subscriptions/admin/commerce/personal-offers/index.php',
        $params
    );
};

$offersscopepill = static function(
    string $label,
    ?moodle_url $removeurl = null
): string {
    $remove = '';
    if ($removeurl !== null) {
        $remove = html_writer::link(
            $removeurl,
            html_writer::span(
                '×',
                'crm-result-scope-pill-remove-symbol'
            ),
            [
                'class' => 'crm-result-scope-pill-remove',
                'title' => get_string(
                    'commerce_result_scope_remove_filter',
                    'local_subscriptions'
                ),
                'aria-label' => get_string(
                    'commerce_result_scope_remove_filter_named',
                    'local_subscriptions',
                    $label
                ),
            ]
        );
    }

    return html_writer::span(
        html_writer::span(
            s($label),
            'crm-result-scope-pill-label'
        )
        . $remove,
        'crm-result-scope-pill'
    );
};

$scopeparams = $params;
unset($scopeparams['page']);

$scopepills = [];
$scopepills[] = $offersscopepill(
    $offerperiodlabel($period, $timefrom, $timeto),
    $period !== '30'
        ? $offersremoveurl($scopeparams, 'period', '30')
        : null
);

if ($query !== '') {
    $scopepills[] = $offersscopepill(
        get_string(
            'commerce_result_scope_search',
            'local_subscriptions',
            $query
        ),
        $offersremoveurl($scopeparams, 'q')
    );
}
if ($productid > 0 && isset($productoptions[$productid])) {
    $scopepills[] = $offersscopepill(
        get_string(
            'commerce_result_scope_product',
            'local_subscriptions',
            $productoptions[$productid]
        ),
        $offersremoveurl($scopeparams, 'productid')
    );
}
if ($status !== '') {
    $scopepills[] = $offersscopepill(
        get_string(
            'commerce_result_scope_status',
            'local_subscriptions',
            $statusoptions[$status] ?? $status
        ),
        $offersremoveurl($scopeparams, 'status')
    );
}

echo html_writer::div(
    html_writer::div(
        get_string(
            'commerce_offers_access_offers_found',
            'local_subscriptions',
            $total
        ),
        'crm-sales-table-count'
    )
    . html_writer::div(
        html_writer::span(
            get_string(
                'commerce_result_scope_label',
                'local_subscriptions'
            ),
            'crm-result-scope-label'
        )
        . implode('', $scopepills),
        'crm-result-scope-pills'
    ),
    'crm-result-summary crm-offers-result-summary'
);

if ($offers === []) {
    echo CommerceOffersAccessPolishRenderer::empty_state(
        get_string('commerce_offers_access_offers_empty_title', 'local_subscriptions'),
        get_string('commerce_offers_access_offers_empty_help', 'local_subscriptions'),
        'fa-tag',
        new moodle_url(
            '/local/subscriptions/admin/commerce/offers-access/create.php',
            ['kind' => 'offer']
        ),
        get_string('commerce_personal_offer_create_individual', 'local_subscriptions')
    );
} else {
    $table = new html_table();
    $table->attributes['class'] = 'generaltable table table-hover align-middle';
    $table->attributes['class'] =
        'generaltable table table-hover align-middle crm-offers-access-list-table';
    $table->head = [
        get_string('commerce_personal_offer_beneficiary', 'local_subscriptions'),
        get_string('commerce_personal_offer_target', 'local_subscriptions'),
        get_string('commerce_personal_offer_pricing', 'local_subscriptions'),
        get_string('commerce_personal_offer_validity', 'local_subscriptions'),
        get_string('commerce_personal_offer_status', 'local_subscriptions'),
        get_string('commerce_offers_access_created', 'local_subscriptions'),
        get_string('actions'),
    ];

    foreach ($offers as $offer) {
        $beneficiaryuser = $offer->get_beneficiary_user_id() !== null
            ? $DB->get_record('user', ['id' => $offer->get_beneficiary_user_id(), 'deleted' => 0], 'id,firstname,lastname,email', IGNORE_MISSING)
            : null;
        $sourcepurchase = $offer->get_source_purchase_id() !== null
            ? $DB->get_record('local_subscriptions_commerce_purchase', ['id' => $offer->get_source_purchase_id()], 'id,reference,timecreated,customerjson', IGNORE_MISSING)
            : null;
        $name = CommercePersonalOfferCrmPresentation::customer_name_from_user($beneficiaryuser);
        if ($name === '') { $name = CommercePersonalOfferCrmPresentation::customer_name_from_purchase($sourcepurchase); }
        $beneficiary = $name !== '' ? html_writer::tag('strong', s($name)) . html_writer::div(s($offer->get_beneficiary_email()), 'small text-muted') : s($offer->get_beneficiary_email());
        if ($offer->get_beneficiary_user_id() !== null) {
            $beneficiary = html_writer::link(
                new moodle_url(
                    '/local/subscriptions/admin/users/view.php',
                    ['id' => $offer->get_beneficiary_user_id()]
                ),
                $beneficiary,
                ['class' => 'crm-offers-access-client-link']
            );
        }
        $campaignkeyvalue = $offer->get_campaign_key();
        if ($campaignkeyvalue !== null && isset($campaignnames[$campaignkeyvalue])) {
            $beneficiary .= html_writer::div(
                html_writer::tag('i', '', [
                    'class' => 'fa fa-users me-1',
                    'aria-hidden' => 'true',
                ])
                . s($campaignnames[$campaignkeyvalue]),
                'crm-offers-access-row-context'
            );
        }
        $validity = '-';
        if ($offer->get_valid_from() !== null || $offer->get_expires_at() !== null) {
            $from = $offer->get_valid_from() === null ? '—' : userdate($offer->get_valid_from());
            $to = $offer->get_expires_at() === null ? '—' : userdate($offer->get_expires_at());
            $validity = s($from . ' → ' . $to);
        }
        $productlabel = CommercePersonalOfferCrmPresentation::business_product_label(
            $DB,
            $offer->get_target_product_id()
        );
        $table->data[] = [
            $beneficiary,
            html_writer::link(
                new moodle_url(
                    '/local/subscriptions/admin/commerce/products/view.php',
                    ['id' => $offer->get_target_product_id()]
                ),
                s($productlabel),
                ['class' => 'crm-offers-access-product-link']
            ),
            $pricinglabel($offer),
            $validity,
            $statusbadge($offer),
            userdate($offer->get_time_created(), get_string('strftimedatetimeshort', 'langconfig')),
            (function() use ($offer, $context): string {
                $viewurl = new moodle_url(
                    '/local/subscriptions/admin/commerce/personal-offers/view.php',
                    ['id' => $offer->get_id()]
                );
                $display = html_writer::link(
                    $viewurl,
                    html_writer::tag('i', '', [
                        'class' => 'fa fa-eye me-1',
                        'aria-hidden' => 'true',
                    ]) . get_string('view'),
                    ['class' => 'btn btn-sm btn-outline-primary']
                );
                if (!has_capability(Capabilities::MANAGE_CRM_ADMIN_TOOLS, $context)) {
                    return $display;
                }

                $groups = [];
                $effective = $offer->get_effective_status(time());
                $offeractions = [];
                if ($effective === CommercePersonalOffer::STATUS_ISSUED) {
                    $offeractions[] = html_writer::link(
                        new moodle_url(
                            '/local/subscriptions/admin/commerce/personal-offers/edit.php',
                            ['id' => $offer->get_id()]
                        ),
                        get_string('commerce_personal_offer_edit', 'local_subscriptions'),
                        ['class' => 'crm-sales-row-menu-link']
                    );
                    $offeractions[] = html_writer::link(
                        new moodle_url(
                            '/local/subscriptions/admin/commerce/personal-offers/view.php',
                            ['id' => $offer->get_id(), 'focus' => 'revoke']
                        ),
                        get_string('commerce_personal_offer_revoke', 'local_subscriptions'),
                        ['class' => 'crm-sales-row-menu-link']
                    );
                }
                if ($offeractions !== []) {
                    $groups[] = html_writer::div(
                        html_writer::div(
                            get_string('commerce_offers_access_menu_offer', 'local_subscriptions'),
                            'crm-sales-row-menu-section'
                        )
                        . implode('', $offeractions),
                        'crm-offers-access-row-menu-group'
                    );
                }

                $communication = html_writer::link(
                    new moodle_url(
                        '/local/subscriptions/admin/commerce/mail/index.php',
                        [
                            'mailtype' => 'personal_offer',
                            'q' => $offer->get_beneficiary_email(),
                        ]
                    ),
                    get_string('commerce_offers_access_offer_mail_journal', 'local_subscriptions'),
                    ['class' => 'crm-sales-row-menu-link']
                );
                $groups[] = html_writer::div(
                    html_writer::div(
                        get_string('commerce_offers_access_menu_communication', 'local_subscriptions'),
                        'crm-sales-row-menu-section'
                    )
                    . $communication,
                    'crm-offers-access-row-menu-group'
                );

                $menu = html_writer::tag(
                    'details',
                    html_writer::tag(
                        'summary',
                        html_writer::tag('i', '', [
                            'class' => 'fa fa-ellipsis-h',
                            'aria-hidden' => 'true',
                        ]),
                        [
                            'class' => 'btn btn-sm btn-outline-secondary crm-sales-row-menu-toggle',
                            'aria-label' => get_string('actions'),
                            'title' => get_string('actions'),
                        ]
                    )
                    . html_writer::div(
                        implode('', $groups),
                        'crm-sales-row-menu'
                    ),
                    ['class' => 'crm-sales-row-actions-menu']
                );

                return html_writer::div(
                    $display . $menu,
                    'crm-sales-actions'
                );
            })(),
        ];
    }
    echo html_writer::table($table);
    if ($total > $perpage) {
        echo $OUTPUT->paging_bar($total, $page, $perpage, $filterurl, 'page');
    }
}

$PAGE->requires->js_init_code(<<<JS
(function() {
    var menus = Array.prototype.slice.call(
        document.querySelectorAll('.crm-offers-access-row-actions')
    );
    if (!menus.length) {
        return;
    }

    menus.forEach(function(menu) {
        menu.addEventListener('toggle', function() {
            if (!menu.open) {
                return;
            }
            menus.forEach(function(other) {
                if (other !== menu) {
                    other.open = false;
                }
            });
        });
    });

    document.addEventListener('click', function(event) {
        menus.forEach(function(menu) {
            if (menu.open && !menu.contains(event.target)) {
                menu.open = false;
            }
        });
    });
})();
JS);

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
