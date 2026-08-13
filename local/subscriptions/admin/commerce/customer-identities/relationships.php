<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\customer\identity\CommerceCustomerIdentityNavigationRenderer;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\crm\user360\identity\User360IdentityGraphRenderer;

$context = AdminSecurity::require(Capabilities::MANAGE_USERS);
$userid = optional_param('userid', 0, PARAM_INT);
$email = trim(optional_param('email', '', PARAM_EMAIL));

if ($userid <= 1 && $email !== '') {
    $userid = (int)$DB->get_field('user', 'id', ['email' => $email, 'deleted' => 0], IGNORE_MISSING);
}
$title = get_string('commerce_identity_relationships_title', 'local_subscriptions');
$pageurl = new moodle_url('/local/subscriptions/admin/commerce/customer-identities/relationships.php', $userid > 1 ? ['userid' => $userid] : []);
CrmPageConfigurator::configure($PAGE, $context, $pageurl, $title, 'local-subscriptions-commerce-customer-relationships-page');

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    ['label' => get_string('crm_commerce_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php')],
    ['label' => get_string('commerce_identity_reconciliation_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/customer-identities/index.php')],
    ['label' => $title, 'url' => null],
]);
echo CrmPageHeader::render($title, get_string('commerce_identity_relationships_description', 'local_subscriptions'), HelpContext::COMMERCE);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::IDENTITIES, $context);
echo CommerceCustomerIdentityNavigationRenderer::render(CommerceCustomerIdentityNavigationRenderer::RELATIONSHIPS);

echo html_writer::start_tag('form', ['method' => 'get', 'class' => 'card card-body mb-4']);
echo html_writer::tag('label', get_string('commerce_identity_relationships_userid', 'local_subscriptions'), ['for' => 'identity-userid', 'class' => 'form-label']);
echo html_writer::empty_tag('input', ['id' => 'identity-userid', 'type' => 'number', 'min' => 2, 'name' => 'userid', 'value' => $userid > 1 ? $userid : '', 'class' => 'form-control mb-2']);
echo html_writer::tag('label', get_string('commerce_identity_relationships_email', 'local_subscriptions'), ['for' => 'identity-email', 'class' => 'form-label']);
echo html_writer::empty_tag('input', ['id' => 'identity-email', 'type' => 'email', 'name' => 'email', 'value' => $email, 'class' => 'form-control mb-2']);
echo html_writer::tag('button', get_string('commerce_identity_relationships_inspect', 'local_subscriptions'), ['type' => 'submit', 'class' => 'btn btn-primary align-self-start']);
echo html_writer::end_tag('form');

if ($userid > 1) {
    echo User360IdentityGraphRenderer::render($userid);
} elseif ($email !== '') {
    echo User360IdentityGraphRenderer::render_email($email);
}

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
