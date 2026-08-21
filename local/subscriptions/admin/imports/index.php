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
        import_csv_page()
);

$pagetitle = get_string(
    'import_subscriptions',
    'local_subscriptions'
);

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $pagetitle,
    'local-subscriptions-commerce-subscriptions-import-page'
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
            'label' => $pagetitle,
            'url' => null,
        ],
    ]
);
echo CrmPageHeader::render(
    $pagetitle,
    get_string(
        'crm_subscriptions_import_description',
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

// Gestion du formulaire de téléchargement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csvfile'])) {
    require_sesskey();
    $tmp = $_FILES['csvfile']['tmp_name'];

	[$rows, $validrows, $headers] = parse_csv_file($tmp);

    $importid = time();
    $tempfile = make_request_directory() . "/csv_import_$importid.csv";
    //file_put_contents($tempfile, $content);

    // Sécurité + copie
    if (!is_uploaded_file($tmp)) {
        throw new moodle_exception('invalidcsvupload', 'local_subscriptions');
    }
    if (!@copy($tmp, $tempfile)) { // (ou move_uploaded_file($tmp, $tempfile))
        throw new moodle_exception('csvwritefail', 'local_subscriptions');
    }

    echo html_writer::start_tag(
        'section',
        [
            'class' => 'crm-legacy-import-card crm-legacy-import-preview-card',
            'aria-labelledby' => 'crm-legacy-import-preview-title',
        ]
    );

    echo html_writer::div(
        html_writer::tag(
            'h2',
            get_string(
                'crm_legacy_import_preview_title',
                'local_subscriptions'
            ),
            [
                'id' => 'crm-legacy-import-preview-title',
                'class' => 'crm-legacy-import-card-title',
            ]
        )
        . html_writer::div(
            get_string(
                'crm_legacy_import_preview_help',
                'local_subscriptions'
            ),
            'crm-legacy-import-card-help'
        ),
        'crm-legacy-import-card-header'
    );

    echo html_writer::start_div(
        'crm-legacy-import-card-body'
    );

    // Formulaire de confirmation
	echo $renderer->render_import_confirmation_form($validrows, $importid);

    // ➕ Message récapitulatif
    $total = count($rows);
    $valid = count($validrows);
    $ignored = $total - $valid;	
    
    if (!empty($rows)) {	
		echo $renderer->render_import_preview_table($rows, $headers);
    }

	echo html_writer::tag('p', get_string('import_preview', 'local_subscriptions'));
	echo $renderer->render_import_actions_and_summary($ignored);	
    echo html_writer::end_tag('form');
    echo html_writer::end_div();
    echo html_writer::end_tag('section');

    echo $renderer->
        render_import_checkbox_script();

    echo CrmWorkspaceRenderer::end();

    echo $OUTPUT->footer();
    exit;
}

echo html_writer::start_tag(
    'section',
    [
        'class' => 'crm-legacy-import-card crm-legacy-import-upload-card',
        'aria-labelledby' => 'crm-legacy-import-upload-title',
    ]
);

echo html_writer::div(
    html_writer::span(
        html_writer::tag('i', '', [
            'class' => 'fa fa-file-text-o',
            'aria-hidden' => 'true',
        ]),
        'crm-legacy-import-card-icon'
    )
    . html_writer::div(
        html_writer::tag(
            'h2',
            get_string(
                'crm_legacy_import_upload_title',
                'local_subscriptions'
            ),
            [
                'id' => 'crm-legacy-import-upload-title',
                'class' => 'crm-legacy-import-card-title',
            ]
        )
        . html_writer::div(
            get_string(
                'crm_legacy_import_upload_help',
                'local_subscriptions'
            ),
            'crm-legacy-import-card-help'
        ),
        'crm-legacy-import-card-heading-copy'
    ),
    'crm-legacy-import-card-header crm-legacy-import-card-header-with-icon'
);

echo html_writer::div(
    $renderer->render_csv_upload_form(),
    'crm-legacy-import-card-body'
);

echo html_writer::end_tag('section');

echo CrmWorkspaceRenderer::end();

echo $OUTPUT->footer();