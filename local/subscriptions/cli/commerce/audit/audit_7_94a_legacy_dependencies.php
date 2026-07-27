<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Phase 7.94A2 audit of remaining Legacy Commerce dependencies.
 *
 * The audit is read-only and idempotent. It classifies direct references to
 * historical Commerce tables and legacy bridge classes by business area.
 *
 * @package    local_subscriptions
 * @copyright  2026 CampusFR
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options, $unrecognised] = cli_get_params(
    [
        'help' => false,
        'json' => false,
        'strict' => false,
        'include-tests' => false,
        'details' => false,
    ],
    [
        'h' => 'help',
        'j' => 'json',
        's' => 'strict',
        't' => 'include-tests',
        'd' => 'details',
    ]
);

if ($unrecognised) {
    $unrecognised = implode("\n  ", $unrecognised);
    cli_error("Unknown options:\n  {$unrecognised}");
}

if ($options['help']) {
    $help = <<<HELP
Audit remaining Legacy Commerce dependencies for Phase 7.94A2.

The command is read-only and can be run repeatedly.

Options:
  -h, --help           Display this help.
  -j, --json           Emit a machine-readable JSON report.
  -s, --strict         Exit with a non-zero status when blocking runtime
                       dependencies are found.
  -t, --include-tests  Include PHPUnit and fixture files.
  -d, --details        Display every matching occurrence with line numbers.

Examples:
  php local/subscriptions/cli/commerce/audit/audit_7_94a_legacy_dependencies.php
  php local/subscriptions/cli/commerce/audit/audit_7_94a_legacy_dependencies.php --details
  php local/subscriptions/cli/commerce/audit/audit_7_94a_legacy_dependencies.php --json
  php local/subscriptions/cli/commerce/audit/audit_7_94a_legacy_dependencies.php --strict

HELP;
    echo $help;
    exit(0);
}

$pluginroot = realpath(__DIR__ . '/..');
if ($pluginroot === false) {
    cli_error('Unable to resolve the local_subscriptions plugin root.');
}

/**
 * Legacy dependency definitions.
 *
 * The keys are stable identifiers used by JSON reports and future certification tooling.
 */
$dependencies = [
    'subscription_catalogue' => [
        'label' => 'Subscription catalogue',
        'tokens' => [
            'subscription_plan',
            'subscription_plan_price',
            'subscription_plan_translation',
            'subscription_plan_entitlement',
            'subscription_plan_upgrade',
            'subscription_access_scope',
            'subscription_access_scope_translation',
        ],
    ],
    'subscription_entitlements' => [
        'label' => 'Subscription entitlements',
        'tokens' => [
            'user_subscription',
        ],
    ],
    'subscription_payments' => [
        'label' => 'Subscription payment requests',
        'tokens' => [
            'subscription_payment_request',
        ],
    ],
    'digital_catalogue' => [
        'label' => 'Digital catalogue',
        'tokens' => [
            'subscription_digital_product',
            'subscription_digital_product_lang',
        ],
    ],
    'digital_payments_entitlements' => [
        'label' => 'Digital payment requests and delivery rights',
        'tokens' => [
            'subscription_digital_payment_request',
        ],
    ],
    'legacy_adapters' => [
        'label' => 'Legacy Commerce adapters',
        'tokens' => [
            'LegacySubscriptionPlanRepository',
            'LegacyDigitalProductRepository',
            'LegacySubscriptionFulfillmentGateway',
            'LegacyDigitalFulfillmentGateway',
            'LegacyCommercePaymentRequestFactory',
            'LegacyPaymentRequestAdapter',
            'CommerceLegacyReadBridge',
            'CommerceDualWriteService',
        ],
    ],
];

$allowedextensions = ['php', 'xml', 'js', 'mustache'];
$selfrelativepath = 'cli/' . basename(__FILE__);
$occurrences = [];
$scannedfiles = 0;

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($pluginroot, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $fileinfo) {
    if (!$fileinfo->isFile()) {
        continue;
    }

    $extension = strtolower($fileinfo->getExtension());
    if (!in_array($extension, $allowedextensions, true)) {
        continue;
    }

    $absolutepath = $fileinfo->getPathname();
    $relativepath = str_replace('\\', '/', substr($absolutepath, strlen($pluginroot) + 1));

    if ($relativepath === $selfrelativepath) {
        continue;
    }

    if (!$options['include-tests'] && preg_match('#(^|/)(tests|fixtures)/#', $relativepath)) {
        continue;
    }

    // Generated third-party code is outside the CampusFR migration scope.
    if (preg_match('#(^|/)(vendor|node_modules)/#', $relativepath)) {
        continue;
    }

    $content = file_get_contents($absolutepath);
    if ($content === false) {
        continue;
    }

    $scannedfiles++;
    $lines = preg_split('/\R/', $content) ?: [];
    foreach ($lines as $linenumber => $line) {
        foreach ($dependencies as $dependencykey => $definition) {
            foreach ($definition['tokens'] as $token) {
                if (!local_subscriptions_794a_line_contains_token($line, $token)) {
                    continue;
                }

                $area = local_subscriptions_794a_classify_area($relativepath);
                $disposition = local_subscriptions_794a_classify_disposition($relativepath, $area);

                $occurrences[] = [
                    'dependency' => $dependencykey,
                    'dependencylabel' => $definition['label'],
                    'token' => $token,
                    'file' => $relativepath,
                    'line' => $linenumber + 1,
                    'area' => $area,
                    'disposition' => $disposition,
                    'severity' => local_subscriptions_794a_severity($disposition),
                    'excerpt' => trim($line),
                ];
            }
        }
    }
}

usort($occurrences, static function(array $left, array $right): int {
    return [$left['severity'], $left['area'], $left['file'], $left['line'], $left['token']]
        <=> [$right['severity'], $right['area'], $right['file'], $right['line'], $right['token']];
});

$summary = local_subscriptions_794a_build_summary($occurrences);
$report = [
    'audit' => '7.94A2 Legacy Commerce dependencies',
    'component' => 'local_subscriptions',
    'generatedat' => time(),
    'pluginroot' => $pluginroot,
    'scannedfiles' => $scannedfiles,
    'includedtests' => (bool)$options['include-tests'],
    'summary' => $summary,
    'occurrences' => $occurrences,
];

if ($options['json']) {
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
} else {
    local_subscriptions_794a_print_report($report, (bool)$options['details']);
}

if ($options['strict'] && $summary['blockingoccurrences'] > 0) {
    exit(2);
}

exit(0);

/**
 * Checks whether a line contains an exact dependency token.
 *
 * Token boundaries prevent a generic table name such as subscription_plan
 * from also matching subscription_plan_price on the same line.
 *
 * @param string $line Source line.
 * @param string $token Dependency token.
 * @return bool
 */
function local_subscriptions_794a_line_contains_token(string $line, string $token): bool {
    return preg_match('/\b' . preg_quote($token, '/') . '\b/i', $line) === 1;
}

/**
 * Classifies a file into a stable business area.
 *
 * @param string $relativepath Path relative to the plugin root.
 * @return string
 */
function local_subscriptions_794a_classify_area(string $relativepath): string {
    $rules = [
        'schema' => '#^db/(install\.xml|upgrade\.php)$#',
        'tests' => '#(^|/)(tests|fixtures)/#',
        'migration' => '#(^|/)(migration|backfill|reconciliation|dualwrite)(/|_)#i',
        'audit_certification' => '#(^|/)(audit|certification|rollout)(/|_)#i',
        'legacy_compatibility' => '#^classes/commerce/legacy/#',
        'checkout_payment' => '#(^|/)(checkout|payment|webhook)(/|\.|_)#i',
        'fulfillment' => '#(^|/)(fulfillment|postpayment)(/|\.|_)#i',
        'crm_admin' => '#(^|/)(crm|admin|tabs|renderer)(/|\.|_)#i',
        'tasks_cron' => '#(^|/)(task|tasks|cron)(/|\.|_)#i',
        'email' => '#(^|/)(email|mailer|mail)(/|\.|_)#i',
        'front_office' => '#^(subscribe|checkout|digital_|my_|portal|download_|payment_).*\.php$#i',
        'ajax_api' => '#^(ajax|external|api)/#i',
        'domain_application' => '#^classes/#',
        'cli_operations' => '#^cli/#',
    ];

    foreach ($rules as $area => $pattern) {
        if (preg_match($pattern, $relativepath)) {
            return $area;
        }
    }

    return 'other';
}

/**
 * Determines whether a dependency is allowed, transitional or blocking for the 7.94 target.
 *
 * @param string $relativepath Relative file path.
 * @param string $area Classified area.
 * @return string
 */
function local_subscriptions_794a_classify_disposition(string $relativepath, string $area): string {
    if ($area === 'schema' || $area === 'tests') {
        return 'allowed_history';
    }

    if (in_array($area, ['migration', 'audit_certification', 'legacy_compatibility'], true)) {
        return 'allowed_compatibility';
    }

    if ($area === 'cli_operations') {
        return 'review_cli';
    }

    if (str_contains($relativepath, '/shadow/') || str_contains($relativepath, 'Shadow')) {
        return 'allowed_compatibility';
    }

    return 'migration_required';
}

/**
 * Maps a disposition to a sortable severity.
 *
 * @param string $disposition Disposition identifier.
 * @return string
 */
function local_subscriptions_794a_severity(string $disposition): string {
    return match ($disposition) {
        'migration_required' => 'blocker',
        'review_cli' => 'warning',
        default => 'info',
    };
}

/**
 * Builds aggregate counters for the report.
 *
 * @param array $occurrences Occurrence records.
 * @return array
 */
function local_subscriptions_794a_build_summary(array $occurrences): array {
    $summary = [
        'totaloccurrences' => count($occurrences),
        'uniquefiles' => 0,
        'blockingoccurrences' => 0,
        'blockingfiles' => 0,
        'warningoccurrences' => 0,
        'bydependency' => [],
        'byarea' => [],
        'bydisposition' => [],
    ];

    $allfiles = [];
    $blockingfiles = [];
    foreach ($occurrences as $occurrence) {
        $allfiles[$occurrence['file']] = true;
        if ($occurrence['severity'] === 'blocker') {
            $summary['blockingoccurrences']++;
            $blockingfiles[$occurrence['file']] = true;
        } else if ($occurrence['severity'] === 'warning') {
            $summary['warningoccurrences']++;
        }

        foreach (['dependency', 'area', 'disposition'] as $dimension) {
            $summarykey = 'by' . $dimension;
            $value = $occurrence[$dimension];
            $summary[$summarykey][$value] = ($summary[$summarykey][$value] ?? 0) + 1;
        }
    }

    $summary['uniquefiles'] = count($allfiles);
    $summary['blockingfiles'] = count($blockingfiles);
    ksort($summary['bydependency']);
    ksort($summary['byarea']);
    ksort($summary['bydisposition']);

    return $summary;
}

/**
 * Prints the human-readable report.
 *
 * @param array $report Complete report.
 * @param bool $showdetails Whether individual occurrences must be displayed.
 * @return void
 */
function local_subscriptions_794a_print_report(array $report, bool $showdetails): void {
    $summary = $report['summary'];

    cli_heading('Phase 7.94A2 - Legacy Commerce dependency audit');
    mtrace('Files scanned: ' . $report['scannedfiles']);
    mtrace('Files containing Legacy dependencies: ' . $summary['uniquefiles']);
    mtrace('Occurrences: ' . $summary['totaloccurrences']);
    mtrace('Blocking runtime files: ' . $summary['blockingfiles']);
    mtrace('Blocking runtime occurrences: ' . $summary['blockingoccurrences']);
    mtrace('CLI occurrences requiring review: ' . $summary['warningoccurrences']);

    mtrace('');
    mtrace('By dependency family:');
    foreach ($summary['bydependency'] as $key => $count) {
        mtrace(sprintf('  %-38s %d', $key, $count));
    }

    mtrace('');
    mtrace('By business area:');
    foreach ($summary['byarea'] as $key => $count) {
        mtrace(sprintf('  %-38s %d', $key, $count));
    }

    mtrace('');
    mtrace('By migration disposition:');
    foreach ($summary['bydisposition'] as $key => $count) {
        mtrace(sprintf('  %-38s %d', $key, $count));
    }

    if ($showdetails) {
        mtrace('');
        mtrace('Detailed occurrences:');
        foreach ($report['occurrences'] as $occurrence) {
            mtrace(sprintf(
                '[%s] [%s] %s:%d | %s | %s',
                strtoupper($occurrence['severity']),
                $occurrence['area'],
                $occurrence['file'],
                $occurrence['line'],
                $occurrence['token'],
                $occurrence['excerpt']
            ));
        }
    }

    mtrace('');
    if ($summary['blockingoccurrences'] === 0) {
        cli_writeln('[OK] No blocking Legacy Commerce runtime dependency found.');
    } else {
        cli_writeln('[MIGRATION REQUIRED] Native-only target is not reached yet.');
        cli_writeln('Run with --details or --json to obtain the migration inventory.');
    }
}