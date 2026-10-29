<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\mail\admin\CommerceMailHealthRenderer;
use local_subscriptions\commerce\mail\admin\CommerceMailSectionNavigationRenderer;
use local_subscriptions\commerce\mail\campaign\CommerceMarketingCampaignRepository;
use local_subscriptions\commerce\mail\campaign\CommerceMarketingCampaignService;
use local_subscriptions\commerce\mail\campaign\CommerceMarketingCourseCampaignGate;
use local_subscriptions\commerce\mail\campaign\CommerceMarketingCampaignTestLanguageOptions;
use local_subscriptions\commerce\mail\audience\CommerceMarketingCampaignAudienceResolver;
use local_subscriptions\commerce\mail\audience\CommerceMarketingCourseAudienceService;
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
$courseenabled = CommerceMarketingCourseCampaignGate::enabled();
// A direct link to the actual release switch, in the same CRM permission scope.
$courseconfigurl = new moodle_url('/local/subscriptions/admin/commerce/mail/configuration.php');
$courseconfigurl->set_anchor('mail-config-course_campaigns_enabled');
$courseconfiglink = html_writer::link(
    $courseconfigurl,
    get_string('commerce_campaign_e9c_open_settings', 'local_subscriptions'),
    ['class' => 'alert-link']
);

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

$templates = $service->template_options();
$recipients = $campaign ? $repository->recipients((int)$campaign->id) : [];
$config = $campaign ? (json_decode((string)($campaign->audienceconfig ?? ''), true) ?: []) : [];
$audience = implode("\n", array_map(
    static fn(\stdClass $recipient): string => implode(';', [
        (string)$recipient->email,
        (string)$recipient->firstname,
        (string)$recipient->lastname,
        (string)$recipient->language,
    ]),
    $recipients
));
$source = $campaign ? (string)($campaign->audiencesource ?? 'manual') : 'manual';
$courseid = (int)($config['courseid'] ?? 0);
$skus = implode(',', $config['supportskus'] ?? []);
$excludedids = implode(',', $config['excludedids'] ?? []);
$selectedexcludedids = array_map('intval', $config['excludedids'] ?? []);
$filters = CommerceMarketingCourseAudienceService::normalise_filters((array)($config['filters'] ?? []));
$deliverylanguage = (string)($config['deliverylanguage'] ?? 'auto');
// Historic saved drafts used buyer-only semantics. Keep those checkboxes
// conservative on first opening; E9 requires an explicit resave of the draft.
if ($campaign && $source === 'course' && empty($config['audiencepolicy'])) {
    $filters['require_verified_purchase'] = true;
    $filters['exclude_inactive_enrolment'] = true;
}
$namevalue = $campaign ? (string)$campaign->name : '';
$templatevalue = $campaign ? (int)$campaign->templateid : 0;
$ctavalue = $campaign ? (string)$campaign->ctaurl : '';
$previewreport = null;
// Only actual, complete frozen translations can be used for a test.
$testlanguages = $campaign
    ? CommerceMarketingCampaignTestLanguageOptions::available($repository->contents((int)$campaign->id))
    : [];
$testlanguage = CommerceMarketingCampaignTestLanguageOptions::default_language($testlanguages);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();
    $mode = optional_param('mode', 'preview', PARAM_ALPHA);
    // The separate test/pause/resume/cancel form contains none of the editor fields.
    // Never parse or validate unrelated audience data for an operational action.
    if (!in_array($mode, ['pause', 'resume', 'cancel', 'test'], true)) {
        $source = optional_param('audiencesource', 'manual', PARAM_ALPHA);
        $namevalue = optional_param('name', '', PARAM_TEXT);
        $templatevalue = optional_param('templateid', 0, PARAM_INT);
        $ctavalue = optional_param('ctaurl', '', PARAM_URL);
        $deliverylanguage = optional_param('deliverylanguage', 'auto', PARAM_ALPHA);
        $audience = optional_param('audience', '', PARAM_RAW);
        $courseid = optional_param('audiencecourseid', 0, PARAM_INT);
        $skus = optional_param('audienceskus', '', PARAM_RAW_TRIMMED);
        $selectedexcludedids = array_values(array_unique(array_map('intval',
            optional_param_array('audienceexcludedusers', [], PARAM_INT)
        )));
        $selectedexcludedids = array_values(array_filter($selectedexcludedids,
            static fn(int $userid): bool => $userid > 0
        ));
        $excludedids = implode(',', $selectedexcludedids);
        $filters = CommerceMarketingCourseAudienceService::normalise_filters([
            'exclude_student_role' => optional_param('exclude_student_role', 0, PARAM_BOOL),
            'exclude_trial_role' => optional_param('exclude_trial_role', 0, PARAM_BOOL),
            'exclude_grammar_role' => optional_param('exclude_grammar_role', 0, PARAM_BOOL),
            'exclude_promotion_access' => optional_param('exclude_promotion_access', 1, PARAM_BOOL),
            'exclude_accompaniment' => optional_param('exclude_accompaniment', 0, PARAM_BOOL),
            'exclude_inactive_enrolment' => optional_param('exclude_inactive_enrolment', 0, PARAM_BOOL),
            'require_verified_purchase' => optional_param('require_verified_purchase', 0, PARAM_BOOL),
        ]);
    }
    try {
        if (count($selectedexcludedids) > 500) {
            throw new \invalid_parameter_exception('Too many manually excluded accounts.');
        }
        if (in_array($mode, ['pause', 'resume', 'cancel', 'test'], true)) {
            if (!$campaign || $id <= 0) {
                throw new \invalid_parameter_exception('A saved campaign is required.');
            }
            if ($mode === 'test') {
                $testlanguage = optional_param('testlanguage', $testlanguage, PARAM_ALPHA);
                if (!isset($testlanguages[$testlanguage])) {
                    throw new \invalid_parameter_exception(get_string(
                        'commerce_marketing_campaign_languages_missing', 'local_subscriptions', $testlanguage . ' (1)'
                    ));
                }
                $service->send_test($id, (int)$USER->id, $testlanguage);
                $message = get_string('commerce_campaign_stage4_test_success', 'local_subscriptions', $USER->email);
            } else if ($mode === 'pause') {
                $service->pause($id);
                $message = get_string('commerce_campaign_stage4_paused', 'local_subscriptions');
            } else if ($mode === 'resume') {
                $service->resume($id);
                $message = get_string('commerce_campaign_stage4_resumed', 'local_subscriptions');
            } else {
                $service->cancel($id);
                $message = get_string('commerce_campaign_stage4_cancelled', 'local_subscriptions');
            }
            redirect($pageurl, $message, null, \core\output\notification::NOTIFY_SUCCESS);
        }
        if (!$editable) {
            throw new \coding_exception('This campaign is locked.');
        }
        if (!in_array($source, ['manual', 'course'], true)) {
            throw new \invalid_parameter_exception('Invalid campaign audience source.');
        }
        if ($mode === 'preview') {
            if ($source === 'course') {
                $previewreport = CommerceMarketingCampaignAudienceResolver::create($DB)->preview_course(
                    $courseid,
                    CommerceMarketingCampaignAudienceResolver::parse_skus($skus),
                    CommerceMarketingCampaignAudienceResolver::parse_excluded_ids($excludedids),
                    $filters
                );
            } else {
                $previewreport = $service->preview_manual($audience);
            }
        } else if (in_array($mode, ['save', 'schedule'], true)) {
            // A server-side feature switch and legal sign-off both apply to course segments.
            $coursemode = $source === 'course' ||
                ($campaign && (string)($campaign->audiencesource ?? 'manual') === 'course');
            if ($mode === 'schedule' && $coursemode && !$courseenabled) {
                throw new \invalid_parameter_exception(
                    get_string('commerce_campaign_e8_disabled', 'local_subscriptions'));
            }
            if ($mode === 'schedule' && $coursemode &&
                    !optional_param('confirm_course_legal', 0, PARAM_BOOL)) {
                throw new \invalid_parameter_exception(
                    get_string('commerce_campaign_e8_legal_required', 'local_subscriptions'));
            }
            if ($mode === 'schedule' && !optional_param('confirm_schedule', 0, PARAM_BOOL)) {
                throw new \invalid_parameter_exception(
                    get_string('commerce_campaign_stage4_confirmation_required', 'local_subscriptions'));
            }
            $campaignid = $service->save([
                'name' => $namevalue,
                'templateid' => $templatevalue,
                'ctaurl' => $ctavalue,
                'audience' => $audience,
                'audiencesource' => $source,
                'audiencecourseid' => $courseid,
                'audienceskus' => $skus,
                'audienceexcludedids' => $excludedids,
                'audiencefilters' => $filters,
                'deliverylanguage' => $deliverylanguage,
            ], (int)$USER->id, $id > 0 ? $id : null);

            if ($mode === 'schedule') {
                $rawschedule = required_param('scheduledat', PARAM_RAW_TRIMMED);
                $timezone = \core_date::get_user_timezone_object();
                $date = \DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $rawschedule, $timezone);
                if (!$date) {
                    throw new \invalid_parameter_exception('Invalid campaign schedule.');
                }
                $service->schedule($campaignid, $date->getTimestamp(), (int)$USER->id,
                    $coursemode && optional_param('confirm_course_legal', 0, PARAM_BOOL));
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
        } else {
            throw new \invalid_parameter_exception('Unsupported campaign action.');
        }
    } catch (\Throwable $exception) {
        if ($mode === 'test') {
            // Generic Moodle invalid_parameter_exception messages hide the real source.
            // Log the origin without dumping email content, tokens or recipient details.
            $origin = get_class($exception) . ' @ ' . basename($exception->getFile()) . ':' . $exception->getLine();
            error_log('[CampusFR Mail Studio test] campaign=' . $id . ' language=' . $testlanguage
                . ' origin=' . $origin);
            \core\notification::error($exception->getMessage() . ' [' . $origin . ']');
        } else if ($mode === 'schedule') {
            // Moodle's invalid_parameter_exception suppresses constructor messages.
            // Show an actionable diagnostic without including email addresses or content.
            $origin = get_class($exception) . ' @ ' . basename($exception->getFile()) . ':' . $exception->getLine();
            error_log('[CampusFR Mail Studio schedule] campaign=' . $id . ' origin=' . $origin);
            \core\notification::error($exception->getMessage() . ' [' . $origin . ']');
        } else {
            \core\notification::error($exception->getMessage());
        }
    }
}

$courseoptions = [];
foreach ($DB->get_records_select('course', 'id > :siteid', ['siteid' => SITEID],
    'fullname ASC', 'id,fullname,shortname', 0, 200) as $courseoption) {
    $courseoptions[(int)$courseoption->id] = format_string($courseoption->fullname) . ' (#' . $courseoption->id . ')';
}

// Offer a human-readable picker for course participants instead of raw Moodle IDs.
$internalcandidates = [];
if ($courseid > SITEID && $DB->record_exists('course', ['id' => $courseid])) {
    $coursecontext = \context_course::instance($courseid);
    $candidateusers = $DB->get_records_sql(
        "SELECT u.id, u.firstname, u.lastname, u.email
           FROM {user} u
          WHERE EXISTS (
                    SELECT 1 FROM {role_assignments} ra
                    JOIN {role} r ON r.id = ra.roleid
                    WHERE ra.contextid = :contextid AND ra.userid = u.id
                      AND r.shortname IN ('student', 'trialstudent', 'grammarstudent')
                )
             OR EXISTS (
                    SELECT 1 FROM {local_subs_commerce_ped_access} a
                     WHERE a.userid = u.id AND a.courseid = :courseid
                       AND (a.promotionid IS NOT NULL OR a.profile = :profile)
                )
          ORDER BY u.lastname ASC, u.firstname ASC, u.id ASC",
        ['contextid' => $coursecontext->id, 'courseid' => $courseid,
         'profile' => 'promotion_progressive']
    );
    foreach ($candidateusers as $candidateuser) {
        $internalcandidates[(int)$candidateuser->id] = $candidateuser;
    }
    // Preserve already selected accounts even if they are no longer participants.
    foreach ($selectedexcludedids as $selectedid) {
        if (!isset($internalcandidates[$selectedid])) {
            $candidateuser = $DB->get_record('user', ['id' => $selectedid],
                'id,firstname,lastname,email', IGNORE_MISSING);
            if ($candidateuser) {
                $internalcandidates[$selectedid] = $candidateuser;
            }
        }
    }
}

$scheduledvalue = $campaign && !empty($campaign->scheduledat)
    ? userdate((int)$campaign->scheduledat, '%Y-%m-%dT%H:%M')
    : userdate(time() + HOURSECS, '%Y-%m-%dT%H:%M');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $scheduledvalue = optional_param('scheduledat', $scheduledvalue, PARAM_RAW_TRIMMED);
}

$campaignjs = <<<'JS'
(function() {
    const source = document.getElementById('marketing-campaign-source');
    const course = document.getElementById('marketing-campaign-course-fields');
    const manual = document.getElementById('marketing-campaign-manual-fields');
    const schedule = document.getElementById('marketing-campaign-schedule-button');
    const confirmation = document.getElementById('marketing-confirm-schedule');
    const confirmationWrap = document.getElementById('marketing-campaign-confirmation-wrap');
    const courseHint = document.getElementById('marketing-campaign-course-lock-hint');
    const manualHint = document.getElementById('marketing-campaign-manual-confirm-hint');
    const search = document.getElementById('marketing-campaign-user-search');
    const userChoices = document.querySelectorAll('[data-marketing-candidate]');
    const userCounter = document.getElementById('marketing-campaign-internal-count');
    const courseSelect = document.getElementById('marketing-campaign-course');
    const previewButton = document.getElementById('marketing-campaign-preview-button');
    const updateUserCount = () => {
        if (!userCounter) { return; }
        const count = document.querySelectorAll('input[name="audienceexcludedusers[]"]:checked').length;
        userCounter.textContent = String(count);
    };
    if (search) {
        search.addEventListener('input', () => {
            const term = search.value.trim().toLocaleLowerCase();
            userChoices.forEach(item => {
                item.classList.toggle('d-none', !!term && !item.textContent.toLocaleLowerCase().includes(term));
            });
        });
    }
    userChoices.forEach(item => item.addEventListener('change', updateUserCount));
    updateUserCount();
    if (courseSelect && previewButton) {
        courseSelect.addEventListener('change', () => {
            // Reload the candidate picker when the course changes, preserving the form's values.
            const form = courseSelect.closest('form');
            if (form && typeof form.requestSubmit === 'function') {
                form.requestSubmit(previewButton);
            }
        });
    }
    if (!source || !course || !manual) { return; }
    const update = () => {
        const courseMode = source.value === 'course';
        course.classList.toggle('d-none', !courseMode);
        manual.classList.toggle('d-none', courseMode);
        const courseEnabled = __COURSE_MARKETING_ENABLED__;
        const legal = document.getElementById('marketing-confirm-course-legal');
        const legalWrap = document.getElementById('marketing-course-legal-wrap');
        if (schedule) {
            schedule.disabled = !confirmation || !confirmation.checked ||
                (courseMode && (!courseEnabled || !legal || !legal.checked));
        }
        if (confirmationWrap) { confirmationWrap.classList.remove('d-none'); }
        if (legalWrap) { legalWrap.classList.toggle('d-none', !courseMode || !courseEnabled); }
        if (courseHint) { courseHint.classList.toggle('d-none', !courseMode || courseEnabled); }
        if (manualHint) {
            manualHint.classList.toggle('d-none', !!(confirmation && confirmation.checked));
        }
    };
    source.addEventListener('change', update);
    if (confirmation) { confirmation.addEventListener('change', update); }
    const legalConfirmation = document.getElementById('marketing-confirm-course-legal');
    if (legalConfirmation) { legalConfirmation.addEventListener('change', update); }
    update();
})();
JS;
$PAGE->requires->js_init_code(str_replace('__COURSE_MARKETING_ENABLED__',
    $courseenabled ? 'true' : 'false', $campaignjs));

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

if (!$templates) {
    echo html_writer::div(
        get_string('commerce_campaign_stage4_no_templates', 'local_subscriptions') . ' ' .
        html_writer::link(
            new moodle_url('/local/subscriptions/admin/commerce/mail/templates/library_edit.php',
                ['category' => 'marketing']),
            get_string('commerce_campaign_stage4_create_template', 'local_subscriptions'),
            ['target' => '_blank', 'rel' => 'noopener noreferrer', 'class' => 'btn btn-sm btn-outline-primary']
        ), 'alert alert-info'
    );
} else {
    echo html_writer::div(
        html_writer::link(
            new moodle_url('/local/subscriptions/admin/commerce/mail/templates/library_edit.php',
                ['category' => 'marketing']),
            get_string('commerce_campaign_stage4_create_template', 'local_subscriptions'),
            ['target' => '_blank', 'rel' => 'noopener noreferrer']
        ) . ' · ' . get_string('commerce_campaign_stage4_template_help', 'local_subscriptions'),
        'form-text mb-3'
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
        'value' => $namevalue,
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
        $templatevalue,
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
// A deliberate campaign-level language override, not a change to Moodle profiles.
// In particular, RU-only marketing content cannot silently be sent to FR/EN profiles.
echo html_writer::div(
    html_writer::tag('label', get_string('commerce_campaign_e11_deliverylanguage', 'local_subscriptions'), [
        'for' => 'marketing-campaign-deliverylanguage', 'class' => 'form-label',
    ])
    . html_writer::select([
        'auto' => get_string('commerce_campaign_e11_language_auto', 'local_subscriptions'),
        'ru' => get_string('commerce_campaign_e11_language_ru', 'local_subscriptions'),
        'fr' => get_string('commerce_campaign_e11_language_fr', 'local_subscriptions'),
        'en' => get_string('commerce_campaign_e11_language_en', 'local_subscriptions'),
    ], 'deliverylanguage', $deliverylanguage, false, [
        'id' => 'marketing-campaign-deliverylanguage',
        'class' => 'form-select',
        'disabled' => !$editable ? 'disabled' : null,
    ])
    . html_writer::div(get_string('commerce_campaign_e11_language_help', 'local_subscriptions'),
        'form-text'),
    'commerce-mail-campaign-field'
);
echo html_writer::div(
    html_writer::tag('label', get_string('commerce_marketing_campaign_cta_url', 'local_subscriptions'), [
        'for' => 'marketing-campaign-cta', 'class' => 'form-label',
    ])
    . html_writer::empty_tag('input', [
        'type' => 'url', 'id' => 'marketing-campaign-cta', 'name' => 'ctaurl',
        'value' => $ctavalue,
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

// Stage 3: guided audience selector. Selection and preview never queue mail.
echo html_writer::tag('h3', get_string('commerce_campaign_stage3_audience_title', 'local_subscriptions'),
    ['class' => 'h5 mt-4']);
echo html_writer::div(
    html_writer::tag('label', get_string('commerce_campaign_stage3_source', 'local_subscriptions'), [
        'for' => 'marketing-campaign-source', 'class' => 'form-label',
    ])
    . html_writer::select([
        'manual' => get_string('commerce_campaign_stage3_manual', 'local_subscriptions'),
        'course' => get_string('commerce_campaign_stage3_course', 'local_subscriptions'),
    ], 'audiencesource', $source, false, [
        'id' => 'marketing-campaign-source', 'class' => 'form-select',
        'disabled' => (!$editable || $campaign !== null) ? 'disabled' : null,
    ])
    . ($campaign ? html_writer::empty_tag('input', [
        'type' => 'hidden', 'name' => 'audiencesource', 'value' => $source,
    ]) : ''), 'commerce-mail-campaign-field mb-3'
);

echo html_writer::start_div('commerce-mail-campaign-course-options card card-body mb-3', [
    'id' => 'marketing-campaign-course-fields',
]);
echo html_writer::div(
    html_writer::tag('label', get_string('commerce_campaign_stage3_courseid', 'local_subscriptions'), [
        'for' => 'marketing-campaign-course', 'class' => 'form-label',
    ])
    . html_writer::select($courseoptions, 'audiencecourseid', $courseid,
        ['0' => get_string('choose')], [
            'id' => 'marketing-campaign-course', 'class' => 'form-select',
            'disabled' => !$editable ? 'disabled' : null,
        ]), 'commerce-mail-campaign-field mb-3'
);
// Legacy SKU exclusions are preserved invisibly; new support offers come from the catalog.
echo html_writer::empty_tag('input', [
    'type' => 'hidden', 'name' => 'audienceskus', 'value' => $skus,
]);
echo html_writer::tag('h4', get_string('commerce_campaign_e7_optional_title', 'local_subscriptions'),
    ['class' => 'h6 fw-semibold mt-3']);
echo html_writer::div(get_string('commerce_campaign_e7_required_help', 'local_subscriptions'),
    'alert alert-light border mb-3');
foreach ([
    'exclude_student_role' => 'commerce_campaign_e9_exclude_student',
    'exclude_trial_role' => 'commerce_campaign_e7_exclude_trial',
    'exclude_grammar_role' => 'commerce_campaign_e7_exclude_grammar',
    'exclude_promotion_access' => 'commerce_campaign_e7_exclude_promotion',
    'exclude_accompaniment' => 'commerce_campaign_e7_exclude_accompaniment',
    'exclude_inactive_enrolment' => 'commerce_campaign_e9_exclude_inactive',
    'require_verified_purchase' => 'commerce_campaign_e9_require_purchase',
] as $key => $labelkey) {
    echo html_writer::div(
        html_writer::empty_tag('input', [
            'type' => 'hidden', 'name' => $key, 'value' => '0',
        ]) . html_writer::checkbox($key, '1', $filters[$key],
            get_string($labelkey, 'local_subscriptions'),
            ['id' => 'marketing-filter-' . $key, 'class' => 'me-2',
             'disabled' => !$editable ? 'disabled' : null]),
        'form-check mb-2'
    );
}
echo html_writer::div(get_string('commerce_campaign_e7_trial_help', 'local_subscriptions'),
    'form-text mb-3');
echo html_writer::div(get_string('commerce_campaign_e7_uncheck_help', 'local_subscriptions'),
    'form-text mb-3');
echo html_writer::tag('h4', get_string('commerce_campaign_e7_internals', 'local_subscriptions'),
    ['class' => 'h6 fw-semibold mt-3']);
echo html_writer::div(get_string('commerce_campaign_e7_internals_help', 'local_subscriptions'),
    'form-text mb-2');
echo html_writer::empty_tag('input', [
    'type' => 'search', 'id' => 'marketing-campaign-user-search', 'class' => 'form-control mb-2',
    'placeholder' => get_string('commerce_campaign_e7_search', 'local_subscriptions'),
    'disabled' => !$editable ? 'disabled' : null,
]);
echo html_writer::start_div('border rounded p-2 mb-2', [
    'style' => 'max-height:220px;overflow-y:auto',
]);
if (!$internalcandidates) {
    echo html_writer::div(get_string('commerce_campaign_e7_choose_course', 'local_subscriptions'),
        'text-muted small p-2');
}
foreach ($internalcandidates as $candidateuser) {
    $candidateid = (int)$candidateuser->id;
    $candidatefullname = trim((string)$candidateuser->firstname . ' ' . (string)$candidateuser->lastname);
    $labeltext = ($candidatefullname ?: '#' . $candidateid) . ' · ' . (string)$candidateuser->email;
    echo html_writer::div(
        html_writer::checkbox('audienceexcludedusers[]', (string)$candidateid,
            in_array($candidateid, $selectedexcludedids, true), s($labeltext),
            ['id' => 'marketing-excluded-' . $candidateid, 'class' => 'me-2',
             'disabled' => !$editable ? 'disabled' : null]),
        'small px-2 py-1', ['data-marketing-candidate' => '1']
    );
}
echo html_writer::end_div();
echo html_writer::div(
    get_string('commerce_campaign_e7_selected', 'local_subscriptions') . ' '
        . html_writer::tag('strong', (string)count($selectedexcludedids),
            ['id' => 'marketing-campaign-internal-count']),
    'form-text mb-2'
);
$courseaudiencenotice = get_string($courseenabled
    ? 'commerce_campaign_e8_ready_warning' : 'commerce_campaign_e8_disabled_warning', 'local_subscriptions');
if (!$courseenabled) {
    $courseaudiencenotice .= ' ' . $courseconfiglink;
}
echo html_writer::div($courseaudiencenotice,
    'alert ' . ($courseenabled ? 'alert-info' : 'alert-warning') . ' mt-3 mb-0');
echo html_writer::end_div();

echo html_writer::start_div('', ['id' => 'marketing-campaign-manual-fields']);
echo html_writer::div(
    html_writer::tag('label', get_string('commerce_marketing_campaign_audience' , 'local_subscriptions'), [
        'for' => 'marketing-campaign-audience', 'class' => 'form-label fw-semibold',
    ])
    . html_writer::tag('textarea', s($audience), [
        'id' => 'marketing-campaign-audience',
        'name' => 'audience',
        'rows' => 10,
        'class' => 'form-control font-monospace',
        'disabled' => !$editable ? 'disabled' : null,
    ])
    . html_writer::div(
        get_string('commerce_marketing_campaign_audience_help', 'local_subscriptions'),
        'form-text'
    ),
    'commerce-mail-campaign-audience'
);
echo html_writer::end_div();

if ($editable) {
    echo html_writer::div(
        html_writer::tag('button', get_string('commerce_campaign_stage3_preview', 'local_subscriptions'), [
            'type' => 'submit', 'name' => 'mode', 'value' => 'preview',
            'formnovalidate' => 'formnovalidate', 'class' => 'btn btn-outline-primary',
            'id' => 'marketing-campaign-preview-button',
        ]), 'commerce-mail-campaign-actions mb-3'
    );
}

// Only show previews after an explicit POST; lists are escaped and capped.
if ($previewreport !== null) {
    $summary = $previewreport['summary'];
    echo html_writer::start_div('commerce-mail-campaign-preview card card-body mb-3');
    echo html_writer::tag('h3', get_string('commerce_campaign_stage3_preview_title', 'local_subscriptions'),
        ['class' => 'h5']);
    echo html_writer::div(get_string('commerce_campaign_stage3_counts', 'local_subscriptions',
        (object)[
            'candidates' => $summary['candidates'],
            'eligible' => $summary['eligible'],
            'excluded' => $summary['excluded'],
        ]), 'alert alert-info');
    echo html_writer::div(get_string('commerce_campaign_e9_source_counts', 'local_subscriptions', (object)[
        'student' => $summary['source_student'] ?? 0,
        'trial' => $summary['source_trialstudent'] ?? 0,
        'grammar' => $summary['source_grammarstudent'] ?? 0,
        'promotion' => $summary['source_promotion'] ?? 0,
    ]), 'form-text mb-3');
    echo html_writer::start_tag('div', ['class' => 'table-responsive']);
    echo html_writer::start_tag('table', ['class' => 'table table-striped table-sm']);
    echo html_writer::tag('thead', html_writer::tag('tr',
        html_writer::tag('th', 'ID')
        . html_writer::tag('th', get_string('email'))
        . html_writer::tag('th', get_string('fullname'))
        . html_writer::tag('th', get_string('commerce_campaign_e9_sources', 'local_subscriptions'))
        . html_writer::tag('th', get_string('status'))
    ));
    echo html_writer::start_tag('tbody');
    $previewrows = $previewreport['rows'];
    usort($previewrows, static fn(array $a, array $b): int =>
        ((int)!$a['eligible'] <=> (int)!$b['eligible']) ?: ((int)$a['userid'] <=> (int)$b['userid']));
    foreach (array_slice($previewrows, 0, 50) as $row) {
        $id = (int)($row['userid'] ?? 0);
        $fullname = trim((string)($row['firstname'] ?? '') . ' ' . (string)($row['lastname'] ?? ''));
        $reasons = (array)($row['reasons'] ?? []);
        $status = !empty($row['eligible'])
            ? get_string('commerce_campaign_stage3_eligible', 'local_subscriptions')
            : implode(', ', array_map(static function(string $reason): string {
                $key = 'commerce_campaign_stage3_reason_' . $reason;
                return get_string_manager()->string_exists($key, 'local_subscriptions')
                    ? get_string($key, 'local_subscriptions') : $reason;
            }, $reasons));
        echo html_writer::tag('tr',
            html_writer::tag('td', $id ? (string)$id : '—')
            . html_writer::tag('td', s((string)$row['email']))
            . html_writer::tag('td', s($fullname))
            . html_writer::tag('td', s(implode(', ', (array)($row['sources'] ?? []))))
            . html_writer::tag('td', s($status))
        );
    }
    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
    echo html_writer::end_tag('div');
    if (count($previewreport['rows']) > 50) {
        echo html_writer::div(get_string('commerce_campaign_stage3_first50', 'local_subscriptions'), 'form-text');
    }
    echo html_writer::end_div();
}

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
            ['type' => 'submit', 'name' => 'mode', 'value' => 'schedule',
                'id' => 'marketing-campaign-schedule-button', 'class' => 'btn btn-primary',
                'disabled' => 'disabled']
        ),
        'commerce-mail-campaign-actions'
    );
    echo html_writer::div(
        html_writer::checkbox('confirm_schedule', '1', false,
            get_string('commerce_campaign_stage4_confirmation', 'local_subscriptions'),
            ['id' => 'marketing-confirm-schedule', 'class' => 'me-2']),
        'mt-3 mb-2',
        ['id' => 'marketing-campaign-confirmation-wrap']
    );
    echo html_writer::div(
        html_writer::checkbox('confirm_course_legal', '1', false,
            get_string('commerce_campaign_e8_legal_confirmation', 'local_subscriptions'),
            ['id' => 'marketing-confirm-course-legal', 'class' => 'me-2']),
        'mt-2 mb-2' . ($source === 'course' && $courseenabled ? '' : ' d-none'),
        ['id' => 'marketing-course-legal-wrap']
    );
    echo html_writer::div(
        get_string('commerce_campaign_e8_disabled', 'local_subscriptions') . ' ' . $courseconfiglink,
        'form-text text-warning mt-2' . ($source === 'course' && !$courseenabled ? '' : ' d-none'),
        ['id' => 'marketing-campaign-course-lock-hint']
    );
    echo html_writer::div(
        get_string('commerce_campaign_stage4_confirmation_required', 'local_subscriptions'),
        'form-text mt-2',
        ['id' => 'marketing-campaign-manual-confirm-hint']
    );
}
echo html_writer::end_tag('form');

// Explicit controls for a saved campaign. Test does not mutate the recipient snapshot.
if ($campaign) {
    $status = (string)$campaign->status;
    $actions = [];
    if ($status === 'draft') {
        $actions['test'] = get_string('commerce_campaign_stage4_send_test', 'local_subscriptions');
    }
    if (in_array($status, ['scheduled', 'queued'], true)) {
        $actions['pause'] = get_string('commerce_campaign_stage4_pause', 'local_subscriptions');
    }
    if ($status === 'paused') {
        $actions['resume'] = get_string('commerce_campaign_stage4_resume', 'local_subscriptions');
    }
    if (in_array($status, ['draft', 'scheduled', 'queued', 'paused'], true)) {
        $actions['cancel'] = get_string('commerce_campaign_stage4_cancel', 'local_subscriptions');
    }
    if ($actions) {
        echo html_writer::start_tag('form', [
            'method' => 'post', 'action' => $pageurl->out(false),
            'class' => 'card card-body mt-3',
        ]);
        echo html_writer::empty_tag('input', [
            'type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey(),
        ]);
        echo html_writer::tag('h3', get_string('commerce_campaign_stage4_controls', 'local_subscriptions'),
            ['class' => 'h5']);
        if ($status === 'draft') {
            echo html_writer::div(
                get_string('commerce_campaign_stage4_test_info', 'local_subscriptions', s($USER->email)),
                'form-text mb-2'
            );
            if ($testlanguages !== []) {
                echo html_writer::select($testlanguages, 'testlanguage', $testlanguage, false,
                    ['class' => 'form-select mb-2', 'style' => 'max-width:150px']);
            } else {
                echo html_writer::div(get_string('commerce_marketing_campaign_languages_missing',
                    'local_subscriptions', 'fr/ru/en'), 'alert alert-warning');
            }
        }
        foreach ($actions as $action => $label) {
            $danger = $action === 'cancel';
            echo html_writer::tag('button', s($label), [
                'type' => 'submit', 'name' => 'mode', 'value' => $action,
                'class' => 'btn btn-sm me-2 mb-2 ' . ($danger ? 'btn-outline-danger' : 'btn-outline-primary'),
                'disabled' => $action === 'test' && $testlanguages === [] ? 'disabled' : null,
                'onclick' => $danger
                    ? 'return confirm(' . json_encode(get_string('commerce_campaign_stage4_cancel_confirm',
                        'local_subscriptions')) . ');' : null,
            ]);
        }
        echo html_writer::end_tag('form');
    }
    $stats = $repository->statistics((int)$campaign->id);
    echo html_writer::tag('h3', get_string('commerce_campaign_stage4_delivery_details', 'local_subscriptions'),
        ['class' => 'h5 mt-4']);
    echo html_writer::div(get_string('commerce_campaign_stage4_delivery_summary', 'local_subscriptions',
        (object)[
            'total' => $stats['total'], 'sent' => $stats['sent'],
            'pending' => $stats['queued'], 'failed' => $stats['failed'],
            'cancelled' => $stats['cancelled'],
        ]), 'alert alert-info');
    echo html_writer::start_tag('div', ['class' => 'table-responsive']);
    echo html_writer::start_tag('table', ['class' => 'table table-striped table-sm']);
    echo html_writer::tag('thead', html_writer::tag('tr',
        html_writer::tag('th', get_string('email'))
        . html_writer::tag('th', get_string('status'))
        . html_writer::tag('th', get_string('date'))
        . html_writer::tag('th', get_string('error'))
    ));
    echo html_writer::start_tag('tbody');
    foreach ($repository->recipient_details((int)$campaign->id, 100) as $detail) {
        echo html_writer::tag('tr',
            html_writer::tag('td', s((string)$detail->email))
            . html_writer::tag('td', s((string)$detail->deliverystatus))
            . html_writer::tag('td', !empty($detail->timesent)
                ? userdate((int)$detail->timesent, get_string('strftimedatetimeshort', 'langconfig')) : '—')
            . html_writer::tag('td', s((string)($detail->lasterror ?? '')))
        );
    }
    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
    echo html_writer::end_tag('div');
    if ($stats['total'] > 100) {
        echo html_writer::div(get_string('commerce_campaign_stage4_first100', 'local_subscriptions'),
            'form-text');
    }
}

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
