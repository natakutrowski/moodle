<?php

require_once(__DIR__ . '/../../../../config.php');
require_once(__DIR__ . '/../../lib/lib_csv.php');
require_once(__DIR__ . '/../../renderer/user_subs_renderer.php');

use local_subscriptions\subscription_config;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBackLinkRenderer;
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
                'crm_subscriptions_title',
                'local_subscriptions'
            ),
            'url' => new moodle_url(
                subscription_config::
                    user_subscriptions_page()
            ),
        ],
        [
            'label' => $pagetitle,
            'url' => null,
        ],
    ]
);

echo CrmBackLinkRenderer::render(
    new moodle_url(
        subscription_config::
            user_subscriptions_page()
    ),
    get_string(
        'crm_subscriptions_title',
        'local_subscriptions'
    )
);

echo CrmPageHeader::render(
    $pagetitle,
    get_string(
        'crm_subscriptions_import_description',
        'local_subscriptions'
    ),
    HelpContext::SUBSCRIPTIONS
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
    echo $renderer->
        render_import_checkbox_script();

    echo CrmWorkspaceRenderer::end();

    echo $OUTPUT->footer();
    exit;
}

echo $renderer->
    render_csv_upload_form();

echo CrmWorkspaceRenderer::end();

echo $OUTPUT->footer();