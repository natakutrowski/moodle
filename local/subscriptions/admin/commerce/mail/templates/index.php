<?php

require_once(__DIR__ . '/../../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\mail\admin\CommerceMailAdminPresentation;
use local_subscriptions\commerce\mail\CommerceMailType;
use local_subscriptions\commerce\mail\template\studio\CommerceMailTemplateDefaults;
use local_subscriptions\commerce\mail\template\studio\CommerceMailTemplateRepository;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);
$url = new moodle_url('/local/subscriptions/admin/commerce/mail/templates/index.php');
$title = get_string('commerce_mail_templates_title', 'local_subscriptions');
CrmPageConfigurator::configure($PAGE, $context, $url, $title, 'local-subscriptions-commerce-mail-templates-page');

$repository = new CommerceMailTemplateRepository($DB);
$records = [];
foreach ($repository->get_all() as $record) {
    $records[$record->mailtype . ':' . $record->language] = $record;
}

$table = new html_table();
$table->attributes['class'] = 'generaltable table-hover align-middle';
$table->head = [
    get_string('commerce_mail_template_type', 'local_subscriptions'),
    get_string('commerce_mail_template_language', 'local_subscriptions'),
    get_string('status'),
    get_string('commerce_mail_template_subject', 'local_subscriptions'),
    get_string('lastmodified'),
    get_string('actions'),
];
foreach (CommerceMailType::all() as $mailtype) {
    foreach (['fr', 'en', 'ru'] as $language) {
        $record = $records[$mailtype . ':' . $language] ?? null;
        $editurl = new moodle_url('/local/subscriptions/admin/commerce/mail/templates/edit.php', [
            'mailtype' => $mailtype,
            'language' => $language,
        ]);
        $status = $record === null
            ? html_writer::span(get_string('commerce_mail_template_default', 'local_subscriptions'), 'badge text-bg-secondary')
            : html_writer::span(
                get_string($record->enabled ? 'enabled' : 'disabled'),
                'badge ' . ($record->enabled ? 'text-bg-success' : 'text-bg-warning')
            );
        $actions = html_writer::link(
            $editurl,
            get_string($record === null ? 'create' : 'edit'),
            ['class' => 'btn btn-sm btn-outline-primary']
        );
        $previewurl = new moodle_url('/local/subscriptions/admin/commerce/mail/templates/preview.php', [
            'mailtype' => $mailtype,
            'language' => $language,
        ]);
        $actions .= ' ' . html_writer::link(
            $previewurl,
            get_string('preview'),
            ['class' => 'btn btn-sm btn-outline-secondary']
        );
        if ($record !== null) {
            $reseturl = new moodle_url('/local/subscriptions/admin/commerce/mail/templates/reset.php', [
                'mailtype' => $mailtype,
                'language' => $language,
                'sesskey' => sesskey(),
            ]);
            $actions .= ' ' . html_writer::link(
                $reseturl,
                get_string('commerce_mail_template_reset', 'local_subscriptions'),
                [
                    'class' => 'btn btn-sm btn-outline-danger',
                    'data-confirmation' => 'modal',
                    'data-confirmation-title-str' => json_encode(['confirm', 'core']),
                    'data-confirmation-content-str' => json_encode(['commerce_mail_template_reset_confirm', 'local_subscriptions']),
                    'data-confirmation-yes-button-str' => json_encode(['yes', 'core']),
                    'data-confirmation-destination' => $reseturl->out(false),
                ]
            );
        }
        $table->data[] = [
            html_writer::span(s(CommerceMailAdminPresentation::type_label($mailtype)), 'badge rounded-pill ' . CommerceMailAdminPresentation::type_badge_class($mailtype)),
            s(CommerceMailAdminPresentation::language_label($language)),
            $status,
            s($record === null ? CommerceMailTemplateDefaults::get($mailtype, $language)['subject'] : $record->subject),
            $record === null ? '—' : userdate((int)$record->timemodified, get_string('strftimedatetimeshort', 'langconfig')),
            $actions,
        ];
    }
}

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    ['label' => get_string('crm_commerce_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php')],
    ['label' => get_string('commerce_mail_admin_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/mail/index.php')],
    ['label' => $title, 'url' => null],
]);
echo CrmPageHeader::render($title, get_string('commerce_mail_templates_description', 'local_subscriptions'), HelpContext::COMMERCE);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::MAIL);
echo html_writer::div(html_writer::link(new moodle_url('/local/subscriptions/admin/commerce/mail/index.php'), get_string('commerce_mail_back_to_log', 'local_subscriptions'), ['class' => 'btn btn-outline-secondary']), 'mb-3');
echo html_writer::table($table);
echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
