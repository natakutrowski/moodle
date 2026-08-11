<?php
require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\personaloffer\campaign\CommercePersonalOfferCampaignManager;
use local_subscriptions\commerce\personaloffer\mail\CommercePersonalOfferMailService;
use local_subscriptions\commerce\personaloffer\audience\CommercePersonalOfferAudienceProviderRegistry;
use local_subscriptions\commerce\mail\admin\CommerceMailAdminPresentation;
use local_subscriptions\crm\commerce\presentation\CommerceDesignSystemRenderer;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\subscription_config;

$context = AdminSecurity::require(Capabilities::VIEW_PAYMENTS);
$id = required_param('id', PARAM_INT);
$manager = CommercePersonalOfferCampaignManager::create($DB);
$mailservice = CommercePersonalOfferMailService::create($DB);
$campaign = $manager->get_campaign($id);
$url = new moodle_url('/local/subscriptions/admin/commerce/personal-offers/campaign_view.php', ['id' => $id]);
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
        } elseif ($action === 'generate') {
            $manager->generate($id, (int)$USER->id);
        } elseif ($action === 'retrygeneration') {
            $manager->retry_generation_errors($id, (int)$USER->id);
        } elseif ($action === 'selection') {
            $manager->update_member_selection($id, optional_param_array('members', [], PARAM_INT), (int)$USER->id);
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
    ['label' => get_string('commerce_personal_offers_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/personal-offers/index.php')],
    ['label' => get_string('commerce_personal_offer_campaigns', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/personal-offers/campaigns.php')],
    ['label' => $campaign->name, 'url' => null],
]);
echo CrmPageHeader::render($campaign->name, get_string('commerce_personal_offer_campaign_view_help', 'local_subscriptions'), HelpContext::COMMERCE);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::PERSONAL_OFFERS, $context);

echo CommerceDesignSystemRenderer::panel(
    get_string('commerce_personal_offer_campaign_identity_title', 'local_subscriptions'),
    html_writer::tag('dl',
        html_writer::tag('dt', get_string('commerce_personal_offer_campaign_key', 'local_subscriptions')) .
        html_writer::tag('dd', html_writer::tag('code', s($campaign->campaignkey))) .
        ($campaign->audiencetype === 'criteria'
            ? html_writer::tag('dt', get_string('commerce_personal_offer_source_type', 'local_subscriptions')) .
              html_writer::tag(
                  'dd',
                  s($sourcedescription !== '' ? $sourcedescription : '—') .
                  ($sourcetype !== '' ? html_writer::div(s($sourcetype), 'small text-muted') : '')
              )
            : '') .
        html_writer::tag('dt', get_string('status')) .
        html_writer::tag(
            'dd',
            get_string_manager()->string_exists(
                'commerce_personal_offer_campaign_status_' . (string)$campaign->status,
                'local_subscriptions'
            )
                ? get_string(
                    'commerce_personal_offer_campaign_status_' . (string)$campaign->status,
                    'local_subscriptions'
                )
                : s((string)$campaign->status)
        ),
        ['class' => 'mb-0']
    )
);


if ($campaign->status === 'snapshot' || !empty($campaign->snapshotat)) {
    echo CommerceDesignSystemRenderer::panel(
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
        ),
        'mt-3'
    );

    if ($campaign->status === 'snapshot') {
        echo html_writer::div(
            get_string('commerce_personal_offer_snapshot_frozen_notice', 'local_subscriptions'),
            'alert alert-warning mt-3'
        );
    }
}


if (
    in_array((string)$campaign->status, ['issued', 'closed'], true)
    || !empty($campaign->certifiedat)
) {
    $certificationcontent = html_writer::tag(
        'p',
        $certification['ready']
            ? get_string('commerce_personal_offer_certification_ready', 'local_subscriptions')
            : get_string(
                'commerce_personal_offer_certification_blocked',
                'local_subscriptions',
                (object)[
                    'generationerrors' => $certification['generationerrors'],
                    'selectedpending' => $certification['selectedpending'],
                    'mailblocking' => $certification['mailblocking'],
                ]
            ),
        ['class' => $certification['ready'] ? 'text-success fw-semibold' : 'text-muted']
    );

    if (!empty($campaign->certifiedat)) {
        $certifier = !empty($campaign->certifiedby)
            ? $DB->get_record(
                'user',
                ['id' => (int)$campaign->certifiedby],
                '*',
                IGNORE_MISSING
            )
            : null;

        $certificationcontent .= html_writer::tag(
            'dl',
            html_writer::tag(
                'dt',
                get_string('commerce_personal_offer_certified_at', 'local_subscriptions')
            ) .
            html_writer::tag('dd', userdate((int)$campaign->certifiedat)) .
            html_writer::tag(
                'dt',
                get_string('commerce_personal_offer_certified_by', 'local_subscriptions')
            ) .
            html_writer::tag(
                'dd',
                $certifier ? s(fullname($certifier)) : '—'
            ),
            ['class' => 'mb-0']
        );
    }

    echo CommerceDesignSystemRenderer::panel(
        get_string('commerce_personal_offer_certification_title', 'local_subscriptions'),
        $certificationcontent,
        'mt-3'
    );
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

    if ($campaign->status === 'snapshot') {
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

    if ($campaign->status === 'issued' && $certification['ready']) {
        $actions .= html_writer::start_tag('form', ['method' => 'post']);
        $actions .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        $actions .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'certify']);
        $actions .= html_writer::tag(
            'button',
            get_string('commerce_personal_offer_certify_campaign', 'local_subscriptions'),
            [
                'class' => 'btn btn-success',
                'onclick' => 'return confirm(' . json_encode(
                    get_string('commerce_personal_offer_certify_confirm', 'local_subscriptions')
                ) . ');',
            ]
        );
        $actions .= html_writer::end_tag('form');
    }

    $actions .= html_writer::end_div();
    echo $actions;
}

echo CommerceDesignSystemRenderer::metrics([
    ['label' => get_string('commerce_personal_offer_metric_total', 'local_subscriptions'), 'value' => $summary['total']],
    ['label' => get_string('commerce_personal_offer_metric_eligible', 'local_subscriptions'), 'value' => $summary['eligible']],
    ['label' => get_string('commerce_personal_offer_metric_covered', 'local_subscriptions'), 'value' => $summary['covered']],
    ['label' => get_string('commerce_personal_offer_metric_identity_review', 'local_subscriptions'), 'value' => $summary['identity_review']],
    ['label' => get_string('commerce_personal_offer_metric_excluded', 'local_subscriptions'), 'value' => $summary['excluded']],
    ['label' => get_string('commerce_personal_offer_metric_error', 'local_subscriptions'), 'value' => $summary['error']],
    ['label' => get_string('commerce_personal_offer_metric_issued', 'local_subscriptions'), 'value' => $summary['issued'] + $summary['replayed']],
]);

echo $OUTPUT->heading(get_string('commerce_personal_offer_mail_title', 'local_subscriptions'), 3);
echo html_writer::div(get_string('commerce_personal_offer_mail_batch_notice', 'local_subscriptions'), 'alert alert-info');
echo CommerceDesignSystemRenderer::metrics([
    ['label' => get_string('commerce_personal_offer_mail_expected', 'local_subscriptions'), 'value' => $mailsummary['eligible']],
    ['label' => get_string('commerce_personal_offer_mail_notqueued', 'local_subscriptions'), 'value' => $mailsummary['notqueued']],
    ['label' => get_string('commerce_personal_offer_mail_queued', 'local_subscriptions'), 'value' => $mailsummary['queued']],
    ['label' => get_string('commerce_personal_offer_mail_processing', 'local_subscriptions'), 'value' => $mailsummary['processing']],
    ['label' => get_string('commerce_personal_offer_mail_sent', 'local_subscriptions'), 'value' => $mailsummary['sent']],
    ['label' => get_string('commerce_personal_offer_mail_failed', 'local_subscriptions'), 'value' => $mailsummary['failed']],
    ['label' => get_string('commerce_personal_offer_mail_cancelled', 'local_subscriptions'), 'value' => $mailsummary['cancelled']],
]);
echo html_writer::div(
    html_writer::link(new moodle_url('/local/subscriptions/admin/commerce/mail/templates/edit.php', ['mailtype' => 'personal_offer', 'language' => 'fr']), get_string('commerce_personal_offer_mail_studio', 'local_subscriptions'), ['class' => 'btn btn-sm btn-outline-primary']) . ' ' .
    html_writer::link(new moodle_url('/local/subscriptions/admin/commerce/mail/index.php', ['mailtype' => 'personal_offer']), get_string('commerce_personal_offer_mail_log', 'local_subscriptions'), ['class' => 'btn btn-sm btn-outline-secondary']),
    'mb-4'
);

if ($campaign->audiencetype === 'criteria') {
    echo html_writer::div(get_string('commerce_personal_offer_criteria_generated_list_help', 'local_subscriptions'), 'alert alert-info mt-4');
}

if ($members) {
    $editable = has_capability(Capabilities::MANAGE_CRM_ADMIN_TOOLS, $context)
        && $campaign->status === 'previewed';
    if ($editable) {
        echo html_writer::start_tag('form', ['method' => 'post', 'class' => 'mt-4']);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'selection']);
    }
    $table = new html_table();
    $table->attributes['class'] = 'generaltable table table-hover align-middle';
    $table->head = [
        $editable ? get_string('select') : '',
        get_string('commerce_personal_offer_customer', 'local_subscriptions'),
        get_string('email'),
        get_string('commerce_personal_offer_moodle_account', 'local_subscriptions'),
        get_string('commerce_personal_offer_eligibility_evidence', 'local_subscriptions'),
        get_string('status'),
        get_string('commerce_personal_offer_existing_offer', 'local_subscriptions'),
        get_string('commerce_personal_offer_id', 'local_subscriptions'),
        get_string('commerce_personal_offer_mail_status', 'local_subscriptions'),
        get_string('message'),
    ];
    foreach ($members as $member) {
        $selected = in_array($member->eligibilitystatus, ['eligible', 'issued', 'replayed'], true);
        $checkbox = $editable && $member->eligibilitystatus === 'eligible'
            ? html_writer::checkbox('members[]', (int)$member->id, $selected, '', ['aria-label' => $member->email])
            : '';

        $fullname = trim((string)($member->firstname ?? '') . ' ' . (string)($member->lastname ?? ''));
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

        $table->data[] = [
            $checkbox,
            s($fullname !== '' ? $fullname : '—'),
            s($member->email),
            $account,
            $evidencehtml,
            s($statuslabel),
            $existingoffer,
            $offer,
            $mailstatus,
            s($reason !== '' ? $reason : '—'),
        ];
    }
    echo html_writer::table($table);
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
