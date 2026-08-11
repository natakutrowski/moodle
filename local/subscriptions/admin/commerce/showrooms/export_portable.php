<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomPortablePackageService;

require_login();
require_sesskey();

$context = context_system::instance();
require_capability('local/subscriptions:manage_showrooms', $context);

$id = required_param('id', PARAM_INT);
$service = new CommerceShowroomPortablePackageService(
    $DB,
    new CommerceShowroomCmsRepository($DB),
    $context
);

$preflight = $service->preflight_export($id);

if (
    $preflight['freetempbytes'] > 0
    && $preflight['freetempbytes']
        < $preflight['requiredfreetempbytes']
) {
    throw new moodle_exception(
        'commerce_showroom_export_insufficient_disk',
        'local_subscriptions',
        '',
        (object)[
            'required' => display_size(
                $preflight['requiredfreetempbytes']
            ),
            'available' => display_size(
                $preflight['freetempbytes']
            ),
        ]
    );
}

$export = $service->export_zip($id);

if (!is_file($export['pathname'])) {
    throw new moodle_exception(
        'commerce_showroom_export_invalid_archive',
        'local_subscriptions'
    );
}

/*
 * Moodle-specific: stream the generated temporary archive and remove it
 * afterwards. This is the large-file-safe download path for generated ZIPs.
 */
send_temp_file(
    $export['pathname'],
    $export['filename'],
    false
);
