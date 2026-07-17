<?php

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

list($options) = cli_get_params(
    [
        'help' => false,
        'apply' => false,
    ],
    [
        'h' => 'help',
    ]
);

if (!empty($options['help'])) {
    echo <<<TXT
Remove duplicate language string definitions.

By default, the command only reports duplicates.
Use --apply to modify the files.

The last definition of each duplicated key is preserved.

Options:
  --apply       Apply the cleanup and create backup files.
  -h, --help    Show this help.

Examples:
  php local/subscriptions/cli/cleanup_duplicate_lang_keys.php
  php local/subscriptions/cli/cleanup_duplicate_lang_keys.php --apply

TXT;

    exit(0);
}

$component = 'local_subscriptions';
$languages = ['fr', 'en', 'ru'];
$apply = !empty($options['apply']);

$errors = 0;
$totalduplicates = 0;

/**
 * Decode a PHP string literal used as a language key.
 */
function local_subscriptions_decode_lang_key(
    string $literal
): string {
    $quote = $literal[0] ?? '';

    if (
        $quote === '' ||
        substr($literal, -1) !== $quote
    ) {
        return '';
    }

    $value = substr(
        $literal,
        1,
        -1
    );

    if ($quote === "'") {
        return str_replace(
            [
                "\\\\",
                "\\'",
            ],
            [
                "\\",
                "'",
            ],
            $value
        );
    }

    return stripcslashes($value);
}

/**
 * Locate every $string['key'] = ...; assignment.
 *
 * @return array<int,array{
 *     key:string,
 *     start:int,
 *     end:int,
 *     line:int
 * }>
 */
function local_subscriptions_find_lang_assignments(
    string $source
): array {
    $tokens = token_get_all($source);

    $items = [];
    $offset = 0;
    $count = count($tokens);

    for ($index = 0; $index < $count; $index++) {
        $token = $tokens[$index];

        $text = is_array($token)
            ? $token[1]
            : $token;

        $tokenoffset = $offset;
        $offset += strlen($text);

        if (
            !is_array($token) ||
            $token[0] !== T_VARIABLE ||
            $token[1] !== '$string'
        ) {
            continue;
        }

        $start = $tokenoffset;
        $line = (int)$token[2];
        $cursor = $index + 1;

        while (
            $cursor < $count &&
            is_array($tokens[$cursor]) &&
            $tokens[$cursor][0] === T_WHITESPACE
        ) {
            $cursor++;
        }

        if (
            $cursor >= $count ||
            $tokens[$cursor] !== '['
        ) {
            continue;
        }

        $cursor++;

        while (
            $cursor < $count &&
            is_array($tokens[$cursor]) &&
            $tokens[$cursor][0] === T_WHITESPACE
        ) {
            $cursor++;
        }

        if (
            $cursor >= $count ||
            !is_array($tokens[$cursor]) ||
            $tokens[$cursor][0] !==
                T_CONSTANT_ENCAPSED_STRING
        ) {
            continue;
        }

        $key =
            local_subscriptions_decode_lang_key(
                $tokens[$cursor][1]
            );

        $cursor++;

        while (
            $cursor < $count &&
            is_array($tokens[$cursor]) &&
            $tokens[$cursor][0] === T_WHITESPACE
        ) {
            $cursor++;
        }

        if (
            $cursor >= $count ||
            $tokens[$cursor] !== ']'
        ) {
            continue;
        }

        $cursor++;

        while (
            $cursor < $count &&
            is_array($tokens[$cursor]) &&
            $tokens[$cursor][0] === T_WHITESPACE
        ) {
            $cursor++;
        }

        if (
            $cursor >= $count ||
            $tokens[$cursor] !== '='
        ) {
            continue;
        }

        $assignmentoffset = 0;

        for ($scan = 0; $scan < $cursor; $scan++) {
            $assignmentoffset += strlen(
                is_array($tokens[$scan])
                    ? $tokens[$scan][1]
                    : $tokens[$scan]
            );
        }

        for (
            ;
            $cursor < $count;
            $cursor++
        ) {
            $current = $tokens[$cursor];
            $currenttext = is_array($current)
                ? $current[1]
                : $current;

            $assignmentoffset += strlen(
                $currenttext
            );

            if ($current === ';') {
                $items[] = [
                    'key' => $key,
                    'start' => $start,
                    'end' => $assignmentoffset,
                    'line' => $line,
                ];

                $index = $cursor;
                $offset = $assignmentoffset;
                break;
            }
        }
    }

    return $items;
}

foreach ($languages as $language) {
    $path =
        $CFG->dirroot .
        '/local/subscriptions/lang/' .
        $language .
        '/' .
        $component .
        '.php';

    if (!is_file($path)) {
        cli_writeln(
            '[ERROR] Missing language file: ' .
            $path
        );

        $errors++;
        continue;
    }

    $source = file_get_contents($path);

    if ($source === false) {
        cli_writeln(
            '[ERROR] Unable to read: ' .
            $path
        );

        $errors++;
        continue;
    }

    $assignments =
        local_subscriptions_find_lang_assignments(
            $source
        );

    $bykey = [];

    foreach ($assignments as $assignment) {
        $bykey[$assignment['key']][] =
            $assignment;
    }

    $removals = [];

    foreach ($bykey as $key => $occurrences) {
        if (count($occurrences) <= 1) {
            continue;
        }

        $totalduplicates++;

        $lines = array_map(
            static fn(array $occurrence): int =>
                $occurrence['line'],
            $occurrences
        );

        cli_writeln(
            sprintf(
                '[DUPLICATE][%s] %s — lines %s',
                $language,
                $key,
                implode(', ', $lines)
            )
        );

        array_pop($occurrences);

        foreach ($occurrences as $occurrence) {
            $removals[] = $occurrence;
        }
    }

    if (!$apply || $removals === []) {
        continue;
    }

    usort(
        $removals,
        static fn(array $left, array $right): int =>
            $right['start'] <=> $left['start']
    );

    $cleaned = $source;

    foreach ($removals as $removal) {
        $length =
            $removal['end'] -
            $removal['start'];

        $cleaned =
            substr_replace(
                $cleaned,
                '',
                $removal['start'],
                $length
            );
    }

    $cleaned = preg_replace(
        "/\n{4,}/",
        "\n\n\n",
        $cleaned
    ) ?? $cleaned;

    $backup =
        $path .
        '.before-7z1-' .
        date('Ymd-His') .
        '.bak';

    if (!copy($path, $backup)) {
        cli_writeln(
            '[ERROR] Unable to create backup: ' .
            $backup
        );

        $errors++;
        continue;
    }

    if (
        file_put_contents(
            $path,
            $cleaned
        ) === false
    ) {
        cli_writeln(
            '[ERROR] Unable to update: ' .
            $path
        );

        $errors++;
        continue;
    }

    cli_writeln(
        '[OK] Cleaned ' .
        $language .
        ' — backup: ' .
        basename($backup)
    );
}

if ($totalduplicates === 0) {
    cli_writeln(
        '[OK] No duplicate language keys found.'
    );
} elseif (!$apply) {
    cli_writeln('');
    cli_writeln(
        '[INFO] Dry run only. Run again with --apply.'
    );
}

if ($errors > 0) {
    cli_error(
        sprintf(
            'Language cleanup completed with %d error(s).',
            $errors
        )
    );
}

exit(0);