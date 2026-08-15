<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\mail\admin\CommerceMailAdminPresentation;
use local_subscriptions\commerce\mail\admin\CommerceMailAdminService;
use local_subscriptions\commerce\mail\admin\CommerceMailSectionNavigationRenderer;
use local_subscriptions\commerce\mail\admin\CommerceMailPreviewRenderer;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::VIEW_PAYMENTS);
$id = required_param('id', PARAM_INT);
$previewview = CommerceMailPreviewRenderer::normalise_view(
    optional_param('view', CommerceMailPreviewRenderer::DESKTOP, PARAM_ALPHA)
);
$previewfont = CommerceMailPreviewRenderer::normalise_font(
    optional_param('font', CommerceMailPreviewRenderer::FONT_BRAND, PARAM_ALPHA)
);
$url = new moodle_url('/local/subscriptions/admin/commerce/mail/view.php', [
    'id' => $id,
    'font' => $previewfont,
]);
$title = get_string('commerce_mail_preview', 'local_subscriptions');

// The mail template renderer needs a fully initialised Moodle page context.
CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $url,
    $title,
    'local-subscriptions-commerce-mail-preview'
);
$PAGE->requires->css('/local/subscriptions/styles/commerce_mail_admin.css');

$service = new CommerceMailAdminService();
$record = $service->find($id);

if ($record === null) {
    throw new moodle_exception('invalidrecord');
}

$preview = $service->preview($id);
$typebadge = html_writer::span(
    s(CommerceMailAdminPresentation::type_label((string)$record->mailtype)),
    'badge rounded-pill ' . CommerceMailAdminPresentation::type_badge_class((string)$record->mailtype)
);
$statusbadge = html_writer::span(
    s(CommerceMailAdminPresentation::status_label((string)$record->status)),
    'badge rounded-pill ' . CommerceMailAdminPresentation::status_badge_class((string)$record->status)
);

$metadata = implode(' · ', [
    s($record->recipientemail),
    s(CommerceMailAdminPresentation::language_label((string)$record->language)),
    $typebadge,
    $statusbadge,
]);

$resendbutton = '';
if ((string)$record->status === 'sent') {
    $resendbutton = html_writer::link(
        new moodle_url('/local/subscriptions/admin/commerce/mail/action.php', [
            'id' => $id,
            'action' => 'resend',
            'sesskey' => sesskey(),
        ]),
        get_string('commerce_mail_resend', 'local_subscriptions'),
        [
            'class' => 'btn btn-primary',
            'onclick' => "return confirm('" . addslashes_js(
                get_string('commerce_mail_resend_confirm', 'local_subscriptions')
            ) . "');",
        ]
    );
}

$secondaryaction = '';
if (in_array($record->status, ['failed', 'queued'], true)) {
    $action = $record->status === 'failed' ? 'retry' : 'cancel';
    $label = $record->status === 'failed'
        ? get_string('retry', 'local_subscriptions')
        : get_string('cancel');

    $secondaryaction = html_writer::link(
        new moodle_url('/local/subscriptions/admin/commerce/mail/action.php', [
            'id' => $id,
            'action' => $action,
            'sesskey' => sesskey(),
        ]),
        $label,
        ['class' => 'btn btn-warning']
    );
}

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
    ['label' => $title, 'url' => null],
]);
echo CrmPageHeader::render(
    $title,
    get_string('commerce_mail_preview_description', 'local_subscriptions'),
    HelpContext::COMMERCE
);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::MAIL);
echo CommerceMailSectionNavigationRenderer::render(CommerceMailSectionNavigationRenderer::JOURNAL);

echo html_writer::tag('h2', s($preview['subject']), ['class' => 'h4 mb-2']);
echo html_writer::div(
    $metadata,
    'd-flex flex-wrap align-items-center gap-2 text-muted mb-3'
);

$technicaldetails = html_writer::tag('summary', get_string('commerce_mail_technical_details', 'local_subscriptions'))
    . html_writer::div(
        html_writer::div(get_string('commerce_mail_internal_id', 'local_subscriptions') . ': #' . (int)$record->id)
        . html_writer::div(get_string('commerce_mail_idempotency_key', 'local_subscriptions') . ': ' . s((string)$record->idempotencykey)),
        'small text-muted mt-2'
    );
echo html_writer::tag('details', $technicaldetails, ['class' => 'mb-3']);

if ((string)$record->status === 'sent') {
    $transport = !empty($CFG->smtphosts)
        ? get_string('commerce_mail_delivery_transport_smtp', 'local_subscriptions', s((string)$CFG->smtphosts))
        : get_string('commerce_mail_delivery_transport_local', 'local_subscriptions');
    $deliverydetails = [
        get_string('commerce_mail_delivery_sent_at', 'local_subscriptions',
            userdate((int)$record->timesent, get_string('strftimedatetimeshort', 'langconfig'))),
        get_string('commerce_mail_delivery_attempts', 'local_subscriptions', (int)$record->attemptcount),
        $transport,
    ];
    echo html_writer::div(
        html_writer::tag('strong', get_string('commerce_mail_delivery_accepted', 'local_subscriptions'))
        . html_writer::tag('div', implode(' · ', array_map('s', $deliverydetails)), ['class' => 'small mt-1'])
        . html_writer::tag('div', get_string('commerce_mail_delivery_disclaimer', 'local_subscriptions'), ['class' => 'small mt-1']),
        'alert alert-success py-2 px-3 mb-3'
    );
}

$previewnavigation = CommerceMailPreviewRenderer::render_navigation($url, $previewview);
$fontnavigation = in_array(
    $previewview,
    [CommerceMailPreviewRenderer::DESKTOP, CommerceMailPreviewRenderer::MOBILE],
    true
)
    ? CommerceMailPreviewRenderer::render_font_navigation($url, $previewfont)
    : '';
$sendnowbutton = '';
if ((string)$record->status === 'queued') {
    $sendnowbutton = html_writer::link(
        new moodle_url('/local/subscriptions/admin/commerce/mail/action.php', [
            'id' => $id, 'action' => 'sendnow', 'sesskey' => sesskey(),
        ]),
        html_writer::tag('i', '', ['class' => 'fa fa-bolt mr-1', 'aria-hidden' => 'true'])
            . get_string('commerce_mail_send_now', 'local_subscriptions'),
        ['class' => 'btn btn-primary']
    );
}
$toolbaractions = implode('', array_filter([$sendnowbutton, $resendbutton]));

$toolbar = html_writer::div(
    $previewnavigation,
    'commerce-mail-preview-toolbar__navigation'
);
if ($fontnavigation !== '') {
    $toolbar .= html_writer::div(
        $fontnavigation,
        'commerce-mail-preview-toolbar__font'
    );
}
if ($toolbaractions !== '') {
    $toolbar .= html_writer::div(
        $toolbaractions,
        'commerce-mail-preview-toolbar__actions'
    );
}

echo html_writer::div($toolbar, 'commerce-mail-preview-toolbar');

echo CommerceMailPreviewRenderer::render(
    $preview['html'],
    $preview['text'],
    $previewview,
    $previewfont
);

if ($secondaryaction !== '') {
    echo html_writer::div($secondaryaction, 'mt-3');
}

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
