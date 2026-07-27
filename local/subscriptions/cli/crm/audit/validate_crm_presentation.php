<?php

declare(strict_types=1);

define('CLI_SCRIPT', true);

require_once __DIR__ . '/../../../../../config.php';
require_once $CFG->libdir . '/clilib.php';

use local_subscriptions\crm\work\rendering\WorkItemPresentation;

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
    $unrecognized = implode(
        PHP_EOL . '  ',
        $unrecognized
    );

    cli_error(
        'Unknown options:' .
        PHP_EOL .
        '  ' .
        $unrecognized
    );
}

if (!empty($options['help'])) {
    $help = <<<HELP
Validate CRM presentation labels and translations.

Options:
-h, --help       Display this help.
-s, --strict     Return a failure exit code for warnings.

Example:
php local/subscriptions/cli/crm/audit/validate_crm_presentation.php --strict

HELP;

    cli_writeln($help);
    exit(0);
}

$pluginroot = dirname(__DIR__);

$errors = [];
$warnings = [];
$successes = [];

/**
 * Register a successful validation.
 *
 * @param string $message
 * @return void
 */
function presentation_validation_ok(
    string $message
): void {
    global $successes;

    $successes[] = $message;
}

/**
 * Register a warning.
 *
 * @param string $message
 * @return void
 */
function presentation_validation_warning(
    string $message
): void {
    global $warnings;

    $warnings[] = $message;
}

/**
 * Register an error.
 *
 * @param string $message
 * @return void
 */
function presentation_validation_error(
    string $message
): void {
    global $errors;

    $errors[] = $message;
}

/**
 * Read a plugin file.
 *
 * @param string $pluginroot
 * @param string $relativepath
 * @return string|null
 */
function presentation_read_file(
    string $pluginroot,
    string $relativepath
): ?string {
    $path =
        $pluginroot .
        DIRECTORY_SEPARATOR .
        $relativepath;

    if (!is_file($path)) {
        presentation_validation_error(
            'Required file is missing: ' .
            $relativepath
        );

        return null;
    }

    $content = file_get_contents($path);

    if ($content === false) {
        presentation_validation_error(
            'Unable to read file: ' .
            $relativepath
        );

        return null;
    }

    return $content;
}

/**
 * Load language keys without executing the language file.
 *
 * @param string $path
 * @return array<string, string>
 */
function presentation_load_language_keys(
    string $path
): array {
    if (!is_file($path)) {
        return [];
    }

    $content = file_get_contents($path);

    if ($content === false) {
        return [];
    }

    preg_match_all(
        '/\$string\[[\'"]([^\'"]+)[\'"]\]\s*=/',
        $content,
        $matches
    );

    if (
        !isset($matches[1]) ||
        !is_array($matches[1])
    ) {
        return [];
    }

    return array_fill_keys(
        $matches[1],
        ''
    );
}

/**
 * Find line number for a byte offset.
 *
 * @param string $content
 * @param int $offset
 * @return int
 */
function presentation_line_number(
    string $content,
    int $offset
): int {
    return substr_count(
        substr($content, 0, $offset),
        PHP_EOL
    ) + 1;
}

$requiredfiles = [
    'classes/crm/assistant/rendering/CrmAssistantRenderer.php',
    'classes/crm/work/rendering/WorkItemPresentation.php',
    'classes/crm/work/intelligence/rendering/WorkItemSuggestionRenderer.php',
    'classes/crm/work/intelligence/services/WorkItemSuggestionService.php',
    'lang/fr/local_subscriptions.php',
    'lang/en/local_subscriptions.php',
    'lang/ru/local_subscriptions.php',
];

foreach ($requiredfiles as $relativepath) {
    $path =
        $pluginroot .
        DIRECTORY_SEPARATOR .
        $relativepath;

    if (!is_file($path)) {
        presentation_validation_error(
            'Required file is missing: ' .
            $relativepath
        );
    }
}

if ($errors === []) {
    presentation_validation_ok(
        'All required CRM presentation files are available.'
    );
}

/*
 * Validate language parity for CRM presentation keys.
 */

$languages = [
    'en',
    'fr',
    'ru',
];

$languagekeys = [];

foreach ($languages as $language) {
    $path =
        $pluginroot .
        '/lang/' .
        $language .
        '/local_subscriptions.php';

    $languagekeys[$language] =
        presentation_load_language_keys(
            $path
        );
}

$prefixes = [
    'crm_assistant_recommendation_',
    'crm_assistant_evidence_',
    'crm_assistant_evidence_value_',
    'crm_work_source_',
    'crm_work_suggestion_reason_',
    'crm_work_status_',
    'crm_work_priority_',
    'crm_work_type_',
];

$presentationkeys = [];

foreach ($languagekeys['en'] ?? [] as $key => $unused) {
    foreach ($prefixes as $prefix) {
        if (str_starts_with($key, $prefix)) {
            $presentationkeys[] = $key;
            break;
        }
    }
}

sort($presentationkeys);

foreach ($presentationkeys as $key) {
    foreach ($languages as $language) {
        if (
            !array_key_exists(
                $key,
                $languagekeys[$language] ?? []
            )
        ) {
            presentation_validation_error(
                sprintf(
                    'Missing presentation string "%s" in language "%s".',
                    $key,
                    $language
                )
            );
        }
    }
}

if ($presentationkeys === []) {
    presentation_validation_warning(
        'No CRM presentation language keys were found.'
    );
} else if ($errors === []) {
    presentation_validation_ok(
        count($presentationkeys) .
        ' CRM presentation strings have FR/EN/RU parity.'
    );
}

/*
 * Validate mandatory labels introduced during 7ZZ-4B.
 */

$mandatorykeys = [
    'crm_assistant_recommendation_suggest_digital_product',
    'crm_assistant_recommendation_suggest_digital_product_desc',
    'crm_assistant_recommendation_create_first_crm_note',
    'crm_assistant_recommendation_create_first_crm_note_desc',

    'crm_assistant_evidence_activity_inactive_30d',
    'crm_assistant_evidence_value_activity_inactive_30d',
    'crm_assistant_evidence_loyalty_no_current_access',
    'crm_assistant_evidence_value_loyalty_no_current_access',

    'crm_assistant_evidence_crm_customer_without_notes',
    'crm_assistant_evidence_opportunity_cross_sell_digital_product',

    'crm_work_source_manual',
    'crm_work_source_assistant',
    'crm_work_source_intelligence',
    'crm_work_source_automation',
    'crm_work_source_inbox',
    'crm_work_source_system',

    'crm_work_suggestion_reason_generated_from_recommendation',
    'crm_work_suggestion_reason_priority_derived_from_recommendation',
    'crm_work_suggestion_reason_type_derived_from_scenario',
    'crm_work_suggestion_reason_team_suggested_from_domain_and_workload',
    'crm_work_suggestion_reason_duplicate_candidates_detected',
];

foreach ($mandatorykeys as $key) {
    foreach ($languages as $language) {
        if (
            !array_key_exists(
                $key,
                $languagekeys[$language] ?? []
            )
        ) {
            presentation_validation_error(
                sprintf(
                    'Required string "%s" is missing from "%s".',
                    $key,
                    $language
                )
            );
        }
    }
}

if ($errors === []) {
    presentation_validation_ok(
        'All mandatory 7ZZ-4B presentation strings are available.'
    );
}

/*
 * Check that Assistant renderer contains the presentation helpers.
 */

$assistantrenderer = presentation_read_file(
    $pluginroot,
    'classes/crm/assistant/rendering/CrmAssistantRenderer.php'
);

if ($assistantrenderer !== null) {
    $requiredmethods = [
        'recommendation_label',
        'recommendation_description',
        'evidence_label',
        'evidence_value',
        'normalize_presentation_key',
        'fallback_key_label',
    ];

    foreach ($requiredmethods as $method) {
        if (
            !preg_match(
                '/function\s+' .
                preg_quote($method, '/') .
                '\s*\(/',
                $assistantrenderer
            )
        ) {
            presentation_validation_error(
                'CrmAssistantRenderer is missing method: ' .
                $method .
                '().'
            );
        }
    }

    if (
        preg_match(
            '/evidence_value\s*\(\s*\$value\s*\)/',
            $assistantrenderer
        )
    ) {
        presentation_validation_error(
            'CrmAssistantRenderer still calls evidence_value() without the evidence key.'
        );
    }

    if (
        preg_match(
            '/return\s+\$key\s*;/',
            $assistantrenderer
        )
    ) {
        presentation_validation_warning(
            'CrmAssistantRenderer contains a direct "return $key;" fallback.'
        );
    }
}

/*
 * Check Work Item presentation methods.
 */

$workpresentation = presentation_read_file(
    $pluginroot,
    'classes/crm/work/rendering/WorkItemPresentation.php'
);

if ($workpresentation !== null) {
    $requiredmethods = [
        'status_label',
        'priority_label',
        'type_label',
        'source_label',
        'domain_label',
        'fallback_label',
    ];

    foreach ($requiredmethods as $method) {
        if (
            !preg_match(
                '/function\s+' .
                preg_quote($method, '/') .
                '\s*\(/',
                $workpresentation
            )
        ) {
            presentation_validation_error(
                'WorkItemPresentation is missing method: ' .
                $method .
                '().'
            );
        }
    }
}

/*
 * Check that suggestion rendering does not expose raw DTO values.
 */

$suggestionrenderer = presentation_read_file(
    $pluginroot,
    'classes/crm/work/intelligence/rendering/WorkItemSuggestionRenderer.php'
);

if ($suggestionrenderer !== null) {
    $rawpatterns = [
        '/suggested_type[^\n]*\$suggestion->type\b/',
        '/suggested_priority[^\n]*\$suggestion->priority\b/',
    ];

    foreach ($rawpatterns as $pattern) {
        if (
            preg_match(
                $pattern,
                $suggestionrenderer,
                $matches,
                PREG_OFFSET_CAPTURE
            )
        ) {
            $offset =
                $matches[0][1] ?? 0;

            presentation_validation_error(
                'Raw Work Item suggestion value detected in ' .
                'WorkItemSuggestionRenderer.php on line ' .
                presentation_line_number(
                    $suggestionrenderer,
                    $offset
                ) .
                '.'
            );
        }
    }

    if (
        !str_contains(
            $suggestionrenderer,
            'WorkItemPresentation::type_label'
        )
    ) {
        presentation_validation_error(
            'WorkItemSuggestionRenderer does not use WorkItemPresentation::type_label().'
        );
    }

    if (
        !str_contains(
            $suggestionrenderer,
            'WorkItemPresentation::priority_label'
        )
    ) {
        presentation_validation_error(
            'WorkItemSuggestionRenderer does not use WorkItemPresentation::priority_label().'
        );
    }
}

/*
 * Check that generated Work Item content does not expose keys directly.
 */

$suggestionservice = presentation_read_file(
    $pluginroot,
    'classes/crm/work/intelligence/services/WorkItemSuggestionService.php'
);

if ($suggestionservice !== null) {
    $forbiddenpatterns = [
        [
            'pattern' =>
                '/return\s+\$recommendation->key\s*;/',
            'message' =>
                'Raw recommendation key returned as a Work Item title.',
        ],
        [
            'pattern' =>
                '/get_string\s*\(\s*
                    [\'"]crm_work_suggestion_source_recommendation[\'"]\s*,\s*
                    [\'"]local_subscriptions[\'"]\s*,\s*
                    \$recommendation->key\s*
                \)/x',
            'message' =>
                'Raw recommendation key passed directly to the source recommendation string.',
        ],
        [
            'pattern' =>
                '/\$line\s*=\s*[\'"]-\s*[\'"]\s*\.\s*\$key\s*;/',
            'message' =>
                'Raw evidence key inserted into a Work Item description.',
        ],
    ];

    foreach ($forbiddenpatterns as $check) {
        if (
            preg_match(
                $check['pattern'],
                $suggestionservice,
                $matches,
                PREG_OFFSET_CAPTURE
            )
        ) {
            $offset =
                $matches[0][1] ?? 0;

            presentation_validation_error(
                $check['message'] .
                ' Line ' .
                presentation_line_number(
                    $suggestionservice,
                    $offset
                ) .
                '.'
            );
        }
    }

    $requiredhelpers = [
        'recommendation_label',
        'evidence_label',
        'evidence_value',
        'normalize_presentation_key',
        'fallback_label',
    ];

    foreach ($requiredhelpers as $helper) {
        if (
            !preg_match(
                '/function\s+' .
                preg_quote($helper, '/') .
                '\s*\(/',
                $suggestionservice
            )
        ) {
            presentation_validation_error(
                'WorkItemSuggestionService is missing helper: ' .
                $helper .
                '().'
            );
        }
    }
}

/*
 * Detect known hardcoded English labels that previously leaked into UI.
 */

$scanpaths = [
    'classes/crm/assistant',
    'classes/crm/work',
    'admin/assistant',
];

$forbiddenliterals = [
    'Customer without notes',
    'Cross sell digital product',
    'Create first CRM note',
    'Suggest digital product',
    'No current access',
    'Inactive customer',
    'Recent payment failure',
    'Trial to purchase',
    'Upgrade subscription',
    'Winback expired customer',
];

foreach ($scanpaths as $relativefolder) {
    $folder =
        $pluginroot .
        DIRECTORY_SEPARATOR .
        $relativefolder;

    if (!is_dir($folder)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(
            $folder,
            FilesystemIterator::SKIP_DOTS
        )
    );

    foreach ($iterator as $fileinfo) {
        if (
            !$fileinfo->isFile() ||
            $fileinfo->getExtension() !== 'php'
        ) {
            continue;
        }

        $content = file_get_contents(
            $fileinfo->getPathname()
        );

        if ($content === false) {
            continue;
        }

        $relativepath = str_replace(
            $pluginroot . DIRECTORY_SEPARATOR,
            '',
            $fileinfo->getPathname()
        );

        foreach ($forbiddenliterals as $literal) {
            $offset = strpos(
                $content,
                $literal
            );

            if ($offset === false) {
                continue;
            }

            presentation_validation_error(
                sprintf(
                    'Hardcoded English CRM label "%s" found in %s on line %d.',
                    $literal,
                    $relativepath,
                    presentation_line_number(
                        $content,
                        $offset
                    )
                )
            );
        }
    }
}

/*
 * Runtime presentation smoke tests.
 */

try {
    $typelabel =
        WorkItemPresentation::type_label(
            'follow_up'
        );

    if (
        $typelabel === '' ||
        $typelabel === 'follow_up'
    ) {
        presentation_validation_error(
            'WorkItemPresentation::type_label() returned a raw or empty value.'
        );
    } else {
        presentation_validation_ok(
            'Work Item type presentation is operational.'
        );
    }

    $prioritylabel =
        WorkItemPresentation::priority_label(
            'urgent'
        );

    if (
        $prioritylabel === '' ||
        $prioritylabel === 'urgent'
    ) {
        presentation_validation_error(
            'WorkItemPresentation::priority_label() returned a raw or empty value.'
        );
    } else {
        presentation_validation_ok(
            'Work Item priority presentation is operational.'
        );
    }

    $fallbacklabel =
        WorkItemPresentation::source_label(
            'future_unknown_source'
        );

    if (
        $fallbacklabel === '' ||
        $fallbacklabel ===
            'future_unknown_source'
    ) {
        presentation_validation_error(
            'Work Item source fallback is not readable.'
        );
    } else {
        presentation_validation_ok(
            'Unknown Work Item values receive a readable fallback.'
        );
    }
} catch (Throwable $exception) {
    presentation_validation_error(
        'Runtime presentation test failed: ' .
        $exception->getMessage()
    );
}

/*
 * Print result.
 */

foreach ($successes as $message) {
    cli_writeln(
        '[OK] ' .
        $message
    );
}

foreach ($warnings as $message) {
    cli_writeln(
        '[WARNING] ' .
        $message
    );
}

foreach ($errors as $message) {
    cli_writeln(
        '[ERROR] ' .
        $message
    );
}

cli_writeln('');

if ($errors !== []) {
    cli_error(
        sprintf(
            'CRM presentation validation failed with %d error(s).',
            count($errors)
        )
    );
}

if (
    !empty($options['strict']) &&
    $warnings !== []
) {
    cli_error(
        sprintf(
            'CRM presentation validation produced %d warning(s) in strict mode.',
            count($warnings)
        )
    );
}

cli_writeln(
    sprintf(
        'CRM presentation validation completed successfully: %d check(s), %d warning(s).',
        count($successes),
        count($warnings)
    )
);

exit(0);