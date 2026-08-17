<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomPackageService;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomPortablePackageService;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

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
    get_string('commerce_showroom_n91_import_intro', 'local_subscriptions'),
    HelpContext::COMMERCE
);

echo CommerceSectionNavigationRenderer::render(
    CommerceSectionNavigationRenderer::SHOWROOMS,
    $context
);

echo html_writer::start_tag('form', [
    'method' => 'post',
    'enctype' => 'multipart/form-data',
    'class' => 'crm-showroom-import-form',
]);
echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'sesskey',
    'value' => sesskey(),
]);

echo html_writer::start_tag('section', [
    'class' => 'card card-body crm-showroom-import-primary',
]);
echo html_writer::div(
    html_writer::tag('i', '', [
        'class' => 'fa fa-box-archive',
        'aria-hidden' => 'true',
    ]),
    'crm-showroom-import-icon'
);
echo html_writer::tag(
    'h2',
    get_string('commerce_showroom_n91_import_package_title', 'local_subscriptions'),
    ['class' => 'h4 mb-1']
);
echo html_writer::tag(
    'p',
    get_string('commerce_showroom_n91_import_package_help', 'local_subscriptions'),
    ['class' => 'text-muted mb-3']
);
echo html_writer::tag(
    'label',
    get_string('commerce_showroom_import_file', 'local_subscriptions'),
    ['for' => 'packagefile', 'class' => 'form-label fw-bold']
);
echo html_writer::empty_tag('input', [
    'type' => 'file',
    'id' => 'packagefile',
    'name' => 'packagefile',
    'accept' => '.zip,.json,application/zip,application/json',
    'class' => 'form-control',
]);
echo html_writer::tag(
    'div',
    get_string('commerce_showroom_n91_import_formats', 'local_subscriptions'),
    ['class' => 'form-text']
);
echo html_writer::end_tag('section');

echo html_writer::tag(
    'details',
    html_writer::tag(
        'summary',
        html_writer::tag('i', '', [
            'class' => 'fa fa-code me-2',
            'aria-hidden' => 'true',
        ])
        . get_string(
            'commerce_showroom_n91_import_json_advanced',
            'local_subscriptions'
        ),
        ['class' => 'crm-showroom-import-json-summary']
    )
    . html_writer::div(
        html_writer::tag(
            'p',
            get_string('commerce_showroom_import_help', 'local_subscriptions'),
            ['class' => 'text-muted mb-2']
        )
        . html_writer::tag(
            'textarea',
            '',
            [
                'id' => 'packagejson',
                'name' => 'packagejson',
                'rows' => 10,
                'class' => 'form-control font-monospace',
                'placeholder' => '{ ... }',
            ]
        ),
        'crm-showroom-import-json-body'
    ),
    ['class' => 'crm-showroom-import-json card']
);

echo html_writer::div(
    html_writer::link(
        new moodle_url('/local/subscriptions/admin/commerce/showrooms/index.php'),
        get_string('cancel'),
        ['class' => 'btn btn-outline-secondary']
    )
    . html_writer::tag(
        'button',
        html_writer::tag('i', '', [
            'class' => 'fa fa-file-import me-1',
            'aria-hidden' => 'true',
        ])
        . get_string('commerce_showroom_n91_import_action', 'local_subscriptions'),
        ['type' => 'submit', 'class' => 'btn btn-primary']
    ),
    'crm-showroom-import-actions'
);

echo html_writer::end_tag('form');
echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
