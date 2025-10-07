<?php
/**
 * Compare Moodle lang files and emit a "missing/untranslated" report
 * with ready-to-translate $string[...] = '...' lines copied from EN.
 *
 * Usage:
 *   php lang_diff.php --ref=/path/en/file.php --target=/path/ru/file.php --out=/path/report.php [--strict] [--show-extra]
 */

ini_set('display_errors', '1');
error_reporting(E_ALL);

$opts = getopt('', ['ref:', 'target:', 'out:', 'strict', 'show-extra']);
if (!isset($opts['ref'], $opts['target'], $opts['out'])) {
    fwrite(STDERR, "Usage: php lang_diff.php --ref=/path/en.php --target=/path/ru.php --out=/path/report.php [--strict] [--show-extra]\n");
    exit(1);
}

$refPath    = $opts['ref'];
$targetPath = $opts['target'];
$outPath    = $opts['out'];
$strict     = array_key_exists('strict', $opts);
$showExtra  = array_key_exists('show-extra', $opts);

// ---- Helpers ----

/**
 * Safely load a Moodle lang file and return its $string array.
 * Handles MOODLE_INTERNAL die() guards.
 */
function load_lang_strings(string $path): array {
    if (!file_exists($path)) {
        throw new RuntimeException("File not found: $path");
    }
    // isolate scope
    $loader = static function (string $filepath) {
        $string = [];
        if (!defined('MOODLE_INTERNAL')) {
            define('MOODLE_INTERNAL', true);
        }
        // Some files might rely on current dir; ensure consistent cwd
        $oldCwd = getcwd();
        chdir(dirname($filepath));
        /** @noinspection PhpIncludeInspection */
        include $filepath;
        chdir($oldCwd);
        return $string ?? [];
    };
    return $loader($path);
}

/**
 * Normalize for “is untranslated?” comparisons.
 */
function norm(?string $s): string {
    // collapse whitespace differences; keep case-sensitive by default
    $s = (string)$s;
    // normalize line endings and trim
    $s = str_replace(["\r\n", "\r"], "\n", $s);
    return trim($s);
}

/**
 * Escape a value for single-quoted PHP string literal.
 * Keeps newlines as-is to match Moodle lang style.
 */
function php_single_quote(string $s): string {
    // Escape backslashes and single quotes
    $s = str_replace(['\\', '\''], ['\\\\', '\\\''], $s);
    return $s;
}

/**
 * Render $string lines for a set of keys using EN values.
 */
function render_report(array $keys, array $enMap): string {
    $lines = [];
    foreach ($keys as $k) {
        $val = $enMap[$k] ?? '';
        $valEsc = php_single_quote($val);
        $lines[] = "\$string['{$k}'] = '{$valEsc}'; // TODO: translate";
    }
    return implode("\n", $lines) . "\n";
}

// ---- Load files ----
try {
    $en = load_lang_strings($refPath);
    $tg = load_lang_strings($targetPath);
} catch (Throwable $e) {
    fwrite(STDERR, "Error: {$e->getMessage()}\n");
    exit(1);
}

ksort($en);
ksort($tg);

// ---- Compute sets ----
$enKeys = array_keys($en);
$tgKeys = array_keys($tg);

$missing = array_values(array_diff($enKeys, $tgKeys));

// Untranslated (present but equal to EN) — only if --strict
$untranslated = [];
if ($strict) {
    foreach ($en as $k => $v) {
        if (array_key_exists($k, $tg)) {
            $vEn = norm($v);
            $vTg = norm((string)$tg[$k]);
            if ($vTg === '' || $vTg === $vEn) {
                $untranslated[] = $k;
            }
        }
    }
    sort($untranslated);
}

// Extra keys (present in target, not in EN) — optional info
$extra = array_values(array_diff($tgKeys, $enKeys));
if ($showExtra && $extra) {
    fwrite(STDOUT, "Extra keys in target (not in reference): " . count($extra) . "\n");
    foreach ($extra as $k) {
        fwrite(STDOUT, "  + {$k}\n");
    }
}

// ---- Build report file ----
$header = <<<PHP
<?php
/**
 * Auto-generated report of missing/non-translated strings.
 * Reference : {$refPath}
 * Target    : {$targetPath}
 * Generated : {date('c')}
 *
 * Contents:
 *  - MISSING: keys present in EN but absent in target
 *  - UNTRANSLATED (--strict): keys present in target but identical to EN (or empty)
 *
 * Values are copied from EN and marked with " // TODO: translate".
 */

PHP;

$report = $header;

// Missing section
$report .= "// === MISSING strings (" . count($missing) . ") ===\n";
if ($missing) {
    $report .= render_report($missing, $en);
} else {
    $report .= "// (none)\n\n";
}

// Untranslated section (strict mode)
$report .= "\n// === UNTRANSLATED strings (strict mode) (" . count($untranslated) . ") ===\n";
if ($strict && $untranslated) {
    $report .= render_report($untranslated, $en);
} else {
    $report .= "// (none or --strict not used)\n";
}

// ---- Write file ----
if (false === file_put_contents($outPath, $report)) {
    fwrite(STDERR, "Failed to write report to: {$outPath}\n");
    exit(1);
}

fwrite(STDOUT, "Report written: {$outPath}\n");
fwrite(STDOUT, "Missing: " . count($missing) . "; Untranslated: " . count($untranslated) . "; Extra: " . count($extra) . "\n");
