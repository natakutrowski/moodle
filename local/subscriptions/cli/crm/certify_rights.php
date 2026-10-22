<?php
/**
 * CR.6 audit of CampusFR role configurations in the CURRENT Moodle instance.
 * Read-only: does not synchronise templates, assign roles or modify user accounts.
 */
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\admin\CrmRoleTemplates;

[$options, $unrecognised] = cli_get_params(['strict' => false, 'help' => false], ['h' => 'help']);
if ($unrecognised) {
    cli_error('Unknown arguments: ' . implode(', ', $unrecognised));
}
if ($options['help']) {
    echo "CRM Rights certification (read-only). --strict also treats template differences as failures.\n";
    exit(0);
}

$context = context_system::instance();
$failed = 0;
$warnings = 0;
$definitions = CrmRoleTemplates::definitions();
$caprecords = $DB->get_records('capabilities', ['component' => 'local_subscriptions']);
$declared = [];
foreach ($caprecords as $caprecord) {
    $declared[$caprecord->name] = true;
}

echo "=== CampusFR CRM Rights / CR.6 — current role configuration ===\n";
echo "Read-only audit. No roles, capabilities or users will be changed.\n\n";
foreach ($definitions as $shortname => $definition) {
    echo "[$shortname] {$definition['name']}\n";
    $role = $DB->get_record('role', ['shortname' => $shortname]);
    if (!$role || !str_starts_with((string)$role->description, 'CampusFR CRM system role (CR.3).')) {
        echo "  FAIL: role not installed or not recognised as CampusFR-managed.\n";
        $failed++;
        continue;
    }
    // Moodle get_records() preserves database keys, which are not guaranteed to start at 0.
    // get_fieldset_select() returns a numerically indexed list of the actual context levels.
    $contextlevels = array_map('intval', $DB->get_fieldset_select(
        'role_context_levels', 'contextlevel', 'roleid = :roleid', ['roleid' => $role->id]
    ));
    if ($contextlevels !== [(int)CONTEXT_SYSTEM]) {
        echo "  FAIL: CRM role must be assignable ONLY at system context.\n";
        $failed++;
    }
    $actual = [];
    foreach ($DB->get_records('role_capabilities', ['roleid' => $role->id,
            'contextid' => $context->id]) as $permission) {
        if ((int)$permission->permission === CAP_ALLOW) {
            $actual[] = $permission->capability;
        }
    }
    sort($actual);
    $wanted = $definition['capabilities'];
    sort($wanted);
    $missing = array_values(array_diff($wanted, $actual));
    $extra = array_values(array_diff($actual, $wanted));
    if ($missing || $extra) {
        echo '  CUSTOMISED: ' . count($missing) . ' removed, ' . count($extra) . " additional grants.\n";
        foreach ($missing as $cap) {
            echo "    - $cap\n";
        }
        foreach ($extra as $cap) {
            echo "    + $cap\n";
        }
        $warnings++;
        if ($options['strict']) {
            $failed++;
        }
    } else {
        echo '  OK: ' . count($actual) . " expected grants.\n";
    }
    foreach ($actual as $cap) {
        // Template differences are allowed by CRM role customisation, but a CRM role
        // must not silently gain Moodle-wide administrative rights.
        if (!isset($declared[$cap]) || in_array($cap,
                ['local/subscriptions:manage_crm_roles'], true)) {
            echo "  SECURITY REVIEW: non-standard/system-level grant $cap\n";
            $failed++;
        }
    }
    $count = $DB->count_records('role_assignments', [
        'roleid' => $role->id, 'contextid' => $context->id]);
    echo "  Assigned team members: $count\n\n";
}
echo "Summary: $failed blocking issue(s), $warnings customised role(s).\n";
echo "Note: template differences are INFORMATIONAL unless --strict is used.\n";
echo "The CLI verifies stored role definitions, not HTTP endpoint access.\n";
exit($failed > 0 ? 2 : 0);
