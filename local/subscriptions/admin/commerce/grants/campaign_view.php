<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\grant\CommerceBulkGrantCampaignService;
use local_subscriptions\commerce\grant\CommerceBulkGrantDryRunService;
use local_subscriptions\commerce\personaloffer\admin\CommercePersonalOfferCrmPresentation;
use local_subscriptions\commerce\mail\service\CommerceGrantCampaignMailService;
use local_subscriptions\crm\commerce\presentation\CommerceDesignSystemRenderer;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\commerce\rendering\CommerceOffersAccessNavigationRenderer;
use local_subscriptions\crm\commerce\rendering\CommerceOffersAccessCampaignRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\subscription_config;
use local_subscriptions\subscription_manager;

$context = AdminSecurity::require(Capabilities::MANAGE_SUBSCRIPTIONS);
$id = required_param('id', PARAM_INT);
$memberquery = trim(optional_param('memberq', '', PARAM_RAW_TRIMMED));
$memberstatusfilter = trim(optional_param('memberstatus', '', PARAM_ALPHANUMEXT));

$campaignmemberperpage = max(
    25,
    optional_param('memberperpage', 25, PARAM_INT)
);
$campaignmemberperpage = in_array(
    $campaignmemberperpage,
    [25, 50, 100],
    true
) ? $campaignmemberperpage : 25;
$campaignmemberpage = max(
    0,
    optional_param('memberpage', 0, PARAM_INT)
);
$campaignmembersort = trim(
    optional_param('membersort', 'client', PARAM_ALPHA)
);
$campaignmemberdir = strtolower(
    optional_param('memberdir', 'asc', PARAM_ALPHA)
) === 'desc' ? 'desc' : 'asc';

$campaignsortheader = static function(
    string $key,
    string $label,
    array $allowed,
    moodle_url $baseurl,
    string $currentsort,
    string $currentdir
): string {
    if (!in_array($key, $allowed, true)) {
        return s($label);
    }

    $params = $baseurl->params();
    $params['membersort'] = $key;
    $params['memberdir'] =
        $currentsort === $key && $currentdir === 'asc'
            ? 'desc'
            : 'asc';
    $params['memberpage'] = 0;

    $active = $currentsort === $key;
    $icon = !$active
        ? 'fa-sort'
        : ($currentdir === 'asc' ? 'fa-sort-asc' : 'fa-sort-desc');

    return html_writer::link(
        new moodle_url($baseurl->get_path(), $params),
        s($label)
        . html_writer::tag('i', '', [
            'class' => 'fa ' . $icon . ' ms-1',
            'aria-hidden' => 'true',
        ]),
        [
            'class' => 'crm-campaign-sort-link'
                . ($active ? ' is-active' : ''),
        ]
    );
};


$service = new CommerceBulkGrantCampaignService($DB);
$campaign = $service->get_campaign($id);
$url = new moodle_url(
    '/local/subscriptions/admin/commerce/grants/campaign_view.php',
    array_filter([
        'id' => $id,
        'memberq' => $memberquery,
        'memberstatus' => $memberstatusfilter,
        'membersort' => $campaignmembersort,
        'memberdir' => $campaignmemberdir,
        'memberpage' => $campaignmemberpage,
        'memberperpage' => $campaignmemberperpage,
    ], static fn(mixed $value): bool => $value !== '')
);

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $url,
    (string)$campaign->name,
    'local-subscriptions-commerce-grant-campaign-view-page'
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();
    $action = required_param('action', PARAM_ALPHA);

    try {
        if ($action === 'launch') {
            $service->launch($id, (int)$USER->id);
            redirect(
                $url,
                get_string('commerce_bulk_grant_campaign_launched', 'local_subscriptions'),
                null,
                \core\output\notification::NOTIFY_SUCCESS
            );
        }

        if ($action === 'runnow') {
            $stats = $service->run_now($id, (int)$USER->id);
            redirect(
                $url,
                get_string(
                    'commerce_bulk_grant_campaign_run_now_done',
                    'local_subscriptions',
                    (int)$stats['processed']
                ),
                null,
                \core\output\notification::NOTIFY_SUCCESS
            );
        }

        if ($action === 'schedule') {
            $raw = required_param('scheduled_at', PARAM_RAW_TRIMMED);
            $scheduledat = \local_subscriptions\commerce\personaloffer\admin\CommercePersonalOfferCrmInput::datetime_local(
                $raw,
                \core_date::get_user_timezone()
            );
            if ($scheduledat === null) {
                throw new moodle_exception(
                    'commerce_bulk_grant_schedule_future_required',
                    'local_subscriptions'
                );
            }
            $service->schedule($id, $scheduledat, (int)$USER->id);
            redirect(
                $url,
                get_string('commerce_bulk_grant_campaign_scheduled', 'local_subscriptions'),
                null,
                \core\output\notification::NOTIFY_SUCCESS
            );
        }

        if ($action === 'retry') {
            $count = $service->retry_failures($id, (int)$USER->id);
            redirect(
                $url,
                get_string('commerce_bulk_grant_campaign_retried', 'local_subscriptions', $count),
                null,
                \core\output\notification::NOTIFY_SUCCESS
            );
        }
    } catch (Throwable $exception) {
        redirect(
            $url,
            $exception->getMessage(),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }
}

$campaign = $service->get_campaign($id);
$members = $service->members($id);
$summary = $service->summary($id);
$mailcampaignsummary = CommerceGrantCampaignMailService::create()->summary($id);
$source = json_decode((string)$campaign->sourcejson, true) ?: [];
$target = json_decode((string)$campaign->targetjson, true) ?: [];

$sourcebusinesslabel = (string)($source['name'] ?? $source['sku'] ?? '—');
if (
    (string)$campaign->sourcetype === CommerceBulkGrantDryRunService::SOURCE_NATIVE_PRODUCT
    && (int)$campaign->sourceid > 0
) {
    $sourcebusinesslabel = CommercePersonalOfferCrmPresentation::business_product_label(
        $DB,
        (int)$campaign->sourceid
    );
} else if (
    (string)$campaign->sourcetype === CommerceBulkGrantDryRunService::SOURCE_LEGACY_PLAN
    && (int)$campaign->sourceid > 0
) {
    $translatedplan = subscription_manager::get_translated_plan_name(
        (int)$campaign->sourceid,
        current_language()
    );
    if (trim((string)$translatedplan) !== '') {
        $sourcebusinesslabel = (string)$translatedplan;
    }
}

$targetbusinesslabel = (int)$campaign->targetproductid > 0
    ? CommercePersonalOfferCrmPresentation::business_product_label(
        $DB,
        (int)$campaign->targetproductid
    )
    : (string)($target['name'] ?? '—');

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    [
        'label' => get_string('crm_commerce_title', 'local_subscriptions'),
        'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php'),
    ],
    [
        'label' => get_string('commerce_offers_access_title', 'local_subscriptions'),
        'url' => new moodle_url('/local/subscriptions/admin/commerce/offers-access/index.php'),
    ],
    [
        'label' => get_string('commerce_offers_access_campaigns_title', 'local_subscriptions'),
        'url' => new moodle_url('/local/subscriptions/admin/commerce/offers-access/campaigns.php'),
    ],
    ['label' => (string)$campaign->name, 'url' => null],
]);
echo CrmPageHeader::render(
    (string)$campaign->name,
    get_string('commerce_bulk_grant_campaign_view_help', 'local_subscriptions'),
    HelpContext::COMMERCE
);
echo CommerceSectionNavigationRenderer::render(
    CommerceSectionNavigationRenderer::OFFERS_ACCESS,
    $context
);
echo CommerceOffersAccessNavigationRenderer::render(
    CommerceOffersAccessNavigationRenderer::CAMPAIGNS
);

$statuskey = 'commerce_bulk_grant_campaign_status_' . (string)$campaign->status;
$statuslabel = get_string_manager()->string_exists($statuskey, 'local_subscriptions')
    ? get_string($statuskey, 'local_subscriptions')
    : (string)$campaign->status;

$grantsteps = [
    [
        'key' => 'configuration',
        'label' => get_string('commerce_offers_access_campaign_step_configuration', 'local_subscriptions'),
        'detail' => get_string('commerce_offers_access_campaign_step_configuration_done', 'local_subscriptions'),
        'state' => 'complete',
    ],
    [
        'key' => 'snapshot',
        'label' => get_string('commerce_offers_access_campaign_step_snapshot', 'local_subscriptions'),
        'detail' => get_string('commerce_offers_access_campaign_step_snapshot_done', 'local_subscriptions', (int)$campaign->selectedcount),
        'state' => 'complete',
    ],
    [
        'key' => 'execution',
        'label' => get_string('commerce_offers_access_campaign_step_execution', 'local_subscriptions'),
        'detail' => in_array((string)$campaign->status, [
            CommerceBulkGrantCampaignService::STATUS_QUEUED,
            CommerceBulkGrantCampaignService::STATUS_RUNNING,
        ], true)
            ? get_string('commerce_offers_access_campaign_step_execution_running', 'local_subscriptions', (int)$campaign->processedcount)
            : ((string)$campaign->status === CommerceBulkGrantCampaignService::STATUS_READY
                ? get_string('commerce_offers_access_campaign_step_execution_waiting', 'local_subscriptions')
                : get_string('commerce_offers_access_campaign_step_execution_done', 'local_subscriptions', (int)$campaign->processedcount)),
        'state' => (string)$campaign->status === CommerceBulkGrantCampaignService::STATUS_READY
            ? 'active'
            : (in_array((string)$campaign->status, [
                CommerceBulkGrantCampaignService::STATUS_QUEUED,
                CommerceBulkGrantCampaignService::STATUS_RUNNING,
            ], true) ? 'active' : 'complete'),
    ],
    [
        'key' => 'complete',
        'label' => get_string('commerce_offers_access_campaign_step_complete', 'local_subscriptions'),
        'detail' => (string)$campaign->status === CommerceBulkGrantCampaignService::STATUS_COMPLETED_ERRORS
            ? get_string('commerce_offers_access_campaign_step_complete_errors', 'local_subscriptions', $summary['failed'])
            : ((string)$campaign->status === CommerceBulkGrantCampaignService::STATUS_COMPLETED
                ? get_string('commerce_offers_access_campaign_step_complete_done', 'local_subscriptions')
                : get_string('commerce_offers_access_campaign_step_complete_waiting', 'local_subscriptions')),
        'state' => (string)$campaign->status === CommerceBulkGrantCampaignService::STATUS_COMPLETED
            ? 'complete'
            : ((string)$campaign->status === CommerceBulkGrantCampaignService::STATUS_COMPLETED_ERRORS
                ? 'error'
                : 'pending'),
    ],
];

echo html_writer::div(
    html_writer::div(
        html_writer::span(
            html_writer::tag('i', '', ['class' => 'fa fa-key', 'aria-hidden' => 'true']),
            'crm-offers-access-campaign-kind-icon is-grant'
        )
        . html_writer::div(
            html_writer::div(
                get_string('commerce_offers_access_kind_grant', 'local_subscriptions'),
                'crm-offers-access-campaign-kind-label'
            )
            . html_writer::div(
                s($statuslabel),
                'crm-offers-access-campaign-status'
            ),
            'crm-offers-access-campaign-kind-copy'
        ),
        'crm-offers-access-campaign-heading'
    )
    . CommerceOffersAccessCampaignRenderer::workflow($grantsteps, 'grant'),
    'crm-offers-access-campaign-overview'
);

echo CommerceDesignSystemRenderer::action_bar([
    [
        'label' => get_string('commerce_bulk_grant_new', 'local_subscriptions'),
        'url' => new moodle_url('/local/subscriptions/admin/commerce/grants/bulk.php'),
        'class' => 'btn crm-grant-action-outline',
    ],
    [
        'label' => get_string('commerce_grants_manual_action', 'local_subscriptions'),
        'url' => new moodle_url(subscription_config::add_manual_subscription_page(), ['workspace' => 'grants']),
        'class' => 'btn crm-grant-action-outline',
    ],
], 'mb-3');

$sourcehtml = html_writer::div(
    html_writer::div(
        html_writer::span(
            get_string('commerce_offers_access_config_source', 'local_subscriptions'),
            'crm-offers-access-campaign-summary-label'
        )
        . html_writer::span(
            s($sourcebusinesslabel),
            'crm-offers-access-campaign-summary-value'
        ),
        'crm-offers-access-campaign-summary-row'
    )
    . html_writer::div(
        html_writer::span(
            get_string('commerce_offers_access_config_product', 'local_subscriptions'),
            'crm-offers-access-campaign-summary-label'
        )
        . html_writer::span(
            s($targetbusinesslabel),
            'crm-offers-access-campaign-summary-value'
        ),
        'crm-offers-access-campaign-summary-row'
    )
    . html_writer::div(
        html_writer::span(
            get_string('commerce_offers_access_campaign_selected', 'local_subscriptions'),
            'crm-offers-access-campaign-summary-label'
        )
        . html_writer::span(
            (string)$summary['total'],
            'crm-offers-access-campaign-summary-value'
        ),
        'crm-offers-access-campaign-summary-row'
    )
    . (!empty($campaign->mailtemplateid)
        ? html_writer::div(
            html_writer::span(
                get_string('commerce_bulk_grant_mail_template', 'local_subscriptions'),
                'crm-offers-access-campaign-summary-label'
            )
            . html_writer::span(
                s((string)(
                    json_decode((string)$campaign->mailtemplatesnapshot, true)['templatename']
                    ?? '#' . (int)$campaign->mailtemplateid
                )),
                'crm-offers-access-campaign-summary-value'
            ),
            'crm-offers-access-campaign-summary-row'
        )
        : '')
    . (!empty($campaign->scheduledat)
        ? html_writer::div(
            html_writer::span(
                get_string('commerce_bulk_grant_schedule_at', 'local_subscriptions'),
                'crm-offers-access-campaign-summary-label'
            )
            . html_writer::span(
                userdate(
                    (int)$campaign->scheduledat,
                    get_string('strftimedatetimeshort', 'langconfig')
                ),
                'crm-offers-access-campaign-summary-value'
            ),
            'crm-offers-access-campaign-summary-row'
        )
        : '')
    . html_writer::div(
        html_writer::span(
            get_string('commerce_offers_access_last_update', 'local_subscriptions'),
            'crm-offers-access-campaign-summary-label'
        )
        . html_writer::span(
            userdate(
                (int)$campaign->timemodified,
                get_string('strftimedatetimeshort', 'langconfig')
            ),
            'crm-offers-access-campaign-summary-value'
        ),
        'crm-offers-access-campaign-summary-row'
    )
    . html_writer::div(
        html_writer::span(
            get_string('commerce_offers_access_campaign_communication_title', 'local_subscriptions'),
            'crm-offers-access-campaign-summary-label'
        )
        . html_writer::span(
            !empty($campaign->sendemail) ? get_string('yes') : get_string('no'),
            'crm-offers-access-campaign-summary-value'
        ),
        'crm-offers-access-campaign-summary-row'
    ),
    'crm-offers-access-campaign-summary'
);

$technical = html_writer::tag(
    'dl',
    html_writer::tag('dt', get_string('commerce_bulk_grant_campaign_source', 'local_subscriptions'))
    . html_writer::tag('dd', html_writer::tag('code', s((string)($source['sku'] ?? ('#' . $campaign->sourceid)))))
    . html_writer::tag('dt', get_string('commerce_bulk_grant_target_product', 'local_subscriptions'))
    . html_writer::tag('dd', html_writer::tag('code', s((string)($target['sku'] ?? '—'))))
    . html_writer::tag('dt', get_string('commerce_bulk_grant_campaign_reason', 'local_subscriptions'))
    . html_writer::tag('dd', trim((string)$campaign->reason) !== '' ? s((string)$campaign->reason) : '—'),
    ['class' => 'mb-0']
);

echo CommerceDesignSystemRenderer::panel(
    get_string('commerce_offers_access_campaign_summary_title', 'local_subscriptions'),
    $sourcehtml
    . CommerceOffersAccessCampaignRenderer::technical(
        get_string('commerce_offers_access_config_technical', 'local_subscriptions'),
        $technical
    ),
    'mb-4'
);

if (in_array((string)$campaign->status, [
    CommerceBulkGrantCampaignService::STATUS_QUEUED,
    CommerceBulkGrantCampaignService::STATUS_RUNNING,
], true)) {
    $croncontent = html_writer::div(
        get_string(
            'commerce_bulk_grant_campaign_cron_notice',
            'local_subscriptions'
        )
    );

    $croncontent .= html_writer::start_tag('form', [
        'method' => 'post',
        'class' => 'mt-2',
    ]);
    $croncontent .= html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'sesskey',
        'value' => sesskey(),
    ]);
    $croncontent .= html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'action',
        'value' => 'runnow',
    ]);
    $croncontent .= html_writer::tag(
        'button',
        html_writer::tag('i', '', [
            'class' => 'fa fa-bolt me-1',
            'aria-hidden' => 'true',
        ])
        . get_string(
            'commerce_bulk_grant_campaign_process_all_now',
            'local_subscriptions'
        ),
        [
            'type' => 'submit',
            'class' => 'btn crm-grant-action-primary btn-sm',
        ]
    );
    $croncontent .= html_writer::end_tag('form');

    echo html_writer::div(
        $croncontent,
        'alert alert-info mt-3'
    );
}


echo CommerceOffersAccessCampaignRenderer::metrics([
    [
        'label' => get_string('commerce_bulk_grant_metric_total', 'local_subscriptions'),
        'value' => $summary['total'],
        'class' => 'is-primary',
    ],
    [
        'label' => get_string('commerce_bulk_grant_campaign_metric_queued', 'local_subscriptions'),
        'value' => $summary['queued'],
    ],
    [
        'label' => get_string('commerce_bulk_grant_campaign_metric_success', 'local_subscriptions'),
        'value' => $summary['completed'],
        'class' => 'is-success is-emphasis',
    ],
    [
        'label' => get_string('commerce_bulk_grant_campaign_metric_skipped', 'local_subscriptions'),
        'value' => $summary['skipped'],
    ],
    [
        'label' => get_string('commerce_bulk_grant_metric_error', 'local_subscriptions'),
        'value' => $summary['failed'],
        'class' => $summary['failed'] > 0 ? 'is-error' : '',
    ],
]);

$actions = '';
if ((string)$campaign->status === CommerceBulkGrantCampaignService::STATUS_READY) {
    $actions .= html_writer::start_tag('form', ['method' => 'post', 'class' => 'd-inline-block me-2 mt-3']);
    $actions .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    $actions .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'launch']);
    $actions .= html_writer::tag(
        'button',
        get_string('commerce_bulk_grant_campaign_launch', 'local_subscriptions', $summary['queued']),
        [
            'type' => 'submit',
            'class' => 'btn crm-grant-action-primary',
            'onclick' => 'return confirm(' . json_encode(
                get_string('commerce_bulk_grant_campaign_launch_confirm', 'local_subscriptions')
            ) . ');',
        ]
    );
    $actions .= html_writer::end_tag('form');

    $actions .= html_writer::start_tag('form', [
        'method' => 'post',
        'class' => 'd-inline-block me-2 mt-3',
    ]);
    $actions .= html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'sesskey',
        'value' => sesskey(),
    ]);
    $actions .= html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'action',
        'value' => 'runnow',
    ]);
    $actions .= html_writer::tag(
        'button',
        get_string('commerce_bulk_grant_campaign_run_now', 'local_subscriptions'),
        [
            'type' => 'submit',
            'class' => 'btn crm-grant-action-primary',
        ]
    );
    $actions .= html_writer::end_tag('form');

    $actions .= html_writer::start_tag('form', [
        'method' => 'post',
        'class' => 'd-inline-flex align-items-end gap-2 mt-3',
    ]);
    $actions .= html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'sesskey',
        'value' => sesskey(),
    ]);
    $actions .= html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'action',
        'value' => 'schedule',
    ]);
    $actions .= html_writer::div(
        html_writer::label(
            get_string('commerce_bulk_grant_schedule_at', 'local_subscriptions'),
            'campaign-scheduled-at',
            false,
            ['class' => 'form-label small mb-1']
        )
        . html_writer::empty_tag('input', [
            'id' => 'campaign-scheduled-at',
            'name' => 'scheduled_at',
            'type' => 'datetime-local',
            'class' => 'form-control form-control-sm',
            'required' => 'required',
        ])
    );
    $actions .= html_writer::tag(
        'button',
        get_string('commerce_bulk_grant_schedule_action', 'local_subscriptions'),
        [
            'type' => 'submit',
            'class' => 'btn crm-grant-action-outline',
        ]
    );
    $actions .= html_writer::end_tag('form');
}


if (
    (string)$campaign->status === CommerceBulkGrantCampaignService::STATUS_COMPLETED_ERRORS
    && $summary['failed'] > 0
) {
    $actions .= html_writer::start_tag('form', ['method' => 'post', 'class' => 'd-inline-block me-2 mt-3']);
    $actions .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    $actions .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'retry']);
    $actions .= html_writer::tag(
        'button',
        get_string('commerce_bulk_grant_campaign_retry', 'local_subscriptions', $summary['failed']),
        ['type' => 'submit', 'class' => 'btn crm-grant-action-outline']
    );
    $actions .= html_writer::end_tag('form');
}

if ($actions !== '') {
    echo html_writer::div($actions, 'mb-4');
}


if (!empty($campaign->sendemail)) {
    $previewurl = new moodle_url(
        '/local/subscriptions/admin/commerce/grants/mail_preview.php',
        array_filter([
            'templateid' => (int)($campaign->mailtemplateid ?? 0),
            'productid' => (int)$campaign->targetproductid,
            'language' => 'ru',
            'embed' => 1,
        ], static fn(mixed $value): bool => $value !== '' && $value !== 0)
    );

    echo CommerceOffersAccessCampaignRenderer::communication(
        (int)$mailcampaignsummary['queued']
            + (int)$mailcampaignsummary['processing'],
        (int)$mailcampaignsummary['sent'],
        (int)$mailcampaignsummary['failed'],
        new moodle_url('/local/subscriptions/admin/commerce/mail/index.php', [
            'q' => (string)$campaign->campaignkey,
        ]),
        new moodle_url('/local/subscriptions/admin/commerce/mail/templates/index.php', [
            'category' => 'transactional',
        ]),
        $previewurl
    );
}

$memberstatusoptions = [
    '' => get_string('all'),
];
foreach ($members as $memberoption) {
    $rawstatus = (string)$memberoption->status;
    if ($rawstatus === '' || isset($memberstatusoptions[$rawstatus])) {
        continue;
    }
    $statuskey = 'commerce_bulk_grant_member_status_' . $rawstatus;
    $memberstatusoptions[$rawstatus] =
        get_string_manager()->string_exists(
            $statuskey,
            'local_subscriptions'
        )
            ? get_string($statuskey, 'local_subscriptions')
            : ucfirst(str_replace('_', ' ', $rawstatus));
}

$memberfilterurl = new moodle_url(
    '/local/subscriptions/admin/commerce/grants/campaign_view.php',
    ['id' => $id]
);
echo html_writer::start_tag('form', [
    'method' => 'get',
    'action' => $memberfilterurl->out(false),
    'class' => 'crm-campaign-member-filters',
]);
echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'id',
    'value' => $id,
]);
echo html_writer::start_div(
    'crm-campaign-member-filter-grid crm-campaign-member-filter-grid-grant'
);
echo html_writer::div(
    html_writer::label(
        get_string('search'),
        'grant-member-query',
        false,
        ['class' => 'form-label']
    )
    . html_writer::empty_tag('input', [
        'id' => 'grant-member-query',
        'name' => 'memberq',
        'type' => 'search',
        'value' => $memberquery,
        'class' => 'form-control',
        'placeholder' => get_string(
            'commerce_campaign_member_search_placeholder',
            'local_subscriptions'
        ),
    ]),
    'crm-campaign-member-filter-field is-wide'
);
echo html_writer::div(
    html_writer::label(
        get_string('status'),
        'grant-member-status',
        false,
        ['class' => 'form-label']
    )
    . html_writer::select(
        $memberstatusoptions,
        'memberstatus',
        $memberstatusfilter,
        false,
        [
            'id' => 'grant-member-status',
            'class' => 'form-select',
        ]
    ),
    'crm-campaign-member-filter-field'
);
echo html_writer::div(
    html_writer::link(
        $memberfilterurl,
        get_string('reset'),
        ['class' => 'btn btn-outline-secondary btn-sm']
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
            'class' => 'btn btn-sm crm-grant-action-primary ms-2',
        ]
    ),
    'crm-campaign-member-filter-actions'
);
echo html_writer::end_div();
echo html_writer::end_tag('form');

$filteredmembers = array_values(array_filter(
    $members,
    static function(\stdClass $member) use (
        $memberquery,
        $memberstatusfilter
    ): bool {
        $fullname = trim(
            (string)$member->firstname
            . ' '
            . (string)$member->lastname
        );
        $haystack = core_text::strtolower(
            trim(
                $fullname . ' '
                . (string)$member->email . ' '
                . (string)$member->userid
            )
        );
        if (
            $memberquery !== ''
            && !str_contains(
                $haystack,
                core_text::strtolower($memberquery)
            )
        ) {
            return false;
        }
        if (
            $memberstatusfilter !== ''
            && (string)$member->status !== $memberstatusfilter
        ) {
            return false;
        }
        return true;
    }
));

$allowedmembersorts = ['client', 'status', 'details'];
if (!in_array($campaignmembersort, $allowedmembersorts, true)) {
    $campaignmembersort = 'client';
}
usort(
    $filteredmembers,
    static function(\stdClass $a, \stdClass $b) use (
        $campaignmembersort,
        $campaignmemberdir
    ): int {
        if ($campaignmembersort === 'status') {
            $avalue = (string)$a->status;
            $bvalue = (string)$b->status;
        } else if ($campaignmembersort === 'details') {
            $avalue = sprintf(
                '%08d %s',
                (int)$a->attempts,
                (string)$a->lasterror
            );
            $bvalue = sprintf(
                '%08d %s',
                (int)$b->attempts,
                (string)$b->lasterror
            );
        } else {
            $avalue = trim(
                (string)$a->lastname
                . ' '
                . (string)$a->firstname
                . ' '
                . (string)$a->email
            );
            $bvalue = trim(
                (string)$b->lastname
                . ' '
                . (string)$b->firstname
                . ' '
                . (string)$b->email
            );
        }
        $comparison = strnatcasecmp($avalue, $bvalue);
        return $campaignmemberdir === 'desc'
            ? -$comparison
            : $comparison;
    }
);

$filteredmembercount = count($filteredmembers);
$maxmemberpage = max(
    0,
    (int)ceil($filteredmembercount / $campaignmemberperpage) - 1
);
$campaignmemberpage = min($campaignmemberpage, $maxmemberpage);
$pagedmembers = array_slice(
    $filteredmembers,
    $campaignmemberpage * $campaignmemberperpage,
    $campaignmemberperpage
);

$table = new html_table();
$table->attributes['class'] = 'generaltable table table-hover align-middle crm-grant-campaign-members-table';
$table->head = [
    $campaignsortheader(
        'client',
        get_string('commerce_bulk_grant_customer', 'local_subscriptions'),
        $allowedmembersorts,
        $url,
        $campaignmembersort,
        $campaignmemberdir
    ),
    $campaignsortheader(
        'status',
        get_string('status'),
        $allowedmembersorts,
        $url,
        $campaignmembersort,
        $campaignmemberdir
    ),
    $campaignsortheader(
        'details',
        get_string(
            'commerce_offers_access_campaign_member_details',
            'local_subscriptions'
        ),
        $allowedmembersorts,
        $url,
        $campaignmembersort,
        $campaignmemberdir
    ),
];

foreach ($pagedmembers as $member) {
    $fullname = trim((string)$member->firstname . ' ' . (string)$member->lastname);

    $statuskey = 'commerce_bulk_grant_member_status_' . (string)$member->status;
    $memberstatus = get_string_manager()->string_exists($statuskey, 'local_subscriptions')
        ? get_string($statuskey, 'local_subscriptions')
        : (string)$member->status;

    $badgeclass = match ((string)$member->status) {
        CommerceBulkGrantCampaignService::MEMBER_COMPLETED => 'bg-success',
        CommerceBulkGrantCampaignService::MEMBER_SKIPPED => 'bg-secondary',
        CommerceBulkGrantCampaignService::MEMBER_FAILED => 'bg-danger',
        default => 'bg-warning text-dark',
    };

    $evidence = json_decode((string)$member->evidencejson, true);
    $evidencehtml = is_array($evidence) && $evidence !== []
        ? implode('<br>', array_map(
            static fn(string $value): string => html_writer::tag('code', s($value)),
            $evidence
        ))
        : '—';

    $membername = s($fullname !== '' ? $fullname : '—');
    if ((int)$member->userid > 0) {
        $membername = html_writer::link(
            new moodle_url(
                subscription_config::admin_user_view_page(),
                ['id' => (int)$member->userid]
            ),
            $membername,
            ['class' => 'crm-offers-access-preview-client-link']
        );
    }

    $client = html_writer::div(
        $membername,
        'crm-offers-access-preview-client-name'
    )
    . html_writer::div(
        s((string)$member->email),
        'crm-offers-access-preview-client-email'
    );

    $details = CommerceOffersAccessCampaignRenderer::technical(
        get_string('commerce_offers_access_campaign_member_details', 'local_subscriptions'),
        html_writer::div(
            get_string('commerce_bulk_grant_campaign_attempts', 'local_subscriptions')
            . ': ' . (int)$member->attempts,
            'mb-2'
        )
        . html_writer::div($evidencehtml, 'mb-2')
        . (trim((string)$member->lasterror) !== ''
            ? html_writer::div(s((string)$member->lasterror), 'text-danger')
            : '')
    );

    $table->data[] = [
        $client,
        html_writer::span(
            s($memberstatus),
            'badge crm-grant-campaign-status-badge ' . $badgeclass
        ),
        $details,
    ];
}

echo html_writer::table($table);

$paginationparams = $url->params();
$paginationparams['memberperpage'] = $campaignmemberperpage;
$paginationparams['membersort'] = $campaignmembersort;
$paginationparams['memberdir'] = $campaignmemberdir;
$paginationurl = new moodle_url(
    '/local/subscriptions/admin/commerce/grants/campaign_view.php',
    $paginationparams
);

echo html_writer::div(
    html_writer::div(
        get_string(
            'commerce_campaign_member_results',
            'local_subscriptions',
            (object)[
                'visible' => count($pagedmembers),
                'total' => $filteredmembercount,
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
            'memberperpage',
            $campaignmemberperpage,
            false,
            [
                'class' => 'form-select form-select-sm crm-campaign-perpage-select',
                'onchange' => 'window.location.href='
                    . json_encode(
                        (new moodle_url(
                            '/local/subscriptions/admin/commerce/grants/campaign_view.php',
                            array_merge($paginationparams, ['memberpage' => 0])
                        ))->out(false)
                    )
                    . '.replace(/memberperpage=\\d+/,'
                    . '"memberperpage="+this.value);',
            ]
        ),
        'crm-campaign-member-perpage'
    ),
    'crm-campaign-member-pagination-meta'
);

if ($filteredmembercount > $campaignmemberperpage) {
    echo $OUTPUT->paging_bar(
        $filteredmembercount,
        $campaignmemberpage,
        $campaignmemberperpage,
        $paginationurl,
        'memberpage'
    );
}

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
