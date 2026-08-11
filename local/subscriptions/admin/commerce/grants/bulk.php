<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\catalog\presentation\CommerceProductDisplayNameResolver;
use local_subscriptions\commerce\grant\CommerceBulkGrantDryRunService;
use local_subscriptions\commerce\grant\CommerceBulkGrantCampaignService;
use local_subscriptions\crm\commerce\presentation\CommerceDesignSystemRenderer;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\subscription_config;
use local_subscriptions\subscription_manager;

$context = AdminSecurity::require(Capabilities::MANAGE_SUBSCRIPTIONS);
$url = new moodle_url('/local/subscriptions/admin/commerce/grants/bulk.php');
$title = get_string('commerce_bulk_grant_title', 'local_subscriptions');

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $url,
    $title,
    'local-subscriptions-commerce-bulk-grant-page'
);

$plans = [];
foreach ($DB->get_records('subscription_plan', null, 'name ASC, id ASC') as $plan) {
    $translated = subscription_manager::get_translated_plan_name((int)$plan->id, current_language());
    $label = $translated ?: format_string((string)$plan->name);
    if (empty($plan->is_active)) {
        $label .= ' · ' . get_string('label_inactive', 'local_subscriptions');
    }
    $plans[(int)$plan->id] = $label;
}

$productresolver = CommerceProductDisplayNameResolver::create($DB);
$products = [];
foreach ($DB->get_records(
    'local_subs_commerce_product',
    ['status' => 'active'],
    'name ASC, id ASC',
    'id,sku,type,name'
) as $product) {
    $name = $productresolver->resolve(
        [(string)$product->sku],
        current_language(),
        (string)$product->name
    );
    $products[(int)$product->id] = [
        'label' => $name . ' · ' . (string)$product->sku,
        'name' => $name,
        'sku' => (string)$product->sku,
        'type' => (string)$product->type,
    ];
}

$sourcetype = optional_param(
    'source_type',
    CommerceBulkGrantDryRunService::SOURCE_LEGACY_PLAN,
    PARAM_ALPHAEXT
);
$sourceplanid = optional_param('source_plan_id', 0, PARAM_INT);
$sourceproductid = optional_param('source_product_id', 0, PARAM_INT);
$targetproductid = optional_param('target_product_id', 0, PARAM_INT);
$simulation = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();
    $action = optional_param('action', 'simulate', PARAM_ALPHA);

    try {
        $sourceid = $sourcetype === CommerceBulkGrantDryRunService::SOURCE_NATIVE_PRODUCT
            ? $sourceproductid
            : $sourceplanid;

        if ($action === 'snapshot') {
            $campaignid = (new CommerceBulkGrantCampaignService($DB))->create_snapshot(
                required_param('campaign_name', PARAM_TEXT),
                $sourcetype,
                $sourceid,
                $targetproductid,
                optional_param_array('selected_userids', [], PARAM_INT),
                (int)$USER->id,
                optional_param('campaign_reason', '', PARAM_TEXT),
                optional_param('send_access_email', 0, PARAM_BOOL) === 1
            );

            redirect(new moodle_url(
                '/local/subscriptions/admin/commerce/grants/campaign_view.php',
                ['id' => $campaignid]
            ));
        }

        $simulation = (new CommerceBulkGrantDryRunService($DB))->simulate(
            $sourcetype,
            $sourceid,
            $targetproductid,
            (int)$USER->id
        );
    } catch (Throwable $exception) {
        $error = $exception->getMessage();

        if ($action === 'snapshot') {
            $simulation = (new CommerceBulkGrantDryRunService($DB))->simulate(
                $sourcetype,
                $sourceid,
                $targetproductid,
                (int)$USER->id
            );
        }
    }
}

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    [
        'label' => get_string('crm_commerce_title', 'local_subscriptions'),
        'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php'),
    ],
    [
        'label' => get_string('commerce_grants_title', 'local_subscriptions'),
        'url' => new moodle_url('/local/subscriptions/admin/commerce/grants/index.php'),
    ],
    ['label' => $title, 'url' => null],
]);
echo CrmPageHeader::render(
    $title,
    get_string('commerce_bulk_grant_description', 'local_subscriptions'),
    HelpContext::COMMERCE
);
echo CommerceSectionNavigationRenderer::render(
    CommerceSectionNavigationRenderer::GRANTS,
    $context
);

echo CommerceDesignSystemRenderer::action_bar([
    [
        'label' => get_string('commerce_grants_manual_action', 'local_subscriptions'),
        'url' => new moodle_url(subscription_config::add_manual_subscription_page()),
        'class' => 'btn btn-outline-primary',
    ],
    [
        'label' => get_string('commerce_grants_back', 'local_subscriptions'),
        'url' => new moodle_url('/local/subscriptions/admin/commerce/grants/index.php'),
        'class' => 'btn btn-outline-secondary',
    ],
], 'mb-3');

if ($error !== null) {
    echo html_writer::div(s($error), 'alert alert-danger');
}

echo CommerceDesignSystemRenderer::filter_panel(
    html_writer::start_tag('form', ['method' => 'post', 'class' => 'mform']) .
    html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'sesskey',
        'value' => sesskey(),
    ]) .
    html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'action',
        'value' => 'simulate',
    ]) .
    html_writer::start_div('row g-3') .
    html_writer::div(
        html_writer::label(
            get_string('commerce_bulk_grant_source_type', 'local_subscriptions'),
            'bulk-source-type',
            false,
            ['class' => 'form-label fw-semibold']
        ) .
        html_writer::select([
            CommerceBulkGrantDryRunService::SOURCE_LEGACY_PLAN =>
                get_string('commerce_bulk_grant_source_legacy', 'local_subscriptions'),
            CommerceBulkGrantDryRunService::SOURCE_NATIVE_PRODUCT =>
                get_string('commerce_bulk_grant_source_native', 'local_subscriptions'),
        ], 'source_type', $sourcetype, false, [
            'id' => 'bulk-source-type',
            'class' => 'form-select',
        ]),
        'col-lg-4'
    ) .
    html_writer::div(
        html_writer::label(
            get_string('commerce_bulk_grant_source_plan', 'local_subscriptions'),
            'bulk-source-plan',
            false,
            ['class' => 'form-label fw-semibold']
        ) .
        html_writer::select($plans, 'source_plan_id', $sourceplanid, ['' => '—'], [
            'id' => 'bulk-source-plan',
            'class' => 'form-select',
        ]),
        'col-lg-4',
        ['id' => 'bulk-source-plan-wrap']
    ) .
    html_writer::div(
        html_writer::label(
            get_string('commerce_bulk_grant_source_product', 'local_subscriptions'),
            'bulk-source-product',
            false,
            ['class' => 'form-label fw-semibold']
        ) .
        html_writer::select(
            array_map(static fn(array $item): string => $item['label'], $products),
            'source_product_id',
            $sourceproductid,
            ['' => '—'],
            ['id' => 'bulk-source-product', 'class' => 'form-select']
        ),
        'col-lg-4',
        ['id' => 'bulk-source-product-wrap']
    ) .
    html_writer::div(
        html_writer::label(
            get_string('commerce_bulk_grant_target_product', 'local_subscriptions'),
            'bulk-target-product',
            false,
            ['class' => 'form-label fw-semibold']
        ) .
        html_writer::select(
            array_map(static fn(array $item): string => $item['label'], $products),
            'target_product_id',
            $targetproductid,
            ['' => '—'],
            [
                'id' => 'bulk-target-product',
                'class' => 'form-select',
                'required' => 'required',
            ]
        ) .
        html_writer::div(
            get_string('commerce_bulk_grant_target_help', 'local_subscriptions'),
            'form-text'
        ),
        'col-lg-4'
    ) .
    html_writer::end_div() .
    html_writer::div(
        html_writer::tag(
            'button',
            get_string('commerce_bulk_grant_simulate', 'local_subscriptions'),
            ['type' => 'submit', 'class' => 'btn btn-primary']
        ),
        'mt-4'
    ) .
    html_writer::end_tag('form')
);

$PAGE->requires->js_init_code(<<<JS
(function() {
    var type = document.getElementById('bulk-source-type');
    var plan = document.getElementById('bulk-source-plan-wrap');
    var product = document.getElementById('bulk-source-product-wrap');
    var planSelect = document.getElementById('bulk-source-plan');
    var productSelect = document.getElementById('bulk-source-product');

    function refreshSource() {
        var nativeMode = type && type.value === 'native_product';
        if (plan) plan.style.display = nativeMode ? 'none' : '';
        if (product) product.style.display = nativeMode ? '' : 'none';
        if (planSelect) planSelect.required = !nativeMode;
        if (productSelect) productSelect.required = nativeMode;
    }

    if (type) type.addEventListener('change', refreshSource);
    refreshSource();
})();
JS);

if ($simulation !== null) {
    $summary = $simulation['summary'];

    echo CommerceDesignSystemRenderer::section_heading(
        get_string('commerce_bulk_grant_preview_title', 'local_subscriptions'),
        get_string('commerce_bulk_grant_preview_help', 'local_subscriptions')
    );

    echo CommerceDesignSystemRenderer::metrics([
        [
            'label' => get_string('commerce_bulk_grant_metric_total', 'local_subscriptions'),
            'value' => $summary['total'],
        ],
        [
            'label' => get_string('commerce_bulk_grant_metric_eligible', 'local_subscriptions'),
            'value' => $summary['eligible'],
        ],
        [
            'label' => get_string('commerce_bulk_grant_metric_owned', 'local_subscriptions'),
            'value' => $summary['alreadyowned'],
        ],
        [
            'label' => get_string('commerce_bulk_grant_metric_identity', 'local_subscriptions'),
            'value' => $summary['identityreview'],
        ],
        [
            'label' => get_string('commerce_bulk_grant_metric_error', 'local_subscriptions'),
            'value' => $summary['error'],
        ],
    ]);

    echo html_writer::div(
        html_writer::tag('strong', get_string('commerce_bulk_grant_dry_run_badge', 'local_subscriptions')) .
        ' ' . get_string('commerce_bulk_grant_no_mutation', 'local_subscriptions'),
        'alert alert-info mt-3'
    );

    echo html_writer::start_div('d-flex flex-wrap gap-2 my-3', ['id' => 'bulk-preview-filters']);
    $filters = [
        'all' => get_string('commerce_bulk_grant_filter_all', 'local_subscriptions', $summary['total']),
        CommerceBulkGrantDryRunService::DECISION_ELIGIBLE =>
            get_string('commerce_bulk_grant_filter_eligible', 'local_subscriptions', $summary['eligible']),
        CommerceBulkGrantDryRunService::DECISION_ALREADY_OWNED =>
            get_string('commerce_bulk_grant_filter_owned', 'local_subscriptions', $summary['alreadyowned']),
        CommerceBulkGrantDryRunService::DECISION_IDENTITY_REVIEW =>
            get_string('commerce_bulk_grant_filter_identity', 'local_subscriptions', $summary['identityreview']),
        CommerceBulkGrantDryRunService::DECISION_ERROR =>
            get_string('commerce_bulk_grant_filter_error', 'local_subscriptions', $summary['error']),
    ];
    foreach ($filters as $key => $label) {
        echo html_writer::tag('button', s($label), [
            'type' => 'button',
            'class' => 'btn btn-sm ' . ($key === 'all' ? 'btn-dark' : 'btn-outline-secondary'),
            'data-bulk-filter' => $key,
        ]);
    }
    echo html_writer::end_div();

    echo html_writer::start_tag('form', [
        'method' => 'post',
        'class' => 'mt-3',
        'id' => 'bulk-snapshot-form',
    ]);
    echo html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'sesskey',
        'value' => sesskey(),
    ]);
    echo html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'action',
        'value' => 'snapshot',
    ]);
    echo html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'source_type',
        'value' => $sourcetype,
    ]);
    echo html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'source_plan_id',
        'value' => $sourceplanid,
    ]);
    echo html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'source_product_id',
        'value' => $sourceproductid,
    ]);
    echo html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'target_product_id',
        'value' => $targetproductid,
    ]);

    $table = new html_table();
    $table->attributes['class'] = 'generaltable table table-hover align-middle';
    $table->head = [
        get_string('select'),
        get_string('commerce_bulk_grant_customer', 'local_subscriptions'),
        get_string('email'),
        get_string('commerce_bulk_grant_moodle_account', 'local_subscriptions'),
        get_string('commerce_bulk_grant_evidence', 'local_subscriptions'),
        get_string('commerce_bulk_grant_target_product', 'local_subscriptions'),
        get_string('commerce_bulk_grant_current_ownership', 'local_subscriptions'),
        get_string('commerce_bulk_grant_decision', 'local_subscriptions'),
    ];

    foreach ($simulation['rows'] as $row) {
        $fullname = trim((string)$row['firstname'] . ' ' . (string)$row['lastname']);
        if ($fullname === '') {
            $fullname = '—';
        }

        $userid = $row['userid'];
        $account = $userid
            ? html_writer::link(
                new moodle_url(subscription_config::admin_user_view_page(), ['id' => $userid]),
                '#' . $userid
            )
            : html_writer::span(
                get_string('commerce_bulk_grant_account_unresolved', 'local_subscriptions'),
                'text-warning fw-semibold'
            );

        $evidence = $row['evidence'] === []
            ? '—'
            : implode('<br>', array_map(
                static fn(string $value): string => html_writer::tag('code', s($value)),
                $row['evidence']
            ));

        $ownershipkey = 'commerce_bulk_grant_ownership_' . $row['ownershipsource'];
        $ownership = get_string_manager()->string_exists($ownershipkey, 'local_subscriptions')
            ? get_string($ownershipkey, 'local_subscriptions')
            : (string)$row['ownershipsource'];

        $decision = (string)$row['decision'];
        $badgeclass = match ($decision) {
            CommerceBulkGrantDryRunService::DECISION_ELIGIBLE => 'bg-success',
            CommerceBulkGrantDryRunService::DECISION_ALREADY_OWNED => 'bg-secondary',
            CommerceBulkGrantDryRunService::DECISION_IDENTITY_REVIEW => 'bg-warning text-dark',
            default => 'bg-danger',
        };
        $decisionlabel = get_string(
            'commerce_bulk_grant_decision_' . $decision,
            'local_subscriptions'
        );
        $decisionhtml = html_writer::span(s($decisionlabel), 'badge ' . $badgeclass);
        if ($decision === CommerceBulkGrantDryRunService::DECISION_ELIGIBLE) {
            $decisionhtml .= html_writer::div(
                get_string(
                    'commerce_bulk_grant_planned_entitlements',
                    'local_subscriptions',
                    (int)$row['grantcount']
                ),
                'small text-muted mt-1'
            );
        } else if ((string)$row['reason'] !== '') {
            $reasonkey = 'commerce_bulk_grant_reason_' . $row['reason'];
            $reason = get_string_manager()->string_exists($reasonkey, 'local_subscriptions')
                ? get_string($reasonkey, 'local_subscriptions')
                : (string)$row['reason'];
            $decisionhtml .= html_writer::div(s($reason), 'small text-muted mt-1');
        }

        $targetlabel = $products[$targetproductid]['name'] ?? (string)$simulation['target']['name'];
        $select = $decision === CommerceBulkGrantDryRunService::DECISION_ELIGIBLE && $userid
            ? html_writer::checkbox(
                'selected_userids[]',
                (int)$userid,
                true,
                '',
                [
                    'class' => 'bulk-beneficiary-checkbox',
                    'aria-label' => $fullname . ' ' . (string)$row['email'],
                ]
            )
            : '—';

        $table->data[] = new html_table_row([
            $select,
            s($fullname),
            s((string)$row['email']),
            $account,
            $evidence,
            s($targetlabel) . html_writer::div(
                s((string)$simulation['target']['sku']),
                'small text-muted'
            ),
            s($ownership),
            $decisionhtml,
        ]);
        $table->data[array_key_last($table->data)]->attributes['data-bulk-status'] = $decision;
    }

    echo html_writer::table($table);

    echo html_writer::div(
        html_writer::tag(
            'button',
            get_string('commerce_bulk_grant_select_all', 'local_subscriptions'),
            [
                'type' => 'button',
                'class' => 'btn btn-sm btn-outline-secondary me-2',
                'id' => 'bulk-select-all',
            ]
        ) .
        html_writer::tag(
            'button',
            get_string('commerce_bulk_grant_select_none', 'local_subscriptions'),
            [
                'type' => 'button',
                'class' => 'btn btn-sm btn-outline-secondary',
                'id' => 'bulk-select-none',
            ]
        ),
        'mb-3'
    );

    echo html_writer::start_div('card card-body border-0 shadow-sm mt-3');
    echo html_writer::tag(
        'h3',
        get_string('commerce_bulk_grant_snapshot_title', 'local_subscriptions'),
        ['class' => 'h5 mb-3']
    );
    echo html_writer::div(
        get_string('commerce_bulk_grant_snapshot_help', 'local_subscriptions'),
        'text-muted mb-3'
    );
    echo html_writer::start_div('row g-3');
    echo html_writer::div(
        html_writer::label(
            get_string('commerce_bulk_grant_campaign_name', 'local_subscriptions'),
            'bulk-campaign-name',
            false,
            ['class' => 'form-label fw-semibold']
        ) .
        html_writer::empty_tag('input', [
            'type' => 'text',
            'name' => 'campaign_name',
            'id' => 'bulk-campaign-name',
            'class' => 'form-control',
            'required' => 'required',
            'maxlength' => 255,
            'placeholder' => get_string('commerce_bulk_grant_campaign_name_placeholder', 'local_subscriptions'),
        ]),
        'col-lg-6'
    );
    echo html_writer::div(
        html_writer::label(
            get_string('commerce_bulk_grant_campaign_reason', 'local_subscriptions'),
            'bulk-campaign-reason',
            false,
            ['class' => 'form-label fw-semibold']
        ) .
        html_writer::empty_tag('input', [
            'type' => 'text',
            'name' => 'campaign_reason',
            'id' => 'bulk-campaign-reason',
            'class' => 'form-control',
            'maxlength' => 255,
        ]),
        'col-lg-6'
    );
    echo html_writer::end_div();
    echo html_writer::div(
        html_writer::checkbox(
            'send_access_email',
            1,
            true,
            get_string('commerce_bulk_grant_send_email', 'local_subscriptions'),
            ['id' => 'bulk-send-access-email']
        ) .
        html_writer::div(
            get_string('commerce_bulk_grant_send_email_help', 'local_subscriptions'),
            'form-text'
        ),
        'mt-3'
    );
    echo html_writer::div(
        html_writer::tag(
            'button',
            get_string('commerce_bulk_grant_create_snapshot', 'local_subscriptions'),
            ['type' => 'submit', 'class' => 'btn btn-primary']
        ),
        'mt-4'
    );
    echo html_writer::end_div();
    echo html_writer::end_tag('form');

    $PAGE->requires->js_init_code(<<<JS
(function() {
    var buttons = document.querySelectorAll('[data-bulk-filter]');
    var rows = document.querySelectorAll('tr[data-bulk-status]');
    var selectAll = document.getElementById('bulk-select-all');
    var selectNone = document.getElementById('bulk-select-none');
    var beneficiaryCheckboxes = document.querySelectorAll('.bulk-beneficiary-checkbox');

    if (selectAll) {
        selectAll.addEventListener('click', function() {
            beneficiaryCheckboxes.forEach(function(box) { box.checked = true; });
        });
    }
    if (selectNone) {
        selectNone.addEventListener('click', function() {
            beneficiaryCheckboxes.forEach(function(box) { box.checked = false; });
        });
    }

    buttons.forEach(function(button) {
        button.addEventListener('click', function() {
            var filter = button.getAttribute('data-bulk-filter');
            buttons.forEach(function(item) {
                item.classList.remove('btn-dark');
                item.classList.add('btn-outline-secondary');
            });
            button.classList.remove('btn-outline-secondary');
            button.classList.add('btn-dark');

            rows.forEach(function(row) {
                row.style.display = filter === 'all' || row.getAttribute('data-bulk-status') === filter
                    ? ''
                    : 'none';
            });
        });
    });
})();
JS);
}

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
