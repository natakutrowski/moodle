<?php
define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\payment\Provider;

global $DB;

// ---------------- CONFIG ----------------
$planid = 29;
$scopeid = 15;
$now = time();

// ---------------- OPTIONS ----------------
$options = getopt('', ['dry-run']);
$dryrun = isset($options['dry-run']);

// ---------------- HEADER ----------------
echo "========================================\n";
echo " Grant A1 lifetime access (ACTIVE, no trial)\n";
echo "========================================\n";

if ($dryrun) {
    echo "⚠️ DRY RUN MODE\n";
}

// ---------------- STEP 1 ----------------
echo "\n[1/6] Fetching eligible users...\n";

$sql = "
    SELECT 
        us.userid,
        u.username,
        u.email,
        sp.name AS planname,
        sp.is_trial,
        us.pricepaid,
        GROUP_CONCAT(DISTINCT r.shortname) AS roles
    FROM {user_subscription} us
    JOIN {user} u ON u.id = us.userid
    JOIN {subscription_plan} sp ON sp.id = us.planid
    LEFT JOIN {role_assignments} ra ON ra.userid = u.id
    LEFT JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 10
    LEFT JOIN {role} r ON r.id = ra.roleid
    WHERE 
        us.start_date <= :now1
        AND (us.end_date > :now2 OR us.end_date = 0)
        AND (
            us.pricepaid > 0
            OR sp.is_trial = 0
        )
    GROUP BY us.userid, u.username, u.email, sp.name, sp.is_trial, us.pricepaid
";

$params = [
    'now1' => $now,
    'now2' => $now
];

$users = $DB->get_records_sql($sql, $params);
$users = array_values($users); // reset keys → évite warning Moodle

$total = count($users);

echo "👉 Eligible users: $total\n";

if ($total === 0) {
    echo "❌ No users found. Abort.\n";
    exit;
}

// ---------------- STEP 2 ----------------
echo "\n[2/6] Preview (first 10 users):\n";

$i = 0;
foreach ($users as $user) {

    $roles = $user->roles ?: 'none';

    // déterminer type
    if ($user->pricepaid > 0) {
        $type = 'paid';
    } elseif ($user->is_trial) {
        $type = 'trial';
    } else {
        $type = 'gift';
    }

    echo "- {$user->username} ({$user->email}) ";
    echo "[plan: {$user->planname}] ";
    echo "[type: {$type}] ";
    echo "[roles: {$roles}] ";
    echo "[ID: {$user->userid}]\n";

    if (++$i >= 10) break;
}

// ---------------- CONFIRM 1 ----------------
echo "\nContinue? (yes/no): ";
$handle = fopen("php://stdin", "r");

if (trim(fgets($handle)) !== 'yes') {
    echo "❌ Aborted.\n";
    exit;
}

// ---------------- STEP 3 ----------------
echo "\n[3/6] Checking existing A1 subscriptions...\n";

$already = 0;

foreach ($users as $user) {
    if ($DB->record_exists('user_subscription', [
        'userid' => $user->userid,
        'planid' => $planid
    ])) {
        $already++;
    }
}

$toinsert = $total - $already;

echo "👉 Already have A1: $already\n";
echo "👉 To grant: $toinsert\n";

// ---------------- CONFIRM 2 ----------------
echo "\nProceed? (yes/no): ";

if (trim(fgets($handle)) !== 'yes') {
    echo "❌ Aborted.\n";
    exit;
}

// ---------------- STEP 4 ----------------
echo "\n[4/6] Processing...\n";

$created = 0;
$skipped = 0;

foreach ($users as $user) {

    if ($DB->record_exists('user_subscription', [
        'userid' => $user->userid,
        'planid' => $planid
    ])) {
        $skipped++;
        continue;
    }

    echo "→ {$user->username} ({$user->email})\n";

    if (!$dryrun) {

        // 🔧 Dates
        $start = $now;
        $end = 0; // lifetime

        // 💰 Currency du plan

        $cur = $DB->get_field_sql("
            SELECT currency
            FROM {subscription_plan_price}
            WHERE planid = :planid
            ORDER BY (currency = 'EUR') DESC, id ASC
        ", ['planid' => $planid], IGNORE_MISSING);
        $currency = $cur ? strtoupper($cur) : 'EUR';

        // 🎯 Création subscription (propre via manager)
        $result = \local_subscriptions\subscription_manager::create_or_extend_subscription(
            $user->userid,
            $planid,
            \local_subscriptions\payment\Provider::MANUAL,
            'gift:a1:' . $user->userid . ':' . $now,
            $start,
            $end,
            0.0,
            $currency,
            $now,
            false, // allowupdate
            0,
            'gift_A1_active_no_trial',
            0.00
        );

        // 🎓 Enrol dans les cours du scope
        \local_subscriptions\subscription_manager::enrol_user_to_courses(
            $user->userid,
            $planid,
            $start,
            $end
        );
    }

    $created++;
}

// ---------------- STEP 5 ----------------
echo "\n[5/6] Summary:\n";

echo "Eligible: $total\n";
echo "Already: $skipped\n";
echo "Created: $created\n";

if ($dryrun) {
    echo "\n⚠️ DRY RUN — nothing written\n";
}

// ---------------- DONE ----------------
echo "\n[6/6] Done ✅\n";
