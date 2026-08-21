<?php

require_once(__DIR__ . '/../../../../config.php');
require_once(__DIR__ . '/../../lib/lib_csv.php');
require_once(__DIR__ . '/../../renderer/user_subs_renderer.php');

use local_subscriptions\subscription_config;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\commerce\rendering\CommerceOffersAccessNavigationRenderer;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::MANAGE_SUBSCRIPTIONS);

$pageurl = new moodle_url(
    subscription_config::
        process_csv_page()
);

$pagetitle = get_string(
    'crm_subscriptions_import_result_title',
    'local_subscriptions'
);

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $pagetitle,
    'local-subscriptions-commerce-subscriptions-import-result-page'
);

echo $OUTPUT->header();

echo CrmWorkspaceRenderer::start(
    CrmNavigationKeys::COMMERCE,
    $context
);

echo CrmBreadcrumbRenderer::render(
    [
        [
            'label' => get_string(
                'crm_commerce_title',
                'local_subscriptions'
            ),
            'url' => new moodle_url(
                subscription_config::
                    admin_commerce_page()
            ),
        ],
        [
            'label' => get_string(
                'commerce_offers_access_title',
                'local_subscriptions'
            ),
            'url' => new moodle_url(
                '/local/subscriptions/admin/commerce/offers-access/index.php'
            ),
        ],
        [
            'label' => get_string(
                'import_subscriptions',
                'local_subscriptions'
            ),
            'url' => new moodle_url(
                subscription_config::
                    import_csv_page()
            ),
        ],
        [
            'label' => $pagetitle,
            'url' => null,
        ],
    ]
);
echo CrmPageHeader::render(
    $pagetitle,
    get_string(
        'crm_subscriptions_import_result_description',
        'local_subscriptions'
    ),
    HelpContext::SUBSCRIPTIONS
);

echo CommerceSectionNavigationRenderer::render(
    CommerceSectionNavigationRenderer::OFFERS_ACCESS,
    $context
);

echo CommerceOffersAccessNavigationRenderer::render(
    CommerceOffersAccessNavigationRenderer::OVERVIEW
);


$renderer =
    new local_subscriptions_user_subs_renderer(
        $PAGE,
        $OUTPUT
    );

require_sesskey();

$source = optional_param('sourcefile', '', PARAM_RAW);
if (!$source) {
    echo html_writer::div(
        get_string(
            'missing_param',
            'local_subscriptions'
        ),
        'alert alert-danger'
    );

    echo html_writer::link(
        new moodle_url(
            subscription_config::
                import_csv_page()
        ),
        get_string(
            'import_subscriptions',
            'local_subscriptions'
        ),
        [
            'class' => 'btn btn-primary',
        ]
    );

    echo CrmWorkspaceRenderer::end();

    echo $OUTPUT->footer();
    exit;
}

$validrows = unserialize(base64_decode($source, true)); 

if (!is_array($validrows) || empty($validrows)) {
    echo html_writer::div(
        get_string(
            'no_valid_rows',
            'local_subscriptions'
        ),
        'alert alert-warning'
    );

    echo CrmWorkspaceRenderer::end();

    echo $OUTPUT->footer();
    exit;
}

[$imported, $skipped] = process_csv_rows($validrows);

echo html_writer::start_tag(
    'section',
    [
        'class' => 'crm-legacy-import-card crm-legacy-import-result-card',
        'aria-labelledby' => 'crm-legacy-import-result-title',
    ]
);

echo html_writer::div(
    html_writer::tag(
        'h2',
        get_string(
            'crm_legacy_import_result_summary_title',
            'local_subscriptions'
        ),
        [
            'id' => 'crm-legacy-import-result-title',
            'class' => 'crm-legacy-import-card-title',
        ]
    )
    . html_writer::div(
        get_string(
            'crm_legacy_import_result_summary_help',
            'local_subscriptions'
        ),
        'crm-legacy-import-card-help'
    ),
    'crm-legacy-import-card-header'
);

echo html_writer::start_div(
    'crm-legacy-import-card-body'
);

// Résumé
echo $renderer->render_import_summary($imported, $skipped);

// Liens de suite
echo html_writer::div(
    html_writer::link(
        new moodle_url(
            subscription_config::
                user_subscriptions_page()
        ),
        get_string(
            'crm_subscriptions_view_list',
            'local_subscriptions'
        ),
        [
            'class' => 'btn btn-primary',
        ]
    ) .
    ' ' .
    html_writer::link(
        new moodle_url(
            subscription_config::
                import_csv_page()
        ),
        get_string(
            'crm_subscriptions_import_another',
            'local_subscriptions'
        ),
        [
            'class' =>
                'btn btn-outline-secondary',
        ]
    ),
    'crm-legacy-import-result-actions'
);

echo html_writer::end_div();
echo html_writer::end_tag('section');

echo CrmWorkspaceRenderer::end();

echo $OUTPUT->footer();