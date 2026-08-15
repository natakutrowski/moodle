<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\mail\admin\CommerceMailHealthRenderer;
use local_subscriptions\commerce\mail\admin\CommerceMailSectionNavigationRenderer;
use local_subscriptions\commerce\mail\campaign\CommerceMarketingCampaignRepository;
use local_subscriptions\commerce\mail\certification\CommerceMailEngineCertificationService;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::MANAGE_SUBSCRIPTIONS);
$pageurl = new moodle_url('/local/subscriptions/admin/commerce/mail/campaigns/index.php');
$title = get_string('commerce_marketing_campaigns_title', 'local_subscriptions');

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $title,
    'local-subscriptions-commerce-mail-campaigns-page'
);
$PAGE->requires->css('/local/subscriptions/styles/commerce_mail_admin.css');

$repository = new CommerceMarketingCampaignRepository($DB);
$campaigns = $repository->all();
$healthreport = (new CommerceMailEngineCertificationService($DB))->certify();

$statusbadge = static function(string $status): string {
    $tone = match ($status) {
        'draft' => 'is-muted',
        'scheduled' => 'is-warning',
        'queued' => 'is-info',
        'completed' => 'is-success',
        'cancelled' => 'is-danger',
        default => 'is-muted',
    };
    $key = 'commerce_marketing_campaign_status_' . $status;
    return html_writer::span(
        get_string($key, 'local_subscriptions'),
        'commerce-mail-campaign-status ' . $tone
    );
};

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    [
        'label' => get_string('crm_commerce_title', 'local_subscriptions'),
        'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php'),
    ],
    [
        'label' => get_string('commerce_mail_admin_title', 'local_subscriptions'),
        'url' => new moodle_url('/local/subscriptions/admin/commerce/mail/index.php'),
    ],
    ['label' => $title, 'url' => null],
]);
echo CrmPageHeader::render(
    $title,
    get_string('commerce_marketing_campaigns_description', 'local_subscriptions'),
    HelpContext::COMMERCE
);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::MAIL);
echo html_writer::div(
    CommerceMailSectionNavigationRenderer::render(
        CommerceMailSectionNavigationRenderer::CAMPAIGNS
    )
    . CommerceMailHealthRenderer::render_compact($healthreport),
    'commerce-mail-workspace-nav-row'
);

echo html_writer::div(
    html_writer::link(
        new moodle_url('/local/subscriptions/admin/commerce/mail/campaigns/edit.php'),
        html_writer::tag('i', '', ['class' => 'fa fa-plus me-1', 'aria-hidden' => 'true'])
            . get_string('commerce_marketing_campaign_new', 'local_subscriptions'),
        ['class' => 'btn btn-primary']
    ),
    'commerce-mail-campaign-toolbar'
);

if ($campaigns === []) {
    echo html_writer::div(
        get_string('commerce_marketing_campaign_empty', 'local_subscriptions'),
        'commerce-mail-library-empty'
    );
} else {
    echo html_writer::start_div('commerce-mail-campaign-list');
    foreach ($campaigns as $campaign) {
        $stats = $repository->statistics((int)$campaign->id);
        $schedule = !empty($campaign->scheduledat)
            ? userdate((int)$campaign->scheduledat, get_string('strftimedatetimeshort', 'langconfig'))
            : '—';
        echo html_writer::tag(
            'article',
            html_writer::div(
                html_writer::div(
                    html_writer::tag('h3', s((string)$campaign->name), ['class' => 'h6 mb-1'])
                    . html_writer::div(
                        get_string(
                            'commerce_marketing_campaign_schedule_summary',
                            'local_subscriptions',
                            $schedule
                        ),
                        'commerce-mail-campaign-meta'
                    ),
                    'commerce-mail-campaign-main'
                )
                . $statusbadge((string)$campaign->status)
                . html_writer::div(
                    html_writer::span(
                        get_string(
                            'commerce_marketing_campaign_recipient_count',
                            'local_subscriptions',
                            $stats['total']
                        ),
                        'commerce-mail-campaign-stat'
                    )
                    . html_writer::span(
                        get_string(
                            'commerce_marketing_campaign_sent_count',
                            'local_subscriptions',
                            $stats['sent']
                        ),
                        'commerce-mail-campaign-stat'
                    )
                    . html_writer::span(
                        get_string(
                            'commerce_marketing_campaign_failed_count',
                            'local_subscriptions',
                            $stats['failed']
                        ),
                        'commerce-mail-campaign-stat'
                    ),
                    'commerce-mail-campaign-stats'
                )
                . html_writer::link(
                    new moodle_url(
                        '/local/subscriptions/admin/commerce/mail/campaigns/edit.php',
                        ['id' => (int)$campaign->id]
                    ),
                    get_string('view'),
                    ['class' => 'btn btn-sm btn-outline-secondary']
                ),
                'commerce-mail-campaign-row'
            )
        );
    }
    echo html_writer::end_div();
}

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
