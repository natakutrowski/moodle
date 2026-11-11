<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\mail\admin\CommerceMailPreviewRenderer;
use local_subscriptions\commerce\mail\admin\CommerceMailSectionNavigationRenderer;
use local_subscriptions\commerce\mail\library\CommerceMailLibrary;
use local_subscriptions\commerce\mail\library\CommerceMailLibraryRepository;
use local_subscriptions\commerce\mail\library\CommerceMailStudioPreviewService;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);
$id = required_param('id', PARAM_INT);
$repository = new CommerceMailLibraryRepository($DB);
$template = $repository->get($id);
$contents = $repository->contents($id);
$language = optional_param('language', 'fr', PARAM_ALPHA);
if (!in_array($language, CommerceMailLibrary::LANGUAGES, true) || !isset($contents[$language])) {
    $language = (string)(array_key_first($contents) ?? 'fr');
}
$view = CommerceMailPreviewRenderer::normalise_view(optional_param('view', 'desktop', PARAM_ALPHA));
$font = CommerceMailPreviewRenderer::normalise_font(optional_param('font', 'brand', PARAM_ALPHA));
$pageurl = new moodle_url('/local/subscriptions/admin/commerce/mail/templates/library_preview.php', [
    'id' => $id, 'language' => $language, 'font' => $font,
]);
$title = get_string('commerce_mail_studio_preview_title', 'local_subscriptions') . ' — ' . (string)$template->name;
CrmPageConfigurator::configure($PAGE, $context, $pageurl, $title,
    'local-subscriptions-commerce-mail-library-preview-page');
$PAGE->requires->css('/local/subscriptions/styles/commerce_mail_admin.css');

$preview = null;
if (isset($contents[$language])) {
    $content = $contents[$language];
    $document = json_decode((string)$content->contentjson, true) ?: [];
    try {
        $preview = CommerceMailStudioPreviewService::render([
            'subject' => (string)$content->subject,
            'preheader' => (string)$content->preheader,
            'bodyhtml' => (string)($document['bodyhtml'] ?? ''),
            'document' => $document,
        ], $language, (string)$template->category);
    } catch (\Throwable $exception) {
        \core\notification::error($exception->getMessage());
    }
}
$listurl = new moodle_url('/local/subscriptions/admin/commerce/mail/templates/index.php');
$editurl = new moodle_url('/local/subscriptions/admin/commerce/mail/templates/library_edit.php', ['id' => $id]);

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    ['label' => get_string('commerce_mail_admin_title', 'local_subscriptions'),
     'url' => new moodle_url('/local/subscriptions/admin/commerce/mail/index.php')],
    ['label' => get_string('commerce_mail_templates_title', 'local_subscriptions'), 'url' => $listurl],
    ['label' => $title, 'url' => null],
]);
echo CrmPageHeader::render($title,
    get_string('commerce_mail_studio_preview_help', 'local_subscriptions'), HelpContext::COMMERCE);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::MAIL);
echo CommerceMailSectionNavigationRenderer::render(CommerceMailSectionNavigationRenderer::TEMPLATES);
echo html_writer::div(
    html_writer::link($listurl, get_string('back'), ['class' => 'btn btn-outline-secondary'])
    . html_writer::link($editurl, get_string('edit'), ['class' => 'btn btn-primary']),
    'd-flex gap-2 mb-3'
);
$toolbar = CommerceMailPreviewRenderer::render_navigation($pageurl, $view);
if (in_array($view, ['desktop', 'mobile'], true)) {
    $toolbar .= CommerceMailPreviewRenderer::render_font_navigation($pageurl, $font);
}
$languageform = html_writer::start_tag('form', ['method' => 'get', 'action' => $pageurl->out(false)]);
foreach (['id' => $id, 'view' => $view, 'font' => $font] as $key => $value) {
    $languageform .= html_writer::empty_tag('input', [
        'type' => 'hidden', 'name' => $key, 'value' => $value,
    ]);
}
$langoptions = [];
foreach (array_keys($contents) as $availablelanguage) {
    $langoptions[$availablelanguage] = strtoupper((string)$availablelanguage);
}
$languageform .= html_writer::tag('label', get_string('language'), [
    'for' => 'mail-studio-preview-language', 'class' => 'form-label',
]);
$languageform .= html_writer::select($langoptions, 'language', $language, false, [
    'id' => 'mail-studio-preview-language', 'class' => 'form-select',
    'onchange' => 'this.form.submit();',
]);
$languageform .= html_writer::end_tag('form');
echo html_writer::div($toolbar . $languageform, 'commerce-mail-preview-toolbar');
if ($preview) {
    echo html_writer::tag('h3', s($preview['subject']), ['class' => 'h5 mb-3']);
    echo CommerceMailPreviewRenderer::render($preview['html'], $preview['text'], $view, $font);
} else {
    echo html_writer::div(get_string('commerce_mail_studio_preview_empty', 'local_subscriptions'),
        'alert alert-info');
}
echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
