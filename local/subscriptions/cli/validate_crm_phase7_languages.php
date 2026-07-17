<?php

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

list($options) = cli_get_params(
    [
        'help' => false,
        'strict' => false,
    ],
    [
        'h' => 'help',
    ]
);

if (!empty($options['help'])) {
    echo <<<TXT
Validate Phase 7 language integration.

Checks:
- language files exist;
- no duplicated language keys;
- FR, EN and RU contain the same keys;
- dynamic Phase 7 language families are complete;
- deprecated French Customer Success literals are absent;
- raw blocked reason codes are not rendered directly.

Options:
  --strict       Convert warnings to errors.
  -h, --help     Show this help.

TXT;

    exit(0);
}

$strict = !empty($options['strict']);
$pluginroot =
    $CFG->dirroot .
    '/local/subscriptions';

$languages = ['fr', 'en', 'ru'];
$component = 'local_subscriptions';

$errors = [];
$warnings = [];
$definitions = [];

/**
 * Extract language key occurrences.
 *
 * @return array<string,int[]>
 */
function local_subscriptions_phase7_lang_keys(
    string $source
): array {
    preg_match_all(
        '/\\$string\\[\\s*[\'"]([^\'"]+)[\'"]\\s*\\]\\s*=/',
        $source,
        $matches,
        PREG_OFFSET_CAPTURE
    );

    $result = [];

    foreach ($matches[1] as $match) {
        $key = $match[0];
        $offset = $match[1];

        $line =
            substr_count(
                substr(
                    $source,
                    0,
                    $offset
                ),
                "\n"
            ) + 1;

        $result[$key][] = $line;
    }

    return $result;
}

foreach ($languages as $language) {
    $path =
        $pluginroot .
        '/lang/' .
        $language .
        '/' .
        $component .
        '.php';

    if (!is_file($path)) {
        $errors[] =
            'Missing language file: ' .
            $path;

        continue;
    }

    $source = file_get_contents($path);

    if ($source === false) {
        $errors[] =
            'Unable to read language file: ' .
            $path;

        continue;
    }

    $definitions[$language] =
        local_subscriptions_phase7_lang_keys(
            $source
        );

    foreach (
        $definitions[$language]
        as $key => $lines
    ) {
        if (count($lines) <= 1) {
            continue;
        }

        $errors[] =
            sprintf(
                'Duplicate key in %s: %s at lines %s',
                $language,
                $key,
                implode(', ', $lines)
            );
    }
}

if (count($definitions) === count($languages)) {
    $referencekeys =
        array_keys(
            $definitions['en']
        );

    sort($referencekeys);

    foreach (['fr', 'ru'] as $language) {
        $keys =
            array_keys(
                $definitions[$language]
            );

        sort($keys);

        $missing =
            array_values(
                array_diff(
                    $referencekeys,
                    $keys
                )
            );

        $extra =
            array_values(
                array_diff(
                    $keys,
                    $referencekeys
                )
            );

        foreach ($missing as $key) {
            $errors[] =
                sprintf(
                    'Missing %s key: %s',
                    $language,
                    $key
                );
        }

        foreach ($extra as $key) {
            $errors[] =
                sprintf(
                    'Extra %s key: %s',
                    $language,
                    $key
                );
        }
    }
}

$requiredkeys = [
    'csplanpage',
    'csplanusersection',
    'csplannoneforuser',
    'csplanblocked',

    'csplanstatus_draft',
    'csplanstatus_active',
    'csplanstatus_paused',
    'csplanstatus_completed',
    'csplanstatus_cancelled',

    'csplanstepstatus_pending',
    'csplanstepstatus_ready',
    'csplanstepstatus_blocked',
    'csplanstepstatus_in_progress',
    'csplanstepstatus_completed',
    'csplanstepstatus_skipped',

    'csplanpriority_low',
    'csplanpriority_normal',
    'csplanpriority_high',
    'csplanpriority_urgent',
    'csplanpriority_critical',

    'csplanobjective_reduce_churn_risk',
    'csplanobjective_resolve_payment_friction',
    'csplanobjective_resolve_support_pressure',
    'csplanobjective_restore_learning_access',
    'csplanobjective_restore_learning_engagement',
    'csplanobjective_develop_customer_opportunity',
    'csplanobjective_coordinate_customer_success',

    'csplandescription_recommendations',

    'csplanblockedreason_dependency_cycle',
    'csplanblockedreason_manual',
    'csplanblockedreason_unknown',

    'csplansource_manual',
    'csplansource_recommendation_engine',
    'csplansource_correlation_engine',
    'csplansource_crm_assistant',
    'csplansource_user_360',

    'csplanprogresslabel',
];

foreach ($languages as $language) {
    if (!isset($definitions[$language])) {
        continue;
    }

    foreach ($requiredkeys as $key) {
        if (
            !isset(
                $definitions[$language][$key]
            )
        ) {
            $errors[] =
                sprintf(
                    'Missing required Phase 7 key in %s: %s',
                    $language,
                    $key
                );
        }
    }
}

$phase7paths = [
    $pluginroot .
        '/classes/crm/success',

    $pluginroot .
        '/classes/crm/assistant',

    $pluginroot .
        '/classes/crm/work',

    $pluginroot .
        '/classes/commandcenter/providers/IntelligenceProvider.php',

    $pluginroot .
        '/admin/assistant',
];

$forbiddenliterals = [
    'Réduire le risque de désabonnement',
    'Résoudre les difficultés de paiement',
    'Résoudre les demandes de support',
    'Rétablir l’accès à la formation',
    'Relancer la progression pédagogique',
    'Développer l’opportunité client',
    'Coordonner le suivi Customer Success',
    'Plan Customer Success préparé à partir de',
];

foreach ($phase7paths as $path) {
    $files = [];

    if (is_file($path)) {
        $files[] = $path;
    } elseif (is_dir($path)) {
        $iterator =
            new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $path,
                    FilesystemIterator::SKIP_DOTS
                )
            );

        foreach ($iterator as $fileinfo) {
            if (
                $fileinfo->isFile() &&
                strtolower(
                    $fileinfo->getExtension()
                ) === 'php'
            ) {
                $files[] =
                    $fileinfo->getPathname();
            }
        }
    }

    foreach ($files as $file) {
        $source = file_get_contents($file);

        if ($source === false) {
            $warnings[] =
                'Unable to inspect: ' .
                $file;

            continue;
        }

        foreach (
            $forbiddenliterals
            as $literal
        ) {
            if (
                strpos(
                    $source,
                    $literal
                ) !== false
            ) {
                $errors[] =
                    sprintf(
                        'Hard-coded translated content in %s: %s',
                        str_replace(
                            $pluginroot . '/',
                            '',
                            $file
                        ),
                        $literal
                    );
            }
        }

        if (
            preg_match(
                '/s\\s*\\(\\s*\\$[A-Za-z_][A-Za-z0-9_]*->blockedreason\\s*\\)/',
                $source
            )
        ) {
            $errors[] =
                'Raw blocked reason rendered in ' .
                str_replace(
                    $pluginroot . '/',
                    '',
                    $file
                );
        }
    }
}

foreach ($warnings as $warning) {
    cli_writeln(
        '[WARNING] ' .
        $warning
    );
}

foreach ($errors as $error) {
    cli_writeln(
        '[ERROR] ' .
        $error
    );
}

if (
    $strict &&
    $warnings !== []
) {
    foreach ($warnings as $warning) {
        $errors[] = $warning;
    }
}

if ($errors !== []) {
    cli_error(
        sprintf(
            'Phase 7 language validation failed with %d error(s).',
            count($errors)
        )
    );
}

cli_writeln(
    '[OK] Phase 7 language files are aligned.'
);

cli_writeln(
    '[OK] No duplicated language keys detected.'
);

cli_writeln(
    '[OK] Required dynamic language families are complete.'
);

cli_writeln(
    '[OK] No deprecated translated Customer Success literals detected.'
);

exit(0);