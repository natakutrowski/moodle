<?php

declare(strict_types=1);

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\course\repair\CommerceNativeCourseProductRepairService;

[$options] = cli_get_params(
    [
        'sku' => '',
        'source-planid' => 0,
        'courseid' => 0,
        'accesslevel' => '',
        'role' => '',
        'apply' => false,
        'strict' => false,
        'json' => false,
        'help' => false,
    ],
    [
        'h' => 'help',
    ]
);

if (!empty($options['help'])) {
    echo "Repair one canonical Native course product contract.\n\n";
    echo "Dry-run is the default. The command is idempotent.\n\n";
    echo "Required:\n";
    echo "  --sku=SKU\n";
    echo "  --source-planid=ID\n";
    echo "  --courseid=ID\n";
    echo "  --accesslevel=LEVEL\n";
    echo "  --role=SHORTNAME\n\n";
    echo "Optional:\n";
    echo "  --apply    Apply the missing mapping and entitlement\n";
    echo "  --strict   Return non-zero when warnings remain\n";
    echo "  --json\n";
    echo "  -h, --help\n";
    exit(0);
}

$sku = strtoupper(trim((string)$options['sku']));
$sourceplanid = (int)$options['source-planid'];
$courseid = (int)$options['courseid'];
$accesslevel = strtolower(trim((string)$options['accesslevel']));
$role = trim((string)$options['role']);

if (
    $sku === '' ||
    $sourceplanid <= 0 ||
    $courseid <= 0 ||
    $accesslevel === '' ||
    $role === ''
) {
    cli_error('All required arguments must be supplied.');
}

$service = new CommerceNativeCourseProductRepairService($DB);

try {
    $result = !empty($options['apply'])
        ? $service->apply(
            $sku,
            $sourceplanid,
            $courseid,
            $accesslevel,
            $role
        )
        : $service->inspect(
            $sku,
            $sourceplanid,
            $courseid,
            $accesslevel,
            $role
        );
} catch (\Throwable $exception) {
    if (!empty($options['json'])) {
        echo json_encode(
            [
                'status' => 'FAILED',
                'error' => $exception->getMessage(),
            ],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        ) . PHP_EOL;
    } else {
        cli_error($exception->getMessage());
    }
    exit(1);
}

if (!empty($options['json'])) {
    echo json_encode(
        [
            'mode' => !empty($options['apply']) ? 'apply' : 'dry-run',
            'status' => $result['complete'] ? 'COMPLETE' : 'READY',
            'result' => $result,
        ],
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
    ) . PHP_EOL;
} else {
    echo "============================================================\n";
    echo "CampusFR Commerce — Native Course Product Repair\n";
    echo !empty($options['apply']) ? "MODE: APPLY\n" : "MODE: DRY-RUN\n";
    echo "============================================================\n\n";

    echo "Native product: {$result['sku']} (#{$result['product']->id})\n";
    echo "Legacy plan: {$result['sourceplan']->name} "
        . "(#{$result['sourceplan']->id}, active="
        . ((int)$result['sourceplan']->is_active === 1 ? 'yes' : 'no')
        . ")\n";
    echo "Course: {$result['course']->fullname} "
        . "(#{$result['course']->id})\n";
    echo "Access: {$result['accesslevel']} / {$result['roleshortname']}\n";
    echo "Resource key: {$result['resourcekey']}\n\n";

    echo "Mapping\n";
    echo "-------\n";
    echo $result['mapping'] === null
        ? "MISSING\n"
        : "OK (map #{$result['mapping']->id})\n";

    echo "\nNative entitlement\n";
    echo "------------------\n";
    echo $result['entitlement'] === null
        ? "MISSING\n"
        : "OK (entitlement #{$result['entitlement']->id})\n";

    echo "\nPlanned actions\n";
    echo "---------------\n";
    if ($result['actions'] === []) {
        echo "NONE\n";
    } else {
        foreach ($result['actions'] as $action) {
            echo "- {$action}\n";
        }
    }

    echo "\nWarnings\n";
    echo "--------\n";
    if ($result['warnings'] === []) {
        echo "NONE\n";
    } else {
        foreach ($result['warnings'] as $warning) {
            echo "- {$warning}\n";
        }
    }

    echo "\nErrors\n";
    echo "------\n";
    if ($result['errors'] === []) {
        echo "NONE\n";
    } else {
        foreach ($result['errors'] as $error) {
            echo "- {$error}\n";
        }
    }

    echo "\n============================================================\n";
    echo 'STATUS: ' . (
        $result['complete']
            ? 'COMPLETE'
            : ($result['ready'] ? 'READY TO APPLY' : 'FAILED')
    ) . "\n";
    echo "============================================================\n";
}

if (!$result['ready']) {
    exit(1);
}

if (!empty($options['strict']) && $result['warnings'] !== []) {
    exit(2);
}

exit(0);
