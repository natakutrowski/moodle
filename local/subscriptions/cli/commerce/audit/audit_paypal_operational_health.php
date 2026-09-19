<?php

declare(strict_types=1);

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\payment\provider\paypal\PayPalOperationalHealthService;

cli_heading('PayPal operational health');

$health = PayPalOperationalHealthService::create()->inspect(true);

cli_writeln('Environment: ' . $health->environment);
cli_writeln(
    'Credentials: ' . ($health->credentialsconfigured ? 'configured' : 'missing')
);
cli_writeln(
    'Webhook ID: ' . ($health->webhookconfigured ? 'configured' : 'missing')
);
cli_writeln(
    'OAuth: ' . ($health->oauthreachable ? 'reachable' : 'unreachable')
);

if ($health->oautherror !== null) {
    cli_writeln('OAuth error: ' . $health->oautherror);
}

cli_writeln('');
cli_writeln(
    $health->is_healthy()
        ? 'PayPal operational health: PASS'
        : 'PayPal operational health: WARN'
);

exit($health->readyforcheckout ? 0 : 1);
