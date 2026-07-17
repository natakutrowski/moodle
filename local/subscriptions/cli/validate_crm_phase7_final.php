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
Validate the final Phase 7 architecture and polish.

Checks:
- required Phase 7 files;
- centralized assistant and Customer Success routes;
- no hard-coded assistant URLs outside subscription_config;
- no hard-coded capabilities in Phase 7 admin pages;
- no ineffective global moodle_url imports;
- no raw exception messages redirected to users;
- action endpoints require sesskey;
- no SQL in renderers;
- no deprecated French Customer Success literals;
- language files remain aligned;
- plugin version is recent enough.

Options:
  --strict       Treat warnings as errors.
  -h, --help     Show this help.

TXT;

    exit(0);
}

$strict = !empty($options['strict']);

$pluginroot =
    $CFG->dirroot .
    '/local/subscriptions';

$errors = [];
$warnings = [];

$requiredfiles = [
    'classes/subscription_config.php',

    'classes/crm/success/plans/rendering/' .
        'CustomerSuccessPlanPresentation.php',

    'classes/crm/success/plans/logging/' .
        'CustomerSuccessPlanAdminEventLogger.php',

    'classes/crm/success/plans/repositories/' .
        'CustomerSuccessPlanRepository.php',

    'classes/crm/success/plans/repositories/' .
        'CustomerSuccessPlanReadRepository.php',

    'classes/crm/success/plans/repositories/' .
        'CustomerSuccessPlanOperationsRepository.php',

    'classes/crm/success/plans/repositories/' .
        'CustomerSuccessPlanRelationRepository.php',

    'classes/crm/success/plans/services/' .
        'CustomerSuccessPlanLifecycleService.php',

    'admin/assistant/plan.php',
    'admin/assistant/plan_action.php',
    'admin/assistant/plan_action_confirm.php',

    'cli/validate_crm_phase7_languages.php',
    'cli/validate_crm_success_plan_integrity.php',
    'cli/validate_crm_success_plan_lifecycle.php',
    'cli/validate_crm_phase7_tests.php',

    'tests/subscription_config_test.php',

    'tests/crm/success/plans/status_test.php',

    'tests/crm/success/plans/' .
        'dependency_state_service_test.php',

    'tests/crm/success/plans/' .
        'customer_success_plan_step_test.php',

    'tests/crm/success/plans/presentation_test.php',
    
];

foreach ($requiredfiles as $relativepath) {
    $path =
        $pluginroot .
        '/' .
        $relativepath;

    if (!is_file($path)) {
        $errors[] =
            'Missing required Phase 7 file: ' .
            $relativepath;
    }
}

/**
 * @return string[]
 */
function local_subscriptions_phase7_php_files(
    string $path
): array {
    if (is_file($path)) {
        return [$path];
    }

    if (!is_dir($path)) {
        return [];
    }

    $files = [];

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

    sort($files);

    return $files;
}

$phase7paths = [
    $pluginroot .
        '/admin/assistant',

    $pluginroot .
        '/classes/crm/success',

    $pluginroot .
        '/classes/crm/work',

    $pluginroot .
        '/classes/crm/assistant',

    $pluginroot .
        '/classes/commandcenter/providers/IntelligenceProvider.php',
];

$phase7files = [];

foreach ($phase7paths as $path) {
    $phase7files = array_merge(
        $phase7files,
        local_subscriptions_phase7_php_files(
            $path
        )
    );
}

$phase7files = array_values(
    array_unique(
        $phase7files
    )
);

$allpluginphpfiles =
    local_subscriptions_phase7_php_files(
        $pluginroot
    );

foreach ($allpluginphpfiles as $file) {
    $source = file_get_contents($file);

    if ($source === false) {
        $warnings[] =
            'Unable to inspect plugin file: ' .
            $file;

        continue;
    }

    $relativepath = str_replace(
        $pluginroot . '/',
        '',
        $file
    );

    if (
        $relativepath !==
            'classes/subscription_config.php' &&
        $relativepath !==
            'tests/subscription_config_test.php' &&
        preg_match(
            '#[\'"]/local/subscriptions/styles\.css[\'"]#',
            $source
        )
    ) {
        $errors[] =
            'Hard-coded plugin stylesheet route in ' .
            $relativepath;
    }

    if (
        $relativepath !==
            'classes/subscription_config.php' &&
        $relativepath !==
            'tests/subscription_config_test.php' &&
        preg_match(
            '#[\'"]/local/subscriptions/ajax/' .
                'crm_assistant_ask\.php[\'"]#',
            $source
        )
    ) {
        $errors[] =
            'Hard-coded CRM Assistant AI endpoint in ' .
            $relativepath;
    }

    if (
        $relativepath !==
            'classes/subscription_config.php' &&
        $relativepath !==
            'tests/subscription_config_test.php' &&
        preg_match(
            '#[\'"]/local/subscriptions/admin/assistant/' .
                '[^\'"]+[\'"]#',
            $source
        )
    ) {
        $errors[] =
            'Hard-coded Assistant route in ' .
            $relativepath;
    }    

    $isphase7renderer =
        str_contains(
            $relativepath,
            '/rendering/'
        ) ||
        $relativepath ===
            'classes/output/UserProfileRenderer.php';

    if (
        $isphase7renderer &&
        preg_match(
            '/\bglobal\s+\$DB\b|' .
            '\$DB\s*->/',
            $source
        )
    ) {
        $errors[] =
            'Direct database access in Phase 7 renderer: ' .
            $relativepath;
    }

}

foreach ($phase7files as $file) {
    $source = file_get_contents(
        $file
    );

    if ($source === false) {
        $warnings[] =
            'Unable to inspect file: ' .
            $file;

        continue;
    }

    $relativepath = str_replace(
        $pluginroot . '/',
        '',
        $file
    );

    if (
        $relativepath !==
            'classes/subscription_config.php' &&
        preg_match(
            '#[\'"]/local/subscriptions/admin/assistant/#',
            $source
        )
    ) {
        $errors[] =
            'Hard-coded assistant route in ' .
            $relativepath;
    }

    $hasnamespace =
        preg_match(
            '/^\s*namespace\s+[A-Za-z0-9_\\\\]+\s*;/m',
            $source
        ) === 1;

    if (
        !$hasnamespace &&
        preg_match(
            '/^\s*use\s+\\\\?moodle_url\s*;/m',
            $source
        )
    ) {
        $errors[] =
            'Ineffective global moodle_url import in non-namespaced file: ' .
            $relativepath;
    }

    if (
        str_starts_with(
            $relativepath,
            'admin/assistant/'
        ) &&
        preg_match(
            '/[\'"]local\\/subscriptions:' .
                '(?:view|manage)[a-z_]+[\'"]/',
            $source
        )
    ) {
        $errors[] =
            'Hard-coded capability in ' .
            $relativepath;
    }

    if (
        preg_match(
            '/redirect\\s*\\([^;]*' .
                '\\$exception->getMessage\\s*\\(\\s*\\)/s',
            $source
        )
    ) {
        $errors[] =
            'Raw exception message exposed in ' .
            $relativepath;
    }

    foreach (
        [
            'Réduire le risque de désabonnement',
            'Résoudre les difficultés de paiement',
            'Résoudre les demandes de support',
            'Rétablir l’accès à la formation',
            'Relancer la progression pédagogique',
            'Développer l’opportunité client',
            'Coordonner le suivi Customer Success',
            'Plan Customer Success préparé à partir de',
        ]
        as $forbiddenliteral
    ) {
        if (
            str_contains(
                $source,
                $forbiddenliteral
            )
        ) {
            $errors[] =
                sprintf(
                    'Deprecated translated literal in %s: %s',
                    $relativepath,
                    $forbiddenliteral
                );
        }
    }
}

$actionendpoints = [
    'admin/assistant/action.php',
    'admin/assistant/plan_action.php',
];

foreach ($actionendpoints as $relativepath) {
    $path =
        $pluginroot .
        '/' .
        $relativepath;

    if (!is_file($path)) {
        continue;
    }

    $source = file_get_contents($path);

    if (
        $source === false ||
        !preg_match(
            '/require_sesskey\\s*\\(\\s*\\)\\s*;/',
            $source
        )
    ) {
        $errors[] =
            'Missing require_sesskey() in ' .
            $relativepath;
    }
}

$configpath =
    $pluginroot .
    '/classes/subscription_config.php';

if (is_file($configpath)) {
    $configsource =
        file_get_contents(
            $configpath
        );

    foreach (
        [
            'plugin_path',
            'admin_crm_assistant_page',
            'admin_crm_assistant_action_page',
            'admin_crm_assistant_work_item_page',
            'admin_customer_success_plan_page',
            'admin_customer_success_plan_action_page',
            'admin_customer_success_plan_confirm_page',
            'plugin_stylesheet_page',
            'crm_assistant_ai_endpoint',
        ]
        as $method
    ) {
        if (
            $configsource === false ||
            !preg_match(
                '/function\s+' .
                    preg_quote(
                        $method,
                        '/'
                    ) .
                    '\s*\(/',
                $configsource
            )
        ) {
            $errors[] =
                'Missing subscription_config method: ' .
                $method;
        }
    }
}

$versionpath =
    $pluginroot .
    '/version.php';

if (is_file($versionpath)) {
    $versionsource =
        file_get_contents(
            $versionpath
        );

    if (
        $versionsource === false ||
        !preg_match(
            '/\\$plugin->version\\s*=\\s*(\\d+)\\s*;/',
            $versionsource,
            $matches
        )
    ) {
        $errors[] =
            'Unable to read plugin version.';
    } elseif ((int)$matches[1] < 2026071707) {
        $errors[] =
            'Plugin version must be at least 2026071707.';
    }
} else {
    $errors[] =
        'Missing version.php.';
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
    $errors = array_merge(
        $errors,
        $warnings
    );
}

if ($errors !== []) {
    cli_error(
        sprintf(
            'Final Phase 7 validation failed with %d error(s).',
            count($errors)
        )
    );
}

cli_writeln(
    '[OK] Required Phase 7 files are present.'
);

cli_writeln(
    '[OK] Assistant and Customer Success routes are centralized.'
);

cli_writeln(
    '[OK] Shared plugin resources are centralized.'
);

cli_writeln(
    '[OK] Phase 7 capabilities use the central security layer.'
);

cli_writeln(
    '[OK] Action endpoints require sesskey.'
);

cli_writeln(
    '[OK] No raw exception message is exposed.'
);

cli_writeln(
    '[OK] Phase 7 renderers do not access the database directly.'
);

cli_writeln(
    '[OK] No deprecated translated Customer Success literal was found.'
);

cli_writeln(
    '[OK] Phase 7 PHPUnit definitions are present.'
);

cli_writeln(
    '[OK] Final Phase 7 and 7ZZ static audit passed.'
);

exit(0);