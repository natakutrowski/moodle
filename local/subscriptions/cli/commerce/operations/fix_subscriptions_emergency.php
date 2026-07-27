<?php
define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

global $DB;

$now = time();
$onehourago = $now - 3600;

// ---------------- OPTIONS ----------------
$options = getopt('', ['dry-run']);
$dryrun = isset($options['dry-run']);

echo "========================================\n";
echo " EMERGENCY FIX SCRIPT\n";
echo "========================================\n";

if ($dryrun) {
    echo "⚠️ DRY RUN MODE\n";
}

// =====================================================
// STEP 1 — FIX TRIALS (LAST HOUR → UNLIMITED)
// =====================================================

echo "\n[1/4] Fetching recent trial subscriptions...\n";

$sql = "
    SELECT us.id, us.userid, u.username, u.email, us.end_date
    FROM {user_subscription} us
    JOIN {subscription_plan} sp ON sp.id = us.planid
    JOIN {user} u ON u.id = us.userid
    WHERE sp.is_trial = 1
      AND us.creation_date >= :since
";

$trials = $DB->get_records_sql($sql, ['since' => $onehourago]);
$trials = array_values($trials);

echo "👉 Trials found: " . count($trials) . "\n";

// Preview
foreach (array_slice($trials, 0, 10) as $t) {
    echo "- {$t->username} ({$t->email}) end_date={$t->end_date}\n";
}

echo "\nFix trial end_date to unlimited? (yes/no): ";
$handle = fopen("php://stdin", "r");

if (trim(fgets($handle)) !== 'yes') {
    echo "❌ Aborted.\n";
    exit;
}

// Apply
$updatedTrials = 0;

foreach ($trials as $t) {

    if (!$dryrun) {
        $rec = new stdClass();
        $rec->id = $t->id;
        $rec->end_date = $t->start_date + (100 * 365 * 24 * 60 * 60);
        $rec->last_update = $now;

        $DB->update_record('user_subscription', $rec);
    }

    $updatedTrials++;
}

echo "✔ Trials fixed: $updatedTrials\n";

// =====================================================
// STEP 2 — FIX STATUS (PLAN 29 ONLY)
// =====================================================

echo "\n[2/4] Fetching wrongly inactive subscriptions (plan 29 only)...\n";

$sql = "
    SELECT us.id, us.userid, u.username, u.email, us.status, us.end_date, us.planid
    FROM {user_subscription} us
    JOIN {user} u ON u.id = us.userid
    WHERE us.planid = :planid
      AND us.status != 'active'
      AND us.end_date = 0
";

$params = [
    'planid' => 29,
    'now' => $now
];

$subs = $DB->get_records_sql($sql, $params);
$subs = array_values($subs);

echo "👉 Subscriptions to reactivate: " . count($subs) . "\n";

// Preview
foreach (array_slice($subs, 0, 10) as $s) {
    echo "- {$s->username} ({$s->email}) [plan: {$s->planid}] status={$s->status}\n";
}

echo "\nReactivate these subscriptions? (yes/no): ";

if (trim(fgets($handle)) !== 'yes') {
    echo "❌ Aborted.\n";
    exit;
}

// Apply
$updatedSubs = 0;

foreach ($subs as $s) {

    if (!$dryrun) {
        $rec = new stdClass();
        $rec->id = $s->id;
        $rec->status = 'active';
        $rec->last_update = $now;

        $DB->update_record('user_subscription', $rec);
    }

    $updatedSubs++;
}

echo "✔ Subscriptions reactivated: $updatedSubs\n";

// =====================================================
// DONE
// =====================================================

echo "\n[4/4] DONE ✅\n";

if ($dryrun) {
    echo "⚠️ No data written (dry-run)\n";
}
