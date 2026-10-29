<?php

declare(strict_types=1);

// Read-only Mail Studio V1 step 1. NEVER sends, enqueues, or stores emails.
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\mail\audience\CommerceMarketingCourseAudienceService;

[$options, $unrecognized] = cli_get_params([
    'help' => false,
    'list-courses' => false,
    'courseid' => 0,
    'exclude-skus' => '',
    'max-rows' => 25,
    'show-excluded' => false,
], ['h' => 'help']);

if ($unrecognized || $options['help']) {
    echo "CampusFR Mail Studio / course participants (READ-ONLY)\n\n"
        . "--list-courses                      List likely A1 courses (no emails)\n"
        . "--courseid=ID                       Moodle course to inspect (required)\n"
        . "--exclude-skus=A1-FR-SUPPORT,A1-RU-SUPPORT\n"
        . "                                    Add support SKUs to those discovered via pedagogical offers\n"
        . "--max-rows=25                       Maximum displayed eligible rows (0 means no rows)\n"
        . "--show-excluded                     Also show individual exclusions (limited by --max-rows)\n\n"
        . "No DB writes, no email sent, no campaign created. NOT an approval to send marketing.\n";
    exit($unrecognized ? 1 : 0);
}

if ($options['list-courses']) {
    $sql = "SELECT DISTINCT c.id, c.shortname, c.fullname
              FROM {course} c
         LEFT JOIN {local_subs_commerce_ped_promo} promo ON promo.courseid = c.id
             WHERE c.id > :siteid
               AND (" . $DB->sql_like('c.shortname', ':shortname', false) . "
                 OR " . $DB->sql_like('c.fullname', ':fullname', false) . "
                 OR promo.id IS NOT NULL)
          ORDER BY c.id";
    foreach ($DB->get_records_sql($sql, [
        'siteid' => SITEID, 'shortname' => '%A1%', 'fullname' => '%A1%',
    ]) as $course) {
        echo (int)$course->id . ' | ' . (string)$course->shortname . ' | ' . (string)$course->fullname . "\n";
    }
    exit(0);
}

$courseid = (int)$options['courseid'];
if ($courseid <= SITEID) {
    cli_error('Use --courseid=ID (or --list-courses).');
}
$additionalskus = array_filter(array_map('trim', explode(',', (string)$options['exclude-skus'])));
$report = CommerceMarketingCourseAudienceService::create($DB)->preview($courseid, $additionalskus);

$summary = $report['summary'];
echo "MAIL STUDIO · COURSE PARTICIPANTS · READ-ONLY\n";
echo 'Course: ' . $report['courseid'] . ' (' . $report['course'] . ")\n";
echo 'Checked: ' . date('c', $report['checkedat']) . "\n";
echo 'Support SKUs: ' . ($report['supportskus'] ? implode(', ', $report['supportskus']) : 'NONE DETECTED') . "\n\n";
foreach ($summary as $name => $count) {
    echo str_pad($name, 43) . ': ' . $count . "\n";
}
if ($report['supportskus'] === []) {
    echo "\nWARNING: no support SKUs found. Verify pedagogical offers and pass --exclude-skus before trusting this preview.\n";
}

echo "\nELIGIBLE (preview only):\n";
$maxrows = max(0, min(200, (int)$options['max-rows']));
$shown = 0;
foreach ($report['rows'] as $row) {
    if (!$row['eligible'] || $shown >= $maxrows) {
        continue;
    }
    echo $row['userid'] . ' | ' . $row['email'] . ' | ' . $row['firstname'] . ' ' . $row['lastname'] . "\n";
    $shown++;
}
if ($options['show-excluded']) {
    echo "\nEXCLUDED (preview only):\n";
    $shown = 0;
    foreach ($report['rows'] as $row) {
        if ($row['eligible'] || $shown >= $maxrows) {
            continue;
        }
        echo $row['userid'] . ' | ' . $row['email'] . ' | ' . implode(', ', $row['reasons']) . "\n";
        $shown++;
    }
}
echo "\nNOTICE: no consent/opt-out marketing verification at this stage; no email may be sent from this report.\n";
