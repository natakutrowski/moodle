<?php

define('CLI_SCRIPT', true);

require_once __DIR__ . '/../../../config.php';
require_once $CFG->libdir . '/clilib.php';

use local_subscriptions\admin\AdminEventPresentation;
use local_subscriptions\admin\AdminEvents;

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
    $help = <<<TXT
Validate CRM administrative event presentation.

Options:
--strict    Return a non-zero exit code on validation errors.
-h, --help  Show this help.

Example:
php local/subscriptions/cli/validate_crm_admin_events.php --strict

TXT;

    mtrace($help);
    exit(0);
}

$languages = [
    'fr',
    'en',
    'ru',
];

$allowedimportances = [
    AdminEventPresentation::IMPORTANCE_NORMAL,
    AdminEventPresentation::IMPORTANCE_MEDIUM,
    AdminEventPresentation::IMPORTANCE_HIGH,
    AdminEventPresentation::IMPORTANCE_CRITICAL,
];

$errors = [];
$warnings = [];

$events = AdminEvents::all();

/*
 * Load each plugin language file directly.
 *
 * We do not use get_string() to switch language because its fourth
 * argument is not a language selector in Moodle 4.5/5.x.
 */
$languagestrings = [];

foreach ($languages as $language) {
    $languagefile =
        $CFG->dirroot .
        '/local/subscriptions/lang/' .
        $language .
        '/local_subscriptions.php';

    if (!is_readable($languagefile)) {
        $errors[] = sprintf(
            'Language file is missing or unreadable: %s',
            $languagefile
        );

        $languagestrings[$language] = [];
        continue;
    }

    $languagestrings[$language] =
        load_language_strings(
            $languagefile
        );

    mtrace(
        sprintf(
            '[OK] %d language strings loaded for %s.',
            count($languagestrings[$language]),
            $language
        )
    );
}

if (
    count($events) !==
    count(array_unique($events))
) {
    $errors[] =
        'AdminEvents::all() contains duplicate event values.';
}

foreach ($events as $event) {
    $stringkey =
        AdminEventPresentation::string_key(
            $event
        );

    foreach ($languages as $language) {
        $strings =
            $languagestrings[$language]
            ?? [];

        if (!array_key_exists(
            $stringkey,
            $strings
        )) {
            $errors[] = sprintf(
                'Missing language string %s for event %s in language %s.',
                $stringkey,
                $event,
                $language
            );

            continue;
        }

        if (
            trim(
                (string)$strings[$stringkey]
            ) === ''
        ) {
            $errors[] = sprintf(
                'Empty language string %s in language %s.',
                $stringkey,
                $language
            );
        }
    }

    $label =
        AdminEventPresentation::label(
            $event
        );

    if (trim($label) === '') {
        $errors[] =
            'Empty runtime label for event: ' .
            $event;
    }

    $icon =
        AdminEventPresentation::icon(
            $event
        );

    if (trim($icon) === '') {
        $errors[] =
            'Missing icon for event: ' .
            $event;
    }

    $category =
        AdminEventPresentation::category(
            $event
        );

    if (
        !preg_match(
            '/^[a-z0-9-]+$/',
            $category
        )
    ) {
        $errors[] = sprintf(
            'Invalid category "%s" for event %s.',
            $category,
            $event
        );
    }

    $type =
        AdminEventPresentation::type(
            $event
        );

    if (
        !preg_match(
            '/^[a-z0-9_]+$/',
            $type
        )
    ) {
        $errors[] = sprintf(
            'Invalid Timeline type "%s" for event %s.',
            $type,
            $event
        );
    }

    $importance =
        AdminEventPresentation::importance(
            $event
        );

    if (
        !in_array(
            $importance,
            $allowedimportances,
            true
        )
    ) {
        $errors[] = sprintf(
            'Invalid importance "%s" for event %s.',
            $importance,
            $event
        );
    }
}

/*
 * Compare the complete language key sets.
 *
 * This catches accidental FR / EN / RU differences beyond the event
 * strings that are explicitly required above.
 */
$englishkeys = array_keys(
    $languagestrings['en'] ?? []
);

sort($englishkeys);

foreach ([
    'fr',
    'ru',
] as $language) {
    $languagekeys = array_keys(
        $languagestrings[$language] ?? []
    );

    sort($languagekeys);

    $missing = array_values(
        array_diff(
            $englishkeys,
            $languagekeys
        )
    );

    $extra = array_values(
        array_diff(
            $languagekeys,
            $englishkeys
        )
    );

    foreach ($missing as $key) {
        $errors[] = sprintf(
            'Language %s is missing key present in English: %s.',
            $language,
            $key
        );
    }

    foreach ($extra as $key) {
        $warnings[] = sprintf(
            'Language %s contains an extra key absent from English: %s.',
            $language,
            $key
        );
    }

    if (
        $missing === [] &&
        $extra === []
    ) {
        mtrace(
            sprintf(
                '[OK] Language %s matches the English key set.',
                $language
            )
        );
    }
}

mtrace(
    sprintf(
        '[OK] %d CRM administrative events discovered.',
        count($events)
    )
);

mtrace(
    '[OK] Languages checked: ' .
    implode(', ', $languages) .
    '.'
);

foreach ($warnings as $warning) {
    mtrace(
        '[WARNING] ' .
        $warning
    );
}

foreach ($errors as $error) {
    mtrace(
        '[ERROR] ' .
        $error
    );
}

if ($errors === []) {
    mtrace(
        '[OK] CRM administrative event presentation is valid.'
    );

    exit(0);
}

mtrace(
    sprintf(
        '[ERROR] Validation failed with %d error(s).',
        count($errors)
    )
);

exit(
    !empty($options['strict'])
        ? 1
        : 0
);

/**
 * Load the strings declared by one Moodle language file.
 *
 * The include is isolated so that its local $string variable cannot
 * overwrite variables from the validator.
 *
 * @return array<string,string>
 */
function load_language_strings(
    string $filepath
): array {
    $loader = static function (
        string $file
    ): array {
        $string = [];

        include $file;

        return is_array($string)
            ? $string
            : [];
    };

    return $loader($filepath);
}