<?php
/**
 * CampusFR CR.7 -- assign/revert CRM system roles on explicit pre-existing TEST accounts.
 * Default mode is read-only. --apply always requires the exact site URL and a confirmation.
 * Moodle course role assignments, enrolments and account details are NEVER edited.
 */
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\admin\CrmRoleTestPlan;
use local_subscriptions\admin\CrmRoleTemplates;

[$opts, $unknown] = cli_get_params([
    'plan' => '',
    'apply' => false,
    'site' => '',
    'confirm' => '',
    'restore' => '',
    'status' => '',
    'accept-drift' => false,
    'help' => false,
], ['h' => 'help']);
if ($unknown) {
    cli_error('Unknown arguments: ' . implode(', ', $unknown));
}
if ($opts['help']) {
    echo <<<'HELP'
CampusFR CRM / CR.7 - role-based access test accounts (CLI only)

  php local/subscriptions/cli/crm/test_accounts.php --plan=/safe/path/CR7_PLAN.json
       Preview changes; never writes anything.

  php local/subscriptions/cli/crm/test_accounts.php --plan=/safe/path/CR7_PLAN.json \
       --apply --site=https://YOUR-EXACT-DEV-URL --confirm=YES-CR7
       Save snapshot, then replace ONLY system CRM roles on explicitly named test accounts.

  php local/subscriptions/cli/crm/test_accounts.php --status=/snapshot/path.json
       Report current roles vs. the snapshot's intended test roles (read-only).

  php local/subscriptions/cli/crm/test_accounts.php --restore=/snapshot/path.json \
       --apply --site=https://YOUR-EXACT-DEV-URL --confirm=YES-CR7
       Restore snapshot's CRM roles; fails closed if their state has changed since apply.
       Add --accept-drift if you intentionally changed CRM roles during test scenarios.

No account creation, no changes to Moodle enrolments or course roles, and no
non-CRM system role changes. Do not run on production. Use recycled TEST users.
HELP;
    echo "\n";
    exit(0);
}

$modecount = (int)($opts['plan'] !== '') + (int)($opts['restore'] !== '') +
    (int)($opts['status'] !== '');
if ($modecount !== 1) {
    cli_error('Specify exactly one of --plan, --restore or --status.');
}
if ($opts['status'] !== '' && $opts['apply']) {
    cli_error('--status must remain read-only.');
}
if ($opts['accept-drift'] && $opts['restore'] === '') {
    cli_error('--accept-drift is allowed only with --restore.');
}
if ($opts['apply'] && ($opts['confirm'] !== 'YES-CR7' ||
        rtrim((string)$opts['site'], '/') !== rtrim((string)$CFG->wwwroot, '/'))) {
    cli_error('WRITE BLOCKED: require --confirm=YES-CR7 and --site exactly matching ' . $CFG->wwwroot);
}

$system = \context_system::instance();
$site = rtrim((string)$CFG->wwwroot, '/');
$roles = [];
foreach (CrmRoleTemplates::definitions() as $shortname => $definition) {
    $role = $DB->get_record('role', ['shortname' => $shortname]);
    if (!$role || !str_starts_with((string)$role->description, 'CampusFR CRM system role (CR.3).')) {
        cli_error('Missing/unrecognised CRM role: ' . $shortname . '. Run CR6 audit first.');
    }
    $levels = array_map('intval', $DB->get_fieldset_select('role_context_levels',
        'contextlevel', 'roleid = :id', ['id' => $role->id]));
    if ($levels !== [(int)CONTEXT_SYSTEM]) {
        cli_error('Incorrect context levels for ' . $shortname . '. Run CR6 audit first.');
    }
    $roles[$shortname] = (int)$role->id;
}

/** @return array<int,string> */
function cr7_actual_roles(int $userid, int $contextid, array $roles): array {
    global $DB;
    $result = [];
    foreach ($DB->get_records('role_assignments',
            ['userid' => $userid, 'contextid' => $contextid]) as $assignment) {
        $shortname = array_search((int)$assignment->roleid, $roles, true);
        if ($shortname !== false) {
            // Never touch CRM grants managed by another Moodle component.
            if ((string)$assignment->component !== '' || (int)$assignment->itemid !== 0) {
                cli_error("User $userid has a component-managed CRM assignment; aborting safely.");
            }
            $result[] = $shortname;
        } else {
            // A Manager/system role makes an access-restriction test misleading.
            cli_error("User $userid has another system role (ID {$assignment->roleid}). Choose a different test account.");
        }
    }
    sort($result, SORT_STRING);
    return $result;
}

function cr7_verified_user(array $entry): \stdClass {
    global $DB;
    $user = $DB->get_record('user', ['id' => $entry['id']], '*', MUST_EXIST);
    if ($user->username !== $entry['username'] || !empty($user->deleted) ||
            !empty($user->suspended) || is_siteadmin($user->id) ||
            isguestuser($user) || (int)$user->id <= 1) {
        cli_error("Invalid/protected account ID {$entry['id']}: user details or status do not match.");
    }
    return $user;
}

function cr7_snapshot_dir(): string {
    global $CFG;
    // Persistent and outside webroot: Moodle may clean moodledata/temp automatically.
    $directory = $CFG->dataroot . '/local_subscriptions_cr7_snapshots';
    if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
        cli_error('Cannot create secure CR7 snapshot directory.');
    }
    if (!is_writable($directory)) {
        cli_error('CR7 snapshot directory is not writable.');
    }
    return $directory;
}

function cr7_read_snapshot(string $filename, string $site): array {
    $basedir = realpath(cr7_snapshot_dir());
    $path = realpath($filename);
    if (!$path || !$basedir || !str_starts_with($path, $basedir . DIRECTORY_SEPARATOR)) {
        cli_error('Snapshot must be inside moodledata/local_subscriptions_cr7_snapshots.');
    }
    try {
        $snapshot = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    } catch (\JsonException $e) {
        cli_error('Invalid snapshot JSON.');
    }
    if (!is_array($snapshot) || ($snapshot['version'] ?? null) !== 1 ||
            ($snapshot['site'] ?? null) !== $site ||
            empty($snapshot['accounts']) || !is_array($snapshot['accounts'])) {
        cli_error('Invalid CR7 snapshot or wrong Moodle site.');
    }
    // Validate account/role schema against the exact same allowlist as --plan.
    $input = ['version' => 1, 'accounts' => []];
    foreach ($snapshot['accounts'] as $entry) {
        if (!is_array($entry) || !array_key_exists('before', $entry) ||
                !array_key_exists('desired', $entry) || !is_array($entry['before']) ||
                !is_array($entry['desired'])) {
            cli_error('Malformed snapshot account entry.');
        }
        CrmRoleTestPlan::role_set($entry['before']);
        $input['accounts'][] = ['id' => $entry['id'], 'username' => $entry['username'],
            'roles' => $entry['desired']];
    }
    CrmRoleTestPlan::parse(json_encode($input, JSON_THROW_ON_ERROR));
    return $snapshot;
}

function cr7_apply_roles(array $entries, int $contextid, array $roles): void {
    global $DB;
    $tx = $DB->start_delegated_transaction();
    foreach ($entries as $entry) {
        $actual = cr7_actual_roles($entry['id'], $contextid, $roles);
        $diff = CrmRoleTestPlan::diff($actual, $entry['roles']);
        foreach ($diff['remove'] as $shortname) {
            role_unassign($roles[$shortname], $entry['id'], $contextid);
        }
        foreach ($diff['add'] as $shortname) {
            role_assign($roles[$shortname], $entry['id'], $contextid);
        }
        $after = cr7_actual_roles($entry['id'], $contextid, $roles);
        if ($after !== $entry['roles']) {
            throw new \coding_exception('CR7 assignment verification failed for user ' . $entry['id']);
        }
    }
    $tx->allow_commit();
    accesslib_clear_all_caches(true);
}

// Process a plan, or reconstruct a plan from the before values stored in a snapshot.
if ($opts['plan'] !== '') {
    if (!is_file($opts['plan']) || !is_readable($opts['plan'])) {
        cli_error('Cannot read --plan file.');
    }
    $entries = CrmRoleTestPlan::parse(file_get_contents($opts['plan']));
    $snapshot = ['version' => 1, 'site' => $site, 'created' => gmdate('c'), 'accounts' => []];
    foreach ($entries as $entry) {
        cr7_verified_user($entry);
        $before = cr7_actual_roles($entry['id'], $system->id, $roles);
        $snapshot['accounts'][] = [
            'id' => $entry['id'], 'username' => $entry['username'],
            'before' => $before, 'desired' => $entry['roles'],
        ];
        $diff = CrmRoleTestPlan::diff($before, $entry['roles']);
        mtrace("User {$entry['id']} ({$entry['username']}): " .
            'currently [' . implode(', ', $before) . '] -> test [' . implode(', ', $entry['roles']) . ']');
        mtrace('    add: [' . implode(', ', $diff['add']) . '] / remove: [' .
            implode(', ', $diff['remove']) . ']');
    }
    if (!$opts['apply']) {
        mtrace('DRY RUN only: no changes and no snapshot. Use --apply with exact site confirmation.');
        exit(0);
    }
    $directory = cr7_snapshot_dir();
    $filename = tempnam($directory, 'cr7_snapshot_');
    if ($filename === false || file_put_contents($filename,
            json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), LOCK_EX) === false) {
        cli_error('Unable to securely save snapshot; no assignments performed.');
    }
    chmod($filename, 0600);
    mtrace('BASELINE SNAPSHOT SAVED BEFORE CHANGES: ' . $filename);
    cr7_apply_roles($entries, $system->id, $roles);
    mtrace('CR7 TEST ROLES APPLIED. Run --status and perform manual login tests.');
    mtrace('RESTORE using --restore=' . $filename . ' --apply --site=' . $site . ' --confirm=YES-CR7');
    exit(0);
}

$snapshot = cr7_read_snapshot((string)($opts['status'] !== '' ? $opts['status'] : $opts['restore']), $site);
$restoring = $opts['restore'] !== '';
$entries = [];
$drift = false;
foreach ($snapshot['accounts'] as $entry) {
    cr7_verified_user($entry);
    $actual = cr7_actual_roles($entry['id'], $system->id, $roles);
    $expected = CrmRoleTestPlan::role_set($entry['desired']);
    $before = CrmRoleTestPlan::role_set($entry['before']);
    if ($actual !== $expected) {
        $drift = true;
    }
    mtrace("User {$entry['id']} ({$entry['username']}): " .
        'current [' . implode(', ', $actual) . '] / CR7 test [' . implode(', ', $expected) .
        '] / original [' . implode(', ', $before) . ']' .
        ($actual === $expected ? ' [test state OK]' : ' [CHANGED SINCE APPLY]'));
    $entries[] = [
        'id' => $entry['id'], 'username' => $entry['username'], 'roles' => $before,
    ];
}
if (!$restoring || !$opts['apply']) {
    mtrace($restoring ? 'DRY RUN RESTORE: no modifications made.' : 'READ ONLY status: no modifications made.');
    exit(0);
}
if ($drift && !$opts['accept-drift']) {
    cli_error('Roles changed during testing. Review; use --accept-drift with explicit site confirmation if intended.');
}
cr7_apply_roles($entries, $system->id, $roles);
mtrace('ORIGINAL SYSTEM CRM ROLES RESTORED. Course enrolments and roles were never touched.');
