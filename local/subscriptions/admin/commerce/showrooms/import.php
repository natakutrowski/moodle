<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomPackageService;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomPortablePackageService;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;

require_login();
$context = context_system::instance();
require_capability('local/subscriptions:manage_showrooms', $context);

$pageurl = new moodle_url('/local/subscriptions/admin/commerce/showrooms/import.php');
$pagetitle = get_string('commerce_showroom_import_create', 'local_subscriptions');

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $pagetitle,
    'local-subscriptions-showroom-import-page'
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();

    $hasupload = isset($_FILES['packagefile'])
        && (int)($_FILES['packagefile']['error'] ?? UPLOAD_ERR_NO_FILE)
            !== UPLOAD_ERR_NO_FILE;

    if ($hasupload) {
        $upload = $_FILES['packagefile'];
        if ((int)($upload['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new moodle_exception('error_uploading_file', 'moodle');
        }

        $filename = clean_param(
            (string)($upload['name'] ?? ''),
            PARAM_FILE
        );
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $tmpname = (string)($upload['tmp_name'] ?? '');

        if ($tmpname === '' || !is_uploaded_file($tmpname)) {
            throw new moodle_exception('error_uploading_file', 'moodle');
        }

        if ($extension === 'zip') {
            $service = new CommerceShowroomPortablePackageService(
                $DB,
                new CommerceShowroomCmsRepository($DB),
                $context
            );
            $report = $service->import_zip($tmpname, (int)$USER->id);

            redirect(
                new moodle_url(
                    '/local/subscriptions/admin/commerce/showrooms/edit.php',
                    ['id' => $report['showroomid']]
                ),
                get_string(
                    'commerce_showroom_import_portable_done',
                    'local_subscriptions',
                    (object)[
                        'blocks' => $report['blockcount'],
                        'media' => $report['mediacount'],
                        'remapped' => $report['remappedcount'],
                    ]
                )
            );
        }

        if ($extension !== 'json') {
            throw new invalid_parameter_exception(
                'The Showroom import file must be .showroom.zip or .json.'
            );
        }

        $size = (int)($upload['size'] ?? 0);
        if (
            $size <= 0
            || $size > CommerceShowroomPackageService::MAX_IMPORT_BYTES
        ) {
            throw new invalid_parameter_exception(
                'Invalid Showroom JSON file size.'
            );
        }

        $json = (string)file_get_contents($tmpname);
    } else {
        $json = optional_param('packagejson', '', PARAM_RAW);
    }

    if (trim($json ?? '') === '') {
        throw new invalid_parameter_exception(
            'A Showroom JSON or portable ZIP package is required.'
        );
    }

    $service = new CommerceShowroomPackageService(
        new CommerceShowroomCmsRepository($DB)
    );
    $id = $service->import($json, (int)$USER->id);

    redirect(
        new moodle_url(
            '/local/subscriptions/admin/commerce/showrooms/edit.php',
            ['id' => $id]
        ),
        get_string(
            'commerce_showroom_import_created_draft',
            'local_subscriptions'
        )
    );
}

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::SHOWROOMS, $context);
echo CrmBreadcrumbRenderer::render([
    [
        'label' => get_string('crm_commerce_title', 'local_subscriptions'),
        'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php'),
    ],
    [
        'label' => get_string('commerce_showroom_cms_title', 'local_subscriptions'),
        'url' => new moodle_url('/local/subscriptions/admin/commerce/showrooms/index.php'),
    ],
    [
        'label' => $pagetitle,
        'url' => null,
    ],
]);
echo CrmPageHeader::render(
    $pagetitle,
    null,
    HelpContext::COMMERCE
);


echo $OUTPUT->notification(
    get_string('commerce_showroom_import_portable_help', 'local_subscriptions'),
    'warning'
);

echo html_writer::start_tag(
    'form',
    [
        'method' => 'post',
        'enctype' => 'multipart/form-data',
        'class' => 'card card-body',
    ]
);
echo html_writer::empty_tag(
    'input',
    ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]
);

echo html_writer::tag(
    'label',
    get_string('commerce_showroom_import_file', 'local_subscriptions'),
    ['for' => 'packagefile', 'class' => 'form-label fw-bold']
);
echo html_writer::empty_tag(
    'input',
    [
        'type' => 'file',
        'id' => 'packagefile',
        'name' => 'packagefile',
        'accept' => '.zip,.json,application/zip,application/json',
        'class' => 'form-control mb-3',
    ]
);

echo html_writer::tag(
    'div',
    get_string('commerce_showroom_import_or_paste', 'local_subscriptions'),
    ['class' => 'text-muted small mb-2']
);

echo html_writer::tag(
    'label',
    get_string('commerce_showroom_import_help', 'local_subscriptions'),
    ['for' => 'packagejson', 'class' => 'form-label']
);
echo html_writer::tag(
    'textarea',
    '',
    [
        'id' => 'packagejson',
        'name' => 'packagejson',
        'rows' => 12,
        'class' => 'form-control font-monospace mb-3',
    ]
);

echo html_writer::tag(
    'button',
    '<i class="fa-solid fa-file-import" aria-hidden="true"></i> '
        . get_string('commerce_showroom_import_create', 'local_subscriptions'),
    ['type' => 'submit', 'class' => 'btn btn-primary align-self-start']
);

echo html_writer::end_tag('form');
echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
