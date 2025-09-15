<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/local/subscriptions/classes/subscription_manager.php');

list($opts,) = cli_get_params([
    'useremail' => 'nicolas.kutrowski2@gmail.com',
    'planid'    => 14,
    'help'      => false,
], ['h'=>'help']);

if (!empty($opts['help'])) {
    echo "Resynchronise les inscriptions cours (timestart/timeend) depuis la souscription active.\n";
    echo "Usage: php local/subscriptions/cli/resync_enrolments.php --useremail=... --planid=...\n";
    exit(0);
}

$user = $DB->get_record('user', ['email'=>$opts['useremail'], 'deleted'=>0], '*', MUST_EXIST);
$plan = $DB->get_record('subscription_plan', ['id'=>(int)$opts['planid']], '*', MUST_EXIST);

// On prend la souscription active la plus récente pour ce plan
$sub = $DB->get_record_sql("
    SELECT * FROM {user_subscription}
     WHERE userid = :uid AND planid = :pid AND status = 'active'
  ORDER BY end_date DESC, id DESC
     LIMIT 1
", ['uid'=>$user->id, 'pid'=>$plan->id]);

if (!$sub) {
    cli_error("Aucune souscription active trouvée pour {$user->email} / plan {$plan->id}");
}

mtrace("Resync enrolments for {$user->email}, plan #{$plan->id}, sub #{$sub->id}");
\local_subscriptions\subscription_manager::enrol_user_to_courses(
    (int)$sub->userid, (int)$sub->planid, (int)($sub->start_date ?: time()), (int)$sub->end_date
);

// Affiche le résultat
$rs = $DB->get_records_sql("
SELECT c.shortname, ue.status, FROM_UNIXTIME(ue.timestart) ts, FROM_UNIXTIME(ue.timeend) te
FROM {user_enrolments} ue
JOIN {enrol} e ON e.id = ue.enrolid
JOIN {course} c ON c.id = e.courseid
WHERE ue.userid = :uid AND e.enrol='manual'
ORDER BY c.shortname
", ['uid'=>$user->id]);

foreach ($rs as $r) {
    printf("%-30s  status=%d  start=%s  end=%s\n", $r->shortname, $r->status, $r->ts, $r->te);
}
