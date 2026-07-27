<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

$pluginroot = dirname(__DIR__);

$languages = [
    'en',
    'fr',
    'ru',
];

$languagekeys = [];
$haserrors = false;

/**
 * Reads all language keys from one Moodle language file.
 *
 * @param string $filepath
 * @return array
 */
function local_subscriptions_read_language_keys(
    string $filepath
): array {
    $content = file_get_contents($filepath);

    if ($content === false) {
        throw new RuntimeException(
            'Unable to read language file: ' .
            $filepath
        );
    }

    preg_match_all(
        '/\\$string\\[[\'"]([^\'"]+)[\'"]\\]\\s*=/',
        $content,
        $matches
    );

    return $matches[1] ?? [];
}

/**
 * Finds literal local_subscriptions get_string keys.
 *
 * @param string $pluginroot
 * @return array
 */
function local_subscriptions_find_used_language_keys(
    string $pluginroot
): array {
    $keys = [];

    $iterator =
        new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $pluginroot,
                FilesystemIterator::SKIP_DOTS
            )
        );

    foreach ($iterator as $file) {
        if (
            !$file->isFile() ||
            strtolower($file->getExtension()) !== 'php'
        ) {
            continue;
        }

        $path = $file->getPathname();

        if (
            str_contains($path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR) ||
            str_contains($path, DIRECTORY_SEPARATOR . 'node_modules' . DIRECTORY_SEPARATOR)
        ) {
            continue;
        }

        $content = file_get_contents($path);

        if ($content === false) {
            continue;
        }

        preg_match_all(
            '/get_string\\(\\s*[\'"]([^\'"]+)[\'"]\\s*,\\s*[\'"]local_subscriptions[\'"]/',
            $content,
            $matches
        );

        foreach ($matches[1] ?? [] as $key) {
            $keys[$key] = true;
        }
    }

    $keys = array_keys($keys);

    sort($keys);

    return $keys;
}

foreach ($languages as $language) {
    $filepath =
        $pluginroot .
        '/lang/' .
        $language .
        '/local_subscriptions.php';

    if (!is_file($filepath)) {
        cli_error(
            'Missing language file: ' .
            $filepath
        );
    }

    $keys =
        local_subscriptions_read_language_keys(
            $filepath
        );

    $counts = array_count_values($keys);

    $duplicates = array_keys(
        array_filter(
            $counts,
            static fn(int $count): bool =>
                $count > 1
        )
    );

    sort($duplicates);

    if ($duplicates !== []) {
        $haserrors = true;

        cli_writeln(
            '[ERROR] Duplicate keys in ' .
            $language .
            ':'
        );

        foreach ($duplicates as $key) {
            cli_writeln(
                '  - ' .
                $key .
                ' (' .
                $counts[$key] .
                ' occurrences)'
            );
        }
    } else {
        cli_writeln(
            '[OK] No duplicate keys in ' .
            $language .
            '.'
        );
    }

    $languagekeys[$language] =
        array_fill_keys(
            array_keys($counts),
            true
        );
}

$englishkeys =
    $languagekeys['en'];

foreach (['fr', 'ru'] as $language) {
    $missing = array_diff_key(
        $englishkeys,
        $languagekeys[$language]
    );

    $extra = array_diff_key(
        $languagekeys[$language],
        $englishkeys
    );

    if ($missing !== []) {
        $haserrors = true;

        cli_writeln(
            '[ERROR] Keys missing from ' .
            $language .
            ':'
        );

        foreach (array_keys($missing) as $key) {
            cli_writeln('  - ' . $key);
        }
    } else {
        cli_writeln(
            '[OK] ' .
            strtoupper($language) .
            ' contains every English key.'
        );
    }

    if ($extra !== []) {
        $haserrors = true;

        cli_writeln(
            '[ERROR] Extra keys in ' .
            $language .
            ' that are absent from English:'
        );

        foreach (array_keys($extra) as $key) {
            cli_writeln('  - ' . $key);
        }
    } else {
        cli_writeln(
            '[OK] ' .
            strtoupper($language) .
            ' has no unmatched extra key.'
        );
    }
}

$usedkeys =
    local_subscriptions_find_used_language_keys(
        $pluginroot
    );

$undeclared = [];

foreach ($usedkeys as $key) {
    if (!isset($englishkeys[$key])) {
        $undeclared[] = $key;
    }
}

if ($undeclared !== []) {
    $haserrors = true;

    cli_writeln(
        '[ERROR] Literal get_string keys missing from English:'
    );

    foreach ($undeclared as $key) {
        cli_writeln('  - ' . $key);
    }
} else {
    cli_writeln(
        '[OK] Every literal local_subscriptions get_string key exists in English.'
    );
}

if ($haserrors) {
    cli_error(
        'CRM language integrity validation failed.'
    );
}

cli_writeln(
    '[OK] CRM language integrity validation passed.'
);