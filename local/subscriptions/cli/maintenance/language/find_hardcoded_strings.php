<?php
// local/subscriptions/cli/maintenance/language/find_hardcoded_strings.php
define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

/*
 * Usage:
 *   php find_hardcoded_strings.php --dir=/path/to/plugin --out=/tmp/hardcoded.txt --limit=200
 *
 * Ce script détecte des chaînes FR/EN "user-facing" en PHP et JS qui ne sont PAS
 * localisées via get_string (PHP) / Str.get_string(s) (JS). Les commentaires sont ignorés.
 */

list($options) = cli_get_params(
    [
        'help'  => false,
        'dir'   => null,
        'out'   => null,
        'limit' => 10000, // nb max de lignes dans le rapport
    ],
    ['h' => 'help']
);

if (!empty($options['help'])) {
    $help = <<<EOF
Scanne PHP/JS (hors commentaires) et signale les chaînes FR/EN non localisées.

Options:
  --dir=<path>    Racine à scanner (défaut: \$CFG->dirroot/local/subscriptions)
  --out=<file|->  Fichier de sortie .txt (ou "-" pour stdout). Défaut: auto dans /tmp
  --limit=<n>     Limite max de lignes du rapport (défaut: 10000)
  -h, --help      Aide

Exemples:
  php find_hardcoded_strings.php
  php find_hardcoded_strings.php --dir=/var/www/moodle/local/subscriptions --out=/tmp/hardcoded.txt
EOF;
    echo $help;
    exit(0);
}

$root = $options['dir'] ?: $CFG->dirroot . '/local/subscriptions';
if (!is_dir($root)) {
    cli_error("Directory not found: $root");
}

$outopt = $options['out'];
if ($outopt === '-' || $outopt === 'stdout') {
    $outfile = '-';
} elseif ($outopt) {
    $outfile = $outopt;
} else {
    $ts = date('Ymd_His');
    $outfile = sys_get_temp_dir() . "/hardcoded_strings_{$ts}.txt";
}
$REPORT_LIMIT = max(1000, (int)$options['limit']);

/* ---------------- Heuristiques / listes ---------------- */

$frenchSmall = ['oui','non','ok','annuler','supprimer','enregistrer','fermer','ouvrir','valider','retour','suivant','précédent','charger','chargement','erreur','succès','avertissement','attention','réessayer','continuer','confirmer','annulé','envoyer','ajouter'];
$englishSmall= ['yes','no','ok','cancel','delete','save','close','open','submit','back','next','previous','load','loading','error','success','warning','retry','continue','confirm','send','add','remove','update'];
$frenchStops = ['le','la','les','un','une','des','et','ou','pour','avec','vous','votre','vos','de','du','au','aux','ce','cette','ces','est','êtes','nous','sur','dans','plus','moins','par','ne','pas'];
$englishStops= ['the','a','an','and','or','for','with','you','your','on','in','from','to','is','are','we','please','click','failed','cannot','could','not'];

$skipDirs = [
    '/vendor/', '/node_modules/', '/amd/build/', '/.git/', '/.idea/', '/.vscode/',
    '/.cache/', '/build/', '/dist/', '/coverage/', '/pix/', '/yui/build/', '/.husky/'
];

function should_skip_path(string $path, array $skipDirs): bool {
    // Normalise les séparateurs Windows -> Unix
    $p = str_replace('\\', '/', $path);

    // Exclusions "brutes" déjà prévues (vendor, node_modules, amd/build, etc.)
    foreach ($skipDirs as $s) {
        if (strpos($p, $s) !== false) return true;
    }

    // 🚫 Ignore tout segment de chemin nommé exactement "cli" ou "lang"
    // (ne matche PAS "client" ni "language")
    if (preg_match('~/(cli|lang)/~', $p)) {
        return true;
    }

    return false;
}


function mb_strip_quotes(string $s): string {
    $len = strlen($s);
    if ($len >= 2) {
        $q1 = $s[0]; $q2 = $s[$len-1];
        if (($q1 === $q2) && ($q1 === "'" || $q1 === '"' || $q1 === '`')) {
            $s = substr($s, 1, -1);
        }
    }
    return $s;
}

function php_unescape_string(string $s): string {
    $s = mb_strip_quotes($s);
    // tente de déséchapper basique
    $s = str_replace(["\\n","\\r","\\t","\\\\"], ["\n","\r","\t","\\"], $s);
    $s = str_replace(["\\'","\\\""], ["'","\""], $s);
    return $s;
}

function looks_like_nonlocalizable_atom(string $s): bool {
    // URL, chemin, classe/id CSS, identifiant simple, format date, fichier.ext, data-attr, etc.
    if ($s === '') return true;
    if (preg_match('~^https?://~i', $s)) return true;
    if (preg_match('~^[A-Za-z0-9_\-/.]+$~', $s) && strpos($s, ' ') === false) {
        // pas d’espace et que des atomes => probablement identifiant/chemin/clé
        return true;
    }
    if (preg_match('~^[#.a-z0-9_\- ]+$~', $s) && !preg_match('~\s+~', $s) ) { // .btn-primary, id123
        return true;
    }
    if (preg_match('~\.(css|js|php|png|jpg|svg|gif|mp4|webm|ico)$~i', $s)) return true;
    if (preg_match('~^\{.*\}$~', $s)) return true; // json-like or placeholder only
    if (preg_match('~%[sd]|\{\$?[a-z][a-z0-9_]*(:?->[a-z0-9_]+)?\}~i', $s)) return false; // placeholders -> peut être UI
    if (preg_match('~^[A-Z_][A-Z0-9_]*$~', $s)) return true; // CONSTANTE
    if (preg_match('~^[0-9\-\.:/ ]+$~', $s)) return true; // chiffres/date/heure
    return false;
}

function has_french_accent(string $s): bool {
    return preg_match('/[àâäéèêëîïôöùûüçœæÀÂÄÉÈÊËÎÏÔÖÙÛÜÇŒÆ]/u', $s) === 1;
}

function guess_lang_score(string $s, array $frStops, array $enStops, array $frSmall, array $enSmall): array {
    $low = mb_strtolower($s, 'UTF-8');
    $scoreFR = has_french_accent($s) ? 2 : 0;
    foreach ($frStops as $w) { if (preg_match('/\b'.preg_quote($w,'/').'\b/u', $low)) $scoreFR++; }
    foreach ($frSmall as $w) { if (preg_match('/\b'.preg_quote($w,'/').'\b/u', $low)) $scoreFR++; }

    $scoreEN = 0;
    foreach ($enStops as $w) { if (preg_match('/\b'.preg_quote($w,'/').'\b/u', $low)) $scoreEN++; }
    foreach ($enSmall as $w) { if (preg_match('/\b'.preg_quote($w,'/').'\b/u', $low)) $scoreEN++; }

    return [$scoreFR, $scoreEN];
}

function is_probably_ui_text(string $s, array $frStops, array $enStops, array $frSmall, array $enSmall): bool {
    $t = trim($s);
    if ($t === '') return false;
    if (looks_like_nonlocalizable_atom($t)) return false;

    $words = preg_split('/\s+/u', $t, -1, PREG_SPLIT_NO_EMPTY);
    $multiword = count($words) >= 2;
    $hasPunct  = preg_match('/[.!?…:;,]/u', $t);
    $hasLetter = preg_match('/[A-Za-z'.$u='À-ÖØ-öø-ÿ'.']/u', $t);

    [$fr,$en] = guess_lang_score($t, $frStops, $enStops, $frSmall, $enSmall);

    if ($hasLetter && ($fr + $en) > 0) return true;
    if ($multiword && $hasLetter) return true;
    if ($hasPunct && $hasLetter) return true;

    // petits mots UI (OK, Yes, No, Save...)
    $low = mb_strtolower($t, 'UTF-8');
    if (in_array($low, $frSmall, true) || in_array($low, $enSmall, true)) return true;

    return false;
}

/* ---------------- JS parsing (littéraux et contexte get_string) ---------------- */

function extract_js_literals(string $src): array {
    // Renvoie: [ [start, end, line, raw] ... ] (sans commentaires)
    $out = [];
    $n = strlen($src);
    $i = 0; $line = 1;
    $state = 'code'; $q = null; $start = 0; $startline = 1;

    while ($i < $n) {
        $ch = $src[$i];

        if ($state === 'code') {
            if ($ch === '/' && $i+1 < $n) {
                $nxt = $src[$i+1];
                if ($nxt === '/') { $state = 'lcomment'; $i+=2; continue; }
                if ($nxt === '*') { $state = 'bcomment'; $i+=2; continue; }
            }
            if ($ch === "'" || $ch === '"' || $ch === '`') {
                $state='str'; $q=$ch; $start=$i; $startline=$line; $i++; continue;
            }
            if ($ch === "\n") $line++;
            $i++; continue;
        }

        if ($state === 'lcomment') {
            if ($ch === "\n") { $state='code'; $line++; }
            $i++; continue;
        }

        if ($state === 'bcomment') {
            if ($ch === "\n") $line++;
            if ($ch === '*' && $i+1 < $n && $src[$i+1] === '/') { $state='code'; $i+=2; continue; }
            $i++; continue;
        }

        if ($state === 'str') {
            if ($ch === "\n" && $q !== '`') { $line++; /* string malformée? on continue */ }
            if ($ch === $q) {
                // check escapes
                $bs = 0; $k = $i-1; while ($k >= 0 && $src[$k] === '\\') { $bs++; $k--; }
                if (($bs % 2) === 0) {
                    $end = $i;
                    $out[] = ['start'=>$start, 'end'=>$end, 'line'=>$startline, 'raw'=>substr($src,$start,$end-$start+1)];
                    $state='code'; $q=null; $i++; continue;
                }
            }
            if ($ch === "\n") $line++;
            $i++; continue;
        }
    }
    return $out;
}

function js_is_wrapped_in_get_string(string $src, int $start): bool {
    // Regarde juste avant la string: ... get_string( "xxx"
    $j = $start - 1;
    while ($j >= 0 && preg_match('/\s/', $src[$j])) $j--;
    if ($j >= 0 && $src[$j] === '(') {
        $k = $j - 1;
        while ($k >= 0 && preg_match('/\s/', $src[$k])) $k--;
        // remonte l'identifiant potentiellement qualifié Str.get_string
        $name = '';
        while ($k >= 0 && preg_match('/[A-Za-z0-9_$.]/', $src[$k])) { $name = $src[$k] . $name; $k--; }
        if (preg_match('/(^|\.)(get_string)$/i', $name)) return true;
    }
    return false;
}

function js_is_get_strings_key_or_component(string $src, int $start): bool {
    // Cherche "key :" ou "component :" juste avant la string
    $from = max(0, $start - 80);
    $ctx  = substr($src, $from, $start - $from);
    return (bool)preg_match('/(?:^|[,{]\s*)(["\']?\s*(key|component)\s*["\']?\s*:\s*)$/i', $ctx);
}

/* ---------------- PHP scanning ---------------- */

function scan_php_file(string $path, array $frStops, array $enStops, array $frSmall, array $enSmall): array {
    $src = file_get_contents($path);
    $tokens = token_get_all($src);
    $finds = [];

    $count = count($tokens);
    for ($i = 0; $i < $count; $i++) {
        $t = $tokens[$i];

        if (is_array($t)) {
            [$id, $text, $line] = $t;

            // ignore comments/doc
            if ($id === T_COMMENT || $id === T_DOC_COMMENT) continue;

            // strings: '...' or "..." without variables (T_CONSTANT_ENCAPSED_STRING)
            if ($id === T_CONSTANT_ENCAPSED_STRING) {
                // Vérifie si c'est l'argument immédiat de get_string( ... )
                $j = $i - 1;
                // skip whitespace and comments
                while ($j >= 0 && is_array($tokens[$j]) && in_array($tokens[$j][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) $j--;
                if ($j >= 1 && $tokens[$j] === '(' && is_array($tokens[$j-1]) && $tokens[$j-1][0] === T_STRING && strtolower($tokens[$j-1][1]) === 'get_string') {
                    continue; // localisé, ignorer
                }

                $val = php_unescape_string($text);
                if (is_probably_ui_text($val, $frStops, $enStops, $frSmall, $enSmall)) {
                    $finds[] = ['file'=>$path, 'line'=>$line, 'text'=>$val, 'type'=>'php'];
                }
                continue;
            }

            // encapsed strings segments (double quotes avec variables)
            if ($id === T_ENCAPSED_AND_WHITESPACE) {
                $val = $text;
                // Heuristique: si ça ressemble à de l'HTML pur, ignore
                if (preg_match('~<\w[^>]*>~', $val)) continue;
                if (is_probably_ui_text($val, $frStops, $enStops, $frSmall, $enSmall)) {
                    $finds[] = ['file'=>$path, 'line'=>$line, 'text'=>trim($val), 'type'=>'php'];
                }
                continue;
            }
        }
    }
    return $finds;
}

/* ---------------- JS scanning ---------------- */

function scan_js_file(string $path, array $frStops, array $enStops, array $frSmall, array $enSmall): array {
    $src = file_get_contents($path);
    $lits = extract_js_literals($src);
    $finds = [];
    foreach ($lits as $lit) {
        $raw = $lit['raw'];
        $start = $lit['start'];
        $line = $lit['line'];

        // Contexte de localisation ?
        if (js_is_wrapped_in_get_string($src, $start)) continue;
        if (js_is_get_strings_key_or_component($src, $start)) continue;

        $val = php_unescape_string($raw); // ok pour JS aussi (quotes/backticks)
        // ignore fragments HTML bruts
        if (preg_match('~<\w[^>]*>~', $val)) continue;

        if (is_probably_ui_text($val, $frStops, $enStops, $frSmall, $enSmall)) {
            $finds[] = ['file'=>$path, 'line'=>$line, 'text'=>trim($val), 'type'=>'js'];
        }
    }
    return $finds;
}

/* ---------------- Walk & report ---------------- */

$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
$findings = [];

foreach ($rii as $fileinfo) {
    if (!$fileinfo->isFile()) continue;
    $path = $fileinfo->getPathname();
    if (should_skip_path($path, $skipDirs)) continue;

    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if ($ext === 'php') {
        $findings = array_merge($findings, scan_php_file($path, $frenchStops, $englishStops, $frenchSmall, $englishSmall));
    } elseif ($ext === 'js') {
        // ignore JS compilés/minifiés fréquents
        if (preg_match('~\.min\.js$~', $path)) continue;
        $findings = array_merge($findings, scan_js_file($path, $frenchStops, $englishStops, $frenchSmall, $englishSmall));
    }
}

// Regroupe par fichier
$byfile = [];
foreach ($findings as $f) {
    $byfile[$f['file']][] = $f;
}
ksort($byfile, SORT_NATURAL | SORT_FLAG_CASE);

// Construit le rapport
$report = '';
$total = 0;
$report .= "== Hardcoded FR/EN strings not using get_string (PHP) or Str.get_string(s) (JS) ==\n";
$report .= "Root: {$root}\n\n";

foreach ($byfile as $file => $items) {
    usort($items, fn($a,$b)=> $a['line'] <=> $b['line']);
    $report .= $file . "\n";
    foreach ($items as $it) {
        $txt = preg_replace('/\s+/', ' ', $it['text']);
        if (mb_strlen($txt, 'UTF-8') > 120) $txt = mb_substr($txt, 0, 117, 'UTF-8') . '...';
        $report .= sprintf("  %5d  [%s]  %s\n", $it['line'], $it['type'], $txt);
        $total++;
        if ($total >= $REPORT_LIMIT) {
            $report .= "\n... (limit reached: {$REPORT_LIMIT})\n";
            break 2;
        }
    }
    $report .= "\n";
}
$report .= "Total findings: {$total}\n";

// Sortie
if ($outfile === '-') {
    echo $report;
} else {
    $dir = dirname($outfile);
    if (!is_dir($dir)) @mkdir($dir, 0777, true);
    if (false === @file_put_contents($outfile, $report)) {
        cli_error("Failed to write report to: $outfile");
    }
    cli_writeln("Report written to: $outfile");
}
