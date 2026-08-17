<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\promotion\domain\CommercePromotion;
use local_subscriptions\commerce\promotion\repository\MoodleCommercePromotionRepository;
use local_subscriptions\commerce\promotion\eligibility\CommercePromotionEligibilityRuleSet;
use local_subscriptions\crm\commerce\rendering\CommerceOffersAccessNavigationRenderer;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);
$repository = new MoodleCommercePromotionRepository();
$pageurl = new moodle_url('/local/subscriptions/admin/commerce/promotions/index.php');
$title = get_string('commerce_promotions_title', 'local_subscriptions');
CrmPageConfigurator::configure($PAGE, $context, $pageurl, $title, 'local-subscriptions-commerce-promotions-page');

$q = trim(optional_param('q', '', PARAM_RAW_TRIMMED));
$statusfilter = optional_param('status', 'all', PARAM_ALPHA);
$modefilter = optional_param('mode', 'all', PARAM_ALPHA);
$validityfilter = optional_param('validity', 'all', PARAM_ALPHA);
$sort = optional_param('sort', 'code', PARAM_ALPHA);
$dir = strtolower(optional_param('dir', 'asc', PARAM_ALPHA));
$page = max(0, optional_param('page', 0, PARAM_INT));
$perpage = optional_param('perpage', 25, PARAM_INT);

if (!in_array($statusfilter, ['all', 'active', 'inactive'], true)) {
    $statusfilter = 'all';
}
if (!in_array($modefilter, ['all', 'coupon', 'automatic'], true)) {
    $modefilter = 'all';
}
if (!in_array($validityfilter, ['all', 'current', 'upcoming', 'expired', 'unlimited'], true)) {
    $validityfilter = 'all';
}
if (!in_array($sort, ['code', 'value', 'validity', 'status', 'uses'], true)) {
    $sort = 'code';
}
if (!in_array($dir, ['asc', 'desc'], true)) {
    $dir = 'asc';
}
if (!in_array($perpage, [25, 50, 100], true)) {
    $perpage = 25;
}

$formatvalue = static function(CommercePromotion $promotion): string {
    if ($promotion->get_discount_type() === CommercePromotion::TYPE_PERCENTAGE) {
        return format_float($promotion->get_discount_value() / 100, 2) . ' %';
    }
    return format_float($promotion->get_discount_value() / 100, 2) . ' ' . ($promotion->get_currency() ?? '');
};
$validity = static function(CommercePromotion $promotion): array {
    $now = time();
    $start = $promotion->get_starts_at();
    $end = $promotion->get_ends_at();
    if ($start !== null && $start > $now) {
        return ['upcoming', get_string('commerce_promotion_validity_upcoming', 'local_subscriptions'), 'text-bg-info'];
    }
    if ($end !== null && $end <= $now) {
        return ['expired', get_string('commerce_promotion_validity_expired', 'local_subscriptions'), 'bg-light text-dark border'];
    }
    if ($start === null && $end === null) {
        return ['unlimited', get_string('commerce_promotion_validity_unlimited', 'local_subscriptions'), 'bg-light text-dark border'];
    }
    return ['current', get_string('commerce_promotion_validity_current', 'local_subscriptions'), 'text-bg-success'];
};

$promotions = $repository->find_all();
$rows = [];
$promotionmetrics = ['total' => count($promotions), 'active' => 0, 'coupons' => 0, 'automatic' => 0];
foreach ($promotions as $promotion) {
    if ($promotion->is_active()) {
        $promotionmetrics['active']++;
    }
    $promotionmetrics[$promotion->is_automatic() ? 'automatic' : 'coupons']++;
    [$validitykey, $validitylabel, $validityclass] = $validity($promotion);
    $rows[] = [
        'promotion' => $promotion,
        'uses' => $repository->count_redemptions((int)$promotion->get_id()),
        'validitykey' => $validitykey,
        'validitylabel' => $validitylabel,
        'validityclass' => $validityclass,
    ];
}

$filteredrows = array_values(array_filter($rows, static function(array $row) use ($q, $statusfilter, $modefilter, $validityfilter): bool {
    /** @var CommercePromotion $promotion */
    $promotion = $row['promotion'];
    if ($q !== '') {
        $haystack = core_text::strtolower(trim((string)$promotion->get_name() . ' ' . (string)($promotion->get_code() ?? '')));
        if (!str_contains($haystack, core_text::strtolower($q))) {
            return false;
        }
    }
    if ($statusfilter === 'active' && !$promotion->is_active()) {
        return false;
    }
    if ($statusfilter === 'inactive' && $promotion->is_active()) {
        return false;
    }
    if ($modefilter === 'coupon' && $promotion->is_automatic()) {
        return false;
    }
    if ($modefilter === 'automatic' && !$promotion->is_automatic()) {
        return false;
    }
    if ($validityfilter !== 'all' && $row['validitykey'] !== $validityfilter) {
        return false;
    }
    return true;
}));

usort($filteredrows, static function(array $a, array $b) use ($sort, $dir): int {
    /** @var CommercePromotion $ap */
    $ap = $a['promotion'];
    /** @var CommercePromotion $bp */
    $bp = $b['promotion'];
    $comparison = match ($sort) {
        'value' => $ap->get_discount_value() <=> $bp->get_discount_value(),
        'validity' => (($ap->get_starts_at() ?? 0) <=> ($bp->get_starts_at() ?? 0)),
        'status' => ((int)$ap->is_active() <=> (int)$bp->is_active()),
        'uses' => ((int)$a['uses'] <=> (int)$b['uses']),
        default => strnatcasecmp((string)($ap->get_code() ?? $ap->get_name()), (string)($bp->get_code() ?? $bp->get_name())),
    };
    return $dir === 'desc' ? -$comparison : $comparison;
});

$total = count($filteredrows);
$maxpage = max(0, (int)ceil($total / $perpage) - 1);
$page = min($page, $maxpage);
$pagedrows = array_slice($filteredrows, $page * $perpage, $perpage);

$baseparams = [
    'q' => $q,
    'status' => $statusfilter,
    'mode' => $modefilter,
    'validity' => $validityfilter,
    'sort' => $sort,
    'dir' => $dir,
    'perpage' => $perpage,
];
$sortlink = static function(string $key, string $label) use ($baseparams, $sort, $dir): string {
    $params = $baseparams;
    $params['sort'] = $key;
    $params['dir'] = ($sort === $key && $dir === 'asc') ? 'desc' : 'asc';
    $params['page'] = 0;
    $icon = $sort !== $key ? 'fa-sort' : ($dir === 'asc' ? 'fa-sort-asc' : 'fa-sort-desc');
    return html_writer::link(
        new moodle_url('/local/subscriptions/admin/commerce/promotions/index.php', $params),
        s($label) . html_writer::tag('i', '', ['class' => 'fa ' . $icon . ' ms-1', 'aria-hidden' => 'true']),
        ['class' => 'commerce-promotions-sort-link' . ($sort === $key ? ' is-active' : '')]
    );
};

$filtersactive = $q !== '' || $statusfilter !== 'all' || $modefilter !== 'all' || $validityfilter !== 'all';
$filterhtml = html_writer::start_tag('form', [
    'method' => 'get',
    'action' => $pageurl->out(false),
    'class' => 'commerce-promotions-filter-form',
]);
$filterhtml .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sort', 'value' => $sort]);
$filterhtml .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'dir', 'value' => $dir]);
$filterhtml .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'perpage', 'value' => $perpage]);
$filterhtml .= html_writer::start_div('commerce-promotions-filter-grid');
$filterhtml .= html_writer::div(
    html_writer::label(get_string('search'), 'promotion-filter-q', false, ['class' => 'form-label']) .
    html_writer::empty_tag('input', [
        'id' => 'promotion-filter-q',
        'type' => 'search',
        'name' => 'q',
        'value' => $q,
        'class' => 'form-control',
        'placeholder' => get_string('commerce_promotions_search_placeholder', 'local_subscriptions'),
    ]),
    'commerce-promotions-filter-field is-search'
);
$filterhtml .= html_writer::div(
    html_writer::label(get_string('status'), 'promotion-filter-status', false, ['class' => 'form-label']) .
    html_writer::select([
        'all' => get_string('all'),
        'active' => get_string('active'),
        'inactive' => get_string('inactive'),
    ], 'status', $statusfilter, false, ['id' => 'promotion-filter-status', 'class' => 'form-select']),
    'commerce-promotions-filter-field'
);
$filterhtml .= html_writer::div(
    html_writer::label(get_string('commerce_promotion_mode', 'local_subscriptions'), 'promotion-filter-mode', false, ['class' => 'form-label']) .
    html_writer::select([
        'all' => get_string('all'),
        'coupon' => get_string('commerce_promotion_coupon_badge', 'local_subscriptions'),
        'automatic' => get_string('commerce_promotion_automatic_badge', 'local_subscriptions'),
    ], 'mode', $modefilter, false, ['id' => 'promotion-filter-mode', 'class' => 'form-select']),
    'commerce-promotions-filter-field'
);
$filterhtml .= html_writer::div(
    html_writer::label(get_string('commerce_promotion_validity', 'local_subscriptions'), 'promotion-filter-validity', false, ['class' => 'form-label']) .
    html_writer::select([
        'all' => get_string('all'),
        'current' => get_string('commerce_promotion_validity_current', 'local_subscriptions'),
        'upcoming' => get_string('commerce_promotion_validity_upcoming', 'local_subscriptions'),
        'expired' => get_string('commerce_promotion_validity_expired', 'local_subscriptions'),
        'unlimited' => get_string('commerce_promotion_validity_unlimited', 'local_subscriptions'),
    ], 'validity', $validityfilter, false, ['id' => 'promotion-filter-validity', 'class' => 'form-select']),
    'commerce-promotions-filter-field'
);
$filterhtml .= html_writer::div(
    html_writer::link($pageurl, get_string('reset'), ['class' => 'btn btn-outline-secondary']) .
    html_writer::tag('button', html_writer::tag('i', '', ['class' => 'fa fa-filter me-1', 'aria-hidden' => 'true']) . get_string('commerce_filters_apply', 'local_subscriptions'), [
        'type' => 'submit',
        'class' => 'btn btn-primary ms-2',
    ]),
    'commerce-promotions-filter-actions'
);
$filterhtml .= html_writer::end_div();
$filterhtml .= html_writer::end_tag('form');

$headeractions = html_writer::link(
    new moodle_url('/local/subscriptions/admin/commerce/promotions/edit.php'),
    html_writer::tag('i', '', ['class' => 'fa fa-plus me-1', 'aria-hidden' => 'true']) . get_string('commerce_promotion_add', 'local_subscriptions'),
    ['class' => 'btn btn-primary']
);

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    ['label' => get_string('crm_commerce_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php')],
    ['label' => get_string('commerce_offers_access_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/offers-access/index.php')],
    ['label' => $title, 'url' => null],
]);
echo CrmPageHeader::render($title, get_string('commerce_promotions_description', 'local_subscriptions'), HelpContext::COMMERCE, $headeractions);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::OFFERS_ACCESS, $context);
echo CommerceOffersAccessNavigationRenderer::render(CommerceOffersAccessNavigationRenderer::PROMOTIONS);

echo html_writer::start_div('commerce-promotions-dashboard');
echo html_writer::start_div('row g-3 mb-3');
foreach ([
    ['commerce_promotions_metric_total', $promotionmetrics['total'], 'fa-tags'],
    ['commerce_promotions_metric_active', $promotionmetrics['active'], 'fa-check-circle'],
    ['commerce_promotions_metric_coupons', $promotionmetrics['coupons'], 'fa-ticket'],
    ['commerce_promotions_metric_automatic', $promotionmetrics['automatic'], 'fa-bolt'],
] as [$metriclabel, $metricvalue, $metricicon]) {
    echo html_writer::div(
        html_writer::div(
            html_writer::div(html_writer::tag('i', '', ['class' => 'fa ' . $metricicon, 'aria-hidden' => 'true']), 'commerce-promotions-metric-icon') .
            html_writer::div(
                html_writer::div((string)$metricvalue, 'commerce-promotions-metric-value') .
                html_writer::div(get_string($metriclabel, 'local_subscriptions'), 'commerce-promotions-metric-label')
            ),
            'card card-body commerce-promotions-metric h-100'
        ),
        'col-6 col-xl-3'
    );
}
echo html_writer::end_div();

echo html_writer::div(
    html_writer::tag('i', '', ['class' => 'fa fa-info-circle', 'aria-hidden' => 'true']) .
    html_writer::div(
        html_writer::tag('strong', get_string('commerce_promotions_mode_title', 'local_subscriptions')) .
        html_writer::div(get_string('commerce_promotions_coupon_hint', 'local_subscriptions'), 'text-muted small mt-1')
    ),
    'commerce-promotions-context-note mb-3'
);

echo html_writer::tag(
    'details',
    html_writer::tag(
        'summary',
        html_writer::div(
            html_writer::tag('i', '', ['class' => 'fa fa-filter', 'aria-hidden' => 'true']) .
            html_writer::tag('strong', get_string('commerce_offers_access_search_filters', 'local_subscriptions')) .
            html_writer::span(
                $filtersactive ? get_string('commerce_sales_filters_active', 'local_subscriptions') : get_string('commerce_sales_filters_collapsed_hint', 'local_subscriptions'),
                'crm-sales-filter-panel-status'
            ),
            'crm-sales-filter-panel-summary-copy'
        ) .
        html_writer::tag('i', '', ['class' => 'fa fa-chevron-down crm-sales-filter-panel-chevron', 'aria-hidden' => 'true']),
        ['class' => 'crm-sales-filter-panel-summary']
    ) . html_writer::div($filterhtml, 'crm-sales-filter-card crm-sales-filter-card-collapsible'),
    ['class' => 'crm-sales-filter-panel commerce-promotions-filter-panel mb-3', 'open' => $filtersactive ? 'open' : null]
);

echo html_writer::div(
    get_string('commerce_promotions_found', 'local_subscriptions', $total),
    'crm-result-summary commerce-promotions-result-summary'
);

if ($pagedrows === []) {
    echo html_writer::div(get_string($filtersactive ? 'commerce_promotions_no_results' : 'commerce_promotions_empty', 'local_subscriptions'), 'alert alert-info');
} else {
    $table = new html_table();
    $table->attributes['class'] = 'generaltable table table-hover align-middle mb-0 commerce-promotions-table';
    $table->head = [
        $sortlink('code', get_string('commerce_promotion_code', 'local_subscriptions')),
        $sortlink('value', get_string('commerce_promotion_value', 'local_subscriptions')),
        $sortlink('validity', get_string('commerce_promotion_validity', 'local_subscriptions')),
        $sortlink('status', get_string('status')),
        $sortlink('uses', get_string('commerce_promotion_uses', 'local_subscriptions')),
        get_string('commerce_promotion_customer_eligibility', 'local_subscriptions'),
        get_string('actions'),
    ];
    foreach ($pagedrows as $row) {
        /** @var CommercePromotion $promotion */
        $promotion = $row['promotion'];
        $id = (int)$promotion->get_id();
        $viewurl = new moodle_url('/local/subscriptions/admin/commerce/promotions/view.php', ['id' => $id]);
        $editurl = new moodle_url('/local/subscriptions/admin/commerce/promotions/edit.php', ['id' => $id]);
        $toggle = new moodle_url('/local/subscriptions/admin/commerce/promotions/action.php', [
            'id' => $id,
            'action' => $promotion->is_active() ? 'disable' : 'enable',
            'sesskey' => sesskey(),
        ]);
        $delete = new moodle_url('/local/subscriptions/admin/commerce/promotions/action.php', [
            'id' => $id,
            'action' => 'delete',
            'sesskey' => sesskey(),
        ]);

        $display = html_writer::link(
            $viewurl,
            html_writer::tag('i', '', ['class' => 'fa fa-eye me-1', 'aria-hidden' => 'true']) . get_string('view'),
            ['class' => 'btn btn-sm btn-primary']
        );
        $managementlinks = html_writer::link(
            $editurl,
            html_writer::tag('i', '', ['class' => 'fa fa-pencil me-2', 'aria-hidden' => 'true']) . get_string('edit'),
            ['class' => 'crm-sales-row-menu-link']
        );
        $managementlinks .= html_writer::link(
            $toggle,
            html_writer::tag('i', '', ['class' => 'fa fa-power-off me-2', 'aria-hidden' => 'true']) . get_string($promotion->is_active() ? 'disable' : 'enable'),
            ['class' => 'crm-sales-row-menu-link']
        );
        $dangerlink = html_writer::link(
            $delete,
            html_writer::tag('i', '', ['class' => 'fa fa-trash me-2', 'aria-hidden' => 'true']) . get_string('delete'),
            [
                'class' => 'crm-sales-row-menu-link text-danger',
                'onclick' => 'return confirm(' . json_encode(get_string('commerce_promotion_delete_confirm', 'local_subscriptions')) . ');',
            ]
        );
        $menu = html_writer::tag(
            'details',
            html_writer::tag('summary', html_writer::tag('i', '', ['class' => 'fa fa-ellipsis-h', 'aria-hidden' => 'true']), [
                'class' => 'btn btn-sm btn-outline-secondary crm-sales-row-menu-toggle',
                'aria-label' => get_string('actions'),
                'title' => get_string('actions'),
            ]) .
            html_writer::div(
                html_writer::div(
                    html_writer::div(get_string('commerce_promotions_menu_manage', 'local_subscriptions'), 'crm-sales-row-menu-section') . $managementlinks,
                    'crm-sales-row-menu-group'
                ) .
                html_writer::div(
                    html_writer::div(get_string('commerce_promotions_menu_danger', 'local_subscriptions'), 'crm-sales-row-menu-section') . $dangerlink,
                    'crm-sales-row-menu-group'
                ),
                'crm-sales-row-menu'
            ),
            ['class' => 'crm-sales-row-actions-menu']
        );
        $actions = html_writer::div($display . $menu, 'crm-sales-actions commerce-promotions-row-actions');

        $rules = CommercePromotionEligibilityRuleSet::from_metadata($promotion->get_metadata());
        $eligibilitylabel = $rules->is_empty()
            ? get_string('commerce_promotion_eligibility_everyone', 'local_subscriptions')
            : get_string('commerce_promotion_eligibility_conditional', 'local_subscriptions');
        $identity = ($promotion->is_automatic()
                ? html_writer::span(get_string('commerce_promotion_automatic_badge', 'local_subscriptions'), 'badge rounded-pill text-bg-info me-2')
                : html_writer::span(get_string('commerce_promotion_coupon_badge', 'local_subscriptions'), 'badge rounded-pill text-bg-warning me-2')) .
            html_writer::link($viewurl, s($promotion->get_code() ?? get_string('commerce_promotion_automatic', 'local_subscriptions')), ['class' => 'fw-semibold text-decoration-none']) .
            html_writer::div(s($promotion->get_name()), 'small text-muted mt-1');
        $table->data[] = [
            $identity,
            html_writer::span(s($formatvalue($promotion)), 'fw-semibold') . html_writer::div(
                get_string($promotion->get_discount_type() === CommercePromotion::TYPE_PERCENTAGE ? 'commerce_promotion_percentage' : 'commerce_promotion_fixed', 'local_subscriptions'),
                'small text-muted'
            ),
            html_writer::span($row['validitylabel'], 'badge rounded-pill ' . $row['validityclass']),
            $promotion->is_active() ? html_writer::span(get_string('active'), 'badge rounded-pill text-bg-success') : html_writer::span(get_string('inactive'), 'badge rounded-pill bg-light text-dark border'),
            (int)$row['uses'],
            s($eligibilitylabel),
            $actions,
        ];
    }
    echo html_writer::start_div('card commerce-promotions-list-card');
    echo html_writer::div(
        html_writer::tag('h2', get_string('commerce_promotions_list_title', 'local_subscriptions'), ['class' => 'h5 mb-1']) .
        html_writer::tag('p', get_string('commerce_promotions_list_description', 'local_subscriptions'), ['class' => 'text-muted mb-0']),
        'commerce-promotions-list-header'
    );
    echo html_writer::div(html_writer::table($table), 'table-responsive');
    echo html_writer::end_div();

    if ($total > $perpage) {
        $pagingparams = $baseparams;
        unset($pagingparams['page']);
        echo $OUTPUT->paging_bar($total, $page, $perpage, new moodle_url($pageurl, $pagingparams), 'page');
    }
}
echo html_writer::end_div();

$PAGE->requires->js_init_code(<<<'JS'
(function() {
    var menus = Array.prototype.slice.call(document.querySelectorAll('.local-subscriptions-commerce-promotions-page .crm-sales-row-actions-menu'));
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
