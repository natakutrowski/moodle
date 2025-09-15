<?php
// local/subscriptions/cli/show_nico_subs.php
define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');

const EMAIL = 'nicolas.kutrowski2@gmail.com';

$user = $DB->get_record('user', ['email' => EMAIL], 'id,username,firstname,lastname,email', IGNORE_MISSING);
if (!$user) {
    echo "❌ User not found: ".EMAIL.PHP_EOL;
    exit(1);
}

echo "👤 {$user->firstname} {$user->lastname} ({$user->email}) [id={$user->id}]\n";
echo "— Subscriptions —\n";

$subs = $DB->get_records('user_subscription', ['userid' => $user->id], 'id ASC');
if (!$subs) {
    echo "(none)\n";
} else {
    foreach ($subs as $s) {
        $plan = $DB->get_record('subscription_plan', ['id' => $s->planid], 'id,name,is_recurring', IGNORE_MISSING);
        $pname = $plan ? $plan->name : '??';
        $rec   = $plan ? (int)$plan->is_recurring : 0;
        printf(
            "#%d  plan=%s [id=%d, recurring=%d]  status=%s  start=%s  end=%s\n",
            $s->id, $pname, $s->planid, $rec, $s->status,
            $s->start_date ? userdate((int)$s->start_date) : '—',
            $s->end_date   ? userdate((int)$s->end_date)   : '—'
        );
    }
}

echo "\n— Course enrolments (manual) —\n";
$sql = "SELECT ue.id, ue.status, c.shortname, FROM_UNIXTIME(ue.timestart) AS ts, FROM_UNIXTIME(ue.timeend) AS te
          FROM {user_enrolments} ue
          JOIN {enrol} e ON e.id = ue.enrolid
          JOIN {course} c ON c.id = e.courseid
         WHERE ue.userid = :uid AND e.enrol = 'manual'
         ORDER BY c.shortname";
$rs = $DB->get_records_sql($sql, ['uid' => $user->id]);
if (!$rs) {
    echo "(none)\n";
} else {
    foreach ($rs as $r) {
        printf("UE#%d  %s  status=%d  start=%s  end=%s\n", $r->id, $r->shortname, $r->status, $r->ts, $r->te);
    }
}
