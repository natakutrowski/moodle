<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\payment\provider\CommercePaymentProviderRegistryFactory;
use local_subscriptions\commerce\payment\provider\alfa\AlfaCommercePaymentProvider;
use local_subscriptions\commerce\payment\provider\stripe\StripeCommercePaymentProvider;

[$options, $unrecognized] = cli_get_params(
    [
        'help' => false,
        'strict' => false,
    ],
    [
        'h' => 'help',
        's' => 'strict',
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
Audit the Commerce-to-Legacy payment bridges.

Options:
-h, --help       Display this help.
-s, --strict     Return a non-zero exit code when an error is found.

Example:
php local/subscriptions/cli/audit_commerce_payment_bridges.php --strict

HELP;

    exit(0);
}

$errors = [];
$warnings = [];

$writeok = static function(
    string $message
): void {
    cli_writeln(
        '[OK] ' . $message
    );
};

$writewarning = static function(
    string $message
) use (&$warnings): void {
    $warnings[] = $message;

    cli_writeln(
        '[WARN] ' . $message
    );
};

$writeerror = static function(
    string $message
) use (&$errors): void {
    $errors[] = $message;

    cli_writeln(
        '[ERROR] ' . $message
    );
};

cli_heading(
    'Commerce payment bridges audit'
);

try {
    $registry =
        CommercePaymentProviderRegistryFactory::create();

    $writeok(
        'The Commerce payment provider registry was created.'
    );
} catch (\Throwable $exception) {
    $writeerror(
        'The registry could not be created: '
        . $exception->getMessage()
    );

    $registry = null;
}

if ($registry !== null) {
    $expectedproviders = [
        StripeCommercePaymentProvider::KEY =>
            StripeCommercePaymentProvider::class,

        AlfaCommercePaymentProvider::KEY =>
            AlfaCommercePaymentProvider::class,
    ];

    $actualkeys =
        $registry->keys();

    foreach (
        $expectedproviders as
        $providerkey => $expectedclass
    ) {
        if (!$registry->has($providerkey)) {
            $writeerror(
                sprintf(
                    'Provider "%s" is not registered.',
                    $providerkey
                )
            );

            continue;
        }

        try {
            $provider =
                $registry->get(
                    $providerkey
                );
        } catch (\Throwable $exception) {
            $writeerror(
                sprintf(
                    'Provider "%s" could not be loaded: %s',
                    $providerkey,
                    $exception->getMessage()
                )
            );

            continue;
        }

        if (!$provider instanceof $expectedclass) {
            $writeerror(
                sprintf(
                    'Provider "%s" uses unexpected class "%s".',
                    $providerkey,
                    get_debug_type($provider)
                )
            );

            continue;
        }

        $writeok(
            sprintf(
                'Provider "%s" is registered with the expected class.',
                $providerkey
            )
        );

        try {
            $capabilities =
                $provider->get_capabilities();

            $writeok(
                sprintf(
                    'Provider "%s" capabilities were loaded.',
                    $providerkey
                )
            );
        } catch (\Throwable $exception) {
            $writeerror(
                sprintf(
                    'Provider "%s" capabilities could not be loaded: %s',
                    $providerkey,
                    $exception->getMessage()
                )
            );

            continue;
        }

        if (!$capabilities->supports_redirect()) {
            $writeerror(
                sprintf(
                    'Provider "%s" does not announce redirect support.',
                    $providerkey
                )
            );
        }

        if ($capabilities->supports_retrieval()) {
            $writeerror(
                sprintf(
                    'Provider "%s" incorrectly announces retrieval support.',
                    $providerkey
                )
            );
        }

        if ($capabilities->supports_cancellation()) {
            $writeerror(
                sprintf(
                    'Provider "%s" incorrectly announces cancellation support.',
                    $providerkey
                )
            );
        }

        if ($capabilities->supports_refunds()) {
            $writeerror(
                sprintf(
                    'Provider "%s" incorrectly announces refund support.',
                    $providerkey
                )
            );
        }

        if (!$capabilities->supports_multiple_lines()) {
            $writeerror(
                sprintf(
                    'Provider "%s" should support aggregated Commerce lines.',
                    $providerkey
                )
            );
        }

        $metadata =
            $capabilities->get_metadata();

        if (
            ($metadata['bridge'] ?? null)
            !== 'legacy'
        ) {
            $writeerror(
                sprintf(
                    'Provider "%s" is not marked as a Legacy bridge.',
                    $providerkey
                )
            );
        }

        if ($provider->is_available()) {
            $writeok(
                sprintf(
                    'Provider "%s" is configured and available.',
                    $providerkey
                )
            );
        } else {
            $writewarning(
                sprintf(
                    'Provider "%s" is registered but not configured in the current environment.',
                    $providerkey
                )
            );
        }

        $currencies =
            $capabilities->get_currencies();

        if ($currencies === []) {
            $writeerror(
                sprintf(
                    'Provider "%s" exposes no currency.',
                    $providerkey
                )
            );
        } else {
            $writeok(
                sprintf(
                    'Provider "%s" supports: %s.',
                    $providerkey,
                    implode(
                        ', ',
                        $currencies
                    )
                )
            );
        }
    }

    $unexpectedkeys =
        array_values(
            array_diff(
                $actualkeys,
                array_keys($expectedproviders)
            )
        );

    if ($unexpectedkeys !== []) {
        $writewarning(
            'Unexpected providers are registered: '
            . implode(
                ', ',
                $unexpectedkeys
            )
        );
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
        'Commerce payment bridges audit failed.'
    );

    exit(
        !empty($options['strict'])
            ? 1
            : 0
    );
}

cli_writeln(
    'Commerce payment bridges audit completed successfully.'
);

exit(0);