<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\payment\CommercePaymentCustomer;
use local_subscriptions\commerce\payment\CommercePaymentLine;
use local_subscriptions\commerce\payment\CommercePaymentRequest;
use local_subscriptions\commerce\runtime\CommerceRuntimeFactory;

[$options, $unrecognized] = cli_get_params(
    [
        'help' =>
            false,

        'strict' =>
            false,
    ],
    [
        'h' =>
            'help',

        's' =>
            'strict',
    ]
);

if ($unrecognized) {
    cli_error(
        'Unknown options: '
        . implode(
            ', ',
            $unrecognized
        )
    );
}

if (!empty($options['help'])) {
    echo <<<HELP
Audit Commerce payment orchestration without calling a payment provider.

Options:
-h, --help       Display this help.
-s, --strict     Return a non-zero exit code when an error is found.

HELP;

    exit(0);
}

$errors = [];
$warnings = [];

$ok = static function(
    string $message
): void {
    cli_writeln(
        '[OK] ' . $message
    );
};

$error = static function(
    string $message
) use (&$errors): void {
    $errors[] = $message;

    cli_writeln(
        '[ERROR] ' . $message
    );
};

$warning = static function(
    string $message
) use (&$warnings): void {
    $warnings[] = $message;

    cli_writeln(
        '[WARN] ' . $message
    );
};

cli_heading(
    'Commerce payment orchestration audit'
);

try {
    $runtime =
        CommerceRuntimeFactory::create();

    $ok(
        'Commerce runtime created.'
    );

    $orchestrator =
        $runtime->payment_orchestration();

    $contextfactory =
        $runtime->payment_contexts();

    $ok(
        'Payment orchestrator and context factory available.'
    );
} catch (\Throwable $exception) {
    $error(
        'Runtime construction failed: '
        . $exception->getMessage()
    );

    $orchestrator = null;
    $contextfactory = null;
}

if (
    $orchestrator !== null
    && $contextfactory !== null
) {
    $scenarios = [
        [
            'provider' =>
                'stripe',

            'currency' =>
                'EUR',
        ],
        [
            'provider' =>
                'alfa',

            'currency' =>
                'RUB',
        ],
    ];

    foreach ($scenarios as $scenario) {
        $request =
            new CommercePaymentRequest(
                'audit:'
                    . $scenario['provider'],
                new CommercePaymentCustomer(
                    null,
                    'audit@example.test',
                    'Commerce',
                    'Audit'
                ),
                [
                    new CommercePaymentLine(
                        'audit:'
                            . $scenario['provider']
                            . ':line',
                        'Commerce audit payment',
                        1,
                        100,
                        $scenario['currency']
                    ),
                ],
                $scenario['currency'],
                100,
                $scenario['provider'],
                'https://example.test/success',
                'https://example.test/cancel',
                [
                    'audit' =>
                        true,
                ]
            );

        $context =
            $contextfactory->create(
                $request,
                false,
                [
                    'audit' =>
                        true,
                ]
            );

        try {
            $simulation =
                $orchestrator->simulate(
                    $request,
                    $context
                );

            if (
                $simulation->get_provider_key()
                !== $scenario['provider']
            ) {
                $error(
                    sprintf(
                        'Scenario "%s" resolved provider "%s".',
                        $scenario['provider'],
                        $simulation
                            ->get_provider_key()
                    )
                );

                continue;
            }

            $ok(
                sprintf(
                    'Provider "%s" was resolved without a remote call.',
                    $scenario['provider']
                )
            );

            if (
                $simulation
                    ->get_validation()
                    ->is_valid()
            ) {
                $ok(
                    sprintf(
                        'Provider "%s" validation passed.',
                        $scenario['provider']
                    )
                );
            } else {
                $warning(
                    sprintf(
                        'Provider "%s" is registered but its current environment validation failed.',
                        $scenario['provider']
                    )
                );
            }
        } catch (\Throwable $exception) {
            $warning(
                sprintf(
                    'Provider "%s" could not be simulated: %s',
                    $scenario['provider'],
                    $exception->getMessage()
                )
            );
        }
    }
}

cli_writeln('');
cli_writeln(
    'Errors: '
    . count($errors)
);
cli_writeln(
    'Warnings: '
    . count($warnings)
);

if ($errors !== []) {
    cli_writeln(
        'Commerce payment orchestration audit failed.'
    );

    exit(
        !empty($options['strict'])
            ? 1
            : 0
    );
}

cli_writeln(
    'Commerce payment orchestration audit completed successfully.'
);

exit(0);