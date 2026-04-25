<?php
define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');

use local_subscriptions\payment\Provider;
use local_subscriptions\subscription_manager;
use local_subscriptions\trial_manager;
use local_subscriptions\model\Status;

global $DB;

// ---------------- OPTIONS ----------------
$options = getopt('', ['userid::', 'email::', 'dry-run']);

$userid = $options['userid'] ?? null;
$email  = $options['email'] ?? null;
$dryrun = isset($options['dry-run']);

if (!$userid && !$email) {
    echo "❌ Provide --userid or --email\n";
    exit;
}

// ---------------- HEADER ----------------
echo "========================================\n";
echo " Upgrade user to A1 (manual)\n";
echo "========================================\n";

if ($dryrun) {
    echo "⚠️ DRY RUN MODE\n";
}

$planid = 29;
$now = time();

// ---------------- STEP 1 — FETCH USER ----------------
echo "\n[1/6] Fetching user...\n";

if ($userid) {
    $user = $DB->get_record('user', ['id' => $userid]);
} else {
    $user = $DB->get_record('user', ['email' => $email]);
}

if (!$user) {
    echo "❌ User not found\n";
    exit;
}

$userid = $user->id;

echo "👉 User:\n";
echo "   ID: {$user->id}\n";
echo "   Username: {$user->username}\n";
echo "   Email: {$user->email}\n";
echo "   Name: {$user->firstname} {$user->lastname}\n";

// ---------------- STEP 2 — FIND ACTIVE TRIAL ----------------
echo "\n[2/6] Checking active trial...\n";

$sql = "
    SELECT us.*
    FROM {user_subscription} us
    JOIN {subscription_plan} sp ON sp.id = us.planid
    WHERE us.userid = :userid
      AND sp.is_trial = 1
      AND us.status = 'active'
    ORDER BY us.start_date DESC
";

$trial = $DB->get_record_sql($sql, ['userid' => $userid]);

if ($trial) {
    echo "👉 Active trial found (ID: {$trial->id})\n";
    echo "   start: {$trial->start_date}\n";
    echo "   end: {$trial->end_date}\n";
} else {
    echo "⚠️ No active trial found\n";
}

// ---------------- STEP 3 — CHECK A1 ----------------
echo "\n[3/6] Checking existing A1 subscription...\n";

$hasA1 = $DB->record_exists('user_subscription', [
    'userid' => $userid,
    'planid' => $planid
]);

echo $hasA1
    ? "⚠️ User already has A1 subscription\n"
    : "✅ No A1 subscription\n";

// ---------------- STEP 4 — PREVIEW ----------------
echo "\n[4/6] Preview:\n";

echo "- Create A1 subscription (14900 RUB)\n";
echo "- Force role: student\n";

if ($trial) {
    echo "- Replace trial (ID {$trial->id}) → Status::REPLACED\n";
}

echo "\nContinue? (yes/no): ";

$handle = fopen("php://stdin", "r");
if (trim(fgets($handle)) !== 'yes') {
    echo "❌ Aborted\n";
    exit;
}

// ---------------- STEP 5 — PROCESS ----------------
echo "\n[5/6] Processing...\n";

// --- CREATE SUB ---
if (!$hasA1) {

    if (!$dryrun) {

        $start = $now;
        $end = 0;

        $currency = subscription_manager::get_plan_default_currency($planid);

        subscription_manager::create_or_extend_subscription(
            $userid,
            $planid,
            Provider::MANUAL,
            'manual:a1_upgrade:' . $userid . ':' . $now,
            $start,
            $end,
            14900.00,
            $currency ?? 'RUB',
            $now,
            false,
            0,
            'manual_upgrade_trial_to_paid',
            0.00
        );

        echo "✔ Subscription created\n";
    } else {
        echo "→ (dry-run) subscription would be created\n";
    }

} else {
    echo "→ Skipped subscription (already exists)\n";
}

// --- ROLE FIX ---
if (!$dryrun) {
    trial_manager::force_role_student($userid, $planid);
    echo "✔ Role switched to student\n";
} else {
    echo "→ (dry-run) role would be switched\n";
}

// --- REPLACE TRIAL ---
if ($trial) {

    if (!$dryrun) {
        $trial->status = Status::REPLACED;
        $trial->last_update = $now;

        $DB->update_record('user_subscription', $trial);

        echo "✔ Trial marked as REPLACED\n";
    } else {
        echo "→ (dry-run) trial would be marked REPLACED\n";
    }
}

// ---------------- DONE ----------------
echo "\n[6/6] DONE ✅\n";

if ($dryrun) {
    echo "⚠️ No data written\n";
}