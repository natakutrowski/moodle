<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\mail\admin\CommerceMailSectionNavigationRenderer;
use local_subscriptions\commerce\mail\builder\CommerceMailBuilder;
use local_subscriptions\commerce\mail\builder\CommerceMailBuilderEditorRenderer;
use local_subscriptions\commerce\mail\library\CommerceMailLibrary;
use local_subscriptions\commerce\mail\library\CommerceMailLibraryRepository;
use local_subscriptions\commerce\personaloffer\mail\CommercePersonalOfferCampaignMailVariableResolver;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);
$id = optional_param('id', 0, PARAM_INT);
$repository = new CommerceMailLibraryRepository($DB);
$record = $id > 0 ? $repository->get($id) : null;
$contents = $record ? $repository->contents((int)$record->id) : [];
$listurl = new moodle_url('/local/subscriptions/admin/commerce/mail/templates/index.php');
$pageurl = new moodle_url('/local/subscriptions/admin/commerce/mail/templates/library_edit.php', $id > 0 ? ['id' => $id] : []);
$requestedlanguage = optional_param('lang', 'fr', PARAM_ALPHA);
$activelanguage = in_array($requestedlanguage, CommerceMailLibrary::LANGUAGES, true)
    ? $requestedlanguage
    : 'fr';

$title = $record
    ? get_string('commerce_mail_library_edit_title', 'local_subscriptions', (string)$record->name)
    : get_string('commerce_mail_library_create_title', 'local_subscriptions');
CrmPageConfigurator::configure($PAGE, $context, $pageurl, $title, 'local-subscriptions-commerce-mail-library-edit-page');
$PAGE->requires->css('/local/subscriptions/styles/commerce_mail_admin.css');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();
    $name = required_param('name', PARAM_TEXT);
    $category = required_param('category', PARAM_ALPHANUMEXT);
    $status = required_param('status', PARAM_ALPHANUMEXT);
    if ($record && !empty($record->runtimekey)) {
        $category = CommerceMailLibrary::CATEGORY_TRANSACTIONAL;
    }
    $translations = [];
    foreach (CommerceMailLibrary::LANGUAGES as $language) {
        if ($category === CommerceMailLibrary::CATEGORY_TRANSACTIONAL) {
            $introhtml = optional_param('intro_' . $language, '', PARAM_RAW);
            $translations[$language] = [
                'subject' => optional_param('subject_' . $language, '', PARAM_TEXT),
                'preheader' => optional_param('preheader_' . $language, '', PARAM_TEXT),
                'bodyhtml' => $introhtml,
                'document' => [
                    'mode' => 'transactional_editorial',
                    'builderversion' => CommerceMailLibrary::BUILDER_VERSION,
                    'bodyhtml' => $introhtml,
                    'heading' => optional_param('heading_' . $language, '', PARAM_TEXT),
                    'introhtml' => $introhtml,
                    'outrohtml' => optional_param('outro_' . $language, '', PARAM_RAW),
                    'signaturehtml' => optional_param('signature_' . $language, '', PARAM_RAW),
                    'headerimage' => optional_param('headerimage_' . $language, 0, PARAM_BOOL) ? 1 : 0,
                    'blocks' => [],
                ],
            ];
        } else {
            $translations[$language] = [
                'subject' => optional_param('subject_' . $language, '', PARAM_TEXT),
                'preheader' => optional_param('preheader_' . $language, '', PARAM_TEXT),
                'bodyhtml' => optional_param('body_' . $language, '', PARAM_RAW),
            ];
        }
    }
    $metadata = $record
        ? (json_decode((string)$record->metadatajson, true) ?: [])
        : [];
    $metadata['foundation'] = 'N5.5';
    $metadata['editor'] = 'mail_builder';
    $saved = $repository->save([
        'name' => $name,
        'category' => $category,
        'status' => $status,
        'runtimekey' => $record->runtimekey ?? null,
        'metadata' => $metadata,
    ], $translations, (int)$USER->id, $record ? (int)$record->id : null);
    redirect(
        new moodle_url('/local/subscriptions/admin/commerce/mail/templates/library_edit.php', ['id' => (int)$saved->id]),
        get_string('changessaved'), null, \core\output\notification::NOTIFY_SUCCESS
    );
}

$categoryoptions = [];
foreach (CommerceMailLibrary::categories() as $item) {
    $categoryoptions[$item] = get_string('commerce_mail_library_category_' . $item, 'local_subscriptions');
}
$selectedcategory = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? optional_param('category', CommerceMailLibrary::CATEGORY_MARKETING, PARAM_ALPHANUMEXT)
    : ($record
        ? (string)$record->category
        : optional_param('category', CommerceMailLibrary::CATEGORY_MARKETING, PARAM_ALPHANUMEXT));
if (!in_array($selectedcategory, CommerceMailLibrary::categories(), true)) {
    $selectedcategory = CommerceMailLibrary::CATEGORY_MARKETING;
}

$statusoptions = [];
foreach (CommerceMailLibrary::statuses() as $item) {
    $statusoptions[$item] = get_string('commerce_mail_library_status_' . $item, 'local_subscriptions');
}

$form = html_writer::start_tag('form', ['method' => 'post', 'action' => $pageurl->out(false), 'class' => 'commerce-mail-library-edit-form']);
$form .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
$form .= html_writer::div(
    html_writer::div(
        html_writer::tag('label', get_string('commerce_mail_library_name', 'local_subscriptions'), ['for' => 'mail-library-name', 'class' => 'form-label'])
        . html_writer::empty_tag('input', ['id' => 'mail-library-name', 'type' => 'text', 'name' => 'name', 'value' => $record ? (string)$record->name : '', 'required' => 'required', 'class' => 'form-control']),
        'commerce-mail-library-edit-field is-name'
    )
    . html_writer::div(
        html_writer::tag('label', get_string('commerce_mail_library_category', 'local_subscriptions'), ['for' => 'mail-library-category', 'class' => 'form-label'])
        . html_writer::select(
            $categoryoptions,
            'category',
            $selectedcategory,
            false,
            [
                'id' => 'mail-library-category',
                'class' => 'form-select',
                'disabled' => ($record && !empty($record->runtimekey)) ? 'disabled' : null,
            ]
        )
        . (($record && !empty($record->runtimekey))
            ? html_writer::empty_tag('input', [
                'type' => 'hidden',
                'name' => 'category',
                'value' => CommerceMailLibrary::CATEGORY_TRANSACTIONAL,
            ])
            : ''),
        'commerce-mail-library-edit-field'
    )
    . html_writer::div(
        html_writer::tag('label', get_string('status'), ['for' => 'mail-library-status', 'class' => 'form-label'])
        . html_writer::select($statusoptions, 'status', $record ? (string)$record->status : CommerceMailLibrary::STATUS_DRAFT, false, ['id' => 'mail-library-status', 'class' => 'form-select']),
        'commerce-mail-library-edit-field'
    ),
    'commerce-mail-library-edit-meta'
);
$form .= html_writer::div(
    get_string('commerce_mail_library_builder_note', 'local_subscriptions'),
    'alert alert-info commerce-mail-library-foundation-note'
);
if ($selectedcategory === CommerceMailLibrary::CATEGORY_TRANSACTIONAL) {
    $form .= html_writer::div(
        get_string('commerce_mail_transactional_builder_note', 'local_subscriptions')
        . '<br>'
        . get_string('commerce_mail_transactional_headerimage_compatibility', 'local_subscriptions'),
        'alert alert-light border commerce-mail-transactional-builder-note'
    );
}

$buildervariables = CommerceMailBuilder::common_variables();
$buildertags = CommerceMailBuilder::common_structural_tags();
if ($selectedcategory === CommerceMailLibrary::CATEGORY_PERSONAL_OFFER) {
    $buildervariables = CommercePersonalOfferCampaignMailVariableResolver::AVAILABLE;
    $buildertags = CommerceMailBuilder::personal_offer_structural_tags();
} else if ($selectedcategory === CommerceMailLibrary::CATEGORY_TRANSACTIONAL) {
    $buildervariables = CommerceMailBuilder::transactional_variables();
    $buildertags = [];
} else if ($selectedcategory === CommerceMailLibrary::CATEGORY_SALES_FOLLOWUP) {
    $buildervariables = CommerceMailBuilder::sales_followup_variables();
    $buildertags = CommerceMailBuilder::sales_followup_structural_tags();
}
$form .= CommerceMailBuilderEditorRenderer::tag_palette(
    $buildervariables,
    $buildertags
);

$languagelabels = [
    'fr' => '🇫🇷 Français',
    'en' => '🇬🇧 English',
    'ru' => '🇷🇺 Русский',
];
$form .= html_writer::start_tag('ul', [
    'class' => 'nav nav-tabs commerce-mail-builder-language-tabs',
    'role' => 'tablist',
]);
foreach (CommerceMailLibrary::LANGUAGES as $language) {
    $isactive = $language === $activelanguage;
    $form .= html_writer::tag(
        'li',
        html_writer::tag('button', $languagelabels[$language] ?? strtoupper($language), [
            'class' => 'nav-link' . ($isactive ? ' active' : ''),
            'type' => 'button',
            'role' => 'tab',
            'data-bs-toggle' => 'tab',
            'data-bs-target' => '#mail-library-language-' . $language,
            'aria-selected' => $isactive ? 'true' : 'false',
        ]),
        ['class' => 'nav-item', 'role' => 'presentation']
    );
}
$form .= html_writer::end_tag('ul');

$form .= html_writer::start_div('tab-content commerce-mail-builder-language-content');
foreach (CommerceMailLibrary::LANGUAGES as $language) {
    $content = $contents[$language] ?? null;
    $contentjson = $content ? (json_decode((string)$content->contentjson, true) ?: []) : [];
    $body = (string)($contentjson['bodyhtml'] ?? '');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $body = optional_param('body_' . $language, $body, PARAM_RAW);
    }

    $isactive = $language === $activelanguage;
    $form .= html_writer::start_div(
        'tab-pane fade commerce-mail-library-language-card'
            . ($isactive ? ' show active' : ''),
        [
            'id' => 'mail-library-language-' . $language,
            'role' => 'tabpanel',
        ]
    );
    $form .= html_writer::div(
        html_writer::tag('h3', $languagelabels[$language] ?? strtoupper($language), [
            'class' => 'h6 mb-0',
        ])
        . html_writer::span(
            get_string('commerce_mail_library_language_optional', 'local_subscriptions'),
            'commerce-mail-library-language-optional'
        ),
        'commerce-mail-library-language-heading'
    );

    $form .= html_writer::div(
        html_writer::tag(
            'label',
            get_string('commerce_mail_template_subject', 'local_subscriptions'),
            ['for' => 'subject-' . $language, 'class' => 'form-label']
        )
        . html_writer::empty_tag('input', [
            'id' => 'subject-' . $language,
            'type' => 'text',
            'name' => 'subject_' . $language,
            'value' => $_SERVER['REQUEST_METHOD'] === 'POST'
                ? optional_param('subject_' . $language, '', PARAM_TEXT)
                : ($content ? (string)$content->subject : ''),
            'class' => 'form-control',
        ]),
        'commerce-mail-library-edit-field'
    );

    $form .= html_writer::div(
        html_writer::tag(
            'label',
            get_string('commerce_mail_template_preheader', 'local_subscriptions'),
            ['for' => 'preheader-' . $language, 'class' => 'form-label']
        )
        . html_writer::empty_tag('input', [
            'id' => 'preheader-' . $language,
            'type' => 'text',
            'name' => 'preheader_' . $language,
            'value' => $_SERVER['REQUEST_METHOD'] === 'POST'
                ? optional_param('preheader_' . $language, '', PARAM_TEXT)
                : ($content ? (string)$content->preheader : ''),
            'class' => 'form-control',
        ]),
        'commerce-mail-library-edit-field'
    );

    if ($selectedcategory === CommerceMailLibrary::CATEGORY_TRANSACTIONAL) {
        $heading = $_SERVER['REQUEST_METHOD'] === 'POST'
            ? optional_param('heading_' . $language, '', PARAM_TEXT)
            : (string)($contentjson['heading'] ?? '');
        $intro = $_SERVER['REQUEST_METHOD'] === 'POST'
            ? optional_param('intro_' . $language, '', PARAM_RAW)
            : (string)($contentjson['introhtml'] ?? $body);
        $outro = $_SERVER['REQUEST_METHOD'] === 'POST'
            ? optional_param('outro_' . $language, '', PARAM_RAW)
            : (string)($contentjson['outrohtml'] ?? '');
        $signature = $_SERVER['REQUEST_METHOD'] === 'POST'
            ? optional_param('signature_' . $language, '', PARAM_RAW)
            : (string)($contentjson['signaturehtml'] ?? '');
        $headerimage = $_SERVER['REQUEST_METHOD'] === 'POST'
            ? optional_param('headerimage_' . $language, 0, PARAM_BOOL)
            : !empty($contentjson['headerimage']);

        $form .= html_writer::div(
            html_writer::tag(
                'label',
                get_string('commerce_mail_template_heading', 'local_subscriptions'),
                ['for' => 'heading-' . $language, 'class' => 'form-label']
            )
            . html_writer::empty_tag('input', [
                'id' => 'heading-' . $language,
                'type' => 'text',
                'name' => 'heading_' . $language,
                'value' => $heading,
                'class' => 'form-control',
            ]),
            'commerce-mail-library-edit-field'
        );
        foreach ([
            'intro' => [$intro, 'commerce_mail_template_intro'],
            'outro' => [$outro, 'commerce_mail_template_outro'],
            'signature' => [$signature, 'commerce_mail_template_signature'],
        ] as $zone => [$zonevalue, $labelkey]) {
            $form .= html_writer::div(
                html_writer::tag(
                    'label',
                    get_string($labelkey, 'local_subscriptions'),
                    ['for' => $zone . '-' . $language, 'class' => 'form-label']
                )
                . CommerceMailBuilderEditorRenderer::rich_editor(
                    $zone . '-' . $language,
                    $zone . '_' . $language,
                    $zonevalue,
                    $context,
                    true,
                    $zone === 'intro' ? 8 : 6
                ),
                'commerce-mail-library-edit-field'
            );
        }
        $form .= html_writer::div(
            html_writer::empty_tag('input', [
                'id' => 'headerimage-' . $language,
                'type' => 'checkbox',
                'name' => 'headerimage_' . $language,
                'value' => '1',
                'checked' => $headerimage ? 'checked' : null,
                'class' => 'form-check-input me-2',
            ])
            . html_writer::tag(
                'label',
                get_string('commerce_mail_template_headerimage_enabled', 'local_subscriptions'),
                ['for' => 'headerimage-' . $language, 'class' => 'form-check-label']
            ),
            'form-check commerce-mail-library-transactional-toggle'
        );
    } else {
        $form .= html_writer::div(
            html_writer::tag(
                'label',
                get_string('commerce_mail_library_body', 'local_subscriptions'),
                ['for' => 'body-' . $language, 'class' => 'form-label']
            )
            . CommerceMailBuilderEditorRenderer::rich_editor(
                'body-' . $language,
                'body_' . $language,
                $body,
                $context,
                true,
                14
            ),
            'commerce-mail-library-edit-field'
        );
    }
    $form .= html_writer::end_div();
}
$form .= html_writer::end_div();

CommerceMailBuilderEditorRenderer::require_copy_behaviour($PAGE);

$form .= html_writer::div(
    html_writer::link($listurl, get_string('cancel'), ['class' => 'btn btn-outline-secondary'])
    . html_writer::tag('button', get_string('savechanges'), ['type' => 'submit', 'class' => 'btn btn-primary']),
    'commerce-mail-library-edit-actions'
);
$form .= html_writer::end_tag('form');

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    ['label' => get_string('crm_commerce_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php')],
    ['label' => get_string('commerce_mail_admin_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/mail/index.php')],
    ['label' => get_string('commerce_mail_templates_title', 'local_subscriptions'), 'url' => $listurl],
    ['label' => $title, 'url' => null],
]);
echo CrmPageHeader::render($title, get_string('commerce_mail_library_edit_description', 'local_subscriptions'), HelpContext::COMMERCE);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::MAIL);
echo CommerceMailSectionNavigationRenderer::render(CommerceMailSectionNavigationRenderer::TEMPLATES);
echo $form;
echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
