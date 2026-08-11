<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\payment\stripe\StripeConfiguration;

[$options, $unrecognised] = cli_get_params(
    [
        'profile' => null,
        'show' => false,
        'help' => false,
    ],
    [
        'p' => 'profile',
        's' => 'show',
        'h' => 'help',
    ]
);

if ($unrecognised) {
    cli_error('Unknown options: ' . implode(', ', $unrecognised));
}

if ($options['help']) {
    cli_writeln("Switch the active Stripe profile.\n\n"
        . "Usage:\n"
        . "  php local/subscriptions/cli/payment/set_stripe_profile.php --profile=test|live_ei|live_sas\n"
        . "  php local/subscriptions/cli/payment/set_stripe_profile.php --show\n");
    exit(0);
}

if ($options['show'] || $options['profile'] === null) {
    $profile = StripeConfiguration::active_profile();
    $config = StripeConfiguration::get($profile);
    cli_writeln('Active Stripe profile: ' . $profile);
    cli_writeln('Stripe mode:           ' . $config['mode']);
    cli_writeln('Secret key configured: ' . ($config['secret_key'] !== '' ? 'yes' : 'no'));
    cli_writeln('Webhook configured:    ' . ($config['webhook_secret'] !== '' ? 'yes' : 'no'));
    exit(0);
}

$requested = strtolower(trim((string)$options['profile']));
if (!in_array($requested, StripeConfiguration::PROFILES, true)) {
    cli_error('Invalid profile. Allowed values: test, live_ei, live_sas.');
}

set_config('stripe_env', $requested, 'local_subscriptions');
$config = StripeConfiguration::get($requested);

cli_writeln('Active Stripe profile switched to: ' . $requested);
cli_writeln('Stripe mode:           ' . $config['mode']);
cli_writeln('Secret key configured: ' . ($config['secret_key'] !== '' ? 'yes' : 'no'));
cli_writeln('Webhook configured:    ' . ($config['webhook_secret'] !== '' ? 'yes' : 'no'));
