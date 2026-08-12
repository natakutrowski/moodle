<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;
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

$id = required_param('id', PARAM_INT);
$repository = new CommerceShowroomCmsRepository($DB);
$showroom = $repository->get($id);
if ($showroom === null) {
    throw new moodle_exception('invalidrecord');
}

$pageurl = new moodle_url(
    '/local/subscriptions/admin/commerce/showrooms/export_portable_preflight.php',
    ['id' => $id]
);
$pagetitle = get_string('commerce_showroom_export_portable', 'local_subscriptions');

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $pagetitle,
    'local-subscriptions-showroom-export-page'
);

$service = new CommerceShowroomPortablePackageService(
    $DB,
    $repository,
    $context
);
$stats = $service->preflight_export($id);

$enoughdisk = $stats['freetempbytes'] === 0
    || $stats['freetempbytes'] >= $stats['requiredfreetempbytes'];

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

echo $OUTPUT->heading(
    get_string('commerce_showroom_export_preflight_title', 'local_subscriptions')
);

$table = new html_table();
$table->attributes['class'] = 'table table-striped';
$table->data = [
    [
        get_string('commerce_showroom_export_preflight_media', 'local_subscriptions'),
        (string)$stats['mediacount'],
    ],
    [
        get_string('commerce_showroom_export_preflight_total', 'local_subscriptions'),
        display_size($stats['bytes']),
    ],
    [
        get_string('commerce_showroom_export_preflight_largest', 'local_subscriptions'),
        display_size($stats['largestfile']),
    ],
    [
        get_string('commerce_showroom_export_preflight_required', 'local_subscriptions'),
        display_size($stats['requiredfreetempbytes']),
    ],
    [
        get_string('commerce_showroom_export_preflight_available', 'local_subscriptions'),
        $stats['freetempbytes'] > 0
            ? display_size($stats['freetempbytes'])
            : get_string('unknown'),
    ],
];

echo html_writer::table($table);

if (!$enoughdisk) {
    echo $OUTPUT->notification(
        get_string(
            'commerce_showroom_export_insufficient_disk',
            'local_subscriptions',
            (object)[
                'required' => display_size($stats['requiredfreetempbytes']),
                'available' => display_size($stats['freetempbytes']),
            ]
        ),
        'error'
    );
} else {
    echo $OUTPUT->notification(
        get_string(
            'commerce_showroom_export_preflight_ready',
            'local_subscriptions'
        ),
        'success'
    );

    echo html_writer::link(
        new moodle_url(
            '/local/subscriptions/admin/commerce/showrooms/export_portable.php',
            [
                'id' => $id,
                'sesskey' => sesskey(),
            ]
        ),
        '<i class="fa-solid fa-box-archive" aria-hidden="true"></i> '
            . get_string(
                'commerce_showroom_export_portable_start',
                'local_subscriptions'
            ),
        ['class' => 'btn btn-success']
    );
}

echo ' ';
echo html_writer::link(
    new moodle_url(
        '/local/subscriptions/admin/commerce/showrooms/edit.php',
        ['id' => $id]
    ),
    get_string('cancel'),
    ['class' => 'btn btn-outline-secondary']
);

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
