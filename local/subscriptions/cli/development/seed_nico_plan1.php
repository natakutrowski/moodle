<?php
// local/subscriptions/cli/development/seed_nico_plan1.php
define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/local/subscriptions/classes/subscription_manager.php');

const PLAN_ID   = 1;  // <- demandé
const EMAIL     = 'nicolas.kutrowski2@gmail.com';
const FIRSTNAME = 'Nico';
const LASTNAME  = 'Kutro';
const USERNAME  = 'nicokutro';

function parse_duration_key_days(string $key): int {
    $key = trim(mb_strtolower($key));
    if (preg_match('/^(\d+)\s*(day|week|month|year)s?$/', $key, $m)) {
        $n = (int)$m[1];
        return match ($m[2]) {
            'day'   => max(1, $n),
            'week'  => max(1, 7 * $n),
            'month' => max(1, 30 * $n),  // approx
            'year'  => max(1, 365 * $n), // approx
        };
    }
    return 365;
}

mtrace("== Seeding test data for Nico / plan #".PLAN_ID." ==");

// 1) User (create or update basics)
$user = $DB->get_record('user', ['email' => EMAIL], '*', IGNORE_MISSING);
if (!$user) {
    $user = (object)[
        'auth'        => 'manual',
        'confirmed'   => 1,
        'mnethostid'  => 1,
        'username'    => USERNAME,
        'firstname'   => FIRSTNAME,
        'lastname'    => LASTNAME,
        'email'       => EMAIL,
        'timecreated' => time(),
        'timemodified'=> time(),
    ];
    $user->id = $DB->insert_record('user', $user);
    mtrace("👤 User created id={$user->id} ({$user->email})");
} else {
    $needupdate = false;
    foreach (['username'=>USERNAME,'firstname'=>FIRSTNAME,'lastname'=>LASTNAME] as $k=>$v) {
        if ($user->$k !== $v) { $user->$k = $v; $needupdate = true; }
    }
    if ($needupdate) {
        $user->timemodified = time();
        $DB->update_record('user', $user);
        mtrace("👤 User updated id={$user->id} ({$user->email})");
    } else {
        mtrace("👤 User exists id={$user->id} ({$user->email})");
    }
}

// 2) Plan & scope
$plan = $DB->get_record('subscription_plan', ['id' => PLAN_ID], '*', MUST_EXIST);
mtrace("📦 Plan #{$plan->id} \"{$plan->name}\" (recurring={$plan->is_recurring}) scope={$plan->accessscopeid}");

$scopeCourseIds = '';
if (!empty($plan->accessscopeid)) {
    $scope = $DB->get_record('subscription_access_scope', ['id' => $plan->accessscopeid], 'id,course_ids', IGNORE_MISSING);
    $scopeCourseIds = $scope ? trim((string)$scope->course_ids) : '';
}
if ($scopeCourseIds === '') {
    mtrace("⚠️  Scope has no course_ids — enrol/suspend tests will be limited.");
}

// 3) Create subscriptions
$now = time();
$created = [];

$enrol = function(\stdClass $s) {
    \local_subscriptions\subscription_manager::enrol_user_to_courses(
        (int)$s->userid, (int)$s->planid, (int)($s->start_date ?: time()), (int)($s->end_date ?: 0)
    );
};

$mkActive = function(int $daysleft) use ($DB, $user, $plan, $now, &$created, $enrol) {
    $s = (object)[
        'userid'        => $user->id,
        'planid'        => $plan->id,
        'status'        => 'active',
        'start_date'    => $now - 10 * DAYSECS,
        'end_date'      => $now + $daysleft * DAYSECS,
        'creation_date' => time(),
        'last_update'   => time(),
    ];
    $s->id = $DB->insert_record('user_subscription', $s);
    $created[] = $s->id;
    $enrol($s);
    mtrace("✅ Active: #{$s->id} → expires in {$daysleft} days (" . userdate($s->end_date) . ")");
};

// 3 actives (J-30/J-7/J-1)
foreach ([30, 7, 1] as $d) { $mkActive($d); }

// 1 queued (start in 120s; duration from plan.duration_key if possible)
$qd = parse_duration_key_days((string)$plan->duration_key);
$qstart = $now + 120;
$q = (object)[
    'userid'        => $user->id,
    'planid'        => $plan->id,
    'status'        => 'queued',
    'start_date'    => $qstart,
    'end_date'      => $qstart + $qd * DAYSECS,
    'creation_date' => time(),
    'last_update'   => time(),
];
$q->id = $DB->insert_record('user_subscription', $q);
$created[] = $q->id;
mtrace("🟡 Queued: #{$q->id} → start ".userdate($q->start_date)." ; end ".userdate($q->end_date)." (≈ {$qd} days)");

// 1 active that already expired yesterday (for suspension test)
$e = (object)[
    'userid'        => $user->id,
    'planid'        => $plan->id,
    'status'        => 'active',
    'start_date'    => $now - 20 * DAYSECS,
    'end_date'      => $now - 1 * DAYSECS,
    'creation_date' => time(),
    'last_update'   => time(),
];
$e->id = $DB->insert_record('user_subscription', $e);
$created[] = $e->id;
$enrol($e);
mtrace("🛑 Active (expired-yesterday candidate): #{$e->id}");

mtrace("—");
mtrace("Done. Created subscriptions: ".implode(', ', $created));
mtrace("Tip: run 'php local/subscriptions/cli/maintenance/cron/run_subs_crons.php' in ~2 minutes, then:");
mtrace("     'php local/subscriptions/cli/development/show_nico_subs.php'");
