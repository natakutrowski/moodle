<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\mail\admin\CommerceMailHealthRenderer;
use local_subscriptions\commerce\mail\admin\CommerceMailSectionNavigationRenderer;
use local_subscriptions\commerce\mail\campaign\CommerceMarketingCampaignRepository;
use local_subscriptions\commerce\mail\campaign\CommerceMarketingCampaignService;
use local_subscriptions\commerce\mail\certification\CommerceMailEngineCertificationService;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::MANAGE_SUBSCRIPTIONS);
$id = optional_param('id', 0, PARAM_INT);
$service = CommerceMarketingCampaignService::create($DB);
$repository = new CommerceMarketingCampaignRepository($DB);
$campaign = $id > 0 ? $repository->get($id) : null;
$editable = $campaign === null || (string)$campaign->status === 'draft';

$pageurl = new moodle_url(
    '/local/subscriptions/admin/commerce/mail/campaigns/edit.php',
    $id > 0 ? ['id' => $id] : []
);
$title = $campaign
    ? (string)$campaign->name
    : get_string('commerce_marketing_campaign_new', 'local_subscriptions');

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $title,
    'local-subscriptions-commerce-mail-campaign-edit-page'
);
$PAGE->requires->css('/local/subscriptions/styles/commerce_mail_admin.css');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();
    $mode = optional_param('mode', 'save', PARAM_ALPHA);
    try {
        $campaignid = $service->save([
            'name' => required_param('name', PARAM_TEXT),
            'templateid' => required_param('templateid', PARAM_INT),
            'ctaurl' => optional_param('ctaurl', '', PARAM_URL),
            'audience' => required_param('audience', PARAM_RAW),
        ], (int)$USER->id, $id > 0 ? $id : null);

        if ($mode === 'schedule') {
            $rawschedule = required_param('scheduledat', PARAM_RAW_TRIMMED);
            $timezone = \core_date::get_user_timezone_object();
            $date = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $rawschedule, $timezone);
            if (!$date) {
                throw new \invalid_parameter_exception('Invalid campaign schedule.');
            }
            $service->schedule($campaignid, $date->getTimestamp(), (int)$USER->id);
        }

        redirect(
            new moodle_url('/local/subscriptions/admin/commerce/mail/campaigns/edit.php', ['id' => $campaignid]),
            get_string(
                $mode === 'schedule'
                    ? 'commerce_marketing_campaign_scheduled'
                    : 'commerce_marketing_campaign_saved',
                'local_subscriptions'
            ),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    } catch (\Throwable $exception) {
        \core\notification::error($exception->getMessage());
    }
}

$templates = $service->template_options();
$recipients = $campaign ? $repository->recipients((int)$campaign->id) : [];
$audience = implode("\n", array_map(
    static fn(\stdClass $recipient): string => implode(';', [
        (string)$recipient->email,
        (string)$recipient->firstname,
        (string)$recipient->lastname,
        (string)$recipient->language,
    ]),
    $recipients
));
$scheduledvalue = $campaign && !empty($campaign->scheduledat)
    ? userdate((int)$campaign->scheduledat, '%Y-%m-%dT%H:%M')
    : userdate(time() + HOURSECS, '%Y-%m-%dT%H:%M');

$healthreport = (new CommerceMailEngineCertificationService($DB))->certify();

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
    [
        'label' => get_string('commerce_marketing_campaigns_title', 'local_subscriptions'),
        'url' => new moodle_url('/local/subscriptions/admin/commerce/mail/campaigns/index.php'),
    ],
    ['label' => $title, 'url' => null],
]);
echo CrmPageHeader::render(
    $title,
    get_string('commerce_marketing_campaign_edit_description', 'local_subscriptions'),
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

if (!$editable) {
    $stats = $repository->statistics((int)$campaign->id);
    echo html_writer::div(
        get_string(
            'commerce_marketing_campaign_locked',
            'local_subscriptions',
            (object)[
                'total' => $stats['total'],
                'sent' => $stats['sent'],
                'failed' => $stats['failed'],
            ]
        ),
        'alert alert-info'
    );
}

echo html_writer::start_tag('form', [
    'method' => 'post',
    'action' => $pageurl->out(false),
    'class' => 'commerce-mail-campaign-editor card card-body',
]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

echo html_writer::start_div('commerce-mail-campaign-editor-grid');
echo html_writer::div(
    html_writer::tag('label', get_string('commerce_marketing_campaign_name', 'local_subscriptions'), [
        'for' => 'marketing-campaign-name', 'class' => 'form-label',
    ])
    . html_writer::empty_tag('input', [
        'type' => 'text', 'id' => 'marketing-campaign-name', 'name' => 'name',
        'value' => $campaign ? (string)$campaign->name : '',
        'class' => 'form-control', 'required' => 'required',
        'disabled' => !$editable ? 'disabled' : null,
    ]),
    'commerce-mail-campaign-field'
);
echo html_writer::div(
    html_writer::tag('label', get_string('commerce_marketing_campaign_template', 'local_subscriptions'), [
        'for' => 'marketing-campaign-template', 'class' => 'form-label',
    ])
    . html_writer::select(
        $templates,
        'templateid',
        $campaign ? (int)$campaign->templateid : 0,
        ['' => get_string('choose')],
        [
            'id' => 'marketing-campaign-template',
            'class' => 'form-select',
            'required' => 'required',
            'disabled' => !$editable ? 'disabled' : null,
        ]
    ),
    'commerce-mail-campaign-field'
);
echo html_writer::div(
    html_writer::tag('label', get_string('commerce_marketing_campaign_cta_url', 'local_subscriptions'), [
        'for' => 'marketing-campaign-cta', 'class' => 'form-label',
    ])
    . html_writer::empty_tag('input', [
        'type' => 'url', 'id' => 'marketing-campaign-cta', 'name' => 'ctaurl',
        'value' => $campaign ? (string)$campaign->ctaurl : '',
        'class' => 'form-control',
        'placeholder' => 'https://www.campusfr.fr/…',
        'disabled' => !$editable ? 'disabled' : null,
    ])
    . html_writer::div(
        get_string('commerce_marketing_campaign_cta_help', 'local_subscriptions'),
        'form-text'
    ),
    'commerce-mail-campaign-field'
);
echo html_writer::div(
    html_writer::tag('label', get_string('commerce_marketing_campaign_schedule', 'local_subscriptions'), [
        'for' => 'marketing-campaign-schedule', 'class' => 'form-label',
    ])
    . html_writer::empty_tag('input', [
        'type' => 'datetime-local', 'id' => 'marketing-campaign-schedule',
        'name' => 'scheduledat', 'value' => $scheduledvalue,
        'class' => 'form-control',
        'disabled' => !$editable ? 'disabled' : null,
    ]),
    'commerce-mail-campaign-field'
);
echo html_writer::end_div();

echo html_writer::div(
    html_writer::tag('label', get_string('commerce_marketing_campaign_audience', 'local_subscriptions'), [
        'for' => 'marketing-campaign-audience', 'class' => 'form-label fw-semibold',
    ])
    . html_writer::tag('textarea', s($audience), [
        'id' => 'marketing-campaign-audience',
        'name' => 'audience',
        'rows' => 10,
        'class' => 'form-control font-monospace',
        'required' => 'required',
        'disabled' => !$editable ? 'disabled' : null,
    ])
    . html_writer::div(
        get_string('commerce_marketing_campaign_audience_help', 'local_subscriptions'),
        'form-text'
    ),
    'commerce-mail-campaign-audience'
);

if ($editable) {
    echo html_writer::div(
        html_writer::tag(
            'button',
            get_string('savechanges'),
            ['type' => 'submit', 'name' => 'mode', 'value' => 'save', 'class' => 'btn btn-outline-secondary']
        )
        . html_writer::tag(
            'button',
            html_writer::tag('i', '', ['class' => 'fa fa-clock-o me-1', 'aria-hidden' => 'true'])
                . get_string('commerce_marketing_campaign_schedule_button', 'local_subscriptions'),
            ['type' => 'submit', 'name' => 'mode', 'value' => 'schedule', 'class' => 'btn btn-primary']
        ),
        'commerce-mail-campaign-actions'
    );
}
echo html_writer::end_tag('form');

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
