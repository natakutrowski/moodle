<?php

declare(strict_types=1);

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\course\storefront\CommerceCourseStorefrontTargetResolver;

[$options] = cli_get_params(
    [
        'courseid' => 0,
        'accesslevel' => 'subscriber',
        'json' => false,
        'help' => false,
    ],
    [
        'h' => 'help',
    ]
);

if (!empty($options['help']) || (int)$options['courseid'] <= 0) {
    echo "Diagnose a CampusFR restriction Storefront target.\n\n";
    echo "Options:\n";
    echo "  --courseid=ID\n";
    echo "  --accesslevel=grammar|full|subscriber\n";
    echo "  --json\n";
    exit(!empty($options['help']) ? 0 : 1);
}

$courseid = (int)$options['courseid'];
$accesslevel = strtolower(trim((string)$options['accesslevel']));
if (!in_array($accesslevel, ['grammar', 'full', 'subscriber'], true)) {
    cli_error('Invalid access level.');
}

$course = $DB->get_record(
    'course',
    ['id' => $courseid],
    'id,fullname,shortname',
    MUST_EXIST
);

$diagnostic = CommerceCourseStorefrontTargetResolver::create()->diagnose(
    [$courseid],
    $accesslevel
);

if (!empty($options['json'])) {
    echo json_encode(
        ['course' => $course, 'diagnostic' => $diagnostic],
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
    ) . PHP_EOL;
    exit(0);
}

echo "============================================================\n";
echo "CampusFR — Restriction Storefront diagnostic\n";
echo "============================================================\n";
echo "Course: {$course->fullname} (#{$course->id})\n";
echo "Access level: {$accesslevel}\n";
echo "Related course IDs: "
    . implode(', ', $diagnostic['relatedcourseids']) . "\n";
echo "Resolved SKUs: "
    . ($diagnostic['resolvedskus'] === []
        ? 'NONE'
        : implode(', ', $diagnostic['resolvedskus']))
    . "\n\n";

echo "Native candidates\n";
echo "-----------------\n";
if ($diagnostic['nativecandidates'] === []) {
    echo "NONE\n";
} else {
    foreach ($diagnostic['nativecandidates'] as $candidate) {
        echo sprintf(
            "- sku=%s productid=%d entitlementid=%d "
            . "courseid=%d accesslevel=%s status=%s\n",
            (string)$candidate->sku,
            (int)$candidate->id,
            (int)$candidate->entitlementid,
            (int)$candidate->resolvedcourseid,
            (string)$candidate->resolvedaccesslevel,
            (string)$candidate->status
        );
    }
}

echo "\nLegacy candidates\n";
echo "-----------------\n";
if ($diagnostic['legacycandidates'] === []) {
    echo "NONE\n";
} else {
    foreach ($diagnostic['legacycandidates'] as $candidate) {
        $mapped = (new \local_subscriptions\commerce\catalog\resolution\CommerceLegacyStorefrontProductResolver($DB))
            ->resolve_subscription_plan((int)$candidate->planid);

        echo sprintf(
            "- plan=%s (#%d) courseid=%d accesslevel=%s role=%s "
            . "priority=%d mappedsku=%s\n",
            (string)$candidate->planname,
            (int)$candidate->planid,
            (int)$candidate->courseid,
            (string)$candidate->accesslevel,
            (string)$candidate->roleshortname,
            (int)$candidate->priority,
            $mapped ? (string)$mapped->sku : 'NONE'
        );
    }
}

echo "\nSTATUS: READ-ONLY DIAGNOSTIC COMPLETE\n";
