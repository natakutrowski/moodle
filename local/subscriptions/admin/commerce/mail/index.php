<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\mail\admin\CommerceMailAdminPresentation;
use local_subscriptions\commerce\mail\admin\CommerceMailAdminService;
use local_subscriptions\commerce\mail\admin\CommerceMailHealthRenderer;
use local_subscriptions\commerce\mail\certification\CommerceMailEngineCertificationService;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::VIEW_PAYMENTS);

$q = optional_param('q', '', PARAM_RAW_TRIMMED);
$status = optional_param('status', '', PARAM_ALPHANUMEXT);
$mailtype = optional_param('mailtype', '', PARAM_ALPHANUMEXT);
$language = optional_param('language', '', PARAM_ALPHANUMEXT);
$purchaseid = optional_param('purchaseid', 0, PARAM_INT);
$page = max(0, optional_param('page', 0, PARAM_INT));
$perpage = 25;

$service = new CommerceMailAdminService();
$result = $service->search(
    compact('q', 'status', 'mailtype', 'language', 'purchaseid'),
    $page,
    $perpage
);

$baseparams = array_filter([
    'q' => $q,
    'status' => $status,
    'mailtype' => $mailtype,
    'language' => $language,
    'purchaseid' => $purchaseid ?: null,
], static fn($value): bool => $value !== '' && $value !== null);

$url = new moodle_url('/local/subscriptions/admin/commerce/mail/index.php', $baseparams);
$title = get_string('commerce_mail_admin_title', 'local_subscriptions');

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $url,
    $title,
    'local-subscriptions-commerce-mail-page'
);
$PAGE->requires->css('/local/subscriptions/styles/commerce_mail_admin.css');
$healthreport = (new CommerceMailEngineCertificationService($DB))->certify();

$filteroptions = ['' => get_string('commerce_mail_filter_all', 'local_subscriptions')];

$form = html_writer::start_tag('form', [
    'method' => 'get',
    'class' => 'row g-2 mb-4 align-items-end',
]);
$form .= html_writer::div(
    html_writer::label(
        get_string('search'),
        'commerce-mail-q',
        false,
        ['class' => 'form-label']
    ) . html_writer::empty_tag('input', [
        'id' => 'commerce-mail-q',
        'name' => 'q',
        'value' => $q,
        'class' => 'form-control',
        'placeholder' => get_string('commerce_mail_search_placeholder', 'local_subscriptions'),
    ]),
    'col-md-3'
);
$form .= html_writer::div(
    html_writer::label(
        get_string('commerce_mail_status_filter', 'local_subscriptions'),
        'commerce-mail-status',
        false,
        ['class' => 'form-label']
    ) . html_writer::select(
        $filteroptions + CommerceMailAdminPresentation::status_options(),
        'status',
        $status,
        false,
        ['id' => 'commerce-mail-status', 'class' => 'form-select']
    ),
    'col-md-2'
);
$form .= html_writer::div(
    html_writer::label(
        get_string('commerce_mail_type_filter', 'local_subscriptions'),
        'commerce-mail-type',
        false,
        ['class' => 'form-label']
    ) . html_writer::select(
        $filteroptions + CommerceMailAdminPresentation::type_options(),
        'mailtype',
        $mailtype,
        false,
        ['id' => 'commerce-mail-type', 'class' => 'form-select']
    ),
    'col-md-3'
);
$form .= html_writer::div(
    html_writer::label(
        get_string('commerce_mail_language_filter', 'local_subscriptions'),
        'commerce-mail-language',
        false,
        ['class' => 'form-label']
    ) . html_writer::select(
        $filteroptions + CommerceMailAdminPresentation::language_options(),
        'language',
        $language,
        false,
        ['id' => 'commerce-mail-language', 'class' => 'form-select']
    ),
    'col-md-2'
);
$form .= html_writer::div(
    html_writer::label(
        get_string('commerce_mail_purchase_id', 'local_subscriptions'),
        'commerce-mail-purchaseid',
        false,
        ['class' => 'form-label']
    ) . html_writer::empty_tag('input', [
        'id' => 'commerce-mail-purchaseid',
        'name' => 'purchaseid',
        'value' => $purchaseid ?: '',
        'class' => 'form-control',
        'inputmode' => 'numeric',
        'placeholder' => get_string('commerce_mail_purchase_id', 'local_subscriptions'),
    ]),
    'col-md-1'
);
$form .= html_writer::div(
    html_writer::empty_tag('input', [
        'type' => 'submit',
        'value' => get_string('filter'),
        'class' => 'btn btn-outline-primary w-100',
    ]),
    'col-md-1'
);
$form .= html_writer::end_tag('form');

$table = new html_table();
$table->attributes['class'] = 'generaltable table-hover align-middle';
$table->head = [
    'ID',
    get_string('date', 'local_subscriptions'),
    get_string('type', 'local_subscriptions'),
    get_string('status', 'local_subscriptions'),
    get_string('email', 'local_subscriptions'),
    get_string('language', 'local_subscriptions'),
    get_string('commerce_mail_purchase_id', 'local_subscriptions'),
    get_string('attempts', 'local_subscriptions'),
    get_string('actions', 'local_subscriptions'),
];

foreach ($result['records'] as $record) {
    $viewurl = new moodle_url(
        '/local/subscriptions/admin/commerce/mail/view.php',
        ['id' => $record->id]
    );

    $typebadge = html_writer::span(
        s(CommerceMailAdminPresentation::type_label((string)$record->mailtype)),
        'badge rounded-pill ' . CommerceMailAdminPresentation::type_badge_class((string)$record->mailtype)
    );
    $statusbadge = html_writer::span(
        s(CommerceMailAdminPresentation::status_label((string)$record->status)),
        'badge rounded-pill ' . CommerceMailAdminPresentation::status_badge_class((string)$record->status)
    );

    $table->data[] = [
        (int)$record->id,
        userdate((int)$record->timecreated, get_string('strftimedatetimeshort', 'langconfig')),
        $typebadge,
        $statusbadge,
        s($record->recipientemail),
        s(CommerceMailAdminPresentation::language_label((string)$record->language)),
        $record->purchaseid === null ? '—' : (int)$record->purchaseid,
        (int)$record->attemptcount . '/' . (int)$record->maxattempts,
        html_writer::link(
            $viewurl,
            get_string('view'),
            ['class' => 'btn btn-sm btn-outline-primary']
        ),
    ];
}

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    [
        'label' => get_string('crm_commerce_title', 'local_subscriptions'),
        'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php'),
    ],
    ['label' => $title, 'url' => null],
]);
echo CrmPageHeader::render(
    $title,
    get_string('commerce_mail_admin_description', 'local_subscriptions'),
    HelpContext::COMMERCE
);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::MAIL);
echo CommerceMailHealthRenderer::render($healthreport);
echo html_writer::div(
    html_writer::link(
        new moodle_url('/local/subscriptions/admin/commerce/mail/templates/index.php'),
        get_string('commerce_mail_templates_manage', 'local_subscriptions'),
        ['class' => 'btn btn-primary']
    ),
    'mb-3'
);
echo $form;
echo html_writer::table($table);
echo $OUTPUT->paging_bar($result['total'], $page, $perpage, $url);
echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
