<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
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

$context = AdminSecurity::require(Capabilities::MANAGE_SUBSCRIPTIONS);
$id = required_param('id', PARAM_INT);
$service = new CommerceBulkGrantCampaignService($DB);
$campaign = $service->get_campaign($id);
$url = new moodle_url('/local/subscriptions/admin/commerce/grants/campaign_view.php', ['id' => $id]);

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
$source = json_decode((string)$campaign->sourcejson, true) ?: [];
$target = json_decode((string)$campaign->targetjson, true) ?: [];

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
    ['label' => (string)$campaign->name, 'url' => null],
]);
echo CrmPageHeader::render(
    (string)$campaign->name,
    get_string('commerce_bulk_grant_campaign_view_help', 'local_subscriptions'),
    HelpContext::COMMERCE
);
echo CommerceSectionNavigationRenderer::render(
    CommerceSectionNavigationRenderer::GRANTS,
    $context
);

echo CommerceDesignSystemRenderer::action_bar([
    [
        'label' => get_string('commerce_bulk_grant_new', 'local_subscriptions'),
        'url' => new moodle_url('/local/subscriptions/admin/commerce/grants/bulk.php'),
        'class' => 'btn btn-outline-primary',
    ],
    [
        'label' => get_string('commerce_grants_manual_action', 'local_subscriptions'),
        'url' => new moodle_url(subscription_config::add_manual_subscription_page()),
        'class' => 'btn btn-outline-secondary',
    ],
], 'mb-3');

$statuskey = 'commerce_bulk_grant_campaign_status_' . (string)$campaign->status;
$statuslabel = get_string_manager()->string_exists($statuskey, 'local_subscriptions')
    ? get_string($statuskey, 'local_subscriptions')
    : (string)$campaign->status;

$sourcehtml = html_writer::tag('dl',
    html_writer::tag('dt', get_string('commerce_bulk_grant_campaign_source', 'local_subscriptions')) .
    html_writer::tag('dd', s((string)($source['name'] ?? $source['sku'] ?? ('#' . $campaign->sourceid)))) .
    html_writer::tag('dt', get_string('commerce_bulk_grant_target_product', 'local_subscriptions')) .
    html_writer::tag('dd',
        s((string)($target['name'] ?? '')) .
        html_writer::div(s((string)($target['sku'] ?? '')), 'small text-muted')
    ) .
    html_writer::tag('dt', get_string('commerce_bulk_grant_campaign_reason', 'local_subscriptions')) .
    html_writer::tag('dd', trim((string)$campaign->reason) !== '' ? s((string)$campaign->reason) : '—') .
    html_writer::tag('dt', get_string('commerce_bulk_grant_email_notification', 'local_subscriptions')) .
    html_writer::tag(
        'dd',
        !empty($campaign->sendemail)
            ? get_string('yes')
            : get_string('no')
    ) .
    html_writer::tag('dt', get_string('status')) .
    html_writer::tag('dd', s($statuslabel)),
    ['class' => 'mb-0']
);
echo CommerceDesignSystemRenderer::panel(
    get_string('commerce_bulk_grant_campaign_snapshot_title', 'local_subscriptions'),
    $sourcehtml,
    'mb-4'
);

echo CommerceDesignSystemRenderer::metrics([
    ['label' => get_string('commerce_bulk_grant_metric_total', 'local_subscriptions'), 'value' => $summary['total']],
    ['label' => get_string('commerce_bulk_grant_campaign_metric_queued', 'local_subscriptions'), 'value' => $summary['queued']],
    ['label' => get_string('commerce_bulk_grant_campaign_metric_success', 'local_subscriptions'), 'value' => $summary['completed']],
    ['label' => get_string('commerce_bulk_grant_campaign_metric_skipped', 'local_subscriptions'), 'value' => $summary['skipped']],
    ['label' => get_string('commerce_bulk_grant_metric_error', 'local_subscriptions'), 'value' => $summary['failed']],
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
            'class' => 'btn btn-danger',
            'onclick' => 'return confirm(' . json_encode(
                get_string('commerce_bulk_grant_campaign_launch_confirm', 'local_subscriptions')
            ) . ');',
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
        ['type' => 'submit', 'class' => 'btn btn-outline-danger']
    );
    $actions .= html_writer::end_tag('form');
}

if ($actions !== '') {
    echo html_writer::div($actions, 'mb-4');
}

if (in_array((string)$campaign->status, [
    CommerceBulkGrantCampaignService::STATUS_QUEUED,
    CommerceBulkGrantCampaignService::STATUS_RUNNING,
], true)) {
    echo html_writer::div(
        get_string('commerce_bulk_grant_campaign_cron_notice', 'local_subscriptions'),
        'alert alert-info mt-3'
    );
}

$table = new html_table();
$table->attributes['class'] = 'generaltable table table-hover align-middle';
$table->head = [
    get_string('commerce_bulk_grant_customer', 'local_subscriptions'),
    get_string('email'),
    get_string('commerce_bulk_grant_moodle_account', 'local_subscriptions'),
    get_string('commerce_bulk_grant_evidence', 'local_subscriptions'),
    get_string('status'),
    get_string('commerce_bulk_grant_campaign_attempts', 'local_subscriptions'),
    get_string('commerce_bulk_grant_campaign_error', 'local_subscriptions'),
];

foreach ($members as $member) {
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

    $table->data[] = [
        s($fullname !== '' ? $fullname : '—'),
        s((string)$member->email),
        html_writer::link(
            new moodle_url(subscription_config::admin_user_view_page(), ['id' => (int)$member->userid]),
            '#' . (int)$member->userid
        ),
        $evidencehtml,
        html_writer::span(s($memberstatus), 'badge ' . $badgeclass),
        (int)$member->attempts,
        trim((string)$member->lasterror) !== '' ? s((string)$member->lasterror) : '—',
    ];
}

echo html_writer::table($table);

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
