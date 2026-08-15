<?php
require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\mail\builder\CommerceMailBuilder;
use local_subscriptions\commerce\mail\builder\CommerceMailBuilderEditorRenderer;
use local_subscriptions\commerce\personaloffer\campaign\CommercePersonalOfferCampaignEmailBuilderService;
use local_subscriptions\commerce\personaloffer\campaign\CommercePersonalOfferCampaignEmailService;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::MANAGE_CRM_ADMIN_TOOLS);
$id = required_param('id', PARAM_INT);
$builder = CommercePersonalOfferCampaignEmailBuilderService::create($DB);
$state = $builder->state($id);
$campaign = $state['campaign'];
$url = new moodle_url('/local/subscriptions/admin/commerce/personal-offers/campaign_email.php', ['id' => $id]);
$title = get_string('commerce_personal_offer_campaign_email_title', 'local_subscriptions');
CrmPageConfigurator::configure($PAGE, $context, $url, $title, 'local-subscriptions-commerce-personal-offer-campaign-email-page');
$PAGE->requires->css('/local/subscriptions/styles/commerce_mail_admin.css');

$requestedlanguage = optional_param('lang', 'fr', PARAM_ALPHA);
$activelanguage = in_array($requestedlanguage, CommercePersonalOfferCampaignEmailService::SUPPORTED_LANGUAGES, true)
    ? $requestedlanguage
    : 'fr';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();
    try {
        $translations = [];
        foreach (CommercePersonalOfferCampaignEmailService::SUPPORTED_LANGUAGES as $language) {
            $translations[$language] = [
                'subject' => optional_param('subject_' . $language, '', PARAM_RAW_TRIMMED),
                'body' => optional_param('body_' . $language, '', PARAM_RAW),
                'bodyformat' => optional_param('bodyformat_' . $language, (int)FORMAT_HTML, PARAM_INT),
                'ctalabel' => optional_param('ctalabel_' . $language, '', PARAM_RAW_TRIMMED),
                'secondaryctalabel' => optional_param('secondaryctalabel_' . $language, '', PARAM_RAW_TRIMMED),
                'secondaryctaurl' => optional_param('secondaryctaurl_' . $language, '', PARAM_RAW_TRIMMED),
                'closing' => optional_param('closing_' . $language, '', PARAM_RAW),
                'closingformat' => optional_param('closingformat_' . $language, (int)FORMAT_HTML, PARAM_INT),
            ];
        }
        $postedlanguage = optional_param('activelang', $activelanguage, PARAM_ALPHA);
        if (in_array($postedlanguage, CommercePersonalOfferCampaignEmailService::SUPPORTED_LANGUAGES, true)) { $activelanguage = $postedlanguage; }
        $builder->save(
            $id,
            required_param('ctadestination', PARAM_ALPHA),
            optional_param('showroomid', 0, PARAM_INT) ?: null,
            $translations,
            (int)$USER->id,
            isset($_FILES['campaignbanner']) && is_array($_FILES['campaignbanner'])
                ? $_FILES['campaignbanner']
                : null,
            optional_param('deletebanner', 0, PARAM_BOOL) === 1,
            isset($_FILES['campaignfooterimage']) && is_array($_FILES['campaignfooterimage'])
                ? $_FILES['campaignfooterimage']
                : null,
            optional_param('deletefooterimage', 0, PARAM_BOOL) === 1
        );
        redirect(
            new moodle_url('/local/subscriptions/admin/commerce/personal-offers/campaign_email_preview.php', ['id' => $id, 'language' => $activelanguage]),
            get_string('commerce_personal_offer_campaign_email_saved_preview_next', 'local_subscriptions'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
    $state = $builder->state($id);
}

$config = $state['config'];
$translations = $state['translations'];
$destination = $config ? (string)$config->ctadestination : CommercePersonalOfferCampaignEmailService::DESTINATION_CHECKOUT;
$selectedshowroom = $config && $config->showroomid !== null ? (int)$config->showroomid : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error !== '') {
    $destination = optional_param('ctadestination', $destination, PARAM_ALPHA);
    $selectedshowroom = optional_param('showroomid', $selectedshowroom, PARAM_INT);
}

$labels = ['fr' => '🇫🇷 Français', 'en' => '🇬🇧 English', 'ru' => '🇷🇺 Русский'];

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    ['label' => get_string('crm_commerce_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php')],
    ['label' => get_string('commerce_personal_offer_campaigns', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/personal-offers/campaigns.php')],
    ['label' => (string)$campaign->name, 'url' => new moodle_url('/local/subscriptions/admin/commerce/personal-offers/campaign_view.php', ['id' => $id])],
    ['label' => $title, 'url' => null],
]);
echo CrmPageHeader::render($title, get_string('commerce_personal_offer_campaign_email_help', 'local_subscriptions'), HelpContext::COMMERCE);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::PERSONAL_OFFERS, $context);
if ($error !== '') {
    echo html_writer::div(s($error), 'alert alert-danger');
}
if (!$state['editable']) {
    echo html_writer::div(get_string('commerce_personal_offer_campaign_email_locked', 'local_subscriptions'), 'alert alert-warning');
}

$mailstudioaction = new moodle_url(
    '/local/subscriptions/admin/commerce/personal-offers/campaign_email_template_action.php'
);
$mailstudiotemplates = $state['mailstudiotemplates'] ?? [];
$mailstudiosource = $state['mailstudiosource'] ?? null;

echo html_writer::start_div('commerce-personal-offer-mailstudio card card-body mb-4');
echo html_writer::div(
    html_writer::div(
        html_writer::tag(
            'h3',
            get_string('commerce_personal_offer_mailstudio_title', 'local_subscriptions'),
            ['class' => 'h5 mb-1']
        )
        . html_writer::div(
            get_string('commerce_personal_offer_mailstudio_help', 'local_subscriptions'),
            'text-muted small'
        ),
        'commerce-personal-offer-mailstudio-heading-copy'
    )
    . html_writer::link(
        new moodle_url(
            '/local/subscriptions/admin/commerce/mail/templates/index.php',
            ['category' => 'personal_offer']
        ),
        get_string('commerce_personal_offer_mailstudio_library', 'local_subscriptions'),
        ['class' => 'btn btn-sm btn-outline-secondary']
    ),
    'commerce-personal-offer-mailstudio-heading'
);

if ($mailstudiosource) {
    echo html_writer::div(
        html_writer::tag(
            'i',
            '',
            ['class' => 'fa fa-copy me-1', 'aria-hidden' => 'true']
        )
        . get_string(
            'commerce_personal_offer_mailstudio_snapshot_source',
            'local_subscriptions',
            (string)$mailstudiosource->name
        ),
        'commerce-personal-offer-mailstudio-source'
    );
}

if ($state['editable']) {
    echo html_writer::start_div('commerce-personal-offer-mailstudio-grid');

    echo html_writer::start_tag('form', [
        'method' => 'post',
        'action' => $mailstudioaction->out(false),
        'class' => 'commerce-personal-offer-mailstudio-action',
    ]);
    echo html_writer::empty_tag('input', [
        'type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey(),
    ]);
    echo html_writer::empty_tag('input', [
        'type' => 'hidden', 'name' => 'id', 'value' => $id,
    ]);
    echo html_writer::empty_tag('input', [
        'type' => 'hidden', 'name' => 'action', 'value' => 'applytemplate',
    ]);
    echo html_writer::tag(
        'label',
        get_string('commerce_personal_offer_mailstudio_apply', 'local_subscriptions'),
        ['for' => 'personal-offer-mailstudio-template', 'class' => 'form-label fw-semibold']
    );
    if ($mailstudiotemplates !== []) {
        echo html_writer::select(
            $mailstudiotemplates,
            'templateid',
            0,
            false,
            [
                'id' => 'personal-offer-mailstudio-template',
                'class' => 'form-select',
            ]
        );
        echo html_writer::div(
            get_string('commerce_personal_offer_mailstudio_apply_help', 'local_subscriptions'),
            'form-text'
        );
        echo html_writer::tag(
            'button',
            get_string('commerce_personal_offer_mailstudio_apply_button', 'local_subscriptions'),
            ['type' => 'submit', 'class' => 'btn btn-outline-primary mt-2']
        );
    } else {
        echo html_writer::div(
            get_string('commerce_personal_offer_mailstudio_empty', 'local_subscriptions'),
            'alert alert-light border py-2 mb-0'
        );
    }
    echo html_writer::end_tag('form');

    echo html_writer::start_tag('form', [
        'method' => 'post',
        'action' => $mailstudioaction->out(false),
        'class' => 'commerce-personal-offer-mailstudio-action',
    ]);
    echo html_writer::empty_tag('input', [
        'type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey(),
    ]);
    echo html_writer::empty_tag('input', [
        'type' => 'hidden', 'name' => 'id', 'value' => $id,
    ]);
    echo html_writer::empty_tag('input', [
        'type' => 'hidden', 'name' => 'action', 'value' => 'savetemplate',
    ]);
    echo html_writer::tag(
        'label',
        get_string('commerce_personal_offer_mailstudio_save_as', 'local_subscriptions'),
        ['for' => 'personal-offer-mailstudio-name', 'class' => 'form-label fw-semibold']
    );
    echo html_writer::empty_tag('input', [
        'type' => 'text',
        'id' => 'personal-offer-mailstudio-name',
        'name' => 'templatename',
        'value' => (string)$campaign->name,
        'required' => 'required',
        'class' => 'form-control',
    ]);
    echo html_writer::div(
        get_string('commerce_personal_offer_mailstudio_save_help', 'local_subscriptions'),
        'form-text'
    );
    echo html_writer::tag(
        'button',
        get_string('commerce_personal_offer_mailstudio_save_button', 'local_subscriptions'),
        ['type' => 'submit', 'class' => 'btn btn-outline-secondary mt-2']
    );
    echo html_writer::end_tag('form');

    echo html_writer::end_div();
}
echo html_writer::end_div();

echo html_writer::start_tag('form', [
    'method' => 'post',
    'enctype' => 'multipart/form-data',
    'class' => 'card card-body',
]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'activelang', 'id' => 'campaign-email-active-language', 'value' => $activelanguage]);

echo html_writer::tag('h3', get_string('commerce_personal_offer_campaign_email_destination', 'local_subscriptions'), ['class' => 'h5']);
echo html_writer::start_div('mb-4');
foreach ([
    CommercePersonalOfferCampaignEmailService::DESTINATION_CHECKOUT => 'commerce_personal_offer_campaign_email_destination_checkout',
    CommercePersonalOfferCampaignEmailService::DESTINATION_SHOWROOM => 'commerce_personal_offer_campaign_email_destination_showroom',
] as $value => $stringkey) {
    $attrs = ['type' => 'radio', 'class' => 'form-check-input', 'name' => 'ctadestination', 'value' => $value, 'id' => 'ctadestination_' . $value];
    if ($destination === $value) { $attrs['checked'] = 'checked'; }
    if (!$state['editable']) { $attrs['disabled'] = 'disabled'; }
    echo html_writer::start_div('form-check mb-2');
    echo html_writer::empty_tag('input', $attrs);
    echo html_writer::tag('label', get_string($stringkey, 'local_subscriptions'), ['for' => 'ctadestination_' . $value, 'class' => 'form-check-label']);
    echo html_writer::end_div();
}
$showroomopts = [0 => get_string('commerce_personal_offer_campaign_email_showroom_choose', 'local_subscriptions')] + $state['showrooms'];
echo html_writer::tag('label', get_string('commerce_personal_offer_campaign_email_showroom', 'local_subscriptions'), ['for' => 'showroomid', 'class' => 'form-label fw-semibold mt-2']);
$showroomattrs = ['id' => 'showroomid', 'class' => 'form-select'];
if (!$state['editable']) { $showroomattrs['disabled'] = 'disabled'; }
echo html_writer::select($showroomopts, 'showroomid', $selectedshowroom, false, $showroomattrs);
echo html_writer::div(get_string('commerce_personal_offer_campaign_email_showroom_help', 'local_subscriptions'), 'form-text');
echo html_writer::end_div();

echo html_writer::tag(
    'h3',
    get_string('commerce_personal_offer_campaign_banner_title', 'local_subscriptions'),
    ['class' => 'h5 mt-2']
);
echo html_writer::div(
    get_string('commerce_personal_offer_campaign_banner_help', 'local_subscriptions'),
    'text-muted mb-3'
);
echo html_writer::start_div('mb-4');
if (!empty($state['bannerurl'])) {
    echo html_writer::div(
        html_writer::empty_tag('img', [
            'src' => $state['bannerurl'],
            'alt' => '',
            'style' => 'display:block;width:100%;max-width:800px;max-height:220px;object-fit:cover;border-radius:12px;border:1px solid #e5e7eb;',
        ]),
        'mb-3'
    );
}
$bannerattrs = [
    'type' => 'file',
    'id' => 'campaignbanner',
    'name' => 'campaignbanner',
    'class' => 'form-control',
    'accept' => 'image/jpeg,image/png,image/webp',
];
if (!$state['editable']) {
    $bannerattrs['disabled'] = 'disabled';
}
echo html_writer::tag(
    'label',
    get_string('commerce_personal_offer_campaign_banner_file', 'local_subscriptions'),
    ['for' => 'campaignbanner', 'class' => 'form-label fw-semibold']
);
echo html_writer::empty_tag('input', $bannerattrs);
echo html_writer::div(
    get_string('commerce_personal_offer_campaign_banner_format_help', 'local_subscriptions'),
    'form-text'
);
if (!empty($state['bannerurl']) && $state['editable']) {
    echo html_writer::start_div('form-check mt-2');
    echo html_writer::empty_tag('input', [
        'type' => 'checkbox',
        'id' => 'deletebanner',
        'name' => 'deletebanner',
        'value' => '1',
        'class' => 'form-check-input',
    ]);
    echo html_writer::tag(
        'label',
        get_string('commerce_personal_offer_campaign_banner_delete', 'local_subscriptions'),
        ['for' => 'deletebanner', 'class' => 'form-check-label']
    );
    echo html_writer::end_div();
}
echo html_writer::end_div();

echo html_writer::tag(
    'h3',
    get_string('commerce_personal_offer_campaign_footer_title', 'local_subscriptions'),
    ['class' => 'h5 mt-2']
);
echo html_writer::div(
    get_string('commerce_personal_offer_campaign_footer_help', 'local_subscriptions'),
    'text-muted mb-3'
);
echo html_writer::start_div('mb-4');
if (!empty($state['footerimageurl'])) {
    echo html_writer::empty_tag('img', [
        'src' => (string)$state['footerimageurl'],
        'alt' => '',
        'style' => 'display:block;max-width:520px;width:100%;height:auto;border-radius:12px;margin-bottom:12px;',
    ]);
}
$footerattrs = [
    'type' => 'file',
    'id' => 'campaignfooterimage',
    'name' => 'campaignfooterimage',
    'class' => 'form-control',
    'accept' => 'image/jpeg,image/png,image/webp',
];
if (!$state['editable']) {
    $footerattrs['disabled'] = 'disabled';
}
echo html_writer::empty_tag('input', $footerattrs);
if (!empty($state['footerimageurl']) && $state['editable']) {
    echo html_writer::start_div('form-check mt-2');
    echo html_writer::empty_tag('input', [
        'type' => 'checkbox',
        'class' => 'form-check-input',
        'id' => 'deletefooterimage',
        'name' => 'deletefooterimage',
        'value' => '1',
    ]);
    echo html_writer::tag(
        'label',
        get_string('commerce_personal_offer_campaign_footer_delete', 'local_subscriptions'),
        ['for' => 'deletefooterimage', 'class' => 'form-check-label']
    );
    echo html_writer::end_div();
}
echo html_writer::end_div();

echo html_writer::tag('h3', get_string('commerce_personal_offer_campaign_email_content', 'local_subscriptions'), ['class' => 'h5']);
echo html_writer::div(get_string('commerce_personal_offer_campaign_email_content_help', 'local_subscriptions'), 'text-muted mb-3');

echo CommerceMailBuilderEditorRenderer::tag_palette(
    $state['variables'],
    CommerceMailBuilder::personal_offer_structural_tags()
);

echo html_writer::start_tag('ul', ['class' => 'nav nav-tabs mb-3', 'role' => 'tablist']);
foreach ($labels as $language => $label) {
    $isactive = $language === $activelanguage;
    echo html_writer::tag('li', html_writer::tag('button', $label, [
        'class' => 'nav-link' . ($isactive ? ' active' : ''), 'type' => 'button', 'role' => 'tab',
        'data-bs-toggle' => 'tab', 'data-bs-target' => '#campaign-email-' . $language,
        'data-language' => $language,
        'aria-controls' => 'campaign-email-' . $language, 'aria-selected' => $isactive ? 'true' : 'false',
    ]), ['class' => 'nav-item', 'role' => 'presentation']);
}
echo html_writer::end_tag('ul');

echo html_writer::start_div('tab-content');
foreach ($labels as $language => $label) {
    $record = $translations[$language] ?? null;
    $values = [
        'subject' => $record ? (string)$record->subject : '',
        'body' => $record ? (string)$record->body : '',
        'bodyformat' => $record ? (int)$record->bodyformat : (int)FORMAT_HTML,
        'ctalabel' => $record ? (string)$record->ctalabel : '',
        'secondaryctalabel' => $record ? (string)($record->secondaryctalabel ?? '') : '',
        'secondaryctaurl' => $record ? (string)($record->secondaryctaurl ?? '') : '',
        'closing' => $record ? (string)($record->closing ?? '') : '',
        'closingformat' => $record ? (int)$record->closingformat : (int)FORMAT_HTML,
    ];
    // M14A migration-on-edit: old Body + Conclusion becomes one continuous body.
    if ($record && trim((string)($record->closing ?? '')) !== '' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        $values['body'] = rtrim($values['body']) . "\n\n" . (string)$record->closing;
        $values['closing'] = '';
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error !== '') {
        $values['subject'] = optional_param('subject_' . $language, $values['subject'], PARAM_RAW_TRIMMED);
        $values['body'] = optional_param('body_' . $language, $values['body'], PARAM_RAW);
        $values['bodyformat'] = optional_param('bodyformat_' . $language, (int)FORMAT_HTML, PARAM_INT);
        $values['ctalabel'] = optional_param('ctalabel_' . $language, $values['ctalabel'], PARAM_RAW_TRIMMED);
        $values['secondaryctalabel'] = optional_param('secondaryctalabel_' . $language, $values['secondaryctalabel'], PARAM_RAW_TRIMMED);
        $values['secondaryctaurl'] = optional_param('secondaryctaurl_' . $language, $values['secondaryctaurl'], PARAM_RAW_TRIMMED);
        $values['closing'] = optional_param('closing_' . $language, $values['closing'], PARAM_RAW);
        $values['closingformat'] = optional_param('closingformat_' . $language, (int)FORMAT_HTML, PARAM_INT);
    }
    if ($values['bodyformat'] !== (int)FORMAT_HTML && $values['body'] !== '') {
        $values['body'] = format_text($values['body'], $values['bodyformat'], ['context' => $context, 'filter' => false]);
    }
    if ($values['closingformat'] !== (int)FORMAT_HTML && $values['closing'] !== '') {
        $values['closing'] = format_text($values['closing'], $values['closingformat'], ['context' => $context, 'filter' => false]);
    }
    $isactive = $language === $activelanguage;
    echo html_writer::start_div('tab-pane fade' . ($isactive ? ' show active' : ''), ['id' => 'campaign-email-' . $language, 'role' => 'tabpanel']);
    foreach ([
        'subject' => ['commerce_personal_offer_campaign_email_subject', false],
        'body' => ['commerce_personal_offer_campaign_email_body', true],
        'secondaryctalabel' => ['commerce_personal_offer_campaign_email_secondary_cta_label', false],
        'secondaryctaurl' => ['commerce_personal_offer_campaign_email_secondary_cta_url', false],
    ] as $field => [$stringkey, $richeditor]) {
        echo html_writer::start_div('mb-3');
        $fieldid = $field . '_' . $language;
        echo html_writer::tag('label', get_string($stringkey, 'local_subscriptions'), ['for' => $fieldid, 'class' => 'form-label fw-semibold']);
        $attrs = ['id' => $fieldid, 'name' => $fieldid, 'class' => 'form-control'];
        if (!$state['editable']) { $attrs['disabled'] = 'disabled'; }
        if ($richeditor) {
            echo CommerceMailBuilderEditorRenderer::rich_editor(
                $fieldid,
                $fieldid,
                $values[$field],
                $context,
                $state['editable'],
                $field === 'body' ? 12 : 6
            );
            echo html_writer::empty_tag('input', [
                'type' => 'hidden',
                'name' => $field . 'format_' . $language,
                'value' => (int)FORMAT_HTML,
            ]);
        } else {
            echo html_writer::empty_tag('input', $attrs + [
                'type' => $field === 'secondaryctaurl' ? 'url' : 'text',
                'value' => $values[$field],
            ]);
            if ($field === 'secondaryctaurl') {
                echo html_writer::div(
                    get_string('commerce_personal_offer_campaign_email_secondary_cta_help', 'local_subscriptions'),
                    'form-text'
                );
            }
        }
        echo html_writer::end_div();
    }
    echo html_writer::end_div();
}
echo html_writer::end_div();

if ($state['editable']) {
    echo html_writer::tag('button', get_string('savechanges'), ['type' => 'submit', 'class' => 'btn btn-primary']);
}
echo html_writer::link(new moodle_url('/local/subscriptions/admin/commerce/personal-offers/campaign_view.php', ['id' => $id]), get_string('cancel'), ['class' => 'btn btn-link ms-2']);
echo html_writer::end_tag('form');
CommerceMailBuilderEditorRenderer::require_copy_behaviour($PAGE);
$PAGE->requires->js_amd_inline(<<<'JS'
document.querySelectorAll('[data-bs-toggle="tab"][data-language]').forEach(function(tab) {
    tab.addEventListener('shown.bs.tab', function() {
        var field = document.getElementById('campaign-email-active-language');
        if (field) {
            field.value = tab.dataset.language || 'fr';
        }
    });
});
JS);
echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
