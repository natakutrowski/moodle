<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\customer\identity\CommerceCustomerIdentityNavigationRenderer;
use local_subscriptions\commerce\customer\identity\CommerceCustomerIdentitySimilarityService;
use local_subscriptions\commerce\customer\identity\CommerceLegacyDigitalIdentityLinkService;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::MANAGE_USERS);

$legacyemail = trim(required_param('email', PARAM_EMAIL));
$targetuserid = required_param('targetuserid', PARAM_INT);
$action = optional_param('action', 'preview', PARAM_ALPHA);

$pageurl = new moodle_url(
    '/local/subscriptions/admin/commerce/customer-identities/legacy-link.php',
    [
        'email' => $legacyemail,
        'targetuserid' => $targetuserid,
    ]
);
$title = get_string(
    'commerce_identity_legacy_link_title',
    'local_subscriptions'
);

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $title,
    'local-subscriptions-commerce-customer-identities-legacy-link-page'
);

$service = new CommerceLegacyDigitalIdentityLinkService(
    $DB,
    new CommerceCustomerIdentitySimilarityService($DB)
);

$preview = $service->preview(
    $legacyemail,
    $targetuserid
);
$executed = false;

if ($action === 'execute') {
    require_sesskey();

    if (!optional_param('confirm', 0, PARAM_BOOL)) {
        throw new moodle_exception(
            'commerce_identity_legacy_link_confirmation_required',
            'local_subscriptions'
        );
    }

    $preview = $service->execute(
        $legacyemail,
        $targetuserid,
        (int)$USER->id
    );
    $executed = true;
}

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(
    CrmNavigationKeys::COMMERCE,
    $context
);
echo CrmBreadcrumbRenderer::render([
    [
        'label' => get_string(
            'crm_commerce_title',
            'local_subscriptions'
        ),
        'url' => new moodle_url(
            '/local/subscriptions/admin/commerce/index.php'
        ),
    ],
    [
        'label' => get_string(
            'commerce_identity_reconciliation_title',
            'local_subscriptions'
        ),
        'url' => new moodle_url(
            '/local/subscriptions/admin/commerce/customer-identities/index.php'
        ),
    ],
    [
        'label' => get_string(
            'commerce_identity_nav_provisioning',
            'local_subscriptions'
        ),
        'url' => new moodle_url(
            '/local/subscriptions/admin/commerce/customer-identities/provisioning.php'
        ),
    ],
    [
        'label' => $title,
        'url' => null,
    ],
]);

echo CrmPageHeader::render(
    $title,
    get_string(
        'commerce_identity_legacy_link_description',
        'local_subscriptions'
    ),
    HelpContext::COMMERCE
);
echo CommerceSectionNavigationRenderer::render(
    CommerceSectionNavigationRenderer::IDENTITIES,
    $context
);
echo CommerceCustomerIdentityNavigationRenderer::render(
    CommerceCustomerIdentityNavigationRenderer::PROVISIONING
);

if ($executed) {
    echo html_writer::div(
        get_string(
            'commerce_identity_legacy_link_success',
            'local_subscriptions',
            (object)[
                'count' => $preview->legacypurchases,
                'userid' => $preview->targetuserid,
            ]
        ),
        'alert alert-success'
    );
} else {
    echo html_writer::div(
        get_string(
            'commerce_identity_legacy_link_dryrun',
            'local_subscriptions'
        ),
        'alert alert-info'
    );
}

$table = new html_table();
$table->attributes['class'] =
    'generaltable table table-hover align-middle';
$table->head = [
    get_string(
        'commerce_identity_legacy_link_source',
        'local_subscriptions'
    ),
    get_string(
        'commerce_identity_legacy_link_target',
        'local_subscriptions'
    ),
    get_string(
        'commerce_identity_similarity_score',
        'local_subscriptions'
    ),
];

$source = s(
    trim(
        $preview->legacyfirstname
        . ' '
        . $preview->legacylastname
    )
)
    . html_writer::div(
        s($preview->legacyemail)
            . ' · '
            . get_string(
                'commerce_identity_legacy_link_purchase_count',
                'local_subscriptions',
                $preview->legacypurchases
            ),
        'small text-muted'
    );

$target = html_writer::link(
    new moodle_url(
        '/local/subscriptions/admin/users/view.php',
        ['id' => $preview->targetuserid]
    ),
    s($preview->targetfullname)
)
    . html_writer::div(
        s($preview->targetemail)
            . ' · #'
            . $preview->targetuserid,
        'small text-muted'
    );

$table->data[] = [
    $source,
    $target,
    html_writer::span(
        $preview->similarityscore . '%',
        $preview->similarityscore >= 70
            ? 'badge bg-success'
            : 'badge bg-warning text-dark'
    ),
];

echo html_writer::table($table);

echo html_writer::div(
    get_string(
        'commerce_identity_legacy_link_preserves_target',
        'local_subscriptions'
    ),
    'alert alert-secondary'
);

if (!$executed && $preview->can_execute()) {
    echo html_writer::start_tag(
        'form',
        [
            'method' => 'post',
            'action' => $pageurl->out(false),
            'class' => 'card card-body border-primary',
        ]
    );
    echo html_writer::empty_tag(
        'input',
        [
            'type' => 'hidden',
            'name' => 'sesskey',
            'value' => sesskey(),
        ]
    );
    echo html_writer::empty_tag(
        'input',
        [
            'type' => 'hidden',
            'name' => 'email',
            'value' => $legacyemail,
        ]
    );
    echo html_writer::empty_tag(
        'input',
        [
            'type' => 'hidden',
            'name' => 'targetuserid',
            'value' => $targetuserid,
        ]
    );
    echo html_writer::empty_tag(
        'input',
        [
            'type' => 'hidden',
            'name' => 'action',
            'value' => 'execute',
        ]
    );

    echo html_writer::start_div('form-check mb-3');
    echo html_writer::empty_tag(
        'input',
        [
            'id' => 'confirm-legacy-link',
            'type' => 'checkbox',
            'name' => 'confirm',
            'value' => '1',
            'required' => 'required',
            'class' => 'form-check-input',
        ]
    );
    echo html_writer::tag(
        'label',
        get_string(
            'commerce_identity_legacy_link_confirm',
            'local_subscriptions'
        ),
        [
            'for' => 'confirm-legacy-link',
            'class' => 'form-check-label',
        ]
    );
    echo html_writer::end_div();

    echo html_writer::tag(
        'button',
        get_string(
            'commerce_identity_legacy_link_execute',
            'local_subscriptions'
        ),
        [
            'type' => 'submit',
            'class' => 'btn btn-primary',
        ]
    );

    echo html_writer::end_tag('form');
} elseif (!$executed) {
    echo html_writer::div(
        get_string(
            'commerce_identity_legacy_link_similarity_too_low',
            'local_subscriptions'
        ),
        'alert alert-danger'
    );
}

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
