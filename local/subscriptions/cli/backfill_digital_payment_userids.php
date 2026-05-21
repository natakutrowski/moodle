<?php
define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

global $DB;

[$options, $unrecognized] = cli_get_params(
    [
        'help' => false,
        'execute' => false,
        'limit' => 0,
    ],
    [
        'h' => 'help',
    ]
);

if ($options['help']) {
    echo "Backfill userid for digital payment requests using buyer email.\n\n";
    echo "Usage:\n";
    echo "  php local/subscriptions/cli/backfill_digital_payment_userids.php\n";
    echo "  php local/subscriptions/cli/backfill_digital_payment_userids.php --execute\n";
    echo "  php local/subscriptions/cli/backfill_digital_payment_userids.php --execute --limit=50\n\n";
    exit(0);
}

$execute = !empty($options['execute']);
$limit = max(0, (int)$options['limit']);

$sql = "
    SELECT pr.id, pr.email, pr.userid, pr.status, pr.creation_date
      FROM {subscription_digital_payment_request} pr
     WHERE pr.userid IS NULL
       AND pr.email IS NOT NULL
       AND pr.email <> ''
  ORDER BY pr.id DESC
";

$records = $DB->get_records_sql($sql, [], 0, $limit ?: 0);

mtrace($execute ? 'MODE: EXECUTE' : 'MODE: DRY-RUN');
mtrace('Found payment requests without userid: ' . count($records));
mtrace(str_repeat('-', 80));

$matched = 0;
$updated = 0;
$notfound = 0;
$ambiguous = 0;

foreach ($records as $pr) {
    $email = \core_text::strtolower(trim($pr->email));

    $users = $DB->get_records('user', [
        'email' => $email,
        'deleted' => 0,
        'suspended' => 0,
    ], '', 'id,email,username,firstname,lastname');

    if (count($users) === 0) {
        $notfound++;
        mtrace("#{$pr->id} {$email} -> no matching user");
        continue;
    }

    if (count($users) > 1) {
        $ambiguous++;
        mtrace("#{$pr->id} {$email} -> ambiguous users: " . implode(', ', array_keys($users)));
        continue;
    }

    $user = reset($users);
    $matched++;

    mtrace("#{$pr->id} {$email} -> userid {$user->id} ({$user->username})");

    if ($execute) {
        $DB->update_record('subscription_digital_payment_request', (object)[
            'id' => $pr->id,
            'userid' => $user->id,
            'last_update' => time(),
        ]);

        $updated++;
    }
}

mtrace(str_repeat('-', 80));
mtrace("Matched: {$matched}");
mtrace("Updated: {$updated}");
mtrace("Not found: {$notfound}");
mtrace("Ambiguous: {$ambiguous}");
mtrace('Done.');