<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\customer\identity\CommerceCustomerIdentityNavigationRenderer;
use local_subscriptions\commerce\customer\quality\CommerceEmailQualityService;
use local_subscriptions\commerce\customer\quality\CommerceLegacyDigitalIdentityService;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::MANAGE_USERS);
$id = required_param('id', PARAM_INT);
$returnurlraw = optional_param('returnurl', '', PARAM_LOCALURL);
$returnurl = $returnurlraw !== '' ? new moodle_url($returnurlraw) : new moodle_url('/local/subscriptions/admin/commerce/customer-identities/legacy-quality.php');
$service = new CommerceLegacyDigitalIdentityService($DB, new CommerceEmailQualityService());
$record = $service->get($id);
$diagnostic = (new CommerceEmailQualityService())->diagnose((string)$record->email);

$pageurl = new moodle_url('/local/subscriptions/admin/commerce/customer-identities/legacy-edit.php', ['id' => $id, 'returnurl' => $returnurl->out(false)]);
$title = get_string('commerce_identity_legacy_edit_title', 'local_subscriptions');
CrmPageConfigurator::configure($PAGE, $context, $pageurl, $title, 'local-subscriptions-commerce-customer-identities-legacy-edit-page');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();
    $email = trim(required_param('email', PARAM_RAW_TRIMMED));
    $firstname = trim(required_param('firstname', PARAM_TEXT));
    $lastname = trim(required_param('lastname', PARAM_TEXT));
    $updatesame = optional_param('updatesame', 0, PARAM_BOOL) === 1;
    try {
        $result = $service->update($id, $email, $firstname, $lastname, $updatesame);
        redirect($returnurl, get_string('commerce_identity_legacy_edit_success', 'local_subscriptions', $result['updated']), null, \core\output\notification::NOTIFY_SUCCESS);
    } catch (\Throwable $e) {
        $error = $e->getMessage();
        $record->email = $email;
        $record->firstname = $firstname;
        $record->lastname = $lastname;
    }
}

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    ['label' => get_string('crm_commerce_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php')],
    ['label' => get_string('commerce_identity_legacy_quality_title', 'local_subscriptions'), 'url' => $returnurl],
    ['label' => $title, 'url' => null],
]);
echo CrmPageHeader::render($title, get_string('commerce_identity_legacy_edit_description', 'local_subscriptions'), HelpContext::COMMERCE);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::IDENTITIES, $context);
echo CommerceCustomerIdentityNavigationRenderer::render(CommerceCustomerIdentityNavigationRenderer::LEGACY_QUALITY);

if ($error !== '') {
    echo html_writer::div(s($error), 'alert alert-danger');
}
if ($diagnostic->suggestion !== null) {
    echo html_writer::div(get_string('commerce_identity_legacy_edit_detected', 'local_subscriptions', $diagnostic->suggestion), 'alert alert-warning');
}
echo html_writer::div(get_string('commerce_identity_legacy_edit_scope_notice', 'local_subscriptions'), 'alert alert-info');

echo html_writer::start_tag('form', ['method' => 'post', 'action' => $pageurl->out(false), 'class' => 'card card-body']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
foreach ([
    ['firstname', 'firstname', (string)$record->firstname, 'text'],
    ['lastname', 'lastname', (string)$record->lastname, 'text'],
    ['email', 'email', (string)$record->email, 'email'],
] as [$name, $labelkey, $value, $type]) {
    echo html_writer::start_div('mb-3');
    echo html_writer::tag('label', get_string($labelkey), ['for' => 'legacy-edit-' . $name, 'class' => 'form-label']);
    $attrs = ['id' => 'legacy-edit-' . $name, 'name' => $name, 'type' => $type, 'value' => $value, 'class' => 'form-control'];
    if ($name === 'email') {
        $attrs['required'] = 'required';
    }
    echo html_writer::empty_tag('input', $attrs);
    echo html_writer::end_div();
}
echo html_writer::start_div('form-check mb-4');
echo html_writer::empty_tag('input', ['id' => 'legacy-edit-updatesame', 'name' => 'updatesame', 'type' => 'checkbox', 'value' => '1', 'class' => 'form-check-input', 'checked' => 'checked']);
echo html_writer::tag('label', get_string('commerce_identity_legacy_edit_update_same', 'local_subscriptions'), ['for' => 'legacy-edit-updatesame', 'class' => 'form-check-label']);
echo html_writer::end_div();
echo html_writer::start_div('d-flex gap-2');
echo html_writer::tag('button', get_string('savechanges'), ['type' => 'submit', 'class' => 'btn btn-primary']);
echo html_writer::link($returnurl, get_string('cancel'), ['class' => 'btn btn-outline-secondary']);
echo html_writer::end_div();
echo html_writer::end_tag('form');

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
