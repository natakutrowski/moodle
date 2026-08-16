<?php
require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\personaloffer\campaign\CommercePersonalOfferCampaignManager;
use local_subscriptions\commerce\personaloffer\admin\CommercePersonalOfferBeneficiaryCorrectionService;
use local_subscriptions\commerce\personaloffer\mail\CommercePersonalOfferMailService;
use local_subscriptions\commerce\personaloffer\audience\CommercePersonalOfferAudienceProviderRegistry;
use local_subscriptions\commerce\mail\admin\CommerceMailAdminPresentation;
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

$context = AdminSecurity::require(Capabilities::VIEW_PAYMENTS);
$id = required_param('id', PARAM_INT);
$memberquery = trim(optional_param('memberq', '', PARAM_RAW_TRIMMED));
$memberstatusfilter = trim(optional_param('memberstatus', '', PARAM_ALPHANUMEXT));
$mailstatusfilter = trim(optional_param('mailstatus', '', PARAM_ALPHANUMEXT));

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


$manager = CommercePersonalOfferCampaignManager::create($DB);
$mailservice = CommercePersonalOfferMailService::create($DB);
$beneficiarycorrection = CommercePersonalOfferBeneficiaryCorrectionService::create($DB);
$campaign = $manager->get_campaign($id);
$url = new moodle_url(
    '/local/subscriptions/admin/commerce/personal-offers/campaign_view.php',
    array_filter([
        'id' => $id,
        'memberq' => $memberquery,
        'memberstatus' => $memberstatusfilter,
        'mailstatus' => $mailstatusfilter,
        'membersort' => $campaignmembersort,
        'memberdir' => $campaignmemberdir,
        'memberpage' => $campaignmemberpage,
        'memberperpage' => $campaignmemberperpage,
    ], static fn(mixed $value): bool => $value !== '')
);
CrmPageConfigurator::configure($PAGE, $context, $url, $campaign->name, 'local-subscriptions-commerce-personal-offer-campaign-view-page');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_capability(Capabilities::MANAGE_CRM_ADMIN_TOOLS, $context);
    require_sesskey();
    $action = required_param('action', PARAM_ALPHA);
    try {
        if ($action === 'preview') {
            $manager->preview($id, (int)$USER->id);
        } elseif ($action === 'snapshot') {
            $manager->create_snapshot($id, (int)$USER->id);
        } elseif ($action === 'schedule') {
            $raw = required_param('scheduledat', PARAM_RAW_TRIMMED);
            $tz = new DateTimeZone('Europe/Paris');
            $dt = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $raw, $tz);
            if (!$dt || $dt->getTimestamp() <= time()) { throw new coding_exception('La date programmée doit être dans le futur.'); }
            if (!empty($campaign->expiresat) && $dt->getTimestamp() >= (int)$campaign->expiresat) { throw new coding_exception('La campagne ne peut pas commencer après l’expiration de l’offre.'); }
            $DB->set_field('local_subs_commerce_offer_campaign', 'scheduledat', $dt->getTimestamp(), ['id'=>$id]);
            $DB->set_field('local_subs_commerce_offer_campaign', 'scheduledby', (int)$USER->id, ['id'=>$id]);
        } elseif ($action === 'unschedule') {
            $DB->set_field('local_subs_commerce_offer_campaign', 'scheduledat', null, ['id'=>$id]);
            $DB->set_field('local_subs_commerce_offer_campaign', 'scheduledby', null, ['id'=>$id]);
        } elseif ($action === 'generate') {
            $manager->generate($id, (int)$USER->id);
        } elseif ($action === 'retrygeneration') {
            $manager->retry_generation_errors($id, (int)$USER->id);
        } elseif ($action === 'selection') {
            $manager->update_member_selection(
                $id,
                optional_param_array('members', [], PARAM_INT),
                (int)$USER->id
            );
        } elseif ($action === 'selectionpage') {
            $manager->update_visible_member_selection(
                $id,
                optional_param_array('visiblemembers', [], PARAM_INT),
                optional_param_array('members', [], PARAM_INT),
                (int)$USER->id
            );
        } elseif ($action === 'queuemail') {
            $mailresult = $mailservice->queue_missing_campaign($id);
            redirect(
                $url,
                get_string(
                    'commerce_personal_offer_mail_campaign_queued',
                    'local_subscriptions',
                    (object)$mailresult
                ),
                null,
                \core\output\notification::NOTIFY_SUCCESS
            );
        } elseif ($action === 'retrymail') {
            $retry = $mailservice->retry_failed_campaign($id);
            redirect(
                $url,
                get_string(
                    'commerce_personal_offer_mail_campaign_retried',
                    'local_subscriptions',
                    (object)$retry
                ),
                null,
                \core\output\notification::NOTIFY_SUCCESS
            );
        } elseif ($action === 'certify') {
            $manager->certify_campaign(
                $id,
                (int)$USER->id,
                $mailservice->campaign_mail_summary($id)
            );
            redirect(
                $url,
                get_string('commerce_personal_offer_campaign_certified', 'local_subscriptions'),
                null,
                \core\output\notification::NOTIFY_SUCCESS
            );
        }
        redirect($url);
    } catch (Throwable $e) {
        redirect($url, $e->getMessage(), null, \core\output\notification::NOTIFY_ERROR);
    }
}

$summary = $manager->summary($id);
$mailsummary = $mailservice->campaign_mail_summary($id);
$certification = $manager->certification_state($id, $mailsummary);
$members = $manager->members($id);
$criteria = json_decode((string)$campaign->criteriajson, true) ?: [];
$sourcetype = (string)($criteria['sourcetype'] ?? '');
$sourceid = (int)($criteria['sourceid'] ?? 0);
$sourcedescription = '';
if ($campaign->audiencetype === 'criteria' && $sourcetype !== '' && $sourceid > 0) {
    try {
        $sourceinfo = CommercePersonalOfferAudienceProviderRegistry::create($DB)
            ->get($sourcetype)
            ->source($sourceid, current_language());
        $sourcedescription = (string)($sourceinfo['name'] ?? '');
    } catch (Throwable $ignored) {
        $sourcedescription = '';
    }
}
if ($sourcedescription === '' && !empty($campaign->sourceproductsku)) {
    $sourcedescription = (string)$campaign->sourceproductsku;
}

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    ['label' => get_string('crm_commerce_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php')],
    ['label' => get_string('commerce_offers_access_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/offers-access/index.php')],
    ['label' => get_string('commerce_offers_access_campaigns_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/offers-access/campaigns.php')],
    ['label' => $campaign->name, 'url' => null],
]);
echo CrmPageHeader::render($campaign->name, get_string('commerce_personal_offer_campaign_view_help', 'local_subscriptions'), HelpContext::COMMERCE);
echo CommerceSectionNavigationRenderer::render(
    CommerceSectionNavigationRenderer::OFFERS_ACCESS,
    $context
);
echo CommerceOffersAccessNavigationRenderer::render(
    CommerceOffersAccessNavigationRenderer::CAMPAIGNS
);

$statuslabelkey = 'commerce_personal_offer_campaign_status_' . (string)$campaign->status;
$statuslabel = get_string_manager()->string_exists(
    $statuslabelkey,
    'local_subscriptions'
) ? get_string($statuslabelkey, 'local_subscriptions') : (string)$campaign->status;

$offersteps = [
    [
        'key' => 'configuration',
        'label' => get_string('commerce_offers_access_campaign_step_configuration', 'local_subscriptions'),
        'detail' => get_string('commerce_offers_access_campaign_step_configuration_done', 'local_subscriptions'),
        'state' => 'complete',
    ],
    [
        'key' => 'audience',
        'label' => get_string('commerce_offers_access_campaign_step_audience', 'local_subscriptions'),
        'detail' => $summary['eligible'] > 0
            ? get_string('commerce_offers_access_campaign_step_audience_count', 'local_subscriptions', $summary['eligible'])
            : get_string('commerce_offers_access_campaign_step_audience_waiting', 'local_subscriptions'),
        'state' => in_array((string)$campaign->status, ['previewed','snapshot','issued','closed'], true)
            ? 'complete'
            : ((string)$campaign->status === 'draft' ? 'active' : 'pending'),
    ],
    [
        'key' => 'snapshot',
        'label' => get_string('commerce_offers_access_campaign_step_snapshot', 'local_subscriptions'),
        'detail' => !empty($campaign->snapshotat)
            ? get_string('commerce_offers_access_campaign_step_snapshot_done', 'local_subscriptions', (int)$campaign->selectedcount)
            : get_string('commerce_offers_access_campaign_step_snapshot_waiting', 'local_subscriptions'),
        'state' => in_array((string)$campaign->status, ['snapshot','issued','closed'], true)
            ? 'complete'
            : ((string)$campaign->status === 'previewed' ? 'active' : 'pending'),
    ],
    [
        'key' => 'execution',
        'label' => get_string('commerce_offers_access_campaign_step_execution', 'local_subscriptions'),
        'detail' => in_array((string)$campaign->status, ['issued','closed'], true)
            ? get_string('commerce_offers_access_campaign_step_execution_done', 'local_subscriptions', $summary['issued'] + $summary['replayed'])
            : get_string('commerce_offers_access_campaign_step_execution_waiting', 'local_subscriptions'),
        'state' => (string)$campaign->status === 'closed'
            ? 'complete'
            : ((string)$campaign->status === 'issued' ? 'active' : 'pending'),
    ],
];

echo html_writer::div(
    html_writer::div(
        html_writer::span(
            html_writer::tag('i', '', ['class' => 'fa fa-tag', 'aria-hidden' => 'true']),
            'crm-offers-access-campaign-kind-icon is-offer'
        )
        . html_writer::div(
            html_writer::div(
                get_string('commerce_offers_access_kind_offer', 'local_subscriptions'),
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
    . CommerceOffersAccessCampaignRenderer::workflow($offersteps, 'offer'),
    'crm-offers-access-campaign-overview'
);

$summaryactions = '';
if (has_capability(Capabilities::MANAGE_CRM_ADMIN_TOOLS, $context)) {
    if ($campaign->status === 'issued' && $certification['ready']) {
        $summaryactions .= html_writer::start_tag('form', [
            'method' => 'post',
            'class' => 'd-inline-block',
        ]);
        $summaryactions .= html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'sesskey',
            'value' => sesskey(),
        ]);
        $summaryactions .= html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'action',
            'value' => 'certify',
        ]);
        $summaryactions .= html_writer::tag(
            'button',
            html_writer::tag('i', '', [
                'class' => 'fa fa-check-circle me-1',
                'aria-hidden' => 'true',
            ])
            . get_string(
                'commerce_personal_offer_certify_campaign',
                'local_subscriptions'
            ),
            [
                'class' => 'btn btn-success btn-sm',
                'onclick' => 'return confirm(' . json_encode(
                    get_string(
                        'commerce_personal_offer_certify_confirm',
                        'local_subscriptions'
                    )
                ) . ');',
            ]
        );
        $summaryactions .= html_writer::end_tag('form');
    }
}

$certificationstatus = '';
if (in_array((string)$campaign->status, ['issued', 'closed'], true)) {
    $certificationstatus = html_writer::div(
        html_writer::tag(
            'i',
            '',
            [
                'class' => 'fa '
                    . ($certification['ready'] ? 'fa-check-circle' : 'fa-info-circle')
                    . ' me-1',
                'aria-hidden' => 'true',
            ]
        )
        . (
            $certification['ready']
                ? get_string(
                    'commerce_personal_offer_certification_ready',
                    'local_subscriptions'
                )
                : get_string(
                    'commerce_personal_offer_certification_blocked',
                    'local_subscriptions',
                    (object)[
                        'generationerrors' => $certification['generationerrors'],
                        'selectedpending' => $certification['selectedpending'],
                        'mailblocking' => $certification['mailblocking'],
                    ]
                )
        ),
        'crm-offer-campaign-certification-status'
            . ($certification['ready'] ? ' is-ready' : '')
    );
}

$summarybody = html_writer::div(
    html_writer::div(
        html_writer::span(
            get_string('commerce_offers_access_config_audience', 'local_subscriptions'),
            'crm-offers-access-campaign-summary-label'
        )
        . html_writer::span(
            s(
                $sourcedescription !== ''
                    ? $sourcedescription
                    : get_string(
                        'commerce_offers_access_config_not_set',
                        'local_subscriptions'
                    )
            ),
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
            (string)((int)$campaign->selectedcount),
            'crm-offers-access-campaign-summary-value'
        ),
        'crm-offers-access-campaign-summary-row'
    )
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
            get_string('commerce_offers_access_campaign_status', 'local_subscriptions'),
            'crm-offers-access-campaign-summary-label'
        )
        . html_writer::span(
            s($statuslabel),
            'crm-offers-access-campaign-summary-value'
        ),
        'crm-offers-access-campaign-summary-row'
    ),
    'crm-offers-access-campaign-summary'
);

if ($certificationstatus !== '' || $summaryactions !== '') {
    $summarybody .= html_writer::div(
        $certificationstatus . $summaryactions,
        'crm-offer-campaign-summary-footer'
    );
}

$summarybody .= CommerceOffersAccessCampaignRenderer::technical(
    get_string('commerce_offers_access_config_technical', 'local_subscriptions'),
    html_writer::tag(
        'dl',
        html_writer::tag(
            'dt',
            get_string('commerce_personal_offer_campaign_key', 'local_subscriptions')
        )
        . html_writer::tag(
            'dd',
            html_writer::tag('code', s($campaign->campaignkey))
        )
        . html_writer::tag(
            'dt',
            get_string('commerce_personal_offer_source_type', 'local_subscriptions')
        )
        . html_writer::tag(
            'dd',
            s($sourcetype !== '' ? $sourcetype : '—')
        ),
        ['class' => 'mb-0']
    )
);

echo CommerceDesignSystemRenderer::panel(
    get_string('commerce_offers_access_campaign_summary_title', 'local_subscriptions'),
    $summarybody,
    'crm-offer-campaign-summary-panel'
);

echo CommerceOffersAccessCampaignRenderer::metrics([
    [
        'label' => get_string('commerce_personal_offer_metric_total', 'local_subscriptions'),
        'value' => $summary['total'],
        'class' => 'is-primary',
    ],
    [
        'label' => get_string('commerce_personal_offer_metric_eligible', 'local_subscriptions'),
        'value' => $summary['eligible'],
    ],
    [
        'label' => get_string('commerce_personal_offer_metric_issued', 'local_subscriptions'),
        'value' => $summary['issued'] + $summary['replayed'],
        'class' => 'is-success is-emphasis',
    ],
    [
        'label' => get_string('commerce_personal_offer_metric_excluded', 'local_subscriptions'),
        'value' => $summary['excluded'],
    ],
    [
        'label' => get_string('commerce_personal_offer_metric_error', 'local_subscriptions'),
        'value' => $summary['error'],
        'class' => $summary['error'] > 0 ? 'is-error' : '',
    ],
]);

echo html_writer::div(
    get_string(
        'commerce_offer_campaign_secondary_counts',
        'local_subscriptions',
        (object)[
            'covered' => (int)$summary['covered'],
            'identity' => (int)$summary['identity_review'],
        ]
    ),
    'crm-offer-campaign-secondary-counts'
);

$emailconfig = \local_subscriptions\commerce\personaloffer\campaign\CommercePersonalOfferCampaignEmailService::create($DB)->get($id);
$emailtranslations = array_keys($emailconfig['translations']);
$emaildestination = $emailconfig['config']
    ? (string)$emailconfig['config']->ctadestination
    : \local_subscriptions\commerce\personaloffer\campaign\CommercePersonalOfferCampaignEmailService::DESTINATION_CHECKOUT;
$emailshowroomname = '';
if ($emaildestination === \local_subscriptions\commerce\personaloffer\campaign\CommercePersonalOfferCampaignEmailService::DESTINATION_SHOWROOM && $emailconfig['config'] && !empty($emailconfig['config']->showroomid)) {
    $emailshowroomname = (string)$DB->get_field('local_subs_showroom', 'name', ['id' => (int)$emailconfig['config']->showroomid], IGNORE_MISSING);
}
$emailready = $emailtranslations !== [];
$destinationready = $emaildestination !== \local_subscriptions\commerce\personaloffer\campaign\CommercePersonalOfferCampaignEmailService::DESTINATION_SHOWROOM || $emailshowroomname !== '';
$audienceready = $summary['eligible'] > 0 || in_array((string)$campaign->status, ['snapshot', 'issued', 'closed'], true);
$snapshotready = !empty($campaign->snapshotat) || in_array((string)$campaign->status, ['snapshot', 'issued', 'closed'], true);
$issuedready = in_array((string)$campaign->status, ['issued', 'closed'], true);
$workflowitems = [
    [true, 'commerce_personal_offer_workflow_commercial', 'commerce_personal_offer_workflow_commercial_ready'],
    [$emailready && $destinationready, 'commerce_personal_offer_workflow_email', $emailready && $destinationready ? 'commerce_personal_offer_workflow_email_ready' : 'commerce_personal_offer_workflow_email_missing'],
    [$audienceready, 'commerce_personal_offer_workflow_audience', $audienceready ? 'commerce_personal_offer_workflow_audience_ready' : 'commerce_personal_offer_workflow_audience_missing'],
    [$snapshotready, 'commerce_personal_offer_workflow_snapshot', $snapshotready ? 'commerce_personal_offer_workflow_snapshot_ready' : 'commerce_personal_offer_workflow_snapshot_missing'],
    [$issuedready, 'commerce_personal_offer_workflow_issue', $issuedready ? 'commerce_personal_offer_workflow_issue_ready' : 'commerce_personal_offer_workflow_issue_missing'],
];
$workflowcontent = html_writer::start_div('list-group list-group-flush');
foreach ($workflowitems as [$ready, $labelkey, $detailkey]) {
    $detail = $detailkey === 'commerce_personal_offer_workflow_audience_ready'
        ? get_string($detailkey, 'local_subscriptions', (int)$summary['eligible'])
        : get_string($detailkey, 'local_subscriptions');
    $workflowcontent .= html_writer::div(
        html_writer::tag('strong', ($ready ? '✓ ' : '○ ') . get_string($labelkey, 'local_subscriptions'), ['class' => $ready ? 'text-success' : 'text-muted']) .
        html_writer::div($detail, 'small text-muted ms-4'),
        'list-group-item px-0'
    );
}
$workflowcontent .= html_writer::end_div();
if (has_capability(\local_subscriptions\admin\Capabilities::MANAGE_CRM_ADMIN_TOOLS, $context)) {
    $workflowcontent .= html_writer::start_div('d-flex flex-wrap gap-2 mt-3');
    $workflowcontent .= html_writer::link(new moodle_url('/local/subscriptions/admin/commerce/personal-offers/campaign_email.php', ['id' => $id]), get_string('commerce_personal_offer_workflow_configure_email', 'local_subscriptions'), ['class' => 'btn btn-outline-primary']);
    $workflowcontent .= html_writer::link(new moodle_url('/local/subscriptions/admin/commerce/personal-offers/campaign_email_preview.php', ['id' => $id]), get_string('commerce_personal_offer_workflow_preview_test', 'local_subscriptions'), ['class' => 'btn btn-outline-secondary']);
    $workflowcontent .= html_writer::link(new moodle_url($url, ['anchor' => 'campaign-audience']), get_string('commerce_personal_offer_workflow_view_audience', 'local_subscriptions'), ['class' => 'btn btn-outline-secondary']);
    $workflowcontent .= html_writer::end_div();
}

$campaignpreviewurl = new moodle_url(
    '/local/subscriptions/admin/commerce/personal-offers/campaign_email_preview.php',
    [
        'id' => $id,
        'language' => $emailtranslations[0] ?? 'fr',
        'embed' => 1,
    ]
);

echo CommerceOffersAccessCampaignRenderer::communication(
    (int)($mailsummary['queued'] ?? 0),
    (int)($mailsummary['sent'] ?? 0),
    (int)($mailsummary['failed'] ?? 0),
    new moodle_url('/local/subscriptions/admin/commerce/mail/index.php', [
        'q' => (string)$campaign->campaignkey,
    ]),
    new moodle_url('/local/subscriptions/admin/commerce/personal-offers/campaign_email.php', [
        'id' => $id,
    ]),
    $campaignpreviewurl
);



if ($campaign->status === 'snapshot' || !empty($campaign->snapshotat)) {
    echo CommerceOffersAccessCampaignRenderer::technical(
        get_string('commerce_personal_offer_snapshot_title', 'local_subscriptions'),
        html_writer::tag(
            'dl',
            html_writer::tag('dt', get_string('commerce_personal_offer_snapshot_selected', 'local_subscriptions')) .
            html_writer::tag('dd', (int)$campaign->selectedcount) .
            html_writer::tag('dt', get_string('commerce_personal_offer_snapshot_date', 'local_subscriptions')) .
            html_writer::tag(
                'dd',
                !empty($campaign->snapshotat)
                    ? userdate((int)$campaign->snapshotat)
                    : '—'
            ) .
            html_writer::tag('dt', get_string('commerce_personal_offer_snapshot_hash', 'local_subscriptions')) .
            html_writer::tag(
                'dd',
                !empty($campaign->snapshothash)
                    ? html_writer::tag('code', s((string)$campaign->snapshothash))
                    : '—'
            ),
            ['class' => 'mb-0']
        )
    );

    if ($campaign->status === 'snapshot') {
        echo html_writer::div(
            get_string('commerce_personal_offer_snapshot_frozen_notice', 'local_subscriptions'),
            'alert alert-warning mt-3'
        );
    }
}


if (has_capability(Capabilities::MANAGE_CRM_ADMIN_TOOLS, $context) && $campaign->status === 'snapshot') {
    $schedulehtml = !empty($campaign->scheduledat)
        ? html_writer::div('Envoi programmé : <strong>' . userdate((int)$campaign->scheduledat) . '</strong> (Europe/Paris). Les offres seront générées puis les emails entreront automatiquement dans la file.', 'alert alert-info')
        : html_writer::div('Vous pouvez lancer maintenant ou programmer le démarrage. Une fois l’heure atteinte, la génération et la mise en file des emails sont automatiques.', 'text-muted mb-3');
    if (empty($campaign->scheduledat)) {
        $schedulehtml .= html_writer::start_tag('form', ['method'=>'post','class'=>'d-flex gap-2 align-items-end flex-wrap']);
        $schedulehtml .= html_writer::empty_tag('input',['type'=>'hidden','name'=>'sesskey','value'=>sesskey()]);
        $schedulehtml .= html_writer::empty_tag('input',['type'=>'hidden','name'=>'action','value'=>'schedule']);
        $schedulehtml .= html_writer::div(html_writer::tag('label','Début des envois (heure de Paris)',['class'=>'form-label fw-semibold']) . html_writer::empty_tag('input',['type'=>'datetime-local','name'=>'scheduledat','class'=>'form-control','required'=>'required']), '');
        $schedulehtml .= html_writer::tag('button','Programmer',['class'=>'btn btn-primary','type'=>'submit']);
        $schedulehtml .= html_writer::end_tag('form');
    } else {
        $schedulehtml .= html_writer::start_tag('form',['method'=>'post']) . html_writer::empty_tag('input',['type'=>'hidden','name'=>'sesskey','value'=>sesskey()]) . html_writer::empty_tag('input',['type'=>'hidden','name'=>'action','value'=>'unschedule']) . html_writer::tag('button','Annuler la programmation',['class'=>'btn btn-outline-danger','type'=>'submit']) . html_writer::end_tag('form');
    }
    echo CommerceDesignSystemRenderer::panel('Programmation', $schedulehtml, 'mt-3');
}

if (has_capability(Capabilities::MANAGE_CRM_ADMIN_TOOLS, $context)) {
    $actions = html_writer::start_div('d-flex flex-wrap gap-2 mt-3');

    if (in_array($campaign->status, ['draft', 'previewed'], true)) {
        $actions .= html_writer::start_tag('form', ['method' => 'post']);
        $actions .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        $actions .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'preview']);
        $actions .= html_writer::tag(
            'button',
            get_string('commerce_personal_offer_preview', 'local_subscriptions'),
            ['class' => 'btn btn-outline-primary']
        );
        $actions .= html_writer::end_tag('form');
    }

    if ($campaign->status === 'previewed' && $summary['eligible'] > 0) {
        $actions .= html_writer::start_tag('form', ['method' => 'post']);
        $actions .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        $actions .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'snapshot']);
        $actions .= html_writer::tag(
            'button',
            get_string('commerce_personal_offer_create_snapshot', 'local_subscriptions'),
            [
                'class' => 'btn btn-primary',
                'onclick' => 'return confirm(' . json_encode(
                    get_string('commerce_personal_offer_snapshot_confirm', 'local_subscriptions')
                ) . ');',
            ]
        );
        $actions .= html_writer::end_tag('form');
    }

    if ($campaign->status === 'snapshot' && empty($campaign->scheduledat)) {
        $actions .= html_writer::start_tag('form', ['method' => 'post']);
        $actions .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        $actions .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'generate']);
        $actions .= html_writer::tag(
            'button',
            get_string(
                'commerce_personal_offer_generate_snapshot',
                'local_subscriptions',
                (int)$campaign->selectedcount
            ),
            [
                'class' => 'btn btn-danger',
                'onclick' => 'return confirm(' . json_encode(
                    get_string('commerce_personal_offer_generate_snapshot_confirm', 'local_subscriptions')
                ) . ');',
            ]
        );
        $actions .= html_writer::end_tag('form');
    }

    if ($campaign->status === 'issued' && $summary['error'] > 0) {
        $actions .= html_writer::start_tag('form', ['method' => 'post']);
        $actions .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        $actions .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'retrygeneration']);
        $actions .= html_writer::tag(
            'button',
            get_string(
                'commerce_personal_offer_retry_generation',
                'local_subscriptions',
                $summary['error']
            ),
            ['class' => 'btn btn-outline-danger']
        );
        $actions .= html_writer::end_tag('form');
    }

    if (
        $campaign->status === 'issued'
        && ($summary['issued'] + $summary['replayed']) > 0
        && $mailsummary['notqueued'] > 0
    ) {
        $actions .= html_writer::start_tag('form', ['method' => 'post']);
        $actions .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        $actions .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'queuemail']);
        $actions .= html_writer::tag(
            'button',
            get_string(
                'commerce_personal_offer_mail_queue_missing',
                'local_subscriptions',
                $mailsummary['notqueued']
            ),
            [
                'class' => 'btn btn-dark',
                'onclick' => 'return confirm(' . json_encode(
                    get_string('commerce_personal_offer_mail_queue_confirm', 'local_subscriptions')
                ) . ');',
            ]
        );
        $actions .= html_writer::end_tag('form');
    }

    if ($campaign->status === 'issued' && $mailsummary['failed'] > 0) {
        $actions .= html_writer::start_tag('form', ['method' => 'post']);
        $actions .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        $actions .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'retrymail']);
        $actions .= html_writer::tag(
            'button',
            get_string(
                'commerce_personal_offer_mail_retry_failed',
                'local_subscriptions',
                $mailsummary['failed']
            ),
            ['class' => 'btn btn-outline-danger']
        );
        $actions .= html_writer::end_tag('form');
    }

    $actions .= html_writer::end_div();
    echo $actions;
}



// Mail monitoring is already rendered in the compact Communication panel above.
// Keep a single operational mail summary instead of duplicating seven large KPI cards.

echo html_writer::tag('div', '', ['id' => 'campaign-audience']);

if ($campaign->audiencetype === 'criteria') {
    echo html_writer::div(
        html_writer::tag('i', '', [
            'class' => 'fa fa-info-circle me-1',
            'aria-hidden' => 'true',
        ])
        . get_string(
            'commerce_personal_offer_criteria_generated_list_help',
            'local_subscriptions'
        ),
        'crm-offer-campaign-simulation-note'
    );
}

if ($members) {
    $memberstatusoptions = [
        '' => get_string('all'),
    ];
    foreach ($members as $memberoption) {
        $rawstatus = (string)$memberoption->eligibilitystatus;
        if ($rawstatus === '' || isset($memberstatusoptions[$rawstatus])) {
            continue;
        }
        $statuskey = 'commerce_personal_offer_member_status_' . $rawstatus;
        $memberstatusoptions[$rawstatus] =
            get_string_manager()->string_exists(
                $statuskey,
                'local_subscriptions'
            )
                ? get_string($statuskey, 'local_subscriptions')
                : ucfirst(str_replace('_', ' ', $rawstatus));
    }

    $mailstatusoptions = [
        '' => get_string('all'),
        'notqueued' => get_string(
            'commerce_personal_offer_mail_notqueued',
            'local_subscriptions'
        ),
        'notapplicable' => get_string(
            'commerce_campaign_mail_not_applicable',
            'local_subscriptions'
        ),
        'queued' => CommerceMailAdminPresentation::status_label('queued'),
        'processing' => CommerceMailAdminPresentation::status_label('processing'),
        'sent' => CommerceMailAdminPresentation::status_label('sent'),
        'failed' => CommerceMailAdminPresentation::status_label('failed'),
        'cancelled' => CommerceMailAdminPresentation::status_label('cancelled'),
    ];

    $memberfilterurl = new moodle_url(
        '/local/subscriptions/admin/commerce/personal-offers/campaign_view.php',
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
        'crm-campaign-member-filter-grid crm-campaign-member-filter-grid-offer'
    );
    echo html_writer::div(
        html_writer::label(
            get_string('search'),
            'offer-member-query',
            false,
            ['class' => 'form-label']
        )
        . html_writer::empty_tag('input', [
            'id' => 'offer-member-query',
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
            'offer-member-status',
            false,
            ['class' => 'form-label']
        )
        . html_writer::select(
            $memberstatusoptions,
            'memberstatus',
            $memberstatusfilter,
            false,
            [
                'id' => 'offer-member-status',
                'class' => 'form-select',
            ]
        ),
        'crm-campaign-member-filter-field'
    );
    echo html_writer::div(
        html_writer::label(
            get_string(
                'commerce_personal_offer_mail_status',
                'local_subscriptions'
            ),
            'offer-mail-status',
            false,
            ['class' => 'form-label']
        )
        . html_writer::select(
            $mailstatusoptions,
            'mailstatus',
            $mailstatusfilter,
            false,
            [
                'id' => 'offer-mail-status',
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
                'class' => 'btn btn-sm crm-offer-campaign-apply ms-2',
            ]
        ),
        'crm-campaign-member-filter-actions'
    );
    echo html_writer::end_div();
    echo html_writer::end_tag('form');

    $allowedmembersorts = [
        'client',
        'status',
        'offer',
        'email',
        'details',
    ];
    if (!in_array($campaignmembersort, $allowedmembersorts, true)) {
        $campaignmembersort = 'client';
    }

    $editable = has_capability(Capabilities::MANAGE_CRM_ADMIN_TOOLS, $context)
        && $campaign->status === 'previewed';
    if ($editable) {
        echo html_writer::start_tag('form', ['method' => 'post', 'class' => 'mt-4']);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        echo html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'action',
            'value' => 'selectionpage',
        ]);
    }
    $table = new html_table();
    $table->attributes['class'] =
        'generaltable table table-hover align-middle crm-offer-campaign-audience-table';
    $table->head = [
        $editable ? get_string('select') : '',
        $campaignsortheader(
            'client',
            get_string('commerce_personal_offer_customer', 'local_subscriptions'),
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
            'offer',
            get_string('commerce_personal_offer_id', 'local_subscriptions'),
            $allowedmembersorts,
            $url,
            $campaignmembersort,
            $campaignmemberdir
        ),
        $campaignsortheader(
            'email',
            get_string('commerce_personal_offer_mail_status', 'local_subscriptions'),
            $allowedmembersorts,
            $url,
            $campaignmembersort,
            $campaignmemberdir
        ),
        $campaignsortheader(
            'details',
            get_string('commerce_offer_campaign_details', 'local_subscriptions'),
            $allowedmembersorts,
            $url,
            $campaignmembersort,
            $campaignmemberdir
        ),
    ];
    $filteredmembers = array_values(array_filter(
        $members,
        static function(\stdClass $member) use (
            $memberquery,
            $memberstatusfilter,
            $mailstatusfilter,
            $mailservice,
            $id
        ): bool {
            $fullname = trim(
                (string)($member->firstname ?? '')
                . ' '
                . (string)($member->lastname ?? '')
            );
            $haystack = core_text::strtolower(
                trim(
                    $fullname . ' '
                    . (string)$member->email . ' '
                    . (string)($member->userid ?? '')
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
                && (string)$member->eligibilitystatus !== $memberstatusfilter
            ) {
                return false;
            }

            if ($mailstatusfilter !== '') {
                $mail = $mailservice->mail_record_for_campaign_member(
                    $id,
                    (int)$member->id
                );
                $mailnotapplicable = in_array(
                    (string)$member->eligibilitystatus,
                    ['excluded', 'covered', 'identity_review', 'error'],
                    true
                );
                $mailstatus = $mail
                    ? (string)$mail->status
                    : ($mailnotapplicable ? 'notapplicable' : 'notqueued');
                if ($mailstatus !== $mailstatusfilter) {
                    return false;
                }
            }

            return true;
        }
    ));

    usort(
        $filteredmembers,
        static function(\stdClass $a, \stdClass $b) use (
            $campaignmembersort,
            $campaignmemberdir,
            $mailservice,
            $id
        ): int {
            $avalue = '';
            $bvalue = '';

            if ($campaignmembersort === 'client') {
                $avalue = trim(
                    (string)($a->lastname ?? '')
                    . ' '
                    . (string)($a->firstname ?? '')
                    . ' '
                    . (string)$a->email
                );
                $bvalue = trim(
                    (string)($b->lastname ?? '')
                    . ' '
                    . (string)($b->firstname ?? '')
                    . ' '
                    . (string)$b->email
                );
            } else if ($campaignmembersort === 'status') {
                $avalue = (string)$a->eligibilitystatus;
                $bvalue = (string)$b->eligibilitystatus;
            } else if ($campaignmembersort === 'offer') {
                $avalue = (string)($a->offerid ?? 0);
                $bvalue = (string)($b->offerid ?? 0);
            } else if ($campaignmembersort === 'email') {
                $amail = $mailservice->mail_record_for_campaign_member(
                    $id,
                    (int)$a->id
                );
                $bmail = $mailservice->mail_record_for_campaign_member(
                    $id,
                    (int)$b->id
                );
                $avalue = (string)($amail->status ?? '');
                $bvalue = (string)($bmail->status ?? '');
            } else {
                $avalue = (string)($a->reason ?? '');
                $bvalue = (string)($b->reason ?? '');
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
        (int)ceil(
            $filteredmembercount / $campaignmemberperpage
        ) - 1
    );
    $campaignmemberpage = min($campaignmemberpage, $maxmemberpage);
    $pagedmembers = array_slice(
        $filteredmembers,
        $campaignmemberpage * $campaignmemberperpage,
        $campaignmemberperpage
    );

    foreach ($pagedmembers as $member) {
        $selected = in_array($member->eligibilitystatus, ['eligible', 'issued', 'replayed'], true);
        $checkbox = '';
        if (
            $editable
            && in_array(
                (string)$member->eligibilitystatus,
                ['eligible', 'excluded'],
                true
            )
        ) {
            $checkbox = html_writer::empty_tag('input', [
                'type' => 'hidden',
                'name' => 'visiblemembers[]',
                'value' => (int)$member->id,
            ]);
            if ((string)$member->eligibilitystatus === 'eligible'
                || (string)($member->reason ?? '') === 'manual_exclusion') {
                $checkbox .= html_writer::checkbox(
                    'members[]',
                    (int)$member->id,
                    $selected,
                    '',
                    ['aria-label' => $member->email]
                );
            }
        }

        $fullname = trim((string)($member->firstname ?? '') . ' ' . (string)($member->lastname ?? ''));
        $clientname = s($fullname !== '' ? $fullname : (string)$member->email);
        if (!empty($member->userid)) {
            $clientname = html_writer::link(
                new moodle_url(
                    subscription_config::admin_user_view_page(),
                    ['id' => (int)$member->userid]
                ),
                $clientname,
                ['class' => 'crm-offers-access-preview-client-link']
            );
        }
        $clienthtml = html_writer::div(
            $clientname,
            'crm-offer-campaign-client-name'
        )
        . html_writer::div(
            s((string)$member->email),
            'crm-offer-campaign-client-email'
        );

        $account = !empty($member->userid)
            ? html_writer::link(
                new moodle_url(subscription_config::admin_user_view_page(), ['id' => (int)$member->userid]),
                '#' . (int)$member->userid
            )
            : html_writer::span(
                get_string('commerce_personal_offer_account_unresolved', 'local_subscriptions'),
                'text-muted'
            );

        $evidence = json_decode((string)($member->evidencejson ?? '[]'), true);
        $evidencehtml = is_array($evidence) && $evidence !== []
            ? implode('<br>', array_map(
                static fn(string $value): string => html_writer::tag('code', s($value)),
                $evidence
            ))
            : '—';

        $offer = $member->offerid
            ? html_writer::link(
                new moodle_url('/local/subscriptions/admin/commerce/personal-offers/view.php', ['id' => $member->offerid]),
                '#' . $member->offerid
            )
            : '—';
        $existingoffer = !empty($member->existingofferid)
            ? html_writer::link(
                new moodle_url('/local/subscriptions/admin/commerce/personal-offers/view.php', ['id' => $member->existingofferid]),
                '#' . $member->existingofferid
            )
            : '—';

        $reason = (string)$member->reason;
        $reasonmap = [
            'manual_exclusion' => 'commerce_personal_offer_reason_manual_exclusion',
            'target_already_owned' => 'commerce_personal_offer_reason_target_owned',
            'invalid_email' => 'commerce_personal_offer_reason_invalid_email',
            'ambiguous_email' => 'commerce_personal_offer_reason_ambiguous_email',
            'account_required' => 'commerce_personal_offer_reason_account_required',
            'account_not_allowed' => 'commerce_personal_offer_reason_account_not_allowed',
            'active_offer_exists' => 'commerce_personal_offer_reason_active_offer_exists',
            'target_acquired_after_snapshot' => 'commerce_personal_offer_reason_target_acquired_after_snapshot',
            'active_offer_created_after_snapshot' => 'commerce_personal_offer_reason_active_offer_created_after_snapshot',
            'active_offer_will_be_replaced' => 'commerce_personal_offer_reason_m11_will_replace',
            'active_offer_will_be_resent' => 'commerce_personal_offer_reason_m11_will_resend',
            'active_offer_reused' => 'commerce_personal_offer_reason_m11_reused',
            'active_offer_payment_in_progress' => 'commerce_personal_offer_reason_m11_payment_in_progress',
            'advanced_audience_rules_not_matched' => 'commerce_personal_offer_reason_advanced_rules_not_matched',
        ];
        if (isset($reasonmap[$reason])) {
            $reason = get_string($reasonmap[$reason], 'local_subscriptions');
        }

        $mail = $mailservice->mail_record_for_campaign_member($id, (int)$member->id);
        $mailstatus = $mail
            ? html_writer::link(
                new moodle_url(
                    '/local/subscriptions/admin/commerce/mail/view.php',
                    ['id' => (int)$mail->id]
                ),
                CommerceMailAdminPresentation::status_label((string)$mail->status)
            )
            : get_string('commerce_personal_offer_mail_notqueued', 'local_subscriptions');

        $statuskey = 'commerce_personal_offer_member_status_' . (string)$member->eligibilitystatus;
        $statuslabel = get_string_manager()->string_exists($statuskey, 'local_subscriptions')
            ? get_string($statuskey, 'local_subscriptions')
            : (string)$member->eligibilitystatus;

        $mailnotapplicable = in_array(
            (string)$member->eligibilitystatus,
            ['excluded', 'covered', 'identity_review', 'error'],
            true
        );
        $rawmailstatus = $mail
            ? (string)$mail->status
            : ($mailnotapplicable ? 'notapplicable' : 'notqueued');
        $memberbadgeclass = match ((string)$member->eligibilitystatus) {
            'eligible' => 'text-bg-info',
            'issued' => 'text-bg-success',
            'replayed' => 'text-bg-primary',
            'excluded' => 'text-bg-secondary',
            'covered' => 'text-bg-warning',
            'identity_review', 'error' => 'text-bg-danger',
            default => 'text-bg-light',
        };
        $memberbadge = html_writer::span(
            s($statuslabel),
            'badge crm-campaign-member-badge ' . $memberbadgeclass
        );

        if ($mail) {
            $mailbadge = html_writer::span(
                s(
                    CommerceMailAdminPresentation::status_label(
                        (string)$mail->status
                    )
                ),
                'badge crm-campaign-mail-badge '
                . CommerceMailAdminPresentation::status_badge_class(
                    (string)$mail->status
                )
            );
            $mailstatus = html_writer::link(
                new moodle_url(
                    '/local/subscriptions/admin/commerce/mail/view.php',
                    ['id' => (int)$mail->id]
                ),
                $mailbadge,
                ['class' => 'text-decoration-none']
            );
        } else {
            $mailstatus = html_writer::span(
                s(
                    get_string(
                        $mailnotapplicable
                            ? 'commerce_campaign_mail_not_applicable'
                            : 'commerce_personal_offer_mail_notqueued',
                        'local_subscriptions'
                    )
                ),
                'badge crm-campaign-mail-badge '
                    . ($mailnotapplicable
                        ? 'text-bg-secondary'
                        : 'text-bg-warning')
            );
        }

        $messagehtml = s($reason !== '' ? $reason : '—');
        if (
            has_capability(Capabilities::MANAGE_CRM_ADMIN_TOOLS, $context)
            && $beneficiarycorrection->can_correct($id, (int)$member->id)
        ) {
            $messagehtml .= html_writer::div(
                html_writer::link(
                    new moodle_url('/local/subscriptions/admin/commerce/personal-offers/correct-beneficiary.php', [
                        'campaignid' => $id,
                        'memberid' => (int)$member->id,
                    ]),
                    get_string('commerce_personal_offer_correct_beneficiary', 'local_subscriptions'),
                    ['class' => 'btn btn-sm btn-outline-secondary mt-2']
                )
            );
        }

        $detailsbody = html_writer::tag(
            'dl',
            html_writer::tag(
                'dt',
                get_string(
                    'commerce_personal_offer_moodle_account',
                    'local_subscriptions'
                )
            )
            . html_writer::tag('dd', $account)
            . html_writer::tag(
                'dt',
                get_string(
                    'commerce_personal_offer_eligibility_evidence',
                    'local_subscriptions'
                )
            )
            . html_writer::tag('dd', $evidencehtml)
            . html_writer::tag(
                'dt',
                get_string(
                    'commerce_personal_offer_existing_offer',
                    'local_subscriptions'
                )
            )
            . html_writer::tag('dd', $existingoffer)
            . html_writer::tag('dt', get_string('message'))
            . html_writer::tag('dd', $messagehtml),
            ['class' => 'crm-offer-campaign-client-details-list mb-0']
        );

        $details = html_writer::tag(
            'details',
            html_writer::tag(
                'summary',
                html_writer::tag('i', '', [
                    'class' => 'fa fa-info-circle me-1',
                    'aria-hidden' => 'true',
                ])
                . get_string(
                    'commerce_offer_campaign_view_details',
                    'local_subscriptions'
                ),
                ['class' => 'crm-offer-campaign-client-details-summary']
            )
            . html_writer::div(
                $detailsbody,
                'crm-offer-campaign-client-details-body'
            ),
            ['class' => 'crm-offer-campaign-client-details']
        );

        $table->data[] = [
            $checkbox,
            $clienthtml,
            $memberbadge,
            $offer,
            $mailstatus,
            $details,
        ];
    }
    echo html_writer::table($table);

    $paginationparams = $url->params();
    $paginationparams['memberperpage'] = $campaignmemberperpage;
    $paginationparams['membersort'] = $campaignmembersort;
    $paginationparams['memberdir'] = $campaignmemberdir;
    $paginationurl = new moodle_url(
        '/local/subscriptions/admin/commerce/personal-offers/campaign_view.php',
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
                                '/local/subscriptions/admin/commerce/personal-offers/campaign_view.php',
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

    if ($editable) {
        echo html_writer::div(
            html_writer::tag('button', get_string('commerce_personal_offer_save_selection', 'local_subscriptions'), ['type' => 'submit', 'class' => 'btn btn-outline-primary']),
            'mt-2'
        );
        echo html_writer::end_tag('form');
    }
} else {
    echo html_writer::div(get_string('commerce_personal_offer_campaign_preview_empty', 'local_subscriptions'), 'alert alert-light border mt-4');
}

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
