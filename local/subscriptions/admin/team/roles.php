<?php
require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/accesslib.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\admin\CrmRoleAssignments;
use local_subscriptions\admin\CrmRoleTemplates;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\crm\help\CrmPageHeader;

$context = AdminSecurity::require(Capabilities::MANAGE_CRM_ROLES);
require_capability('moodle/role:assign', $context);

$url = new moodle_url('/local/subscriptions/admin/team/roles.php');
$title = get_string('crm_team_permissions', 'local_subscriptions');
CrmPageConfigurator::configure($PAGE, $context, $url, $title, 'local-subscriptions-crm-team-page');
$defs = CrmRoleTemplates::definitions();
$counts = [];
foreach ($defs as $shortname => $def) {
    try {
        $role = CrmRoleAssignments::resolve_role($shortname);
        $counts[$shortname] = $DB->count_records('role_assignments', ['roleid' => $role->id, 'contextid' => $context->id]);
    } catch (Exception $e) {
        $counts[$shortname] = 0;
    }
}
echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::TEAM, $context);
echo CrmPageHeader::render($title, get_string('crm_team_permissions_note', 'local_subscriptions'));
echo html_writer::link(new moodle_url('/local/subscriptions/admin/team/index.php'),
    get_string('crm_team_back', 'local_subscriptions'), ['class' => 'btn btn-outline-secondary mb-3']);
$table = new html_table();
$table->attributes['class'] = 'generaltable table table-striped';
$table->head = [get_string('role'), get_string('crm_team_role_count', 'local_subscriptions'),
    get_string('crm_team_permissions', 'local_subscriptions'), get_string('edit')];
foreach ($defs as $shortname => $def) {
    try {
        $role = CrmRoleAssignments::resolve_role($shortname);
    } catch (Exception $e) {
        continue;
    }
    // Read live system role permissions: the CR.3 template is NOT the source of truth after customization.
    $live = $DB->get_records('role_capabilities', ['roleid' => $role->id, 'contextid' => $context->id]);
    $caps = [];
    foreach ($live as $permission) {
        if ((int)$permission->permission === CAP_ALLOW &&
                str_starts_with($permission->capability, 'local/subscriptions:')) {
            $caps[] = $permission->capability;
        }
    }
    sort($caps);
    $tags = array_map(static fn($cap) => html_writer::tag('code', s($cap)), $caps);
    $edit = has_capability('moodle/role:manage', $context) ?
        html_writer::link(new moodle_url('/admin/roles/define.php',
            ['action' => 'edit', 'roleid' => $role->id]),
            get_string('crm_team_edit_permissions', 'local_subscriptions'),
            ['class' => 'btn btn-outline-primary btn-sm']) : '—';
    $table->data[] = [s($role->name), (string)$counts[$shortname], implode(', ', $tags), $edit];
}
// Virtual super-administrator entry: effective permissions come from Moodle itself.
$admins = get_admins();
$adminlabel = get_string('crm_team_super_admin', 'local_subscriptions');
$adminnames = [];
foreach ($admins as $siteadmin) {
    $adminnames[] = s(fullname($siteadmin));
}
array_unshift($table->data, [
    s($adminlabel),
    (string)count($admins),
    get_string('crm_team_super_admin_help', 'local_subscriptions') . ' — ' .
        implode(', ', $adminnames),
    '—',
]);
echo html_writer::table($table);
echo html_writer::div(get_string('crm_team_role_edit_warning', 'local_subscriptions'), 'alert alert-info');
echo html_writer::div(get_string('crm_team_history', 'local_subscriptions'), 'text-muted mt-3');
echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
