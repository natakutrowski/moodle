<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\mail\admin\CommerceMailAdminService;
use local_subscriptions\commerce\mail\builder\CommerceMailBuilder;
use local_subscriptions\commerce\mail\builder\CommerceMailBuilderEditorRenderer;
use local_subscriptions\commerce\mail\CommerceMailContext;
use local_subscriptions\commerce\mail\CommerceMailIdempotencyKey;
use local_subscriptions\commerce\mail\CommerceMailRecipient;
use local_subscriptions\commerce\mail\CommerceMailRequest;
use local_subscriptions\commerce\mail\CommerceMailRuntime;
use local_subscriptions\commerce\mail\CommerceMailType;
use local_subscriptions\commerce\mail\sales\CommerceSalesFollowupService;
use local_subscriptions\commerce\purchase\presentation\CommercePurchasePresentation;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::MANAGE_SUBSCRIPTIONS);
$id = required_param('id', PARAM_INT);

$service = CommerceSalesFollowupService::create($DB);
$details = $service->details($id);
$service->assert_eligible($details);
$summary = $details->summary;
$language = $service->language($details);
$tokens = $service->context($details);
$history = $service->previous_followups($id);
$templateoptions = $service->template_options((int)$USER->id);

if ($templateoptions === []) {
    throw new \moodle_exception(
        'commerce_sales_followup_no_templates',
        'local_subscriptions'
    );
}

$templateid = optional_param('templateid', 0, PARAM_INT);
if (!isset($templateoptions[$templateid])) {
    $status = strtolower($summary->paymentstatus);
    $needle = in_array($status, ['failed', 'declined', 'error'], true)
        ? 'échoué'
        : (in_array($status, ['cancelled', 'canceled'], true) ? 'annulé' : 'attente');
    $templateid = (int)array_key_first($templateoptions);
    foreach ($templateoptions as $candidateid => $label) {
        if (str_contains(core_text::strtolower($label), $needle)) {
            $templateid = (int)$candidateid;
            break;
        }
    }
}

$templatecontent = $service->template_content($templateid, $language);
$subject = optional_param('subject', $templatecontent['subject'], PARAM_TEXT);
$bodyhtml = optional_param('bodyhtml', $templatecontent['bodyhtml'], PARAM_RAW);
$mode = optional_param('mode', '', PARAM_ALPHA);
$preview = null;

$pageurl = new moodle_url(
    '/local/subscriptions/admin/commerce/purchases/followup_mail.php',
    ['id' => $id, 'templateid' => $templateid]
);
$title = get_string('commerce_sales_followup_title', 'local_subscriptions');

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $title,
    'local-subscriptions-commerce-sales-followup-page'
);
$PAGE->requires->css('/local/subscriptions/styles/commerce_mail_admin.css');
CommerceMailBuilderEditorRenderer::require_copy_behaviour($PAGE);

$buildrequest = static function(
    string $idempotencykey
) use (
    $summary,
    $language,
    $tokens,
    $subject,
    $bodyhtml,
    $templateid
): CommerceMailRequest {
    $recipientname = $summary->customer->display_name();
    return new CommerceMailRequest(
        CommerceMailType::SALES_FOLLOWUP,
        new CommerceMailRecipient(
            $summary->customer->email,
            $recipientname,
            $summary->customer->userid
        ),
        new CommerceMailContext([
            'subject' => $subject,
            'bodyhtml' => $bodyhtml,
            'tokens' => $tokens,
            'source_template_id' => $templateid,
            'resume_payment_label' => get_string(
                'commerce_sales_followup_resume_payment',
                'local_subscriptions'
            ),
            'manual' => [
                'byuserid' => (int)$GLOBALS['USER']->id,
                'timecreated' => time(),
            ],
        ]),
        $language,
        $idempotencykey,
        (int)$summary->id
    );
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();

    // Re-read immediately before preview/send. In particular, never send a
    // recovery mail after the payment has since become successful.
    $details = $service->details($id);
    $service->assert_eligible($details);

    if ($mode === 'preview') {
        $request = $buildrequest(
            CommerceMailIdempotencyKey::normalise(
                'sales-followup-preview-' . $id . '-' . (int)$USER->id . '-' . time()
            )
        );
        $message = CommerceMailRuntime::template_registry()
            ->get(CommerceMailType::SALES_FOLLOWUP)
            ->render($request);
        $preview = [
            'subject' => $message->get_subject(),
            'html' => $message->get_html(),
        ];
    } else if ($mode === 'send') {
        $key = CommerceMailIdempotencyKey::normalise(sprintf(
            'sales-followup-%d-%d-%d',
            $id,
            (int)$USER->id,
            time()
        ));
        $record = CommerceMailRuntime::queue_service()->queue($buildrequest($key));
        $result = (new CommerceMailAdminService())->send_now((int)$record->id);
        $sent = (int)($result['sent'] ?? 0) === 1;

        redirect(
            new moodle_url('/local/subscriptions/admin/commerce/mail/view.php', [
                'id' => (int)$record->id,
            ]),
            get_string(
                $sent
                    ? 'commerce_sales_followup_sent'
                    : 'commerce_sales_followup_send_failed',
                'local_subscriptions'
            ),
            null,
            $sent
                ? \core\output\notification::NOTIFY_SUCCESS
                : \core\output\notification::NOTIFY_WARNING
        );
    }
}

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    [
        'label' => get_string('crm_commerce_title', 'local_subscriptions'),
        'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php'),
    ],
    [
        'label' => get_string('commerce_purchases_title', 'local_subscriptions'),
        'url' => new moodle_url('/local/subscriptions/admin/commerce/purchases/index.php'),
    ],
    [
        'label' => $summary->publicreference !== ''
            ? $summary->publicreference
            : $summary->reference,
        'url' => new moodle_url(
            '/local/subscriptions/admin/commerce/purchases/view.php',
            ['id' => $id]
        ),
    ],
    ['label' => $title, 'url' => null],
]);
echo CrmPageHeader::render(
    $title,
    get_string('commerce_sales_followup_description', 'local_subscriptions'),
    HelpContext::COMMERCE
);
echo CommerceSectionNavigationRenderer::render(
    CommerceSectionNavigationRenderer::PURCHASES,
    $context
);

echo html_writer::start_div('commerce-sales-followup-layout');

echo html_writer::start_tag('aside', [
    'class' => 'commerce-sales-followup-context card card-body',
]);
echo html_writer::tag(
    'h3',
    get_string('commerce_sales_followup_context_title', 'local_subscriptions'),
    ['class' => 'h6 mb-3']
);
$contextrows = [
    get_string('commerce_sales_followup_customer', 'local_subscriptions')
        => ($summary->customer->display_name() ?: $summary->customer->email),
    get_string('commerce_sales_followup_order', 'local_subscriptions')
        => (string)$tokens['order_reference'],
    get_string('commerce_sales_followup_product', 'local_subscriptions')
        => (string)$tokens['product_name'],
    get_string('commerce_sales_followup_amount', 'local_subscriptions')
        => CommercePurchasePresentation::money($summary->totalminor, $summary->currency),
    get_string('commerce_sales_followup_payment_status', 'local_subscriptions')
        => $summary->paymentstatus,
    get_string('commerce_sales_followup_language', 'local_subscriptions')
        => strtoupper($language),
];
foreach ($contextrows as $label => $value) {
    echo html_writer::div(
        html_writer::span(s($label), 'commerce-sales-followup-context-label')
        . html_writer::span(s($value), 'commerce-sales-followup-context-value'),
        'commerce-sales-followup-context-row'
    );
}
if ((string)$tokens['checkout_url'] === '') {
    echo html_writer::div(
        get_string(
            'commerce_sales_followup_no_resume_url',
            'local_subscriptions'
        ),
        'alert alert-light border small mt-3 mb-0'
    );
}

if ($history['count'] > 0 && $history['last']) {
    $last = $history['last'];
    echo html_writer::div(
        html_writer::tag(
            'i',
            '',
            ['class' => 'fa fa-history me-1', 'aria-hidden' => 'true']
        )
        . get_string(
            'commerce_sales_followup_previous',
            'local_subscriptions',
            (object)[
                'count' => $history['count'],
                'date' => userdate(
                    (int)$last->timecreated,
                    get_string('strftimedatetimeshort', 'langconfig')
                ),
            ]
        ),
        'commerce-sales-followup-history'
    );
}
echo html_writer::end_tag('aside');

echo html_writer::start_div('commerce-sales-followup-editor');

echo html_writer::start_tag('form', [
    'method' => 'get',
    'action' => (new moodle_url(
        '/local/subscriptions/admin/commerce/purchases/followup_mail.php'
    ))->out(false),
    'class' => 'commerce-sales-followup-template-picker card card-body mb-3',
]);
echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'id',
    'value' => $id,
]);
echo html_writer::tag(
    'label',
    get_string('commerce_sales_followup_template', 'local_subscriptions'),
    ['for' => 'sales-followup-template', 'class' => 'form-label fw-semibold']
);
echo html_writer::div(
    html_writer::link(
        new moodle_url('/local/subscriptions/admin/commerce/mail/templates/index.php', [
            'category' => 'sales_followup',
        ]),
        get_string('commerce_sales_followup_open_library', 'local_subscriptions'),
        ['class' => 'small']
    ),
    'commerce-sales-followup-library-link'
);
echo html_writer::div(
    html_writer::select(
        $templateoptions,
        'templateid',
        $templateid,
        false,
        ['id' => 'sales-followup-template', 'class' => 'form-select']
    )
    . html_writer::tag(
        'button',
        get_string('commerce_sales_followup_load_template', 'local_subscriptions'),
        ['type' => 'submit', 'class' => 'btn btn-outline-secondary']
    ),
    'commerce-sales-followup-template-row'
);
echo html_writer::end_tag('form');

echo html_writer::start_tag('form', [
    'method' => 'post',
    'action' => $pageurl->out(false),
    'class' => 'commerce-sales-followup-compose card card-body',
]);
echo html_writer::empty_tag('input', [
    'type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey(),
]);
echo html_writer::empty_tag('input', [
    'type' => 'hidden', 'name' => 'templateid', 'value' => $templateid,
]);

echo html_writer::tag(
    'label',
    get_string('commerce_mail_library_subject', 'local_subscriptions'),
    ['for' => 'sales-followup-subject', 'class' => 'form-label fw-semibold']
);
echo html_writer::empty_tag('input', [
    'type' => 'text',
    'id' => 'sales-followup-subject',
    'name' => 'subject',
    'value' => $subject,
    'class' => 'form-control mb-3',
    'required' => 'required',
]);

echo CommerceMailBuilderEditorRenderer::tag_palette(
    CommerceMailBuilder::sales_followup_variables(),
    CommerceMailBuilder::sales_followup_structural_tags()
);

echo html_writer::tag(
    'label',
    get_string('commerce_mail_library_body', 'local_subscriptions'),
    ['for' => 'sales-followup-body', 'class' => 'form-label fw-semibold mt-3']
);
echo CommerceMailBuilderEditorRenderer::rich_editor(
    'sales-followup-body',
    'bodyhtml',
    $bodyhtml,
    $context,
    true,
    14
);

echo html_writer::div(
    html_writer::tag(
        'button',
        html_writer::tag('i', '', [
            'class' => 'fa fa-eye me-1',
            'aria-hidden' => 'true',
        ]) . get_string('commerce_sales_followup_preview', 'local_subscriptions'),
        [
            'type' => 'submit',
            'name' => 'mode',
            'value' => 'preview',
            'class' => 'btn btn-outline-primary',
        ]
    )
    . html_writer::tag(
        'button',
        html_writer::tag('i', '', [
            'class' => 'fa fa-paper-plane me-1',
            'aria-hidden' => 'true',
        ]) . get_string('commerce_sales_followup_send_now', 'local_subscriptions'),
        [
            'type' => 'submit',
            'name' => 'mode',
            'value' => 'send',
            'class' => 'btn btn-primary',
            'onclick' => "return confirm('" . addslashes(
                get_string(
                    'commerce_sales_followup_send_confirm',
                    'local_subscriptions'
                )
            ) . "');",
            'data-confirmation' => 'modal',
            'data-confirmation-title-str' => json_encode(['confirm', 'core']),
            'data-confirmation-content-str' => json_encode([
                'commerce_sales_followup_send_confirm',
                'local_subscriptions',
            ]),
            'data-confirmation-yes-button-str' => json_encode([
                'commerce_sales_followup_send_now',
                'local_subscriptions',
            ]),
        ]
    ),
    'commerce-sales-followup-actions'
);
echo html_writer::end_tag('form');

if ($preview !== null) {
    echo html_writer::start_div(
        'commerce-sales-followup-preview card card-body mt-3'
    );
    echo html_writer::tag(
        'h3',
        get_string('commerce_sales_followup_preview_title', 'local_subscriptions'),
        ['class' => 'h6']
    );
    echo html_writer::div(
        html_writer::tag('strong', s($preview['subject'])),
        'commerce-sales-followup-preview-subject'
    );
    echo html_writer::tag('iframe', '', [
        'class' => 'commerce-sales-followup-preview-frame',
        'srcdoc' => $preview['html'],
        'title' => get_string(
            'commerce_sales_followup_preview_title',
            'local_subscriptions'
        ),
    ]);
    echo html_writer::end_div();
}

echo html_writer::end_div();
echo html_writer::end_div();

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
