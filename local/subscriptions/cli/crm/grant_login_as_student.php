<?php
/** Explicit, idempotent permission grant for CampusFR CRM Support only. */
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\admin\Capabilities;

[$options, $unrecognised] = cli_get_params(['apply' => false], []);
if ($unrecognised) {
    cli_error('Unknown parameters: ' . implode(', ', $unrecognised));
}
if (!in_array($CFG->wwwroot, [
        'https://moodle-v2.campusfr.fr',
        'https://www.campusfr.fr',
    ], true)) {
    cli_error('Unexpected Moodle site; grant refused.');
}
$capability = Capabilities::LOGIN_AS_STUDENT;
if (!$DB->record_exists('capabilities', ['name' => $capability])) {
    cli_error('Missing new capability. Upgrade the plugin first.');
}
$role = $DB->get_record('role', ['shortname' => 'campus_crm_support'], '*', MUST_EXIST);
if (!str_starts_with((string)$role->description, 'CampusFR CRM system role (CR.3).')) {
    cli_error('Support role is not CampusFR-managed.');
}
$context = context_system::instance();
$existing = $DB->get_record('role_capabilities', [
    'roleid' => $role->id, 'contextid' => $context->id, 'capability' => $capability,
], '*', IGNORE_MISSING);
if ($existing) {
    mtrace('PRESERVED existing Support permission: ' . $existing->permission);
} else if ($options['apply']) {
    assign_capability($capability, CAP_ALLOW, $role->id, $context->id, true);
    accesslib_clear_all_caches(true);
    mtrace('GRANTED CRM-scoped student impersonation to Support.');
} else {
    mtrace('WOULD GRANT CRM-scoped student impersonation to Support.');
}
// Assert that this script never silently grants global Moodle impersonation.
mtrace('Moodle system-wide moodle/user:loginas: UNCHANGED.');
