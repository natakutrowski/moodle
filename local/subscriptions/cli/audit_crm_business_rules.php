<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\crm\business\CrmBusinessAuditService;

[$options, $unrecognized] = cli_get_params(
    [
        'help' => false,
        'strict' => false,
        'json' => false,
    ],
    [
        'h' => 'help',
        's' => 'strict',
        'j' => 'json',
    ]
);

if (!empty($unrecognized)) {
    $unrecognized = implode("\n  ", $unrecognized);

    cli_error(
        "Unknown options:\n  {$unrecognized}"
    );
}

if (!empty($options['help'])) {
    $help = <<<HELP
Audit the CRM subscription, payment and currency business rules.

Options:
-h, --help      Display this help.
-s, --strict    Exit with an error when data warnings are detected.
-j, --json      Output the audit report as JSON.

Examples:
php local/subscriptions/cli/audit_crm_business_rules.php

php local/subscriptions/cli/audit_crm_business_rules.php --strict

php local/subscriptions/cli/audit_crm_business_rules.php --json

HELP;

    echo $help;
    exit(0);
}

$service = new CrmBusinessAuditService();
$report = $service->run();

if (!empty($options['json'])) {
    echo json_encode(
        $report->to_array(),
        JSON_PRETTY_PRINT
        | JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
    ) . PHP_EOL;

    if (!empty($options['strict']) && $report->has_warnings()) {
        exit(1);
    }

    exit(0);
}

cli_heading('CRM business rules audit');

print_metric(
    'Subscriptions',
    $report->subscriptions
);

print_metric(
    'Trial subscriptions',
    $report->trialSubscriptions
);

print_metric(
    'Distinct trial users',
    $report->trialUsers
);

print_metric(
    'Paid subscriptions',
    $report->paidSubscriptions
);

print_metric(
    'Distinct paid customers',
    $report->paidCustomers
);

print_metric(
    'Legacy paid subscriptions',
    $report->legacyPaidSubscriptions
);

print_metric(
    'Unconfirmed subscriptions',
    $report->unconfirmedSubscriptions,
    $report->unconfirmedSubscriptions > 0
);

print_metric(
    'Successful subscription payments',
    $report->successfulSubscriptionPayments
);

print_metric(
    'Successful subscription payments without current link',
    $report->unlinkedSuccessfulSubscriptionPayments
);

print_metric(
    'Successful digital payments',
    $report->successfulDigitalPayments
);

print_metric(
    'Distinct digital customers',
    $report->digitalCustomers
);

echo PHP_EOL;
cli_heading('Data consistency');

print_metric(
    'Trial plan/status mismatches',
    $report->trialPlanStatusMismatches,
    $report->trialPlanStatusMismatches > 0
);

print_metric(
    'Trial provider mismatches',
    $report->trialProviderMismatches,
    $report->trialProviderMismatches > 0
);

print_metric(
    'Trial subscriptions containing a payment',
    $report->paidTrialSubscriptions,
    $report->paidTrialSubscriptions > 0
);

print_metric(
    'Historical payments whose subscription was deleted',
    $report->paidRequestsWithoutSubscription
);

if ($report->paidRequestsWithoutSubscription > 0) {
    cli_writeln(
        '  These payment records remain valid for revenue reporting.'
    );

    cli_writeln(
        '  Their former user_subscription record is no longer present.'
    );
}

print_metric(
    'Successful subscription payments without payment date',
    $report->paidRequestsWithoutPaymentDate,
    $report->paidRequestsWithoutPaymentDate > 0
);

print_metric(
    'Successful subscription payments without currency',
    $report->paidRequestsWithoutCurrency,
    $report->paidRequestsWithoutCurrency > 0
);

print_metric(
    'Successful digital payments without payment date',
    $report->digitalPaymentsWithoutPaymentDate,
    $report->digitalPaymentsWithoutPaymentDate > 0
);

print_metric(
    'Successful digital payments without currency',
    $report->digitalPaymentsWithoutCurrency,
    $report->digitalPaymentsWithoutCurrency > 0
);

print_metric(
    'Subscription/payment currency mismatches',
    $report->subscriptionCurrencyMismatches,
    $report->subscriptionCurrencyMismatches > 0
);

echo PHP_EOL;
cli_heading('Observed subscription statuses');
print_map($report->subscriptionStatuses);

echo PHP_EOL;
cli_heading('Observed subscription payment statuses');
print_map($report->subscriptionPaymentStatuses);

echo PHP_EOL;
cli_heading('Observed digital payment statuses');
print_map($report->digitalPaymentStatuses);

echo PHP_EOL;
cli_heading('Observed currencies');

echo 'Subscription payments: ';
echo !empty($report->subscriptionCurrencies)
    ? implode(', ', $report->subscriptionCurrencies)
    : '(none)';
echo PHP_EOL;

echo 'Digital payments: ';
echo !empty($report->digitalCurrencies)
    ? implode(', ', $report->digitalCurrencies)
    : '(none)';
echo PHP_EOL;

echo PHP_EOL;

if ($report->has_warnings()) {
    cli_writeln(
        '[WARNING] Business-data inconsistencies were detected.'
    );

    cli_writeln(
        'Review the counts above before enabling the new Dashboard metrics.'
    );

    if (!empty($options['strict'])) {
        exit(1);
    }
} else {
    cli_writeln(
        '[OK] CRM business data matches the canonical rules.'
    );
}

exit(0);

/**
 * Print one audit metric.
 */
function print_metric(
    string $label,
    int $value,
    bool $warning = false
): void {
    $prefix = $warning ? '[WARNING]' : '[OK]';

    cli_writeln(
        sprintf(
            '%s %-58s %d',
            $prefix,
            $label . ':',
            $value
        )
    );
}

/**
 * Print grouped values.
 *
 * @param array<string, int> $values
 */
function print_map(array $values): void {
    if (empty($values)) {
        cli_writeln('(none)');
        return;
    }

    foreach ($values as $key => $value) {
        cli_writeln(
            sprintf(
                '  %-30s %d',
                $key,
                $value
            )
        );
    }
}