<?php
/** One-time opt-in role defaults for new promotion rights; never resets existing custom permissions. */
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\admin\Capabilities;

[$options, $unrecognised] = cli_get_params(['apply' => false], []);
if ($unrecognised) {
    cli_error('Unknown arguments: ' . implode(', ', $unrecognised));
}
if ($CFG->wwwroot !== 'https://moodle-v2.campusfr.fr' &&
    $CFG->wwwroot !== 'https://www.campusfr.fr') {
    cli_error('Unexpected CampusFR site; refuse role grants.');
}
$defaults = [
    'campus_crm_support' => [Capabilities::VIEW_PROMOTIONS],
    'campus_crm_technical' => [Capabilities::VIEW_PROMOTIONS,
        Capabilities::MANAGE_PROMOTIONS, Capabilities::MANAGE_PROMOTION_XP],
];
$ctx = context_system::instance();
foreach ($defaults as $shortname => $capabilities) {
    $role = $DB->get_record('role', ['shortname' => $shortname], '*', MUST_EXIST);
    if (!str_starts_with((string)$role->description, 'CampusFR CRM system role (CR.3).')) {
        cli_error('Role is not CampusFR-managed: ' . $shortname);
    }
    foreach ($capabilities as $capability) {
        if (!$DB->record_exists('capabilities', ['name' => $capability])) {
            cli_error('Missing capability; upgrade Moodle first: ' . $capability);
        }
        $existing = $DB->get_record('role_capabilities', [
            'roleid' => $role->id, 'contextid' => $ctx->id, 'capability' => $capability],
            '*', IGNORE_MISSING);
        // Never override an existing record, including a deliberate CAP_INHERIT.
        // This CLI is a one-time initial grant, not a role synchroniser.
        if ($existing) {
            mtrace('PRESERVED ' . $shortname . ': ' . $capability
                . ' (permission=' . $existing->permission . ')');
            continue;
        }
        if ($options['apply']) {
            assign_capability($capability, CAP_ALLOW, $role->id, $ctx->id, true);
        }
        mtrace(($options['apply'] ? 'GRANTED ' : 'WOULD GRANT ')
            . $shortname . ': ' . $capability);
    }
}
if ($options['apply']) {
    accesslib_clear_all_caches(true);
}
mtrace('Support promotion XP editing is NOT granted by default. No existing permissions were revoked.');
