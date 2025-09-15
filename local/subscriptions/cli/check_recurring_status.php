<?php
// local/subscriptions/cli/check_recurring_status.php
// Contrôle du renouvellement récurrent (plan 14 pour Nico), et des enrolments.
//
// Utilisation (par défaut Nico + plan 14) :
//   php local/subscriptions/cli/check_recurring_status.php
//
// Options facultatives :
//   php local/subscriptions/cli/check_recurring_status.php --useremail=user@ex.com --planid=14
//
define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

list($options, $unrecognized) = cli_get_params(
    [
        'useremail' => 'nicolas.kutrowski2@gmail.com',
        'planid'    => 14,
        'help'      => false,
    ],
    [
        'h' => 'help',
    ]
);

if (!empty($options['help'])) {
    echo "Contrôle du renouvellement récurrent (end_date + 1 mois) et des accès cours.\n\n";
    echo "Usage:\n  php local/subscriptions/cli/check_recurring_status.php [--useremail=EMAIL] [--planid=ID]\n";
    exit(0);
}

$useremail = trim((string)$options['useremail']);
$planid    = (int)$options['planid'];

$user = $DB->get_record('user', ['email' => $useremail], 'id,username,firstname,lastname,email,deleted', IGNORE_MISSING);
if (!$user || (int)$user->deleted === 1) {
    cli_error("❌ Utilisateur introuvable ou supprimé: {$useremail}");
}

$plan = $DB->get_record('subscription_plan', ['id' => $planid], '*', IGNORE_MISSING);
if (!$plan) {
    cli_error("❌ Plan introuvable: id={$planid}");
}

$scope = null;
$scopecoursesraw = '';
if (!empty($plan->accessscopeid)) {
    $scope = $DB->get_record('subscription_access_scope', ['id'=>$plan->accessscopeid], 'id,course_ids', IGNORE_MISSING);
    $scopecoursesraw = $scope ? (string)$scope->course_ids : '';
}

function parse_course_ids_from_raw(string $raw): array {
    $raw = trim($raw);
    if ($raw === '') return [];
    $ids = [];
    $first = substr($raw, 0, 1);
    if ($first === '[' || $first === '{') {
        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            foreach ($decoded as $item) {
                $val = is_array($item) ? ($item['id'] ?? $item['courseid'] ?? null) : $item;
                if (is_numeric($val)) $ids[] = (int)$val;
            }
        }
    } else {
        $parts = preg_split('/[,\;\s]+/', $raw, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($parts as $p) if (is_numeric($p)) $ids[] = (int)$p;
    }
    $ids = array_values(array_unique($ids));
    // Optionnel : filtrer les ID inexistants
    global $DB;
    $valid = [];
    foreach ($ids as $id) {
        if ($DB->record_exists('course', ['id'=>$id])) $valid[] = $id;
    }
    return $valid;
}

$courses = parse_course_ids_from_raw($scopecoursesraw);

echo "👤 User: {$user->firstname} {$user->lastname} <{$user->email}> [id={$user->id}]\n";
echo "📦 Plan: #{$plan->id} \"{$plan->name}\"  is_recurring={$plan->is_recurring}  scope={$plan->accessscopeid}\n";
if ($scope) {
    echo "   Scope course_ids: ".($scopecoursesraw !== '' ? $scopecoursesraw : '∅')."\n";
}
echo "—\n";

// 1) Montrer toutes les souscriptions de cet utilisateur sur ce plan
$subs = $DB->get_records('user_subscription', ['userid'=>$user->id, 'planid'=>$plan->id], 'id ASC');
if (!$subs) {
    echo "Aucune souscription sur ce plan.\n";
    exit(0);
}

$now = time();
$activeRecurring = null;

echo "Souscriptions (plan={$plan->id}) :\n";
foreach ($subs as $s) {
    $start = $s->start_date ? userdate((int)$s->start_date) : '—';
    $end   = $s->end_date   ? userdate((int)$s->end_date)   : '—';
    $prov  = property_exists($s,'provider_subscription_id') ? ($s->provider_subscription_id ?: '∅') : '∅';
    $flag  = '';
    if ($s->status === 'active' && $plan->is_recurring && $prov !== '∅') {
        $activeRecurring = $s;
        $flag = '  ← recurring target';
    }
    printf("  #%d  status=%-9s  start=%s  end=%s  provider_sub=%s%s\n",
        $s->id, $s->status, $start, $end, $prov, $flag);
}
echo "—\n";

// Si aucune "active recurring", on prend la dernière active
if (!$activeRecurring) {
    $cand = array_values(array_filter($subs, fn($x) => $x->status === 'active'));
    if ($cand) {
        $activeRecurring = end($cand);
        echo "ℹ️  Aucune active+provider_sub trouvée; on prend la dernière active: #{$activeRecurring->id}\n";
    }
}

// 2) Focus : la souscription récurrente active
if ($activeRecurring) {
    $s = $activeRecurring;
    $daysleft = $s->end_date ? ceil( max(0, $s->end_date - $now) / DAYSECS ) : null;
    echo "🎯 Souscription à surveiller: #{$s->id}\n";
    echo "    start: ".($s->start_date ? userdate((int)$s->start_date) : '—')."\n";
    echo "    end  : ".($s->end_date   ? userdate((int)$s->end_date)   : '—').($daysleft!==null ? "  (J-{$daysleft})" : '')."\n";
    if (!empty($s->provider_subscription_id)) {
        echo "    provider_subscription_id: {$s->provider_subscription_id}\n";
    }
} else {
    echo "⚠️  Pas de souscription active à surveiller sur ce plan.\n";
}

// 3) Afficher les inscriptions cours (manual) pour les cours du scope
echo "—\n";
echo "Inscriptions cours (enrol='manual') ".($courses ? "filtrées par scope" : "(toutes)")." :\n";

$params = ['uid' => $user->id];
$whereCourse = '';
if ($courses) {
    list($inSql, $inParams) = $DB->get_in_or_equal($courses, SQL_PARAMS_NAMED, 'cid');
    $whereCourse = "AND e.courseid $inSql";
    $params = array_merge($params, $inParams);
}

$sql = "SELECT ue.id, ue.status, ue.timestart, ue.timeend, c.shortname
          FROM {user_enrolments} ue
          JOIN {enrol} e ON e.id = ue.enrolid
          JOIN {course} c ON c.id = e.courseid
         WHERE ue.userid = :uid AND e.enrol = 'manual' $whereCourse
         ORDER BY c.shortname";
$rows = $DB->get_records_sql($sql, $params);

if (!$rows) {
    echo "  (aucune)\n";
} else {
    foreach ($rows as $r) {
        $ts = $r->timestart ? userdate((int)$r->timestart) : '—';
        $te = $r->timeend   ? userdate((int)$r->timeend)   : '—';
        // status: 0 = actif, 1 = suspendu
        printf("  UE#%-6d  %-30s  status=%d  start=%s  end=%s\n", $r->id, $r->shortname, $r->status, $ts, $te);
    }
}

echo "—\n";
echo "Tips:\n";
echo "  1) Avance le Stripe Test Clock (customer attaché) de +1 mois.\n";
echo "  2) Relance ce script pour voir 'end' repoussé.\n";
echo "  3) Vérifie que les 'end' des user_enrolments suivent.\n";
