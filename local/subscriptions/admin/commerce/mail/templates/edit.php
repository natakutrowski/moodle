<?php

require_once(__DIR__ . '/../../../../../../config.php');
require_once($CFG->dirroot . '/local/subscriptions/forms/commerce/mail/CommerceMailTemplateForm.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\mail\admin\CommerceMailAdminPresentation;
use local_subscriptions\commerce\mail\CommerceMailType;
use local_subscriptions\commerce\mail\template\studio\CommerceMailHeaderImageService;
use local_subscriptions\commerce\mail\template\studio\CommerceMailTemplateDefaults;
use local_subscriptions\commerce\mail\template\studio\CommerceMailTemplateRepository;
use local_subscriptions\commerce\mail\transactional\CommerceTransactionalMailStudioBridge;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\form\commerce\mail\CommerceMailTemplateForm;

$context = AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);
$mailtype = required_param('mailtype', PARAM_ALPHANUMEXT);
$language = required_param('language', PARAM_ALPHANUMEXT);
if (!in_array($mailtype, CommerceMailType::all(), true) || !in_array($language, ['fr', 'en', 'ru'], true)) {
    throw new moodle_exception('invalidparameter');
}

$transactionalbridge = CommerceTransactionalMailStudioBridge::create($DB);
if (in_array($mailtype, CommerceTransactionalMailStudioBridge::supported_types(), true)) {
    $mailstudiotemplate = $transactionalbridge->template($mailtype);
    if ($mailstudiotemplate !== null) {
        redirect(new moodle_url(
            '/local/subscriptions/admin/commerce/mail/templates/library_edit.php',
            ['id' => (int)$mailstudiotemplate->id]
        ));
    }
}

$listurl = new moodle_url('/local/subscriptions/admin/commerce/mail/templates/index.php');
$pageurl = new moodle_url('/local/subscriptions/admin/commerce/mail/templates/edit.php', compact('mailtype', 'language'));
$title = get_string('commerce_mail_template_edit_title', 'local_subscriptions', (object)[
    'type' => CommerceMailAdminPresentation::type_label($mailtype),
    'language' => CommerceMailAdminPresentation::language_label($language),
]);
CrmPageConfigurator::configure($PAGE, $context, $pageurl, $title, 'local-subscriptions-commerce-mail-template-edit-page');

$repository = new CommerceMailTemplateRepository($DB);
$record = $repository->get($mailtype, $language);
$values = $record ? (array)$record : CommerceMailTemplateDefaults::get($mailtype, $language);
$draftitemid = file_get_submitted_draft_itemid('headerimage_filemanager');
if ($record !== null) {
    file_prepare_draft_area(
        $draftitemid,
        $context->id,
        'local_subscriptions',
        CommerceMailHeaderImageService::FILEAREA,
        (int)$record->id,
        ['subdirs' => 0, 'maxfiles' => 1, 'accepted_types' => ['image']]
    );
}

$form = new CommerceMailTemplateForm($pageurl, ['context' => $context]);
$form->set_data((object)[
    'mailtype' => $mailtype,
    'language' => $language,
    'mailtypelabel' => CommerceMailAdminPresentation::type_label($mailtype),
    'languagelabel' => CommerceMailAdminPresentation::language_label($language),
    'enabled' => $values['enabled'] ?? 1,
    'subject' => $values['subject'] ?? '',
    'preheader' => $values['preheader'] ?? '',
    'heading' => $values['heading'] ?? '',
    'intro_editor' => ['text' => $values['introhtml'] ?? '', 'format' => FORMAT_HTML],
    'outro_editor' => ['text' => $values['outrohtml'] ?? '', 'format' => FORMAT_HTML],
    'signature_editor' => ['text' => $values['signaturehtml'] ?? '', 'format' => FORMAT_HTML],
    'headerimage' => $values['headerimage'] ?? 0,
    'headerimage_filemanager' => $draftitemid,
]);

if ($form->is_cancelled()) {
    redirect($listurl);
}
if ($data = $form->get_data()) {
    $saved = $repository->save([
        'mailtype' => $mailtype,
        'language' => $language,
        'enabled' => $data->enabled,
        'subject' => $data->subject,
        'preheader' => $data->preheader,
        'heading' => $data->heading,
        'introhtml' => $data->intro_editor['text'] ?? '',
        'outrohtml' => $data->outro_editor['text'] ?? '',
        'signaturehtml' => $data->signature_editor['text'] ?? '',
        'headerimage' => $data->headerimage,
    ], (int)$USER->id);

    $fs = get_file_storage();
    if (empty($data->headerimage)) {
        $fs->delete_area_files($context->id, 'local_subscriptions', CommerceMailHeaderImageService::FILEAREA, (int)$saved->id);
    } else {
        file_save_draft_area_files(
            (int)$data->headerimage_filemanager,
            $context->id,
            'local_subscriptions',
            CommerceMailHeaderImageService::FILEAREA,
            (int)$saved->id,
            ['subdirs' => 0, 'maxfiles' => 1, 'accepted_types' => ['image']]
        );
    }

    redirect($listurl, get_string('changessaved'), null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    ['label' => get_string('crm_commerce_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php')],
    ['label' => get_string('commerce_mail_templates_title', 'local_subscriptions'), 'url' => $listurl],
    ['label' => $title, 'url' => null],
]);
echo CrmPageHeader::render($title, get_string('commerce_mail_template_edit_description', 'local_subscriptions'), HelpContext::COMMERCE);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::MAIL);
$form->display();
echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
