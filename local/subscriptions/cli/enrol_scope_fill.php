#!/usr/bin/env php
<?php
/**
 * Enrol Scope Fill — Inscrire automatiquement les abonnés actifs
 * à tous les cours présents dans l'access scope de leur plan.
 *
 * Options :
 *   --userid=ID   : traiter uniquement cet utilisateur
 *   --dry-run     : simulation (aucune inscription effectuée)
 *   --verbose     : logs détaillés
 *   --help        : aide
 *
 * Exemples :
 *   php enrol_scope_fill.php --userid=24 --dry-run --verbose
 *   php enrol_scope_fill.php --verbose
 */

define('CLI_SCRIPT', true);

// ---- Adapter le chemin vers config.php selon l'emplacement réel du script.
require(__DIR__ . '/../../../config.php');

require_once($CFG->libdir . '/clilib.php');
require_once($CFG->libdir . '/enrollib.php');
require_once($CFG->dirroot . '/enrol/manual/lib.php');

// -----------------------------------------------------------------------------
// CONFIG — adapter si vos noms diffèrent.
// -----------------------------------------------------------------------------

// Souscriptions (active, dates en UNIX ts)
const TBL_SUBS          = 'user_subscription'; // => mdl_user_subscription
const COL_SUBS_ID       = 'id';
const COL_SUBS_USERID   = 'userid';
const COL_SUBS_STATUS   = 'status';
const COL_SUBS_START    = 'start_date'; // int UNIX
const COL_SUBS_END      = 'end_date';   // int UNIX
const COL_SUBS_PLANID   = 'planid';
const SUB_STATUS_ACTIVE = 'active';

// Plans → scope
const TBL_PLAN          = 'subscription_plan';
const COL_PLAN_ID       = 'id';
const COL_PLAN_SCOPEID  = 'accessscopeid';   // ← confirmé par ton DESCRIBE

// Scopes avec CSV de cours
const TBL_SCOPE         = 'subscription_access_scope';
const COL_SCOPE_ID      = 'id';
const COL_SCOPE_COURSES = 'course_ids';


// Rôle d’inscription (par shortname, fallback #5)
const DEFAULT_ROLE_SHORTNAME = 'student';

// -----------------------------------------------------------------------------
// CLI options
// -----------------------------------------------------------------------------
list($options, $unrecognized) = cli_get_params([
    'userid'  => null,
    'dry-run' => false,
    'verbose' => false,
    'help'    => false,
], [
    'u' => 'userid',
    'n' => 'dry-run',
    'v' => 'verbose',
    'h' => 'help',
]);

if (!empty($options['help'])) {
    $help = <<<EOF
Inscrire automatiquement les abonnés actifs aux cours du scope lié au plan.

Options :
  --userid=ID   Traiter uniquement cet utilisateur
  --dry-run     Simulation (aucune inscription)
  --verbose     Logs détaillés
  --help        Aide

Exemples :
  php enrol_scope_fill.php --userid=24 --dry-run --verbose
  php enrol_scope_fill.php --verbose
EOF;
    mtrace($help);
    exit(0);
}

$useridFilter = $options['userid'] ? (int)$options['userid'] : null;
$dryrun  = (bool)$options['dry-run'];
$verbose = (bool)$options['verbose'];

function vlog(string $msg): void {
    global $verbose;
    if ($verbose) {
        mtrace($msg);
    }
}

// -----------------------------------------------------------------------------
// Utils
// -----------------------------------------------------------------------------
/** roleid à partir du shortname (fallback 5) */
function get_roleid_by_shortname(string $shortname): int {
    global $DB;
    if ($role = $DB->get_record('role', ['shortname' => $shortname], 'id', IGNORE_MISSING)) {
        return (int)$role->id;
    }
    return 5;
}

/** L’utilisateur est-il déjà inscrit à un courseid, via n’importe quel enrol ? */
function is_user_enrolled_in_course(int $userid, int $courseid): bool {
    global $DB;
    $sql = "SELECT 1
              FROM {user_enrolments} ue
              JOIN {enrol} e ON e.id = ue.enrolid
             WHERE ue.userid = :uid
               AND e.courseid = :cid
             LIMIT 1";
    return $DB->record_exists_sql($sql, ['uid' => $userid, 'cid' => $courseid]);
}

/** Instance manual enrol active pour le courseid */
function get_manual_enrol_instance(int $courseid): ?stdClass {
    $instances = enrol_get_instances($courseid, true);
    foreach ($instances as $inst) {
        if ($inst->enrol === 'manual' && (int)$inst->status === ENROL_INSTANCE_ENABLED) {
            return $inst;
        }
    }
    return null;
}

// AVANT (bug) : il y avait un LIMIT 1 dans le SQL
// record_exists_sql() en rajoute un -> "LIMIT 1 LIMIT 0, 1"

// APRÈS (OK) : on enlève LIMIT 1 du SQL
function safe_is_user_enrolled_in_course(int $userid, int $courseid): bool {
    global $DB;
    try {
        $sql = "SELECT 1
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON e.id = ue.enrolid
                 WHERE ue.userid = :uid
                   AND e.courseid = :cid";
        return $DB->record_exists_sql($sql, ['uid' => $userid, 'cid' => $courseid]);
    } catch (\dml_exception $e) {
        $debug = property_exists($e, 'debuginfo') ? $e->debuginfo : '(no debuginfo)';
        mtrace("DML ERROR is_user_enrolled_in_course(u={$userid}, c={$courseid}): {$e->getMessage()} | {$debug}");
        return false; // on considère “non inscrit” pour ne pas bloquer
    }
}


/** Instance manual enrol – sûre (journalise au lieu de casser) */
function safe_get_manual_enrol_instance(int $courseid): ?stdClass {
    try {
        $instances = enrol_get_instances($courseid, true);
        foreach ($instances as $inst) {
            if ($inst->enrol === 'manual' && (int)$inst->status === ENROL_INSTANCE_ENABLED) {
                return $inst;
            }
        }
        return null;
    } catch (\Throwable $e) {
        mtrace("ERROR get_instances(course={$courseid}): " . $e->getMessage());
        return null;
    }
}

/** Enrol avec dates – en dry-run, on ne touche pas à la DB (zéro appel enrol/*) */
function enrol_user_with_dates(int $userid, int $courseid, int $timestart, int $timeend, bool $dryrun): bool {
    if ($dryrun) {
        mtrace("DRY-RUN: enrol_user → user={$userid}, course={$courseid}, start=" .
               ($timestart ? date('Y-m-d', $timestart) : 'now') . ", end=" .
               ($timeend ? date('Y-m-d', $timeend) : '∞') . " (aucun accès DB enrol/* en simulation)");
        return true;
    }

    $inst = safe_get_manual_enrol_instance($courseid);
    if (!$inst) {
        mtrace("WARN: pas d’instance 'manual' active pour courseid={$courseid}, skip.");
        return false;
    }

    $plugin = enrol_get_plugin('manual');
    if (!$plugin instanceof enrol_manual_plugin) {
        mtrace("ERR: plugin 'manual' indisponible.");
        return false;
    }

    $roleid = get_roleid_by_shortname(DEFAULT_ROLE_SHORTNAME);
    $plugin->enrol_user($inst, $userid, $roleid, $timestart, $timeend);
    return true;
}


/** Récupère les courseids (existants) pour le scope du plan (course_ids CSV) */
function get_scope_courses_for_plan(int $planid): array {
    global $DB;

    try {
        // 1) Lire le plan avec la colonne confirmée
        $plan = $DB->get_record(TBL_PLAN, [COL_PLAN_ID => $planid], '*', IGNORE_MISSING);
        if (!$plan) {
            mtrace("WARN: plan {$planid} introuvable");
            return [];
        }
        if (empty($plan->{COL_PLAN_SCOPEID})) {
            mtrace("WARN: plan {$planid} sans scope (" . COL_PLAN_SCOPEID . " est vide)");
            return [];
        }
        $scopeid = (int)$plan->{COL_PLAN_SCOPEID};
        mtrace("DEBUG: plan {$planid} → scope {$scopeid}");

        // 2) Lire le scope (course_ids CSV)
        $scope = $DB->get_record(TBL_SCOPE, [COL_SCOPE_ID => $scopeid], '*', IGNORE_MISSING);
        if (!$scope) {
            mtrace("WARN: scope {$scopeid} introuvable");
            return [];
        }
        if (!property_exists($scope, COL_SCOPE_COURSES)) {
            mtrace("WARN: colonne '".COL_SCOPE_COURSES."' absente du scope {$scopeid}");
            return [];
        }
        $csv = trim((string)$scope->{COL_SCOPE_COURSES});
        mtrace("DEBUG: scope {$scopeid} course_ids='{$csv}'");

        if ($csv === '') {
            mtrace("DEBUG: scope {$scopeid} → aucun cours");
            return [];
        }

        // 3) Parser CSV -> ids
        $raw = preg_split('/[,\s]+/', $csv, -1, PREG_SPLIT_NO_EMPTY);
        $ids = [];
        foreach ($raw as $t) {
            if (is_numeric($t)) {
                $ids[] = (int)$t;
            }
        }
        $ids = array_values(array_unique(array_filter($ids)));
        if (!$ids) {
            mtrace("DEBUG: scope {$scopeid} → aucun id numérique parsé");
            return [];
        }

        // 4) Garder uniquement les cours existants
        list($inSql, $params) = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED);
        $existing = $DB->get_fieldset_sql("SELECT id FROM {course} WHERE id $inSql", $params);
        $existing = array_map('intval', $existing);

        if (count($existing) < count($ids)) {
            $missing = array_diff($ids, $existing);
            if ($missing) {
                mtrace("WARN: courses inexistants dans scope {$scopeid}: " . implode(',', $missing));
            }
        }

        return $existing;

    } catch (\dml_exception $e) {
        $debug = property_exists($e, 'debuginfo') ? $e->debuginfo : '(no debuginfo)';
        mtrace("DML ERROR in get_scope_courses_for_plan(plan={$planid}): {$e->getMessage()} | code={$e->errorcode} | debug={$debug}");
        return [];
    } catch (\Throwable $e) {
        mtrace("ERROR in get_scope_courses_for_plan(plan={$planid}): " . $e->getMessage());
        return [];
    }
}



// -----------------------------------------------------------------------------
// MAIN
// -----------------------------------------------------------------------------
global $DB;

$params = [
    'active' => SUB_STATUS_ACTIVE,
    'now'    => time(),
];

$where = "s." . COL_SUBS_STATUS . " = :active AND s." . COL_SUBS_END . " > :now";

if ($useridFilter) {
    $where .= " AND s." . COL_SUBS_USERID . " = :userid";
    $params['userid'] = $useridFilter;
}

$sql = sprintf(
    "SELECT
        s.%s AS subid,
        s.%s AS userid,
        s.%s AS planid,
        s.%s AS startts,
        s.%s AS endts,
        u.username, u.email
     FROM {%s} s
     JOIN {user} u ON u.id = s.%s
     WHERE %s
     ORDER BY s.%s, s.%s",
    COL_SUBS_ID,
    COL_SUBS_USERID,
    COL_SUBS_PLANID,
    COL_SUBS_START,
    COL_SUBS_END,
    TBL_SUBS,
    COL_SUBS_USERID,
    $where,
    COL_SUBS_USERID,
    COL_SUBS_ID
);

$subs = $DB->get_records_sql($sql, $params);

if (!$subs) {
    mtrace("Aucune souscription active à traiter." . ($useridFilter ? " (userid={$useridFilter})" : ""));
    exit(0);
}

mtrace("Trouvé " . count($subs) . " souscription(s) active(s)" . ($useridFilter ? " pour userid={$useridFilter}" : "") . ". " . ($dryrun ? "[DRY-RUN]" : ""));

$processed = 0;
$enrolled  = 0;
$skipped   = 0;

foreach ($subs as $s) {
    $processed++;
    $userid = (int)$s->userid;
    $planid = (int)$s->planid;
    $tstart = (int)$s->startts ?: time();
    $tend   = (int)$s->endts   ?: 0;

    vlog("Sub #{$s->subid} user={$userid} ({$s->email}) plan={$planid} window=" .
        ($tstart ? date('Y-m-d', $tstart) : 'now') . " → " .
        ($tend ? date('Y-m-d', $tend) : '∞'));

    // Récup cours du scope
    try {
        $courseids = get_scope_courses_for_plan($planid);
    } catch (\Throwable $e) {
        mtrace("ERR: get_scope_courses_for_plan(plan={$planid}) : " . $e->getMessage());
        $skipped++;
        continue;
    }

    if (empty($courseids)) {
        vlog("  → Aucun cours dans le scope du plan {$planid}, skip.");
        $skipped++;
        continue;
    }

    foreach ($courseids as $courseid) {
        try {
            // Vérifier que le cours existe (sinon certains appels core cassent)
            if (!$DB->record_exists('course', ['id' => $courseid])) {
                mtrace("WARN: course {$courseid} inexistant, skip.");
                continue;
            }

            // En dry-run, on ne fait PAS de check d’inscription (pour éviter une DML read error ici).
            if (!$dryrun) {
                if (safe_is_user_enrolled_in_course($userid, $courseid)) {
                    vlog("    ✓ déjà inscrit au cours {$courseid}");
                    continue;
                }
            } else {
                vlog("    (dry-run) on ne vérifie pas l’inscription existante pour éviter un accès DB fragile");
            }

            if ($tend && $tend <= time()) {
                vlog("    ✗ souscription expirée (course {$courseid}), skip.");
                continue;
            }

            $ok = enrol_user_with_dates($userid, $courseid, $tstart ?: time(), $tend ?: 0, $dryrun);
            if ($ok) {
                $enrolled++;
                mtrace("    + inscrit user={$userid} → course={$courseid}" . ($dryrun ? " [SIMULÉ]" : ""));
            }

        } catch (\dml_exception $e) {
            $debug = property_exists($e, 'debuginfo') ? $e->debuginfo : '(no debuginfo)';
            mtrace("DML ERROR loop(course={$courseid}): {$e->getMessage()} | {$debug}");
            continue; // on passe au suivant
        } catch (\Throwable $e) {
            mtrace("ERROR loop(course={$courseid}): " . $e->getMessage());
            continue;
        }
    }

}

mtrace("Terminé. Souscriptions traitées: {$processed}. Inscriptions ajoutées: {$enrolled}. Ignorées: {$skipped}.");
exit(0);
