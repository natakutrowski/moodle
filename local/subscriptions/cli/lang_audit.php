<?php
// local/subscriptions/cli/check_lang_strings.php
define('CLI_SCRIPT', true);
require(__DIR__.'/../../../config.php');
require_once($CFG->libdir.'/clilib.php');

/*
 * Usage:
 *   php check_lang_strings.php --lang=fr --comp=local_subscriptions --dir=/path/to/plugin --out=/path/to/report.txt --limit=12
 *
 * Fait :
 * - UNUSED / MISSING (avec fichiers:lignes pour MISSING)
 * - Doublons de définitions (avec lignes du langfile)
 * - Doublons de valeurs (mêmes traductions pour clés différentes)
 * - Vérif apostrophe ASCII + placeholders {a}/{$a}/{$a->...}
 * - Sortie fichier: --out=/chemin/rapport.txt (ou --out=- pour stdout)
 * - Whitelist des clés marquées "// do not delete" sur la ligne du langfile
 * - Scan .php, .mustache, et .js (Str.get_string / Str.get_strings)
 */

list($options) = cli_get_params(
    [
        'help'  => false,
        'lang'  => 'en',
        'comp'  => 'local_subscriptions',
        'dir'   => null,
        'out'   => null,
        'limit' => 12,
    ],
    [
        'h' => 'help',
    ]
);

if (!empty($options['help'])) {
    $help = <<<EOF
Checks Moodle lang strings for a component and writes a TXT report:
- UNUSED and MISSING keys (MISSING shows file:line usage locations)
- DUPLICATE key definitions (with lang file line numbers)
- DUPLICATE values (same translation text across different keys)
- Apostrophe + {a}/{$a} checks
- Scans .php, .mustache, and .js (Str.get_string / Str.get_strings)
- Whitelists keys marked with "// do not delete" on the same line in lang file

Options:
  --lang=<code>     Language to check (default: en)
  --comp=<name>     Component (default: local_subscriptions)
  --dir=<path>      Plugin root (default: \$CFG->dirroot/local/subscriptions)
  --out=<file|->    Output report path; use "-" for stdout (default: auto filename)
  --limit=<int>     Max usages listed per key (default: 12)
  -h, --help        Show this help
EOF;
    echo $help;
    exit(0);
}

$comp      = (string)$options['comp'];
$plugindir = $options['dir'] ?: $CFG->dirroot.'/local/subscriptions';
$lang      = preg_replace('~[^a-zA-Z_+-]~', '', (string)$options['lang']) ?: 'en';
$USAGE_LIMIT = max(1, (int)$options['limit']);

$langfile = $plugindir."/lang/{$lang}/{$comp}.php";
if (!file_exists($langfile)) {
    cli_error("Lang file not found: $langfile");
}
$enref = $plugindir."/lang/en/{$comp}.php";
$hasen = file_exists($enref);

/* ---------- sortie ---------- */
$outopt = $options['out'];
if ($outopt === '-' || $outopt === 'stdout') {
    $outfile = '-';
} elseif ($outopt) {
    $outfile = $outopt;
} else {
    $baseDir = is_writable($plugindir) ? $plugindir : sys_get_temp_dir();
    $ts = date('Ymd_His');
    $outfile = rtrim($baseDir, '/')."/lang_report_{$comp}_{$lang}_{$ts}.txt";
}

/* ---------- helpers ---------- */
$report = '';
$add = function(string $line = '') use (&$report) { $report .= $line . PHP_EOL; };

/* ------------------------------------------------------------------
 * 1) Parse lang file: definitions + duplicates (with line numbers)
 * ------------------------------------------------------------------ */
$langSource = file_get_contents($langfile);
$langLines  = file($langfile, FILE_IGNORE_NEW_LINES);

preg_match_all(
    '/\$string\[\s*[\'"]([^\'"]+)[\'"]\s*\]\s*=\s*(["\'])(.*?)\2\s*;/s',
    $langSource,
    $defMatches,
    PREG_OFFSET_CAPTURE
);

$definedAllOccurrences = [];   // key => [ [line, value], ... ]
$definedLatest = [];           // key => last value
$definedLastLine = [];         // key => last line number
$protectedKeys = [];           // keys marked with "do not delete"

foreach ($defMatches[1] as $i => $mkey) {
    $key   = $mkey[0];
    $value = $defMatches[3][$i][0];

    $offset = $mkey[1];
    $line = substr_count(substr($langSource, 0, $offset), "\n") + 1;

    $definedAllOccurrences[$key][] = ['line' => $line, 'value' => $value];
    $definedLatest[$key] = $value;
    $definedLastLine[$key] = $line;

    if (!empty($langLines[$line-1]) && stripos($langLines[$line-1], 'do not delete') !== false) {
        $protectedKeys[$key] = true;
    }
}
$definedKeys = array_keys($definedLatest);

/* EN reference keys (optional) */
$definedEnKeys = [];
if ($hasen) {
    $enSource = file_get_contents($enref);
    if (preg_match_all(
        '/\$string\[\s*[\'"]([^\'"]+)[\'"]\s*\]\s*=\s*(["\'])(.*?)\2\s*;/s',
        $enSource, $mEN, PREG_OFFSET_CAPTURE)) {
        foreach ($mEN[1] as $i => $mkey) {
            $definedEnKeys[$mkey[0]] = true;
        }
    }
}

/* ------------------------------------------------------------------
 * 2) Scan plugin code for used keys (with file + line) — PHP, Mustache, JS
 * ------------------------------------------------------------------ */
$usageByKey = [];   // key => [ [file,line,type], ... ]
$usedAll = [];      // flat list (for counts)
$compRx = preg_quote($comp, '/');

$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($plugindir));
foreach ($rii as $fileinfo) {
    if (!$fileinfo->isFile()) continue;
    $ext = strtolower(pathinfo($fileinfo->getFilename(), PATHINFO_EXTENSION));
    if (!in_array($ext, ['php','mustache','js'])) continue;

    $path = $fileinfo->getPathname();
    // Ignore AMD build to avoid duplicates
    if (preg_match('~[/\\\\]amd[/\\\\]build[/\\\\]~', $path)) continue;

    $src = file_get_contents($path);

    // --- PHP get_string('key','comp') / double quotes
    if ($ext === 'php' || $ext === 'mustache') {
        if (preg_match_all("/get_string\(\s*'([^']+)'\s*,\s*'{$compRx}'\s*(?:,|\))/", $src, $m, PREG_OFFSET_CAPTURE)) {
            foreach ($m[1] as $mm) {
                $key = $mm[0]; $off = $mm[1];
                $line = substr_count(substr($src, 0, $off), "\n") + 1;
                $usageByKey[$key][] = ['file' => $path, 'line' => $line, 'type' => 'php'];
                $usedAll[] = $key;
            }
        }
        if (preg_match_all('/get_string\(\s*"([^"]+)"\s*,\s*"'.$compRx.'"\s*(?:,|\))/', $src, $m, PREG_OFFSET_CAPTURE)) {
            foreach ($m[1] as $mm) {
                $key = $mm[0]; $off = $mm[1];
                $line = substr_count(substr($src, 0, $off), "\n") + 1;
                $usageByKey[$key][] = ['file' => $path, 'line' => $line, 'type' => 'php'];
                $usedAll[] = $key;
            }
        }
        // Mustache: {{#str}} key, component {{/str}}
        if ($ext === 'mustache') {
            if (preg_match_all('/\{\{#str\}\}\s*([A-Za-z0-9_.:-]+)\s*,\s*'.$compRx.'\s*\{\{\/str\}\}/', $src, $m, PREG_OFFSET_CAPTURE)) {
                foreach ($m[1] as $mm) {
                    $key = $mm[0]; $off = $mm[1];
                    $line = substr_count(substr($src, 0, $off), "\n") + 1;
                    $usageByKey[$key][] = ['file' => $path, 'line' => $line, 'type' => 'mustache'];
                    $usedAll[] = $key;
                }
            }
        }
    }

    // --- JS: Str.get_string(...) and Str.get_strings([...]) ---
    if ($ext === 'js') {
        // Heuristique: seulement si le fichier mentionne core/str (AMD ou ESM)
        if (!preg_match('/core\/str/', $src)) {
            continue;
        }

        // 2.1 get_string('key','comp')
        if (preg_match_all('/\b(?:[A-Za-z_$][\w$]*\.)?get_string\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*[\'"]'.$compRx.'[\'"]\s*(?:,|\))/',
            $src, $m, PREG_OFFSET_CAPTURE)) {
            foreach ($m[1] as $mm) {
                $key = $mm[0]; $off = $mm[1];
                $line = substr_count(substr($src, 0, $off), "\n") + 1;
                $usageByKey[$key][] = ['file' => $path, 'line' => $line, 'type' => 'js:get_string'];
                $usedAll[] = $key;
            }
        }
        // 2.2 get_strings([{key:'k', component:'comp'}, ...])
        if (preg_match_all('/\bget_strings\s*\(\s*\[(.*?)\]\s*\)/s', $src, $gm, PREG_OFFSET_CAPTURE)) {
            foreach ($gm[1] as $arrMatch) {
                $arrStr = $arrMatch[0];
                $arrOff = $arrMatch[1];
                if (preg_match_all('/\{[^}]*\}/s', $arrStr, $objs, PREG_OFFSET_CAPTURE)) {
                    foreach ($objs[0] as $obj) {
                        $objStr = $obj[0];
                        $objOff = $obj[1];
                        if (!preg_match('/component\s*:\s*[\'"]'.$compRx.'[\'"]/i', $objStr)) continue;
                        if (preg_match('/key\s*:\s*[\'"]([^\'"]+)[\'"]/i', $objStr, $km)) {
                            $key = $km[1];
                            $off = $arrOff + $objOff;
                            $line = substr_count(substr($src, 0, $off), "\n") + 1;
                            $usageByKey[$key][] = ['file' => $path, 'line' => $line, 'type' => 'js:get_strings'];
                            $usedAll[] = $key;
                        }
                    }
                }
            }
        }
    }
}

$usedUnique = array_values(array_unique($usedAll));

/* ------------------------------------------------------------------
 * 3) Reports: UNUSED / MISSING / DUPLICATES
 * ------------------------------------------------------------------ */
$unusedCandidates  = array_values(array_diff($definedKeys, $usedUnique));
$unusedProtected   = [];
$unused            = [];
foreach ($unusedCandidates as $k) {
    if (!empty($protectedKeys[$k])) $unusedProtected[] = $k;
    else $unused[] = $k;
}
$missing = array_values(array_diff($usedUnique, $definedKeys));

/* Duplicate definitions */
$duplicateDefs = [];
foreach ($definedAllOccurrences as $k => $occ) {
    if (count($occ) > 1) {
        $duplicateDefs[$k] = array_map(fn($o) => $o['line'], $occ);
    }
}

/* Duplicate values */
$valueMap = [];
foreach ($definedLatest as $k => $v) {
    $norm = trim(preg_replace("/\s+/", " ", $v));
    $valueMap[$norm][] = $k;
}
$duplicateValues = array_filter($valueMap, fn($keys) => count($keys) > 1);

/* Most used */
$useCounts = array_count_values($usedAll);
arsort($useCounts);
$mostUsed = array_slice($useCounts, 0, 10, true);

/* ------------------------------------------------------------------
 * 4) Apostrophe + {a} checks
 * ------------------------------------------------------------------ */
$apostropheIssues = [];
$placeholderRx = '/\{(?:\$?a)(?:->[A-Za-z0-9_]+)?\}/u';
foreach ($definedLatest as $k => $v) {
    if (!preg_match($placeholderRx, $v)) continue;
    if (strpos($v, "'") === false) continue;

    $line = $definedLastLine[$k] ?? 0;
    $nearRx = "/(?:'\\s*\\{(?:\\$?a)(?:->[A-Za-z0-9_]+)?\\})|(?:\\{(?:\\$?a)(?:->[A-Za-z0-9_]+)?\\}\\s*')/u";
    $spacingRx = "/'\\s+\\{|\\}\\s+'/u";

    $type = 'apostrophe_with_placeholder';
    if (preg_match($nearRx, $v)) $type = 'apostrophe_near_placeholder';
    elseif (preg_match($spacingRx, $v)) $type = 'apostrophe_spacing_with_placeholder';

    $pv = preg_replace('/\s+/', ' ', $v);
    if (mb_strlen($pv) > 100) $pv = mb_substr($pv, 0, 97).'...';

    $apostropheIssues[] = ['key'=>$k,'line'=>$line,'preview'=>$pv,'type'=>$type];
}

/* Cross-lang compare with EN (optional) */
$missingComparedToEn = [];
$extraComparedToEn   = [];
if ($hasen) {
    $enkeys = array_keys($definedEnKeys);
    $missingComparedToEn = array_values(array_diff($enkeys, $definedKeys));
    $extraComparedToEn   = array_values(array_diff($definedKeys, $enkeys));
}

/* ------------------------------------------------------------------
 * 5) Build report
 * ------------------------------------------------------------------ */
$add("== Component: {$comp} | Lang: {$lang}");
$add("Lang file: {$langfile}");
$add("Plugin dir: {$plugindir}");
$add('');

$add("== UNUSED strings (defined but not used) ==");
if (empty($unused)) $add("  (none)");
else foreach ($unused as $k) $add("  - $k");

$add("");
$add("== IGNORED (marked \"do not delete\" in lang file) ==");
if (empty($unusedProtected)) $add("  (none)");
else {
    sort($unusedProtected, SORT_NATURAL | SORT_FLAG_CASE);
    foreach ($unusedProtected as $k) $add("  - $k");
}

$add("");
$add("== MISSING strings (used but not defined) ==");
if (empty($missing)) {
    $add("  (none)");
} else {
    foreach ($missing as $k) {
        $add("  - $k");
        $usages = $usageByKey[$k] ?? [];
        if (empty($usages)) {
            $add("      • (aucun emplacement détecté — clé peut-être construite dynamiquement)");
            continue;
        }
        usort($usages, function($a, $b) {
            return [$a['file'], $a['line']] <=> [$b['file'], $b['line']];
        });
        $shown = 0;
        foreach ($usages as $u) {
            $add(sprintf("      • %s:%d (%s)", $u['file'], $u['line'], $u['type']));
            if (++$shown >= $USAGE_LIMIT) {
                $rest = count($usages) - $shown;
                if ($rest > 0) $add("      … (+{$rest} autres)");
                break;
            }
        }
    }
}

$add("");
$add("== DUPLICATE DEFINITIONS in lang file (same key defined multiple times) ==");
if (empty($duplicateDefs)) {
    $add("  (none)");
} else {
    foreach ($duplicateDefs as $k => $linesList) {
        $linesStr = implode(', ', $linesList);
        $add("  - $k  (lang {$lang}: lines $linesStr)");
        $usages = $usageByKey[$k] ?? [];
        if (empty($usages)) {
            $add("      • (no usage found)");
        } else {
            $shown = 0;
            foreach ($usages as $u) {
                $add("      • {$u['file']}:{$u['line']}");
                if (++$shown >= $USAGE_LIMIT) {
                    $rest = count($usages) - $shown;
                    if ($rest > 0) $add("      … (+{$rest} more)");
                    break;
                }
            }
        }
    }
}

$add("");
$add("== DUPLICATE VALUES in lang file (different keys share identical value) ==");
if (empty($duplicateValues)) {
    $add("  (none)");
} else {
    foreach ($duplicateValues as $val => $keys) {
        $preview = preg_replace('/\s+/', ' ', $val);
        if (mb_strlen($preview) > 80) $preview = mb_substr($preview, 0, 77) . '...';
        $add('  • "'.$preview.'"');
        foreach ($keys as $k) {
            $add("     - {$k}");
            $usages = $usageByKey[$k] ?? [];
            if (empty($usages)) {
                $add("         • (no usage found)");
            } else {
                $shown = 0;
                foreach ($usages as $u) {
                    $add("         • {$u['file']}:{$u['line']}");
                    if (++$shown >= $USAGE_LIMIT) {
                        $rest = count($usages) - $shown;
                        if ($rest > 0) $add("         … (+{$rest} more)");
                        break;
                    }
                }
            }
        }
    }
}

$add("");
$add("== APOSTROPHE + PLACEHOLDER checks ==");
if (empty($apostropheIssues)) $add("  (none)");
else foreach ($apostropheIssues as $i) {
    $add("  - {$i['key']}  (line {$i['line']})  [{$i['type']}]  -> {$i['preview']}");
}

if ($hasen) {
    $add("");
    $add("== COMPARE TO EN reference ==");
    $add("Missing in {$lang} (present in en):");
    if (empty($missingComparedToEn)) $add("  (none)");
    else foreach ($missingComparedToEn as $k) $add("  - $k");
    $add("Extra in {$lang} (not in en):");
    if (empty($extraComparedToEn)) $add("  (none)");
    else foreach ($extraComparedToEn as $k) $add("  - $k");
}

$add("");
$add("== MOST USED keys in code (top 10) ==");
foreach ($mostUsed as $k => $cnt) $add("  - $k  (x{$cnt})");

$add("");
$add("Done.");

/* ------------------------------------------------------------------
 * 6) Write report
 * ------------------------------------------------------------------ */
if ($outfile === '-') {
    echo $report;
    exit(0);
}
$dir = dirname($outfile);
if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
if (false === @file_put_contents($outfile, $report)) {
    cli_error("Failed to write report to: $outfile");
}
cli_writeln("Report written to: $outfile");
exit(0);
