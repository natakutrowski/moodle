<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\mail\admin\CommerceMailSectionNavigationRenderer;
use local_subscriptions\commerce\mail\library\CommerceMailLibraryImporter;
use local_subscriptions\commerce\mail\library\CommerceMailLibraryRepository;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);
$pageurl = new moodle_url('/local/subscriptions/admin/commerce/mail/templates/import.php');
$listurl = new moodle_url('/local/subscriptions/admin/commerce/mail/templates/index.php');
$title = get_string('commerce_mail_library_import_title', 'local_subscriptions');
CrmPageConfigurator::configure($PAGE, $context, $pageurl, $title, 'local-subscriptions-commerce-mail-library-import-page');
$PAGE->requires->css('/local/subscriptions/styles/commerce_mail_admin.css');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();
    if (!isset($_FILES['templatefile']) || (int)$_FILES['templatefile']['error'] !== UPLOAD_ERR_OK) {
        $error = get_string('commerce_mail_library_import_missing', 'local_subscriptions');
    } else if ((int)$_FILES['templatefile']['size'] > 2 * 1024 * 1024) {
        $error = get_string('commerce_mail_library_import_too_large', 'local_subscriptions');
    } else {
        $json = file_get_contents((string)$_FILES['templatefile']['tmp_name']);
        try {
            $record = (new CommerceMailLibraryImporter(new CommerceMailLibraryRepository($DB)))->import_json((string)$json, (int)$USER->id);
            redirect(new moodle_url('/local/subscriptions/admin/commerce/mail/templates/library_edit.php', ['id' => (int)$record->id]), get_string('commerce_mail_library_import_success', 'local_subscriptions'), null, \core\output\notification::NOTIFY_SUCCESS);
        } catch (\Throwable $exception) {
            $error = $exception->getMessage();
        }
    }
}
$form = html_writer::start_tag('form', ['method' => 'post', 'enctype' => 'multipart/form-data', 'action' => $pageurl->out(false), 'class' => 'commerce-mail-library-import-form']);
$form .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
$form .= html_writer::tag('i', '', ['class' => 'fa fa-upload commerce-mail-library-import-icon', 'aria-hidden' => 'true']);
$form .= html_writer::tag('h2', get_string('commerce_mail_library_import_drop_title', 'local_subscriptions'), ['class' => 'h5']);
$form .= html_writer::div(get_string('commerce_mail_library_import_help', 'local_subscriptions'), 'commerce-mail-library-import-help');
$form .= html_writer::empty_tag('input', ['type' => 'file', 'name' => 'templatefile', 'accept' => '.json,application/json', 'required' => 'required', 'class' => 'form-control']);
$form .= html_writer::div(
    html_writer::link($listurl, get_string('cancel'), ['class' => 'btn btn-outline-secondary'])
    . html_writer::tag('button', get_string('commerce_mail_library_import', 'local_subscriptions'), ['type' => 'submit', 'class' => 'btn btn-primary']),
    'commerce-mail-library-import-actions'
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
echo CrmPageHeader::render($title, get_string('commerce_mail_library_import_description', 'local_subscriptions'), HelpContext::COMMERCE);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::MAIL);
echo CommerceMailSectionNavigationRenderer::render(CommerceMailSectionNavigationRenderer::TEMPLATES);
if ($error !== '') { echo html_writer::div(s($error), 'alert alert-danger'); }
echo $form;
echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
