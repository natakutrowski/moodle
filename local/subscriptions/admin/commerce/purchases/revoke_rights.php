<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseReadRepository;
use local_subscriptions\commerce\purchase\revocation\CommercePurchaseRightsRevocationService;
use local_subscriptions\crm\commerce\presentation\CommerceDesignSystemRenderer;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::MANAGE_SUBSCRIPTIONS);
$id = required_param('id', PARAM_INT);

$repository = new CommercePurchaseReadRepository($DB);
$purchase = $repository->find_by_id($id);
if ($purchase === null) {
    throw new moodle_exception('commerce_purchase_not_found', 'local_subscriptions');
}

$service = CommercePurchaseRightsRevocationService::create($DB);
$rights = $service->preview($purchase->summary->reference);
$returnurl = new moodle_url(
    '/local/subscriptions/admin/commerce/purchases/view.php',
    ['id' => $id]
);
$pageurl = new moodle_url(
    '/local/subscriptions/admin/commerce/purchases/revoke_rights.php',
    ['id' => $id]
);
$title = get_string('commerce_rights_revoke_page_title', 'local_subscriptions');

if ($rights === []) {
    redirect(
        $returnurl,
        get_string('commerce_rights_nothing_to_revoke', 'local_subscriptions'),
        null,
        \core\output\notification::NOTIFY_INFO
    );
}

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $title,
    'local-subscriptions-commerce-revoke-rights-page'
);

if (data_submitted()) {
    require_sesskey();

    $confirmed = optional_param('confirmrevoke', 0, PARAM_BOOL);
    $reason = optional_param('reason', '', PARAM_TEXT);

    if (!$confirmed) {
        redirect(
            $pageurl,
            get_string('commerce_rights_confirmation_required', 'local_subscriptions'),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }

    $result = $service->revoke(
        $purchase->summary->reference,
        (int)$USER->id,
        $reason
    );

    redirect(
        new moodle_url(
            '/local/subscriptions/admin/commerce/purchases/view.php',
            [
                'id' => $id,
                'rights_revoked' => $result['revoked'] > 0 ? 1 : 0,
            ]
        ),
        get_string(
            'commerce_rights_revoke_success',
            'local_subscriptions',
            $result['revoked']
        ),
        null,
        \core\output\notification::NOTIFY_SUCCESS
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
        'label' => get_string('commerce_purchases_title', 'local_subscriptions'),
        'url' => new moodle_url('/local/subscriptions/admin/commerce/purchases/index.php'),
    ],
    [
        'label' => $purchase->summary->publicreference ?: $purchase->summary->reference,
        'url' => $returnurl,
    ],
    [
        'label' => $title,
        'url' => null,
    ],
]);

echo CrmPageHeader::render(
    $title,
    get_string('commerce_rights_revoke_page_help', 'local_subscriptions'),
    HelpContext::COMMERCE
);
echo CommerceSectionNavigationRenderer::render(
    CommerceSectionNavigationRenderer::PURCHASES
);

$items = '';
foreach ($rights as $right) {
    $items .= html_writer::tag(
        'li',
        s($right['productsku'] . ' — ' . $right['type'])
    );
}

echo CommerceDesignSystemRenderer::panel(
    get_string('commerce_rights_revoke_summary_title', 'local_subscriptions'),
    html_writer::tag(
        'p',
        s(get_string('commerce_rights_revoke_warning', 'local_subscriptions')),
        ['class' => 'mb-2']
    ) . html_writer::tag('ul', $items, ['class' => 'mb-0']),
    'mt-3'
);

$form = html_writer::start_tag('form', [
    'method' => 'post',
    'action' => $pageurl->out(false),
    'class' => 'mt-3',
]);
$form .= html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'id',
    'value' => $id,
]);
$form .= html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'sesskey',
    'value' => sesskey(),
]);

$form .= html_writer::start_div('mb-3');
$form .= html_writer::tag(
    'label',
    get_string('commerce_rights_revoke_reason', 'local_subscriptions'),
    [
        'for' => 'commerce-rights-revoke-reason',
        'class' => 'form-label fw-semibold',
    ]
);
$form .= html_writer::empty_tag('input', [
    'id' => 'commerce-rights-revoke-reason',
    'name' => 'reason',
    'type' => 'text',
    'class' => 'form-control',
    'maxlength' => '255',
]);
$form .= html_writer::end_div();

$form .= html_writer::div(
    html_writer::checkbox(
        'confirmrevoke',
        '1',
        false,
        '',
        [
            'id' => 'commerce-rights-revoke-confirm',
            'class' => 'form-check-input',
            'required' => 'required',
        ]
    )
    . html_writer::tag(
        'label',
        get_string('commerce_rights_revoke_confirmation', 'local_subscriptions'),
        [
            'for' => 'commerce-rights-revoke-confirm',
            'class' => 'form-check-label ms-2',
        ]
    ),
    'form-check mb-4'
);

$form .= html_writer::start_div('d-flex flex-wrap gap-2');
$form .= html_writer::tag(
    'button',
    get_string('commerce_rights_revoke_submit', 'local_subscriptions'),
    [
        'type' => 'submit',
        'class' => 'btn btn-danger',
    ]
);
$form .= html_writer::link(
    $returnurl,
    get_string('cancel'),
    ['class' => 'btn btn-outline-secondary']
);
$form .= html_writer::end_div();
$form .= html_writer::end_tag('form');

echo CommerceDesignSystemRenderer::panel(
    get_string('commerce_rights_revoke_form_title', 'local_subscriptions'),
    $form,
    'mt-4'
);

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
