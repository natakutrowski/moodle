<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomStatus;
use local_subscriptions\crm\commerce\presentation\CommerceDesignSystemRenderer;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

require_login();
require_capability(
    'local/subscriptions:manage_showrooms',
    context_system::instance()
);

$context = context_system::instance();
$pageurl = new moodle_url(
    '/local/subscriptions/admin/commerce/showrooms/index.php'
);
$pagetitle = get_string(
    'commerce_showroom_cms_title',
    'local_subscriptions'
);

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $pagetitle,
    'local-subscriptions-showroom-list-page'
);

$repository = new CommerceShowroomCmsRepository($DB);

$delete = optional_param('delete', 0, PARAM_INT);
if ($delete > 0 && confirm_sesskey()) {
    $repository->delete($delete);
    redirect(
        $PAGE->url,
        get_string('changessaved'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

$query = trim(optional_param('q', '', PARAM_RAW_TRIMMED));
$status = trim(optional_param('status', 'all', PARAM_ALPHANUMEXT));
$language = trim(optional_param('language', 'all', PARAM_ALPHANUMEXT));
$sort = trim(optional_param('sort', 'name', PARAM_ALPHA));
$direction = strtolower(
    trim(optional_param('dir', 'asc', PARAM_ALPHA))
);
$page = max(0, optional_param('page', 0, PARAM_INT));
$perpage = optional_param('perpage', 25, PARAM_INT);

$allowedstatuses = array_merge(
    ['all'],
    array_keys(CommerceShowroomStatus::options())
);
if (!in_array($status, $allowedstatuses, true)) {
    $status = 'all';
}

$allowedlanguages = ['all', 'fr', 'en', 'ru'];
if (!in_array($language, $allowedlanguages, true)) {
    $language = 'all';
}

$allowedsorts = ['name', 'status', 'languages', 'modified'];
if (!in_array($sort, $allowedsorts, true)) {
    $sort = 'name';
}
if (!in_array($direction, ['asc', 'desc'], true)) {
    $direction = 'asc';
}
if (!in_array($perpage, [25, 50, 100], true)) {
    $perpage = 25;
}

$showrooms = $repository->all();
$rows = [];
$kpi = [
    'total' => count($showrooms),
    CommerceShowroomStatus::PUBLISHED => 0,
    CommerceShowroomStatus::REVIEW => 0,
    CommerceShowroomStatus::DRAFT => 0,
    CommerceShowroomStatus::ARCHIVED => 0,
];

foreach ($showrooms as $showroom) {
    $showroomstatus = (string)$showroom->status;
    if (isset($kpi[$showroomstatus])) {
        $kpi[$showroomstatus]++;
    }

    $slugs = [];
    foreach ([
        'fr' => (string)$showroom->slugfr,
        'en' => (string)$showroom->slugen,
        'ru' => (string)$showroom->slugru,
    ] as $lang => $slug) {
        $slug = trim($slug);
        if ($slug !== '') {
            $slugs[$lang] = $slug;
        }
    }

    $rows[] = [
        'showroom' => $showroom,
        'slugs' => $slugs,
        'languagecount' => count($slugs),
    ];
}

$filteredrows = array_values(array_filter(
    $rows,
    static function(array $row) use (
        $query,
        $status,
        $language
    ): bool {
        $showroom = $row['showroom'];

        if ($query !== '') {
            $haystack = core_text::strtolower(
                trim(
                    (string)$showroom->name
                    . ' '
                    . (string)$showroom->showroomkey
                    . ' '
                    . implode(' ', $row['slugs'])
                )
            );
            if (
                !str_contains(
                    $haystack,
                    core_text::strtolower($query)
                )
            ) {
                return false;
            }
        }

        if (
            $status !== 'all'
            && (string)$showroom->status !== $status
        ) {
            return false;
        }

        if (
            $language !== 'all'
            && !isset($row['slugs'][$language])
        ) {
            return false;
        }

        return true;
    }
));

usort(
    $filteredrows,
    static function(array $a, array $b) use (
        $sort,
        $direction
    ): int {
        $ashowroom = $a['showroom'];
        $bshowroom = $b['showroom'];

        $comparison = match ($sort) {
            'status' => strnatcasecmp(
                (string)$ashowroom->status,
                (string)$bshowroom->status
            ),
            'languages' =>
                $a['languagecount'] <=> $b['languagecount'],
            'modified' =>
                (int)$ashowroom->timemodified
                    <=> (int)$bshowroom->timemodified,
            default => strnatcasecmp(
                (string)$ashowroom->name,
                (string)$bshowroom->name
            ),
        };

        return $direction === 'desc'
            ? -$comparison
            : $comparison;
    }
);

$total = count($filteredrows);
$maxpage = max(0, (int)ceil($total / $perpage) - 1);
$page = min($page, $maxpage);
$pagedrows = array_slice(
    $filteredrows,
    $page * $perpage,
    $perpage
);

$params = array_filter([
    'q' => $query,
    'status' => $status,
    'language' => $language,
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
    $nextdirection =
        $sort === $key && $direction === 'asc'
            ? 'desc'
            : 'asc';
    $sortparams = $params;
    $sortparams['sort'] = $key;
    $sortparams['dir'] = $nextdirection;
    $sortparams['page'] = 0;

    $icon = $sort !== $key
        ? 'fa-sort'
        : (
            $direction === 'asc'
                ? 'fa-sort-asc'
                : 'fa-sort-desc'
        );

    return html_writer::link(
        new moodle_url(
            '/local/subscriptions/admin/commerce/showrooms/index.php',
            $sortparams
        ),
        s($label)
        . html_writer::tag('i', '', [
            'class' => 'fa ' . $icon . ' ms-1',
            'aria-hidden' => 'true',
        ]),
        [
            'class' => 'crm-showroom-sort-link'
                . ($sort === $key ? ' is-active' : ''),
        ]
    );
};

$statusoptions = [
    'all' => get_string('all'),
] + CommerceShowroomStatus::options();

$languageoptions = [
    'all' => get_string('all'),
    'fr' => '🇫🇷 Français',
    'en' => '🇬🇧 English',
    'ru' => '🇷🇺 Русский',
];

$filtersareactive = $query !== ''
    || $status !== 'all'
    || $language !== 'all';

$filterhtml = html_writer::start_tag('form', [
    'method' => 'get',
    'action' => $pageurl->out(false),
    'class' => 'crm-showrooms-filter-form',
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

$filterhtml .= html_writer::start_div(
    'crm-showrooms-filter-grid'
);
$filterhtml .= html_writer::div(
    html_writer::label(
        get_string('search'),
        'showroom-filter-q',
        false,
        ['class' => 'form-label']
    )
    . html_writer::empty_tag('input', [
        'id' => 'showroom-filter-q',
        'name' => 'q',
        'type' => 'search',
        'value' => $query,
        'class' => 'form-control',
        'placeholder' => get_string(
            'commerce_showroom_n9_search_placeholder',
            'local_subscriptions'
        ),
    ]),
    'crm-showrooms-filter-field is-search'
);
$filterhtml .= html_writer::div(
    html_writer::label(
        get_string('status'),
        'showroom-filter-status',
        false,
        ['class' => 'form-label']
    )
    . html_writer::select(
        $statusoptions,
        'status',
        $status,
        false,
        [
            'id' => 'showroom-filter-status',
            'class' => 'form-select',
        ]
    ),
    'crm-showrooms-filter-field'
);
$filterhtml .= html_writer::div(
    html_writer::label(
        get_string(
            'commerce_showroom_n9_public_language',
            'local_subscriptions'
        ),
        'showroom-filter-language',
        false,
        ['class' => 'form-label']
    )
    . html_writer::select(
        $languageoptions,
        'language',
        $language,
        false,
        [
            'id' => 'showroom-filter-language',
            'class' => 'form-select',
        ]
    ),
    'crm-showrooms-filter-field'
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
        . get_string(
            'commerce_filters_apply',
            'local_subscriptions'
        ),
        [
            'type' => 'submit',
            'class' => 'btn btn-primary ms-2',
        ]
    ),
    'crm-showrooms-filter-actions'
);
$filterhtml .= html_writer::end_div();
$filterhtml .= html_writer::end_tag('form');

$removeurl = static function(
    string $name
) use (
    $params
): moodle_url {
    $next = $params;
    unset($next[$name], $next['page']);

    if ($name === 'status') {
        $next['status'] = 'all';
    }
    if ($name === 'language') {
        $next['language'] = 'all';
    }

    return new moodle_url(
        '/local/subscriptions/admin/commerce/showrooms/index.php',
        $next
    );
};

$scopepill = static function(
    string $label,
    moodle_url $remove
): string {
    return html_writer::span(
        html_writer::span(
            s($label),
            'crm-result-scope-pill-label'
        )
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
if ($status !== 'all') {
    $scopepills[] = $scopepill(
        get_string(
            'commerce_showroom_n9_scope_status',
            'local_subscriptions',
            $statusoptions[$status] ?? $status
        ),
        $removeurl('status')
    );
}
if ($language !== 'all') {
    $scopepills[] = $scopepill(
        get_string(
            'commerce_showroom_n9_scope_language',
            'local_subscriptions',
            $languageoptions[$language]
        ),
        $removeurl('language')
    );
}

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::SHOWROOMS, $context);
echo CrmBreadcrumbRenderer::render([
    [
        'label' => get_string(
            'crm_commerce_title',
            'local_subscriptions'
        ),
        'url' => new moodle_url(
            '/local/subscriptions/admin/commerce/index.php'
        ),
    ],
    [
        'label' => get_string('commerce_showroom_cms_title', 'local_subscriptions'),
        'url' => null,
    ],
]);

echo CrmPageHeader::render(
    $pagetitle,
    get_string(
        'commerce_showroom_n9_description',
        'local_subscriptions'
    ),
    HelpContext::COMMERCE
);

echo CommerceSectionNavigationRenderer::render(
    CommerceSectionNavigationRenderer::SHOWROOMS,
    $context
);

echo html_writer::div(
    html_writer::link(
        new moodle_url(
            '/local/subscriptions/admin/commerce/showrooms/edit.php'
        ),
        html_writer::tag('i', '', [
            'class' => 'fa fa-plus me-1',
            'aria-hidden' => 'true',
        ])
        . get_string(
            'commerce_showroom_cms_create',
            'local_subscriptions'
        ),
        ['class' => 'btn btn-primary']
    )
    . html_writer::link(
        new moodle_url(
            '/local/subscriptions/admin/commerce/showrooms/import.php'
        ),
        html_writer::tag('i', '', [
            'class' => 'fa fa-file-import me-1',
            'aria-hidden' => 'true',
        ])
        . get_string(
            'commerce_showroom_import_create',
            'local_subscriptions'
        ),
        ['class' => 'btn btn-outline-primary']
    ),
    'crm-showrooms-top-actions'
);

echo html_writer::div(
    CommerceDesignSystemRenderer::metrics([
        [
            'label' => get_string(
                'commerce_showroom_n9_kpi_total',
                'local_subscriptions'
            ),
            'value' => $kpi['total'],
        ],
        [
            'label' => CommerceShowroomStatus::label(
                CommerceShowroomStatus::PUBLISHED
            ),
            'value' => $kpi[CommerceShowroomStatus::PUBLISHED],
        ],
        [
            'label' => CommerceShowroomStatus::label(
                CommerceShowroomStatus::REVIEW
            ),
            'value' => $kpi[CommerceShowroomStatus::REVIEW],
        ],
        [
            'label' => CommerceShowroomStatus::label(
                CommerceShowroomStatus::DRAFT
            ),
            'value' => $kpi[CommerceShowroomStatus::DRAFT],
        ],
        [
            'label' => CommerceShowroomStatus::label(
                CommerceShowroomStatus::ARCHIVED
            ),
            'value' => $kpi[CommerceShowroomStatus::ARCHIVED],
        ],
    ]),
    'crm-showrooms-kpis'
);

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
            'class' =>
                'fa fa-chevron-down crm-sales-filter-panel-chevron',
            'aria-hidden' => 'true',
        ]),
        ['class' => 'crm-sales-filter-panel-summary']
    )
    . html_writer::div(
        $filterhtml,
        'crm-sales-filter-card crm-sales-filter-card-collapsible'
    ),
    [
        'class' =>
            'crm-sales-filter-panel crm-showrooms-filter-panel',
        'open' => $filtersareactive ? 'open' : null,
    ]
);

echo html_writer::div(
    html_writer::div(
        get_string(
            'commerce_showroom_n9_found',
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
    'crm-result-summary crm-showrooms-result-summary'
);

if ($pagedrows === []) {
    echo CommerceDesignSystemRenderer::empty_state(
        get_string(
            'commerce_showroom_n9_empty_title',
            'local_subscriptions'
        ),
        get_string(
            'commerce_showroom_n9_empty',
            'local_subscriptions'
        )
    );
} else {
    $table = new html_table();
    $table->attributes['class'] =
        'generaltable table table-hover align-middle '
        . 'crm-showrooms-table';
    $table->head = [
        $sortlink('name', get_string('name')),
        $sortlink('status', get_string('status')),
        $sortlink(
            'languages',
            get_string(
                'commerce_showroom_n9_public_pages',
                'local_subscriptions'
            )
        ),
        $sortlink(
            'modified',
            get_string(
                'commerce_showroom_n9_updated',
                'local_subscriptions'
            )
        ),
        html_writer::span(
            get_string('actions'),
            'crm-showrooms-actions-heading'
        ),
    ];

    foreach ($pagedrows as $row) {
        $showroom = $row['showroom'];
        $editurl = new moodle_url(
            '/local/subscriptions/admin/commerce/showrooms/edit.php',
            ['id' => $showroom->id]
        );
        $historyurl = new moodle_url(
            '/local/subscriptions/admin/commerce/showrooms/history.php',
            ['id' => $showroom->id]
        );
        $deleteurl = new moodle_url(
            $PAGE->url,
            [
                'delete' => $showroom->id,
                'sesskey' => sesskey(),
            ]
        );
        $exporturl = new moodle_url(
            '/local/subscriptions/admin/commerce/showrooms/export.php',
            ['id' => $showroom->id]
        );
        $portableurl = new moodle_url(
            '/local/subscriptions/admin/commerce/showrooms/export_portable_preflight.php',
            ['id' => $showroom->id]
        );

        $namehtml = html_writer::link(
            $editurl,
            format_string((string)$showroom->name),
            ['class' => 'crm-showroom-name-link']
        );

        $statusbadge = html_writer::span(
            CommerceShowroomStatus::label(
                (string)$showroom->status
            ),
            'badge rounded-pill bg-'
                . CommerceShowroomStatus::badge_class(
                    (string)$showroom->status
                )
                . ' crm-showroom-status-badge'
        );

        $sluglabels = [
            'fr' => '🇫🇷',
            'en' => '🇬🇧',
            'ru' => '🇷🇺',
        ];
        $publiclinks = [];
        foreach ($row['slugs'] as $lang => $slug) {
            $publiclinks[] = html_writer::link(
                new moodle_url('/' . ltrim($slug, '/')),
                html_writer::span(
                    $sluglabels[$lang] ?? strtoupper($lang),
                    'crm-showroom-public-language'
                )
                . html_writer::span(
                    s($slug),
                    'crm-showroom-public-slug'
                )
                . html_writer::tag('i', '', [
                    'class' => 'fa fa-external-link ms-1',
                    'aria-hidden' => 'true',
                ]),
                [
                    'class' => 'crm-showroom-public-link',
                    'target' => '_blank',
                    'rel' => 'noopener',
                ]
            );
        }
        $publichtml = $publiclinks === []
            ? html_writer::span(
                get_string(
                    'commerce_showroom_n9_no_public_page',
                    'local_subscriptions'
                ),
                'text-muted'
            )
            : html_writer::div(
                implode('', $publiclinks),
                'crm-showroom-public-links'
            );

        $displaybutton = html_writer::link(
            $editurl,
            html_writer::tag('i', '', [
                'class' => 'fa fa-pencil me-1',
                'aria-hidden' => 'true',
            ])
            . get_string('edit'),
            [
                'class' =>
                    'btn btn-sm btn-outline-primary '
                    . 'crm-showroom-primary-action',
            ]
        );

        $groups = [];

        $publicactions = '';
        foreach ($row['slugs'] as $lang => $slug) {
            $publicactions .= html_writer::link(
                new moodle_url('/' . ltrim($slug, '/')),
                html_writer::span(
                    $sluglabels[$lang] ?? strtoupper($lang),
                    'me-2'
                )
                . get_string(
                    'commerce_showroom_n9_open_public',
                    'local_subscriptions',
                    strtoupper($lang)
                ),
                [
                    'class' => 'crm-sales-row-menu-link',
                    'target' => '_blank',
                    'rel' => 'noopener',
                ]
            );
        }
        if ($publicactions !== '') {
            $groups[] = html_writer::div(
                html_writer::div(
                    get_string(
                        'commerce_showroom_n9_menu_public',
                        'local_subscriptions'
                    ),
                    'crm-sales-row-menu-section'
                )
                . $publicactions,
                'crm-sales-row-menu-group'
            );
        }

        $adminactions =
            html_writer::link(
                $historyurl,
                html_writer::tag('i', '', [
                    'class' => 'fa fa-history me-2',
                    'aria-hidden' => 'true',
                ])
                . get_string(
                    'commerce_showroom_history',
                    'local_subscriptions'
                ),
                ['class' => 'crm-sales-row-menu-link']
            )
            . html_writer::link(
                $exporturl,
                html_writer::tag('i', '', [
                    'class' => 'fa fa-file-code me-2',
                    'aria-hidden' => 'true',
                ])
                . get_string(
                    'commerce_showroom_n9_export_json',
                    'local_subscriptions'
                ),
                ['class' => 'crm-sales-row-menu-link']
            )
            . html_writer::link(
                $portableurl,
                html_writer::tag('i', '', [
                    'class' => 'fa fa-box-archive me-2',
                    'aria-hidden' => 'true',
                ])
                . get_string(
                    'commerce_showroom_n9_export_portable',
                    'local_subscriptions'
                ),
                ['class' => 'crm-sales-row-menu-link']
            );

        $groups[] = html_writer::div(
            html_writer::div(
                get_string(
                    'commerce_showroom_n9_menu_admin',
                    'local_subscriptions'
                ),
                'crm-sales-row-menu-section'
            )
            . $adminactions,
            'crm-sales-row-menu-group'
        );

        $dangeractions = html_writer::link(
            $deleteurl,
            html_writer::tag('i', '', [
                'class' => 'fa fa-trash me-2',
                'aria-hidden' => 'true',
            ])
            . get_string('delete'),
            [
                'class' =>
                    'crm-sales-row-menu-link text-danger',
                'onclick' => 'return confirm('
                    . json_encode(
                        get_string(
                            'commerce_showroom_n9_delete_confirm',
                            'local_subscriptions'
                        )
                    )
                    . ');',
            ]
        );
        $groups[] = html_writer::div(
            html_writer::div(
                get_string(
                    'commerce_showroom_n9_menu_danger',
                    'local_subscriptions'
                ),
                'crm-sales-row-menu-section'
            )
            . $dangeractions,
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
                        'crm-showroom-row-menu-trigger',
                    'aria-label' => get_string('actions'),
                ]
            )
            . html_writer::div(
                implode('', $groups),
                'crm-sales-row-menu'
            ),
            ['class' => 'crm-showroom-row-actions']
        );

        $actions = html_writer::div(
            $displaybutton . $menu,
            'crm-showroom-actions'
        );

        $table->data[] = [
            $namehtml,
            $statusbadge,
            $publichtml,
            html_writer::span(
                userdate(
                    (int)$showroom->timemodified,
                    get_string(
                        'strftimedatetimeshort',
                        'langconfig'
                    )
                ),
                'crm-showroom-updated'
            ),
            $actions,
        ];
    }

    echo html_writer::table($table);

    if ($total > $perpage) {
        $pagingparams = $params;
        unset($pagingparams['page']);
        echo $OUTPUT->paging_bar(
            $total,
            $page,
            $perpage,
            new moodle_url($pageurl, $pagingparams),
            'page'
        );
    }
}

$PAGE->requires->js_init_code(<<<'JS'
(function() {
    var menus = Array.prototype.slice.call(
        document.querySelectorAll('.crm-showroom-row-actions')
    );
    if (!menus.length) {
        return;
    }

    function positionMenu(menu) {
        var trigger = menu.querySelector('.crm-showroom-row-menu-trigger');
        var panel = menu.querySelector('.crm-sales-row-menu');
        if (!trigger || !panel) {
            return;
        }

        var rect = trigger.getBoundingClientRect();
        var width = Math.max(245, panel.offsetWidth || 245);
        var left = Math.min(
            window.innerWidth - width - 12,
            Math.max(12, rect.right - width)
        );

        panel.style.position = 'fixed';
        panel.style.left = left + 'px';
        panel.style.right = 'auto';
        panel.style.top = (rect.bottom + 6) + 'px';

        var panelrect = panel.getBoundingClientRect();
        if (panelrect.bottom > window.innerHeight - 12) {
            panel.style.top = Math.max(
                12,
                rect.top - panelrect.height - 6
            ) + 'px';
        }
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
            window.requestAnimationFrame(function() {
                positionMenu(menu);
            });
        });
    });

    ['resize', 'scroll'].forEach(function(eventname) {
        window.addEventListener(eventname, function() {
            menus.forEach(function(menu) {
                if (menu.open) {
                    positionMenu(menu);
                }
            });
        }, true);
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
