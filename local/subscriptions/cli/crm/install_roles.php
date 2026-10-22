<?php
/** Install/update system CRM role profiles; dry-run by default. Never assigns users. */
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/admin/roles/lib.php');

use local_subscriptions\admin\CrmRoleTemplates;

list($options, $unrecognized) = cli_get_params(['apply' => false, 'help' => false], ['h' => 'help']);
if ($unrecognized) {
    cli_error('Unknown arguments: ' . implode(', ', $unrecognized));
}
if ($options['help']) {
    echo "CampusFR CRM profiles. Default: dry-run. --apply creates/updates roles and capabilities.\n";
    exit(0);
}
$apply = (bool)$options['apply'];
$context = context_system::instance();
$definitions = CrmRoleTemplates::definitions();
foreach ($definitions as $shortname => $definition) {
    $role = $DB->get_record('role', ['shortname' => $shortname]);
    mtrace(($apply ? 'APPLY' : 'DRY RUN') . ' ' . $shortname . ': ' . ($role ? 'existing' : 'create'));
    if (!$apply) {
        continue;
    }
    if (!$role) {
        $roleid = create_role($definition['name'], $shortname,
            'CampusFR CRM system role (CR.3). No Moodle administration rights.', '');
        set_role_contextlevels($roleid, [CONTEXT_SYSTEM]);
    } else {
        $roleid = (int)$role->id;
        // Never alter arbitrary pre-existing roles with the same shortname.
        if (!str_starts_with((string)$role->description, 'CampusFR CRM system role (CR.3).')) {
            cli_error('Role shortname collision: ' . $shortname . '. Resolve manually before applying.');
        }
    }
    $wanted = array_fill_keys($definition['capabilities'], true);
    foreach ($DB->get_records('capabilities', ['component' => 'local_subscriptions']) as $record) {
        $capability = $record->name;
        if (str_starts_with($capability, 'local/subscriptions:')) {
            assign_capability($capability, isset($wanted[$capability]) ? CAP_ALLOW : CAP_INHERIT,
                $roleid, $context->id, true);
        }
    }
    mtrace('  ' . count($wanted) . ' permitted capabilities');
}
if ($apply) {
    accesslib_clear_all_caches(true);
}
mtrace($apply ? 'CRM role profiles synced; NO user assignments performed.' : 'No changes made. Use --apply after review.');
