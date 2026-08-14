<?php

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\customer\merge\CommerceCustomerPreferredIdentityPasswordRepairService;

[$options, $unrecognized] = cli_get_params([
    'help' => false,
    'emails' => '',
    'execute' => false,
], [
    'h' => 'help',
    'e' => 'emails',
    'x' => 'execute',
]);

if ($unrecognized) {
    cli_error('Unknown options: ' . implode(', ', $unrecognized));
}
if ($options['help'] || trim((string)$options['emails']) === '') {
    echo "Repair password hashes for preferred-login identity swaps created before the M13 password fix.\n\n";
    echo "Dry-run (default):\n";
    echo "  php local/subscriptions/cli/repair_preferred_identity_passwords.php --emails=a@example.com,b@example.com\n\n";
    echo "Execute after reviewing the dry-run:\n";
    echo "  php local/subscriptions/cli/repair_preferred_identity_passwords.php --emails=a@example.com,b@example.com --execute\n";
    exit($options['help'] ? 0 : 1);
}

$emails = array_values(array_unique(array_filter(array_map('trim', explode(',', (string)$options['emails'])))));
$service = new CommerceCustomerPreferredIdentityPasswordRepairService($DB);
$failed = false;
foreach ($emails as $email) {
    try {
        $result = $service->repair_by_preferred_email($email, (bool)$options['execute']);
        cli_writeln(sprintf(
            '[%s] %s merge=%d target=#%s source=#%s',
            strtoupper((string)$result['status']),
            $email,
            (int)$result['mergeid'],
            isset($result['targetuserid']) ? (string)$result['targetuserid'] : '-',
            isset($result['sourceuserid']) ? (string)$result['sourceuserid'] : '-'
        ));
    } catch (Throwable $e) {
        $failed = true;
        cli_writeln('[ERROR] ' . $email . ' — ' . $e->getMessage());
    }
}

if (!$options['execute']) {
    cli_writeln('Dry-run only. Re-run with --execute after reviewing the result.');
}
exit($failed ? 2 : 0);
