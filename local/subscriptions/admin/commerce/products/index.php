<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\catalog\navigation\CommerceCatalogLinkGenerator;
use local_subscriptions\commerce\catalog\presentation\CommerceCatalogPresentation;
use local_subscriptions\commerce\catalog\presentation\CommerceCatalogProductNameResolver;
use local_subscriptions\commerce\catalog\readmodel\CommerceCatalogReadRepository;
use local_subscriptions\commerce\catalog\validation\CommerceCatalogValidator;
use local_subscriptions\commerce\catalog\validation\CommerceCatalogActivationValidator;
use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;
use local_subscriptions\crm\commerce\presentation\CommerceDesignSystemRenderer;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\subscription_config;

$context = AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);

$query = trim(optional_param('q', '', PARAM_RAW_TRIMMED));
$type = trim(optional_param('type', '', PARAM_ALPHANUMEXT));
$currency = strtoupper(trim(optional_param('currency', '', PARAM_ALPHA)));
$origin = trim(optional_param('origin', '', PARAM_ALPHANUMEXT));
$status = trim(optional_param('status', '', PARAM_ALPHANUMEXT));
$validationfilter = trim(optional_param('validation', '', PARAM_ALPHANUMEXT));
$sort = trim(optional_param('sort', 'name', PARAM_ALPHA));
$direction = strtolower(trim(optional_param('dir', 'asc', PARAM_ALPHA)));
$page = max(0, optional_param('page', 0, PARAM_INT));
$perpage = optional_param('perpage', 25, PARAM_INT);

$allowedstatus = ['all', 'active', 'inactive', 'archived'];
if (!in_array($status, $allowedstatus, true)) {
    $status = 'active';
}
$allowedvalidation = ['', 'valid', 'incomplete'];
if (!in_array($validationfilter, $allowedvalidation, true)) {
    $validationfilter = '';
}
$allowedorigin = ['', 'native', 'legacy'];
if (!in_array($origin, $allowedorigin, true)) {
    $origin = '';
}
$allowedsorts = ['name', 'type', 'status', 'price'];
if (!in_array($sort, $allowedsorts, true)) {
    $sort = 'name';
}
$direction = $direction === 'desc' ? 'desc' : 'asc';
$perpage = in_array($perpage, [25, 50, 100], true) ? $perpage : 25;

$pageurl = new moodle_url('/local/subscriptions/admin/commerce/products/index.php');
$pagetitle = get_string('commerce_catalog_title', 'local_subscriptions');
CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $pagetitle,
    'local-subscriptions-commerce-products-page'
);

$repository = new CommerceCatalogReadRepository($DB);
$allproducts = $repository->find_all();
$productmanager = (new CommerceCatalogFactory($DB))->product_manager();
$activationvalidator = new CommerceCatalogActivationValidator($DB);

$collect = static function(array $products, callable $getter): array {
    $values = [];
    foreach ($products as $product) {
        $value = trim((string)$getter($product));
        if ($value !== '') {
            $values[$value] = true;
        }
    }
    ksort($values);
    return array_keys($values);
};
$options = static fn(string $dimension, array $values): array =>
    ['' => get_string('all')]
    + array_combine(
        $values,
        array_map(
            static fn(string $value): string =>
                CommerceCatalogPresentation::label($dimension, $value),
            $values
        )
    );

$typeoptions = $options(
    'type',
    $collect($allproducts, static fn($product): string => $product->get_type())
);

$currencies = [];
foreach ($allproducts as $product) {
    foreach ($product->get_prices() as $price) {
        $currencies[$price->get_currency()] = $price->get_currency();
    }
}
ksort($currencies);
$currencyoptions = ['' => get_string('all')] + $currencies;

$originoptions = [
    '' => get_string('all'),
    'native' => get_string('commerce_catalog_origin_native', 'local_subscriptions'),
    'legacy' => get_string('commerce_catalog_origin_legacy_only', 'local_subscriptions'),
];
$statusoptions = [
    'all' => get_string('all'),
    'active' => get_string('commerce_product_filter_status_active', 'local_subscriptions'),
    'inactive' => get_string('commerce_product_filter_status_inactive', 'local_subscriptions'),
    'archived' => get_string('commerce_product_filter_status_archived', 'local_subscriptions'),
];
$validationoptions = [
    '' => get_string('all'),
    'valid' => get_string('commerce_product_filter_validation_valid', 'local_subscriptions'),
    'incomplete' => get_string('commerce_product_filter_validation_incomplete', 'local_subscriptions'),
];

/**
 * Build the normalized row model once so filters, KPIs and sorting all use
 * the same business interpretation.
 *
 * @var array<int,array<string,mixed>> $rows
 */
$rows = [];
foreach ($allproducts as $product) {
    $validation = $product->get_origin() === 'native'
        ? $activationvalidator->validate(
            $productmanager
                ->get_editor_data($product->get_sku())
                ->get_product()
        )
        : (new CommerceCatalogValidator())->validate($product);

    $lifecyclestatus = $product->get_editorial_status() === 'archived'
        ? 'archived'
        : (
            $product->get_availability() === 'on_sale'
                ? 'active'
                : 'inactive'
        );
    $originfamily = $product->get_origin() === 'native'
        ? 'native'
        : 'legacy';

    $prices = $product->get_prices();
    $pricevalues = [];
    foreach ($prices as $price) {
        $pricevalues[] = (float)$price->get_amount_minor();
    }

    $rows[] = [
        'product' => $product,
        'displayname' => CommerceCatalogProductNameResolver::resolve(
            $DB,
            $product
        ),
        'validation' => $validation,
        'status' => $lifecyclestatus,
        'origin' => $originfamily,
        'price' => $pricevalues === [] ? PHP_FLOAT_MAX : min($pricevalues),
    ];
}

$kpi = [
    'total' => count($rows),
    'active' => 0,
    'inactive' => 0,
    'archived' => 0,
    'incomplete' => 0,
];
foreach ($rows as $row) {
    if (isset($kpi[$row['status']])) {
        $kpi[$row['status']]++;
    }
    if (!$row['validation']->is_valid()) {
        $kpi['incomplete']++;
    }
}

$filteredrows = array_values(array_filter(
    $rows,
    static function(array $row) use (
        $query,
        $type,
        $currency,
        $origin,
        $status,
        $validationfilter
    ): bool {
        $product = $row['product'];

        if ($query !== '') {
            $haystack = core_text::strtolower(
                trim(
                    (string)$row['displayname']
                    . ' '
                    . (string)$product->get_sku()
                    . ' '
                    . (string)$product->get_type()
                )
            );
            if (!str_contains($haystack, core_text::strtolower($query))) {
                return false;
            }
        }

        if ($type !== '' && $product->get_type() !== $type) {
            return false;
        }
        if ($origin !== '' && $row['origin'] !== $origin) {
            return false;
        }
        if ($status !== 'all' && $row['status'] !== $status) {
            return false;
        }
        if (
            $validationfilter === 'valid'
            && !$row['validation']->is_valid()
        ) {
            return false;
        }
        if (
            $validationfilter === 'incomplete'
            && $row['validation']->is_valid()
        ) {
            return false;
        }
        if ($currency !== '') {
            $hascurrency = false;
            foreach ($product->get_prices() as $price) {
                if ($price->get_currency() === $currency) {
                    $hascurrency = true;
                    break;
                }
            }
            if (!$hascurrency) {
                return false;
            }
        }

        return true;
    }
));

usort(
    $filteredrows,
    static function(array $a, array $b) use ($sort, $direction): int {
        $aproduct = $a['product'];
        $bproduct = $b['product'];

        $comparison = match ($sort) {
            'type' => strnatcasecmp(
                (string)$aproduct->get_type(),
                (string)$bproduct->get_type()
            ),
            'status' => strnatcasecmp(
                (string)$a['status'],
                (string)$b['status']
            ),
            'price' => $a['price'] <=> $b['price'],
            default => strnatcasecmp(
                (string)$a['displayname'],
                (string)$b['displayname']
            ),
        };

        return $direction === 'desc' ? -$comparison : $comparison;
    }
);

$total = count($filteredrows);
$maxpage = max(0, (int)ceil($total / $perpage) - 1);
$page = min($page, $maxpage);
$pagedrows = array_slice($filteredrows, $page * $perpage, $perpage);

$params = array_filter([
    'q' => $query,
    'type' => $type,
    'currency' => $currency,
    'origin' => $origin,
    'status' => $status,
    'validation' => $validationfilter,
    'sort' => $sort,
    'dir' => $direction,
    'perpage' => $perpage,
], static fn(mixed $value): bool => $value !== '');

$sortlink = static function(
    string $key,
    string $label
) use (
    $params,
    $sort,
    $direction
): string {
    $nextdirection = $sort === $key && $direction === 'asc'
        ? 'desc'
        : 'asc';
    $sortparams = $params;
    $sortparams['sort'] = $key;
    $sortparams['dir'] = $nextdirection;
    $sortparams['page'] = 0;

    $icon = $sort !== $key
        ? 'fa-sort'
        : ($direction === 'asc' ? 'fa-sort-asc' : 'fa-sort-desc');

    return html_writer::link(
        new moodle_url(
            '/local/subscriptions/admin/commerce/products/index.php',
            $sortparams
        ),
        s($label)
        . html_writer::tag('i', '', [
            'class' => 'fa ' . $icon . ' ms-1',
            'aria-hidden' => 'true',
        ]),
        [
            'class' => 'crm-product-sort-link'
                . ($sort === $key ? ' is-active' : ''),
        ]
    );
};

$filtersareactive = $query !== ''
    || $type !== ''
    || $currency !== ''
    || $origin !== ''
    || $status !== 'active'
    || $validationfilter !== '';

$filterhtml = html_writer::start_tag('form', [
    'method' => 'get',
    'action' => $pageurl->out(false),
    'class' => 'crm-products-filter-form',
]);
$filterhtml .= html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'sort',
    'value' => $sort,
]);
$filterhtml .= html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'dir',
    'value' => $direction,
]);
$filterhtml .= html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'perpage',
    'value' => $perpage,
]);

$filterhtml .= html_writer::start_div('crm-products-filter-grid');
$filterhtml .= html_writer::div(
    html_writer::label(
        get_string('search'),
        'product-filter-q',
        false,
        ['class' => 'form-label']
    )
    . html_writer::empty_tag('input', [
        'id' => 'product-filter-q',
        'name' => 'q',
        'type' => 'search',
        'value' => $query,
        'class' => 'form-control',
        'placeholder' => get_string(
            'commerce_product_search_placeholder',
            'local_subscriptions'
        ),
    ]),
    'crm-products-filter-field is-search'
);

$selectfield = static function(
    string $name,
    string $label,
    array $options,
    string $selected
): string {
    return html_writer::div(
        html_writer::label(
            $label,
            'product-filter-' . $name,
            false,
            ['class' => 'form-label']
        )
        . html_writer::select(
            $options,
            $name,
            $selected,
            false,
            [
                'id' => 'product-filter-' . $name,
                'class' => 'form-select',
            ]
        ),
        'crm-products-filter-field'
    );
};

$filterhtml .= $selectfield(
    'type',
    get_string('commerce_product_type', 'local_subscriptions'),
    $typeoptions,
    $type
);
$filterhtml .= $selectfield(
    'status',
    get_string('commerce_product_status', 'local_subscriptions'),
    $statusoptions,
    $status
);
$filterhtml .= $selectfield(
    'validation',
    get_string('commerce_product_validation_filter', 'local_subscriptions'),
    $validationoptions,
    $validationfilter
);
$filterhtml .= $selectfield(
    'origin',
    get_string('commerce_catalog_origin', 'local_subscriptions'),
    $originoptions,
    $origin
);
$filterhtml .= $selectfield(
    'currency',
    get_string('currency'),
    $currencyoptions,
    $currency
);
$filterhtml .= html_writer::div(
    html_writer::link(
        $pageurl,
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
        [
            'type' => 'submit',
            'class' => 'btn crm-product-action-primary ms-2',
        ]
    ),
    'crm-products-filter-actions'
);
$filterhtml .= html_writer::end_div();
$filterhtml .= html_writer::end_tag('form');

$removeurl = static function(
    string $name
) use ($params): moodle_url {
    $next = $params;
    unset($next[$name], $next['page']);
    return new moodle_url(
        '/local/subscriptions/admin/commerce/products/index.php',
        $next
    );
};
$scopepill = static function(
    string $label,
    moodle_url $remove
): string {
    return html_writer::span(
        html_writer::span(s($label), 'crm-result-scope-pill-label')
        . html_writer::link(
            $remove,
            html_writer::span(
                '×',
                'crm-result-scope-pill-remove-symbol'
            ),
            [
                'class' => 'crm-result-scope-pill-remove',
                'aria-label' => get_string(
                    'commerce_result_scope_remove_filter_named',
                    'local_subscriptions',
                    $label
                ),
            ]
        ),
        'crm-result-scope-pill'
    );
};

$scopepills = [];
if ($query !== '') {
    $scopepills[] = $scopepill(
        get_string(
            'commerce_result_scope_search',
            'local_subscriptions',
            $query
        ),
        $removeurl('q')
    );
}
if ($type !== '') {
    $scopepills[] = $scopepill(
        get_string(
            'commerce_product_scope_type',
            'local_subscriptions',
            $typeoptions[$type] ?? $type
        ),
        $removeurl('type')
    );
}
if ($status !== 'all') {
    $scopepills[] = $scopepill(
        get_string(
            'commerce_result_scope_status',
            'local_subscriptions',
            $statusoptions[$status] ?? $status
        ),
        $removeurl('status')
    );
}
if ($validationfilter !== '') {
    $scopepills[] = $scopepill(
        get_string(
            'commerce_product_scope_validation',
            'local_subscriptions',
            $validationoptions[$validationfilter]
        ),
        $removeurl('validation')
    );
}
if ($origin !== '') {
    $scopepills[] = $scopepill(
        get_string(
            'commerce_product_scope_origin',
            'local_subscriptions',
            $originoptions[$origin]
        ),
        $removeurl('origin')
    );
}
if ($currency !== '') {
    $scopepills[] = $scopepill(
        get_string(
            'commerce_product_scope_currency',
            'local_subscriptions',
            $currency
        ),
        $removeurl('currency')
    );
}

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    [
        'label' => get_string('crm_commerce_title', 'local_subscriptions'),
        'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php'),
    ],
    ['label' => $pagetitle, 'url' => null],
]);
echo CrmPageHeader::render(
    $pagetitle,
    get_string('commerce_catalog_description', 'local_subscriptions'),
    HelpContext::COMMERCE
);
echo CommerceSectionNavigationRenderer::render(
    CommerceSectionNavigationRenderer::PRODUCTS
);

echo html_writer::tag(
    'details',
    html_writer::tag(
        'summary',
        html_writer::div(
            html_writer::div(
                html_writer::tag('i', '', [
                    'class' => 'fa-solid fa-graduation-cap',
                    'aria-hidden' => 'true',
                ]),
                'crm-products-course-journey__icon'
            )
            . html_writer::div(
                html_writer::tag(
                    'h2',
                    get_string('commerce_product_course_journey_title', 'local_subscriptions'),
                    ['class' => 'h5 mb-1']
                )
                . html_writer::tag(
                    'p',
                    get_string('commerce_product_course_journey_description', 'local_subscriptions'),
                    ['class' => 'text-muted mb-0']
                ),
                'flex-grow-1'
            ),
            'd-flex align-items-start gap-3 flex-grow-1'
        )
        . html_writer::tag('i', '', [
            'class' => 'fa fa-chevron-down crm-products-course-journey__chevron',
            'aria-hidden' => 'true',
        ]),
        ['class' => 'crm-products-course-journey__summary']
    )
    . html_writer::div(
        html_writer::div(
            html_writer::div(
                html_writer::span('1', 'crm-products-course-step__number')
                . html_writer::span(
                    get_string('commerce_configuration_catalogue_step_course', 'local_subscriptions'),
                    'crm-products-course-step__label'
                ),
                'crm-products-course-step'
            )
            . html_writer::span('→', 'crm-products-course-step__arrow')
            . html_writer::div(
                html_writer::span('2', 'crm-products-course-step__number')
                . html_writer::span(
                    get_string('commerce_configuration_catalogue_step_scope', 'local_subscriptions'),
                    'crm-products-course-step__label'
                ),
                'crm-products-course-step'
            )
            . html_writer::span('→', 'crm-products-course-step__arrow')
            . html_writer::div(
                html_writer::span('3', 'crm-products-course-step__number')
                . html_writer::span(
                    get_string('commerce_configuration_catalogue_step_plan', 'local_subscriptions'),
                    'crm-products-course-step__label'
                ),
                'crm-products-course-step'
            )
            . html_writer::span('→', 'crm-products-course-step__arrow')
            . html_writer::div(
                html_writer::span('4', 'crm-products-course-step__number')
                . html_writer::span(
                    get_string('commerce_configuration_catalogue_step_product', 'local_subscriptions'),
                    'crm-products-course-step__label'
                ),
                'crm-products-course-step'
            ),
            'crm-products-course-journey__steps'
        )
        . html_writer::div(
            html_writer::link(
                new moodle_url(subscription_config::commerce_access_scopes_page()),
                html_writer::tag('i', '', [
                    'class' => 'fa-solid fa-key me-1',
                    'aria-hidden' => 'true',
                ])
                . get_string('commerce_configuration_open_scopes', 'local_subscriptions'),
                ['class' => 'btn btn-outline-primary']
            )
            . html_writer::link(
                new moodle_url(subscription_config::commerce_plans_page()),
                html_writer::tag('i', '', [
                    'class' => 'fa-solid fa-puzzle-piece me-1',
                    'aria-hidden' => 'true',
                ])
                . get_string('commerce_configuration_open_plans', 'local_subscriptions'),
                ['class' => 'btn btn-outline-primary']
            ),
            'd-flex flex-wrap gap-2 mt-3'
        ),
        'crm-products-course-journey__body'
    ),
    ['class' => 'card mb-3 crm-products-course-journey']
);

echo html_writer::div(
    html_writer::link(
        new moodle_url(
            '/local/subscriptions/admin/commerce/products/edit.php'
        ),
        html_writer::tag('i', '', [
            'class' => 'fa fa-plus me-1',
            'aria-hidden' => 'true',
        ])
        . get_string('commerce_product_add', 'local_subscriptions'),
        ['class' => 'btn crm-product-action-primary']
    ),
    'crm-products-top-actions'
);

echo CommerceDesignSystemRenderer::metrics([
    [
        'label' => get_string('commerce_product_kpi_total', 'local_subscriptions'),
        'value' => $kpi['total'],
    ],
    [
        'label' => get_string('commerce_product_kpi_active', 'local_subscriptions'),
        'value' => $kpi['active'],
    ],
    [
        'label' => get_string('commerce_product_kpi_inactive', 'local_subscriptions'),
        'value' => $kpi['inactive'],
    ],
    [
        'label' => get_string('commerce_product_kpi_archived', 'local_subscriptions'),
        'value' => $kpi['archived'],
    ],
    [
        'label' => get_string('commerce_product_kpi_incomplete', 'local_subscriptions'),
        'value' => $kpi['incomplete'],
    ],
]);

echo html_writer::tag(
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
                get_string(
                    'commerce_offers_access_search_filters',
                    'local_subscriptions'
                )
            )
            . html_writer::span(
                $filtersareactive
                    ? get_string(
                        'commerce_sales_filters_active',
                        'local_subscriptions'
                    )
                    : get_string(
                        'commerce_sales_filters_collapsed_hint',
                        'local_subscriptions'
                    ),
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
        $filterhtml,
        'crm-sales-filter-card crm-sales-filter-card-collapsible'
    ),
    [
        'class' => 'crm-sales-filter-panel crm-products-filter-panel',
        'open' => $filtersareactive ? 'open' : null,
    ]
);

echo html_writer::div(
    html_writer::div(
        get_string(
            'commerce_products_found',
            'local_subscriptions',
            $total
        ),
        'crm-sales-table-count'
    )
    . (
        $scopepills === []
            ? ''
            : html_writer::div(
                html_writer::span(
                    get_string(
                        'commerce_result_scope_label',
                        'local_subscriptions'
                    ),
                    'crm-result-scope-label'
                )
                . implode('', $scopepills),
                'crm-result-scope-pills'
            )
    ),
    'crm-result-summary crm-products-result-summary'
);

if ($pagedrows === []) {
    echo CommerceDesignSystemRenderer::empty_state(
        get_string('commerce_products_empty_title', 'local_subscriptions'),
        get_string('commerce_products_empty', 'local_subscriptions')
    );
} else {
    $table = new html_table();
    $table->attributes['class'] =
        'generaltable table table-hover align-middle crm-products-table';
    $table->attributes['aria-label'] = get_string(
        'commerce_catalog_table_label',
        'local_subscriptions'
    );
    $table->head = [
        $sortlink(
            'name',
            get_string('commerce_product_name', 'local_subscriptions')
        ),
        $sortlink(
            'type',
            get_string('commerce_product_type', 'local_subscriptions')
        ),
        $sortlink(
            'status',
            get_string('commerce_product_status', 'local_subscriptions')
        ),
        get_string(
            'commerce_product_validation_filter',
            'local_subscriptions'
        ),
        $sortlink(
            'price',
            get_string('commerce_prices', 'local_subscriptions')
        ),
        get_string('actions'),
    ];

    foreach ($pagedrows as $row) {
        $product = $row['product'];
        $validation = $row['validation'];
        $lifecyclestatus = $row['status'];
        $viewurl = CommerceCatalogLinkGenerator::view_url($product);

        $originlabel = $row['origin'] === 'native'
            ? get_string(
                'commerce_catalog_origin_native_short',
                'local_subscriptions'
            )
            : get_string(
                'commerce_catalog_origin_legacy_short',
                'local_subscriptions'
            );

        $name = html_writer::link(
            $viewurl,
            format_string((string)$row['displayname']),
            ['class' => 'crm-product-name-link']
        )
        . html_writer::div(
            s($originlabel),
            'crm-product-origin'
        );

        $statusbadge = CommerceCatalogPresentation::badge(
            'lifecycle',
            $lifecyclestatus
        );
        $validationbadge = CommerceCatalogPresentation::badge(
            'technical',
            $validation->is_valid() ? 'valid' : 'incomplete'
        );

        $displaybutton = html_writer::link(
            $viewurl,
            html_writer::tag('i', '', [
                'class' => 'fa fa-eye me-1',
                'aria-hidden' => 'true',
            ])
            . get_string('view'),
            ['class' => 'btn btn-sm crm-product-action-outline']
        );

        $groups = [];
        $productactions = '';
        if ($product->get_origin() === 'native') {
            $productactions .= html_writer::link(
                new moodle_url(
                    '/local/subscriptions/admin/commerce/products/edit.php',
                    ['sku' => $product->get_sku()]
                ),
                html_writer::tag('i', '', [
                    'class' => 'fa fa-pencil me-2',
                    'aria-hidden' => 'true',
                ])
                . get_string('edit'),
                ['class' => 'crm-sales-row-menu-link']
            );
        }

        $statusaction = $lifecyclestatus === 'active'
            ? 'deactivate'
            : 'activate';
        if ($lifecyclestatus !== 'archived') {
            if ($product->get_origin() === 'native') {
                $statusurl = new moodle_url(
                    '/local/subscriptions/admin/commerce/products/status.php',
                    [
                        'sku' => $product->get_sku(),
                        'action' => $statusaction,
                        'sesskey' => sesskey(),
                    ]
                );
            } else {
                $statusurl = new moodle_url(
                    '/local/subscriptions/admin/commerce/products/legacy_status.php',
                    [
                        'origin' => $product->get_origin(),
                        'id' => (int)$product->get_id(),
                        'action' => $statusaction,
                        'return' => 'index',
                        'sesskey' => sesskey(),
                    ]
                );
            }

            $productactions .= html_writer::link(
                $statusurl,
                html_writer::tag('i', '', [
                    'class' => $statusaction === 'activate'
                        ? 'fa fa-toggle-on me-2'
                        : 'fa fa-toggle-off me-2',
                    'aria-hidden' => 'true',
                ])
                . get_string(
                    $statusaction === 'activate'
                        ? 'commerce_product_activate'
                        : 'commerce_product_deactivate',
                    'local_subscriptions'
                ),
                ['class' => 'crm-sales-row-menu-link']
            );
        }

        if ($productactions !== '') {
            $groups[] = html_writer::div(
                html_writer::div(
                    get_string(
                        'commerce_product_menu_product',
                        'local_subscriptions'
                    ),
                    'crm-sales-row-menu-section'
                )
                . $productactions,
                'crm-sales-row-menu-group'
            );
        }

        $contextactions = html_writer::link(
            new moodle_url(
                '/local/subscriptions/admin/commerce/purchases/index.php',
                ['q' => (string)$row['displayname']]
            ),
            html_writer::tag('i', '', [
                'class' => 'fa fa-shopping-cart me-2',
                'aria-hidden' => 'true',
            ])
            . get_string(
                'commerce_product_menu_sales',
                'local_subscriptions'
            ),
            ['class' => 'crm-sales-row-menu-link']
        );
        $groups[] = html_writer::div(
            html_writer::div(
                get_string(
                    'commerce_product_menu_commerce',
                    'local_subscriptions'
                ),
                'crm-sales-row-menu-section'
            )
            . $contextactions,
            'crm-sales-row-menu-group'
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
                    'class' =>
                        'btn btn-sm btn-outline-secondary '
                        . 'crm-sales-row-menu-toggle',
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

        $table->data[] = [
            $name,
            CommerceCatalogPresentation::badge(
                'type',
                $product->get_type()
            ),
            $statusbadge,
            $validationbadge,
            (function() use ($product): string {
                $prices = [];
                foreach ($product->get_prices() as $price) {
                    $currency = strtoupper($price->get_currency());
                    $flag = match ($currency) {
                        'EUR' => '🇪🇺',
                        'RUB' => '🇷🇺',
                        default => '🌐',
                    };
                    $prices[] = html_writer::div(
                        html_writer::span(
                            $flag,
                            'crm-product-price-flag',
                            [
                                'role' => 'img',
                                'aria-label' => $currency,
                                'title' => $currency,
                            ]
                        )
                        . html_writer::span(
                            CommerceCatalogPresentation::prices([$price], $product->get_metadata()),
                            'crm-product-price-value'
                        ),
                        'crm-product-price-line'
                    );
                }
                return implode('', $prices);
            })(),
            html_writer::div(
                $displaybutton . $menu,
                'crm-sales-actions crm-product-actions'
            ),
        ];
    }

    echo html_writer::table($table);

    $paginationparams = $params;
    unset($paginationparams['page']);
    $paginationurl = new moodle_url(
        '/local/subscriptions/admin/commerce/products/index.php',
        $paginationparams
    );

    echo html_writer::div(
        html_writer::div(
            get_string(
                'commerce_product_pagination_count',
                'local_subscriptions',
                (object)[
                    'visible' => count($pagedrows),
                    'total' => $total,
                ]
            ),
            'crm-campaign-member-count'
        )
        . html_writer::div(
            get_string(
                'commerce_campaign_member_per_page',
                'local_subscriptions'
            )
            . ' '
            . html_writer::select(
                [25 => '25', 50 => '50', 100 => '100'],
                'perpage',
                $perpage,
                false,
                [
                    'class' =>
                        'form-select form-select-sm '
                        . 'crm-product-perpage-select',
                    'onchange' =>
                        'var u=new URL(window.location.href);'
                        . 'u.searchParams.set("perpage",this.value);'
                        . 'u.searchParams.set("page","0");'
                        . 'window.location.href=u.toString();',
                ]
            ),
            'crm-campaign-member-perpage'
        ),
        'crm-campaign-member-pagination-meta'
    );

    if ($total > $perpage) {
        echo $OUTPUT->paging_bar(
            $total,
            $page,
            $perpage,
            $paginationurl,
            'page'
        );
    }
}

$PAGE->requires->js_init_code(<<<JS
(function() {
    var menus = Array.prototype.slice.call(
        document.querySelectorAll(
            '.local-subscriptions-commerce-products-page '
            + '.crm-sales-row-actions-menu'
        )
    );
    menus.forEach(function(menu) {
        menu.addEventListener('toggle', function() {
            if (!menu.open) return;
            menus.forEach(function(other) {
                if (other !== menu) other.open = false;
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
