<?php
// This file is part of Moodle - https://moodle.org/
//
// CampusFR filedir audit tool — version 2.
// Strictly read-only, except for an explicitly requested JSON export.

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options, $unrecognized] = cli_get_params(
    [
        'help' => false,
        'top' => 30,
        'json' => null,
        'scan-filesystem' => false,
        'scan-repository' => false,
    ],
    [
        'h' => 'help',
        't' => 'top',
        'j' => 'json',
        's' => 'scan-filesystem',
        'r' => 'scan-repository',
    ]
);

if ($unrecognized) {
    cli_error("Options inconnues :\n  " . implode("\n  ", $unrecognized));
}

if ($options['help']) {
    echo <<<TXT
Audit en lecture seule du stockage Moodle filedir — CampusFR V2.

Nouveautés V2 :
- classement des gros fichiers sans répétitions trompeuses ;
- taille logique et taille physique unique par groupe ;
- répartition par activité Moodle ;
- analyse spécifique des packages et exports H5P ;
- classement des contenus les plus réutilisés ;
- audit facultatif de moodledata/repository ;
- scan facultatif des fichiers physiques sans référence DB.

Usage :
  php local/subscriptions/cli/maintenance/filedir/audit_filedir_v2.php
  php local/subscriptions/cli/maintenance/filedir/audit_filedir_v2.php --top=100
  php local/subscriptions/cli/maintenance/filedir/audit_filedir_v2.php --scan-repository
  php local/subscriptions/cli/maintenance/filedir/audit_filedir_v2.php --scan-filesystem
  php local/subscriptions/cli/maintenance/filedir/audit_filedir_v2.php --scan-repository --scan-filesystem \\
      --json=/root/filedir-audit-v2.json

Options :
  -h, --help              Affiche cette aide.
  -t, --top=N             Nombre d'éléments par classement (1 à 500, défaut 30).
  -j, --json=FICHIER      Écrit une copie JSON du rapport au chemin indiqué.
  -s, --scan-filesystem   Parcourt réellement filedir pour rechercher les fichiers
                          physiques qui ne sont pas référencés dans mdl_files.
  -r, --scan-repository   Parcourt moodledata/repository et classe les fichiers
                          par extension et sous-répertoire de premier niveau.

Le script ne supprime, ne déplace et ne modifie aucun contenu Moodle.
La seule écriture possible est le fichier fourni avec --json.
TXT;
    echo PHP_EOL;
    exit(0);
}

$toplimit = max(1, min(500, (int)$options['top']));
$filedir = rtrim($CFG->dataroot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'filedir';
$repositorydir = rtrim($CFG->dataroot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'repository';

if (!is_dir($filedir)) {
    cli_error("Répertoire filedir introuvable : {$filedir}");
}

function campusfr_format_bytes(int|float $bytes, int $precision = 2): string {
    $negative = $bytes < 0;
    $value = abs((float)$bytes);
    $units = ['o', 'Ko', 'Mo', 'Go', 'To', 'Po'];
    $index = 0;
    while ($value >= 1024 && $index < count($units) - 1) {
        $value /= 1024;
        $index++;
    }
    return ($negative ? '-' : '') . number_format($value, $precision, ',', ' ') . ' ' . $units[$index];
}

function campusfr_contenthash_path(string $filedir, string $contenthash): string {
    return $filedir . DIRECTORY_SEPARATOR . substr($contenthash, 0, 2)
        . DIRECTORY_SEPARATOR . substr($contenthash, 2, 2)
        . DIRECTORY_SEPARATOR . $contenthash;
}

function campusfr_group_add(array &$groups, string $key, int $logicalbytes, string $contenthash): void {
    if (!isset($groups[$key])) {
        $groups[$key] = [
            'references' => 0,
            'logical_bytes' => 0,
            'physical_bytes' => 0,
            '_hashes' => [],
        ];
    }
    $groups[$key]['references']++;
    $groups[$key]['logical_bytes'] += $logicalbytes;
    if (!isset($groups[$key]['_hashes'][$contenthash])) {
        $groups[$key]['_hashes'][$contenthash] = true;
        $groups[$key]['physical_bytes'] += $logicalbytes;
    }
}

function campusfr_group_finalize(array &$groups): void {
    foreach ($groups as &$stats) {
        $stats['unique_contents'] = count($stats['_hashes']);
        unset($stats['_hashes']);
    }
    unset($stats);
    uasort($groups, static function(array $a, array $b): int {
        if ($a['physical_bytes'] === $b['physical_bytes']) {
            return $b['logical_bytes'] <=> $a['logical_bytes'];
        }
        return $b['physical_bytes'] <=> $a['physical_bytes'];
    });
}

function campusfr_print_groups(string $title, array $groups, int $limit): void {
    echo PHP_EOL . $title . PHP_EOL;
    echo str_repeat('-', 132) . PHP_EOL;
    printf("%-65s %10s %10s %18s %18s\n", 'Élément', 'Référ.', 'Uniques', 'Logique', 'Physique unique');
    echo str_repeat('-', 132) . PHP_EOL;
    $i = 0;
    foreach ($groups as $label => $stats) {
        if ($i++ >= $limit) {
            break;
        }
        printf(
            "%-65s %10s %10s %18s %18s\n",
            core_text::substr((string)$label, 0, 64),
            number_format($stats['references'], 0, ',', ' '),
            number_format($stats['unique_contents'], 0, ',', ' '),
            campusfr_format_bytes($stats['logical_bytes']),
            campusfr_format_bytes($stats['physical_bytes'])
        );
    }
}

function campusfr_course_label(stdClass $record): string {
    if (!empty($record->courseid)) {
        $shortname = trim((string)($record->courseshortname ?? ''));
        $fullname = trim((string)($record->coursefullname ?? ''));
        return '#' . $record->courseid . ' — ' . ($shortname !== '' ? $shortname : ($fullname !== '' ? $fullname : 'cours'));
    }
    return '[Hors cours / système / utilisateur]';
}

function campusfr_h5p_family(string $filename): string {
    $name = core_text::strtolower(pathinfo($filename, PATHINFO_FILENAME));
    $name = preg_replace('/-\d+-\d+$/', '', $name);
    $name = preg_replace('/[_\s]+/', '-', $name);
    return $name !== '' ? $name : '[nom H5P inconnu]';
}

function campusfr_keep_top(array &$items, array $item, int $limit, string $sortkey): void {
    $items[] = $item;
    usort($items, static fn(array $a, array $b): int => $b[$sortkey] <=> $a[$sortkey]);
    if (count($items) > $limit) {
        array_pop($items);
    }
}

function campusfr_scan_directory(string $root, int $toplimit): array {
    $result = [
        'exists' => is_dir($root),
        'files' => 0,
        'bytes' => 0,
        'by_extension' => [],
        'by_top_directory' => [],
        'largest_files' => [],
    ];
    if (!$result['exists']) {
        return $result;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_FILEINFO),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    foreach ($iterator as $fileinfo) {
        if (!$fileinfo->isFile()) {
            continue;
        }
        $size = (int)$fileinfo->getSize();
        $result['files']++;
        $result['bytes'] += $size;

        $extension = core_text::strtolower(pathinfo($fileinfo->getFilename(), PATHINFO_EXTENSION));
        $extension = $extension !== '' ? '.' . $extension : '[sans extension]';
        if (!isset($result['by_extension'][$extension])) {
            $result['by_extension'][$extension] = ['files' => 0, 'bytes' => 0];
        }
        $result['by_extension'][$extension]['files']++;
        $result['by_extension'][$extension]['bytes'] += $size;

        $relative = ltrim(substr($fileinfo->getPathname(), strlen($root)), DIRECTORY_SEPARATOR);
        $parts = preg_split('~[\\\\/]~', $relative);
        $topdir = count($parts) > 1 ? $parts[0] : '[racine]';
        if (!isset($result['by_top_directory'][$topdir])) {
            $result['by_top_directory'][$topdir] = ['files' => 0, 'bytes' => 0];
        }
        $result['by_top_directory'][$topdir]['files']++;
        $result['by_top_directory'][$topdir]['bytes'] += $size;

        campusfr_keep_top($result['largest_files'], [
            'path' => $fileinfo->getPathname(),
            'filesize' => $size,
        ], $toplimit, 'filesize');
    }
    uasort($result['by_extension'], static fn(array $a, array $b): int => $b['bytes'] <=> $a['bytes']);
    uasort($result['by_top_directory'], static fn(array $a, array $b): int => $b['bytes'] <=> $a['bytes']);
    return $result;
}

function campusfr_print_simple_scan_groups(string $title, array $groups, int $limit): void {
    echo PHP_EOL . $title . PHP_EOL;
    echo str_repeat('-', 100) . PHP_EOL;
    printf("%-65s %12s %18s\n", 'Élément', 'Fichiers', 'Taille');
    echo str_repeat('-', 100) . PHP_EOL;
    $i = 0;
    foreach ($groups as $label => $stats) {
        if ($i++ >= $limit) {
            break;
        }
        printf("%-65s %12s %18s\n", core_text::substr((string)$label, 0, 64), number_format($stats['files'], 0, ',', ' '), campusfr_format_bytes($stats['bytes']));
    }
}

echo PHP_EOL;
echo "============================================================" . PHP_EOL;
echo " AUDIT MOODLE FILEDIR — CAMPUSFR V2" . PHP_EOL;
echo "============================================================" . PHP_EOL;
echo "Moodledata : {$CFG->dataroot}" . PHP_EOL;
echo "Filedir    : {$filedir}" . PHP_EOL;
echo "Début      : " . userdate(time(), '%Y-%m-%d %H:%M:%S') . PHP_EOL;
echo "Mode       : lecture seule" . PHP_EOL;

$sql = "
    SELECT
        f.id, f.contenthash, f.pathnamehash, f.contextid, f.component, f.filearea,
        f.itemid, f.filepath, f.filename, f.filesize, f.mimetype,
        ctx.contextlevel, ctx.instanceid AS contextinstanceid,
        cm.id AS cmid, cm.course AS cmcourseid,
        COALESCE(cm.course, cctx.id) AS courseid,
        COALESCE(c.shortname, cctx.shortname) AS courseshortname,
        COALESCE(c.fullname, cctx.fullname) AS coursefullname
    FROM {files} f
    LEFT JOIN {context} ctx ON ctx.id = f.contextid
    LEFT JOIN {course_modules} cm
           ON ctx.contextlevel = :modulelevel AND cm.id = ctx.instanceid
    LEFT JOIN {course} c ON c.id = cm.course
    LEFT JOIN {course} cctx
           ON ctx.contextlevel = :courselevel AND cctx.id = ctx.instanceid
    WHERE f.filename <> :directorymarker AND f.filesize > 0
    ORDER BY f.id ASC
";
$params = [
    'modulelevel' => CONTEXT_MODULE,
    'courselevel' => CONTEXT_COURSE,
    'directorymarker' => '.',
];

$summary = [
    'logical_references' => 0,
    'logical_bytes' => 0,
    'unique_referenced_contenthashes' => 0,
    'unique_referenced_bytes' => 0,
    'missing_physical_files' => 0,
    'missing_physical_bytes' => 0,
];

$groups = [
    'component' => [],
    'filearea' => [],
    'mimetype' => [],
    'course' => [],
    'activity' => [],
    'h5p_activity' => [],
    'h5p_family' => [],
];

$hashes = [];
$largestunique = [];
$activitycache = [];

$recordset = $DB->get_recordset_sql($sql, $params);
foreach ($recordset as $record) {
    $size = (int)$record->filesize;
    $hash = (string)$record->contenthash;
    $component = (string)($record->component ?: '[sans composant]');
    $filearea = (string)($record->filearea ?: '[sans zone]');
    $mimetype = (string)($record->mimetype ?: '[type MIME inconnu]');
    $course = campusfr_course_label($record);

    $summary['logical_references']++;
    $summary['logical_bytes'] += $size;

    campusfr_group_add($groups['component'], $component, $size, $hash);
    campusfr_group_add($groups['filearea'], $component . ' / ' . $filearea, $size, $hash);
    campusfr_group_add($groups['mimetype'], $mimetype, $size, $hash);
    campusfr_group_add($groups['course'], $course, $size, $hash);

    $activitylabel = '[Hors contexte activité]';
    if (!empty($record->cmid) && !empty($record->cmcourseid)) {
        $cmid = (int)$record->cmid;
        if (!isset($activitycache[$cmid])) {
            try {
                $modinfo = get_fast_modinfo((int)$record->cmcourseid);
                if (isset($modinfo->cms[$cmid])) {
                    $cm = $modinfo->cms[$cmid];
                    $activitycache[$cmid] = $course . ' / ' . $cm->modname . ' #' . $cmid . ' — ' . format_string($cm->name, true, ['context' => $cm->context]);
                } else {
                    $activitycache[$cmid] = $course . ' / activité #' . $cmid;
                }
            } catch (Throwable $e) {
                $activitycache[$cmid] = $course . ' / activité #' . $cmid . ' [nom indisponible]';
            }
        }
        $activitylabel = $activitycache[$cmid];
    }
    campusfr_group_add($groups['activity'], $activitylabel, $size, $hash);

    if ($component === 'mod_h5pactivity' && $filearea === 'package') {
        campusfr_group_add($groups['h5p_activity'], $activitylabel, $size, $hash);
    }
    if (($component === 'core_h5p' && $filearea === 'export') || $component === 'mod_h5pactivity') {
        campusfr_group_add($groups['h5p_family'], campusfr_h5p_family((string)$record->filename), $size, $hash);
    }

    if (!isset($hashes[$hash])) {
        $physicalpath = campusfr_contenthash_path($filedir, $hash);
        $exists = is_file($physicalpath);
        $hashes[$hash] = [
            'references' => 1,
            'filesize' => $size,
            'exists' => $exists,
            'path' => $physicalpath,
            'sample_filename' => (string)$record->filename,
            'sample_component' => $component,
            'sample_filearea' => $filearea,
            'sample_course' => $course,
            'sample_activity' => $activitylabel,
        ];
        $summary['unique_referenced_contenthashes']++;
        $summary['unique_referenced_bytes'] += $size;
        if (!$exists) {
            $summary['missing_physical_files']++;
            $summary['missing_physical_bytes'] += $size;
        }
    } else {
        $hashes[$hash]['references']++;
    }
}
$recordset->close();

foreach ($groups as &$group) {
    campusfr_group_finalize($group);
}
unset($group);

foreach ($hashes as $hash => $item) {
    campusfr_keep_top($largestunique, [
        'contenthash' => $hash,
        'filesize' => $item['filesize'],
        'references' => $item['references'],
        'filename' => $item['sample_filename'],
        'component_filearea' => $item['sample_component'] . '/' . $item['sample_filearea'],
        'course' => $item['sample_course'],
        'activity' => $item['sample_activity'],
        'exists' => $item['exists'],
    ], $toplimit, 'filesize');
}

$mostreused = array_map(static function(string $hash, array $item): array {
    return [
        'contenthash' => $hash,
        'filesize' => $item['filesize'],
        'references' => $item['references'],
        'logical_bytes' => $item['filesize'] * $item['references'],
        'saved_bytes' => $item['filesize'] * max(0, $item['references'] - 1),
        'filename' => $item['sample_filename'],
        'component_filearea' => $item['sample_component'] . '/' . $item['sample_filearea'],
    ];
}, array_keys($hashes), array_values($hashes));
usort($mostreused, static function(array $a, array $b): int {
    if ($a['saved_bytes'] === $b['saved_bytes']) {
        return $b['references'] <=> $a['references'];
    }
    return $b['saved_bytes'] <=> $a['saved_bytes'];
});
$mostreused = array_slice($mostreused, 0, $toplimit);

$summary['deduplication_saved_bytes'] = max(0, $summary['logical_bytes'] - $summary['unique_referenced_bytes']);

// Note: du may differ slightly because it measures allocated blocks and includes physical orphans.
echo PHP_EOL . 'SYNTHÈSE' . PHP_EOL;
echo str_repeat('-', 86) . PHP_EOL;
printf("%-52s %30s\n", 'Références logiques mdl_files', number_format($summary['logical_references'], 0, ',', ' '));
printf("%-52s %30s\n", 'Taille logique cumulée', campusfr_format_bytes($summary['logical_bytes']));
printf("%-52s %30s\n", 'Contenus physiques uniques référencés', number_format($summary['unique_referenced_contenthashes'], 0, ',', ' '));
printf("%-52s %30s\n", 'Taille physique unique référencée', campusfr_format_bytes($summary['unique_referenced_bytes']));
printf("%-52s %30s\n", 'Économie estimée par déduplication', campusfr_format_bytes($summary['deduplication_saved_bytes']));
printf("%-52s %30s\n", 'Contenus référencés absents du disque', number_format($summary['missing_physical_files'], 0, ',', ' '));
printf("%-52s %30s\n", 'Volume DB correspondant aux absents', campusfr_format_bytes($summary['missing_physical_bytes']));

campusfr_print_groups('RÉPARTITION PAR COMPOSANT', $groups['component'], $toplimit);
campusfr_print_groups('RÉPARTITION PAR COMPOSANT / FILEAREA', $groups['filearea'], $toplimit);
campusfr_print_groups('RÉPARTITION PAR TYPE MIME', $groups['mimetype'], $toplimit);
campusfr_print_groups('RÉPARTITION PAR COURS', $groups['course'], $toplimit);
campusfr_print_groups('RÉPARTITION PAR ACTIVITÉ MOODLE', $groups['activity'], $toplimit);
campusfr_print_groups('PACKAGES H5P PAR ACTIVITÉ', $groups['h5p_activity'], $toplimit);
campusfr_print_groups('FAMILLES H5P DÉDUITES DU NOM DE FICHIER', $groups['h5p_family'], $toplimit);

echo PHP_EOL . 'PLUS GROS CONTENUS PHYSIQUES UNIQUES' . PHP_EOL;
echo str_repeat('-', 148) . PHP_EOL;
printf("%-12s %8s %-38s %-26s %-45s\n", 'Taille', 'Référ.', 'Fichier', 'Composant / zone', 'Activité');
echo str_repeat('-', 148) . PHP_EOL;
foreach ($largestunique as $item) {
    printf(
        "%-12s %8s %-38s %-26s %-45s\n",
        campusfr_format_bytes($item['filesize']),
        number_format($item['references'], 0, ',', ' '),
        core_text::substr($item['filename'], 0, 37),
        core_text::substr($item['component_filearea'], 0, 25),
        core_text::substr($item['activity'], 0, 44)
    );
}

echo PHP_EOL . 'CONTENUS LES PLUS RÉUTILISÉS / ÉCONOMIE DE DÉDUPLICATION' . PHP_EOL;
echo str_repeat('-', 130) . PHP_EOL;
printf("%-12s %10s %18s %-42s %-30s\n", 'Taille', 'Référ.', 'Économie', 'Fichier', 'Composant / zone');
echo str_repeat('-', 130) . PHP_EOL;
foreach ($mostreused as $item) {
    printf(
        "%-12s %10s %18s %-42s %-30s\n",
        campusfr_format_bytes($item['filesize']),
        number_format($item['references'], 0, ',', ' '),
        campusfr_format_bytes($item['saved_bytes']),
        core_text::substr($item['filename'], 0, 41),
        core_text::substr($item['component_filearea'], 0, 29)
    );
}

$repositoryscan = ['enabled' => false];
if (!empty($options['scan-repository'])) {
    echo PHP_EOL . 'SCAN DU REPOSITORY' . PHP_EOL;
    echo str_repeat('-', 86) . PHP_EOL;
    echo "Répertoire : {$repositorydir}" . PHP_EOL;
    $repositoryscan = campusfr_scan_directory($repositorydir, $toplimit);
    $repositoryscan['enabled'] = true;
    if (!$repositoryscan['exists']) {
        echo "Repository absent." . PHP_EOL;
    } else {
        printf("%-52s %30s\n", 'Fichiers parcourus', number_format($repositoryscan['files'], 0, ',', ' '));
        printf("%-52s %30s\n", 'Taille totale', campusfr_format_bytes($repositoryscan['bytes']));
        campusfr_print_simple_scan_groups('REPOSITORY PAR EXTENSION', $repositoryscan['by_extension'], $toplimit);
        campusfr_print_simple_scan_groups('REPOSITORY PAR SOUS-RÉPERTOIRE PRINCIPAL', $repositoryscan['by_top_directory'], $toplimit);
        echo PHP_EOL . 'PLUS GROS FICHIERS DU REPOSITORY' . PHP_EOL;
        echo str_repeat('-', 115) . PHP_EOL;
        printf("%-14s %s\n", 'Taille', 'Chemin');
        echo str_repeat('-', 115) . PHP_EOL;
        foreach ($repositoryscan['largest_files'] as $item) {
            printf("%-14s %s\n", campusfr_format_bytes($item['filesize']), $item['path']);
        }
    }
}

$filesystemscan = [
    'enabled' => false,
    'files' => 0,
    'bytes' => 0,
    'orphan_files' => 0,
    'orphan_bytes' => 0,
    'nonhash_files' => 0,
    'largest_orphans' => [],
];
if (!empty($options['scan-filesystem'])) {
    echo PHP_EOL . 'SCAN PHYSIQUE DE FILEDIR' . PHP_EOL;
    echo str_repeat('-', 86) . PHP_EOL;
    echo "Parcours en cours..." . PHP_EOL;
    $filesystemscan['enabled'] = true;

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($filedir, FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_FILEINFO),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    foreach ($iterator as $fileinfo) {
        if (!$fileinfo->isFile()) {
            continue;
        }
        $size = (int)$fileinfo->getSize();
        $filesystemscan['files']++;
        $filesystemscan['bytes'] += $size;
        $basename = $fileinfo->getBasename();
        if (!preg_match('/^[a-f0-9]{40}$/', $basename)) {
            $filesystemscan['nonhash_files']++;
            continue;
        }
        if (!isset($hashes[$basename])) {
            $filesystemscan['orphan_files']++;
            $filesystemscan['orphan_bytes'] += $size;
            campusfr_keep_top($filesystemscan['largest_orphans'], [
                'contenthash' => $basename,
                'filesize' => $size,
                'path' => $fileinfo->getPathname(),
                'mtime' => $fileinfo->getMTime(),
            ], $toplimit, 'filesize');
        }
    }

    printf("%-52s %30s\n", 'Fichiers physiques parcourus', number_format($filesystemscan['files'], 0, ',', ' '));
    printf("%-52s %30s\n", 'Taille physique parcourue', campusfr_format_bytes($filesystemscan['bytes']));
    printf("%-52s %30s\n", 'Fichiers sans référence DB', number_format($filesystemscan['orphan_files'], 0, ',', ' '));
    printf("%-52s %30s\n", 'Volume sans référence DB', campusfr_format_bytes($filesystemscan['orphan_bytes']));
    printf("%-52s %30s\n", 'Fichiers au nom non standard', number_format($filesystemscan['nonhash_files'], 0, ',', ' '));

    if ($filesystemscan['largest_orphans']) {
        echo PHP_EOL . 'PLUS GROS FICHIERS PHYSIQUES SANS RÉFÉRENCE DB' . PHP_EOL;
        echo str_repeat('-', 115) . PHP_EOL;
        printf("%-14s %-42s %s\n", 'Taille', 'Contenthash', 'Chemin');
        echo str_repeat('-', 115) . PHP_EOL;
        foreach ($filesystemscan['largest_orphans'] as $item) {
            printf("%-14s %-42s %s\n", campusfr_format_bytes($item['filesize']), $item['contenthash'], $item['path']);
        }
    }
    echo PHP_EOL . "ATTENTION : aucun fichier détecté n'est supprimé par ce script." . PHP_EOL;
}

$report = [
    'version' => 2,
    'generated_at' => date(DATE_ATOM),
    'moodledata' => $CFG->dataroot,
    'filedir' => $filedir,
    'summary' => $summary,
    'groups' => $groups,
    'largest_unique_files' => $largestunique,
    'most_reused_contents' => $mostreused,
    'repository_scan' => $repositoryscan,
    'filesystem_scan' => $filesystemscan,
];

if (!empty($options['json'])) {
    $jsonpath = (string)$options['json'];
    $json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        cli_error('Impossible de sérialiser le JSON : ' . json_last_error_msg());
    }
    if (file_put_contents($jsonpath, $json . PHP_EOL) === false) {
        cli_error("Impossible d'écrire le rapport JSON : {$jsonpath}");
    }
    echo PHP_EOL . "Rapport JSON écrit dans : {$jsonpath}" . PHP_EOL;
}

echo PHP_EOL;
echo "Fin        : " . userdate(time(), '%Y-%m-%d %H:%M:%S') . PHP_EOL;
echo "Résultat   : audit V2 terminé, aucune donnée Moodle modifiée." . PHP_EOL;
echo PHP_EOL;