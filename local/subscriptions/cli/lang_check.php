<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

/**
 * Usage:
 *   php lang_check.php --components=local_subscriptions,local_campus --langs=en,fr,ru
 * Defaults:
 *   components: local_subscriptions,local_campus
 *   langs: en,fr,ru
 */

list($opts, $unrec) = cli_get_params([
    'components' => 'local_subscriptions,local_campus',
    'langs'      => 'en,fr,ru',
    'help'       => false,
], ['h'=>'help']);

if (!empty($opts['help'])) {
    echo "Check language file consistency across languages.\n";
    echo "Options: --components=local_subscriptions,local_campus --langs=en,fr,ru\n";
    exit(0);
}

$components = array_filter(array_map('trim', explode(',', $opts['components'])));
$langs      = array_filter(array_map('trim', explode(',', $opts['langs'])));

// Map composant → chemin base
$basepaths = [];
foreach ($components as $comp) {
    if (strpos($comp, 'local_') === 0) {
        // ex: local_subscriptions -> /local/subscriptions
        $dir = $CFG->dirroot . '/local/' . substr($comp, strlen('local_'));
    } else if (strpos($comp, 'block_') === 0) {
        $dir = $CFG->dirroot . '/blocks/' . substr($comp, strlen('block_'));
    } else if (strpos($comp, 'mod_') === 0) {
        $dir = $CFG->dirroot . '/mod/' . substr($comp, strlen('mod_'));
    } else {
        $dir = $CFG->dirroot . '/' . str_replace('_','/',$comp);
    }
    $basepaths[$comp] = $dir;
}

function parse_lang_file($file) {
    if (!is_file($file)) return [];
    $txt = file_get_contents($file);
    // Simple parseur: $string['key'] = 'value';  (gère quotes simples/doubles)
    $re1 = '/\$string\[[\'"]([^\'"]+)[\'"]\]\s*=\s*(?:\'(?:\\\\\'|[^\'])*\'|"(?:\\\\"|[^"])*")\s*;/u';
    preg_match_all($re1, $txt, $m);
    // $m[1] = keys
    $keys = [];
    foreach ($m[1] as $k) {
        $keys[$k] = true;
    }
    return array_keys($keys);
}

$totalIssues = 0;

foreach ($components as $comp) {
    $dir = $basepaths[$comp] ?? null;
    if (!$dir) { mtrace("Component $comp: base path not resolved"); continue; }

    mtrace("== $comp ==");
    // construire carte lang -> keys
    $keysByLang = [];
    foreach ($langs as $lg) {
        $file = $dir . "/lang/$lg/{$comp}.php";
        $keysByLang[$lg] = parse_lang_file($file);
        mtrace("  [$lg] ".basename($file)." : ".count($keysByLang[$lg])." clés");
    }

    // Canon: premier de la liste (souvent en)
    $canon = $langs[0];
    $canonKeys = array_flip($keysByLang[$canon] ?? []);
    foreach ($langs as $lg) {
        if ($lg === $canon) continue;
        $set = array_flip($keysByLang[$lg] ?? []);

        // Manquantes dans $lg (présentes dans canon)
        $missing = array_diff_key($canonKeys, $set);
        // Extra dans $lg (pas dans canon)
        $extra   = array_diff_key($set, $canonKeys);

        if ($missing) {
            $totalIssues += count($missing);
            mtrace("  ! Manquantes en $lg (" . count($missing) . "): ".implode(', ', array_slice(array_keys($missing),0,20)).(count($missing)>20?' ...':''));
        }
        if ($extra) {
            mtrace("  ? Supplémentaires en $lg (" . count($extra) . "): ".implode(', ', array_slice(array_keys($extra),0,20)).(count($extra)>20?' ...':''));
        }
    }
    mtrace("");
}

if ($totalIssues === 0) {
    mtrace("Tout est cohérent ✅");
    exit(0);
} else {
    mtrace("Terminé avec $totalIssues élément(s) à corriger.");
    exit(1);
}
