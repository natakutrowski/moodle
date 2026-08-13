<?php

declare(strict_types=1);

define('CLI_SCRIPT', true);

require dirname(__DIR__, 5) . '/config.php';
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\checkout\guest\CommerceUnfinishedGuestCheckoutRecoveryService;

[$options, $unrecognized] = cli_get_params([
    'email' => null,
    'execute' => false,
    'help' => false,
], [
    'e' => 'email',
    'x' => 'execute',
    'h' => 'help',
]);

if ($options['help']) {
    echo <<<TXT
Audit unfinished Guest Checkout accounts.

Options:
  --email=user@example.com  Limit to one email.
  --execute                 Repair currently stuck existing_account sessions.
  --help                    Show this help.

Dry-run is the default. This command never unsuspends a user and never marks a
purchase paid. It only converts proven unfinished Guest Checkout browser
sessions from existing_account back to provisional so checkout can resume.

TXT;
    exit(0);
}

$email = $options['email'] !== null
    ? \core_text::strtolower(trim((string)$options['email']))
    : null;

$service = CommerceUnfinishedGuestCheckoutRecoveryService::create();
$candidates = $service->audit($email);

echo "UNFINISHED GUEST CHECKOUT ACCOUNTS: " . count($candidates) . PHP_EOL;

foreach ($candidates as $candidate) {
    echo PHP_EOL;
    echo "USER #{$candidate['userid']} {$candidate['email']}" . PHP_EOL;
    echo "  username       : {$candidate['username']}" . PHP_EOL;
    echo "  source session : #{$candidate['source_session_id']} ({$candidate['source_status']})" . PHP_EOL;
    echo "  purchase ref   : " . ($candidate['purchase_reference'] ?: '-') . PHP_EOL;
    echo "  payment ref    : " . ($candidate['payment_reference'] ?: '-') . PHP_EOL;
    echo "  stuck sessions : {$candidate['stuck_sessions']}" . PHP_EOL;

    if ($candidate['purchases'] === []) {
        echo "  purchases      : NONE" . PHP_EOL;
    } else {
        echo "  purchases:" . PHP_EOL;
        foreach ($candidate['purchases'] as $purchase) {
            echo "    #{$purchase->id} {$purchase->reference}"
                . " status={$purchase->status}"
                . " total={$purchase->totalminor} {$purchase->currency}"
                . PHP_EOL;
        }
    }
}

if (!$options['execute']) {
    echo PHP_EOL . "READ-ONLY: no Campus data changed." . PHP_EOL;
    exit(0);
}

$result = $service->repair_stuck_sessions($email);
echo PHP_EOL;
echo "REPAIRED USERS    : {$result['users']}" . PHP_EOL;
echo "REPAIRED SESSIONS : {$result['sessions']}" . PHP_EOL;
echo "No user was unsuspended and no payment status was changed." . PHP_EOL;
