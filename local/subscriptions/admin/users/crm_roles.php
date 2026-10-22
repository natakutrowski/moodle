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
$userid = required_param('id', PARAM_INT);
$target = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);
// Admin and self-profiles are displayable from the User360 quick actions, but are read-only.
// Never allow a caller to change their own roles or the roles of a Moodle site administrator.
$readonly = is_siteadmin($userid) || (int)$USER->id === $userid || !empty($target->suspended);
if ($readonly && $_SERVER['REQUEST_METHOD'] === 'POST') {
    throw new moodle_exception('invalidparameter');
}
$postaction = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();
    $postaction = required_param('action', PARAM_ALPHA);
    if (!in_array($postaction, ['save', 'course'], true)) {
        throw new moodle_exception('invalidparameter');
    }
}
$url = new moodle_url('/local/subscriptions/admin/users/crm_roles.php', ['id' => $userid]);
if ($postaction === 'save') {
    $selected = optional_param_array('roles', [], PARAM_ALPHANUMEXT);
    $definitions = CrmRoleTemplates::definitions();
    foreach ($selected as $shortname) {
        if (!array_key_exists($shortname, $definitions)) {
            throw new moodle_exception('invalidparameter');
        }
    }
    $assignedbefore = CrmRoleAssignments::assigned($userid);
    $assignable = get_assignable_roles($context, ROLENAME_SHORT);
    $editable = [];
    foreach ($definitions as $shortname => $definition) {
        try {
            $role = CrmRoleAssignments::resolve_role($shortname);
        } catch (Exception $e) {
            continue;
        }
        if (is_siteadmin() || isset($assignable[$role->id])) {
            $editable[$shortname] = true;
        }
    }
    // Never implicitly remove roles that the actor cannot delegate.
    foreach ($definitions as $shortname => $definition) {
        if (!isset($editable[$shortname])) {
            continue;
        }
        $was = isset($assignedbefore[$shortname]);
        $want = in_array($shortname, $selected, true);
        if ($was !== $want) {
            CrmRoleAssignments::change((int)$USER->id, $userid, $shortname, $want ? 'add' : 'remove');
        }
    }
    redirect($url, get_string('crm_roles_saved', 'local_subscriptions'));
}
// Course-context role changes use Moodle's native role API and delegation matrix.
// Only manual role assignments are edited; enrolment-managed roles are untouched.
if ($postaction === 'course') {
    $assignmentid = required_param('assignmentid', PARAM_INT);
    $newroleid = required_param('newroleid', PARAM_INT);
    $assignment = $DB->get_record('role_assignments', ['id' => $assignmentid, 'userid' => $userid], '*', MUST_EXIST);
    $coursecontext = context::instance_by_id((int)$assignment->contextid, MUST_EXIST);
    if ($coursecontext->contextlevel !== CONTEXT_COURSE || $assignment->component !== '' || (int)$assignment->itemid !== 0) {
        throw new moodle_exception('invalidparameter');
    }
    require_capability('moodle/role:assign', $coursecontext);
    $assignable = get_assignable_roles($coursecontext, ROLENAME_SHORT);
    $existingrole = (int)$assignment->roleid;
    if (!isset($assignable[$existingrole]) || !isset($assignable[$newroleid])) {
        throw new required_capability_exception($coursecontext, 'moodle/role:assign', 'nopermissions', '');
    }
    if ($newroleid !== $existingrole) {
        role_assign($newroleid, $userid, $coursecontext->id);
        role_unassign($existingrole, $userid, $coursecontext->id, '', 0);
    }
    redirect($url, get_string('crm_roles_saved', 'local_subscriptions'));
}
CrmPageConfigurator::configure($PAGE, $context, $url,
    get_string('crm_roles_title', 'local_subscriptions'), 'local-subscriptions-crm-team-page');
echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::TEAM, $context);
echo CrmPageHeader::render(fullname($target) . ' — ' . get_string('crm_roles_title', 'local_subscriptions'),
    get_string('crm_roles_description', 'local_subscriptions'));
echo html_writer::link(new moodle_url('/local/subscriptions/admin/team/index.php'),
    get_string('crm_team_back', 'local_subscriptions'), ['class' => 'btn btn-outline-secondary mb-3']);
if ($readonly) {
    // Read-only access: site admins inherit all Moodle/CRM rights independently of role assignments.
    $message = is_siteadmin($userid)
        ? get_string('crm_team_super_admin_help', 'local_subscriptions')
        : get_string('crm_roles_description', 'local_subscriptions');
    echo html_writer::div(s($message), 'alert alert-info');
    if (is_siteadmin($userid)) {
        echo html_writer::div(get_string('crm_team_super_admin', 'local_subscriptions'),
            'badge badge-primary mb-3');
    } else {
        $readonlyroles = CrmRoleAssignments::assigned($userid);
        foreach (CrmRoleTemplates::definitions() as $shortname => $definition) {
            if (isset($readonlyroles[$shortname])) {
                echo html_writer::span(s($definition['name']), 'badge badge-secondary mr-2 mb-2');
            }
        }
    }
    echo html_writer::div(html_writer::link(
        new moodle_url('/local/subscriptions/admin/users/view.php', ['id' => $userid]),
        get_string('crm_roles_back', 'local_subscriptions'), ['class' => 'btn btn-link']), 'mt-3');
    echo CrmWorkspaceRenderer::end();
    echo $OUTPUT->footer();
    exit;
}
$assigned = CrmRoleAssignments::assigned($userid);
$assignable = get_assignable_roles($context, ROLENAME_SHORT);
$rows = [];
foreach (CrmRoleTemplates::definitions() as $shortname => $definition) {
    try {
        $role = CrmRoleAssignments::resolve_role($shortname);
    } catch (Exception $e) {
        continue;
    }
    $canedit = is_siteadmin() || isset($assignable[$role->id]);
    $checked = isset($assigned[$shortname]);
    $checkbox = html_writer::empty_tag('input', [
        'type' => 'checkbox', 'name' => 'roles[]', 'value' => $shortname,
        'class' => 'custom-control-input', 'id' => 'crm-role-' . $role->id,
        'checked' => $checked ? 'checked' : null, 'disabled' => !$canedit ? 'disabled' : null,
    ]);
    $checkbox .= html_writer::tag('label', s($definition['name']), [
        'class' => 'custom-control-label font-weight-bold', 'for' => 'crm-role-' . $role->id]);
    $rows[] = html_writer::tag('div', $checkbox, ['class' => 'custom-control custom-checkbox', 'style' => 'min-width: 310px; white-space: nowrap;']);
    $rows[count($rows) - 1] = html_writer::tag('td', end($rows), ['style' => 'width: 340px; min-width: 340px;']);
    $rows[count($rows) - 1] .= html_writer::tag('td', s(implode(' · ', array_map(
        static fn($cap) => preg_replace('~^local/subscriptions:~', '', $cap), $definition['capabilities']))),
        ['class' => 'text-muted small']);
    $rows[count($rows) - 1] = html_writer::tag('tr', end($rows));
}
echo html_writer::start_tag('form', ['method' => 'post', 'action' => $url]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $userid]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'save']);
echo html_writer::start_div('card mb-4');
echo html_writer::tag('h3', get_string('crm_roles_title', 'local_subscriptions'), ['class' => 'card-header h5']);
echo html_writer::start_div('card-body');
echo html_writer::tag('p', get_string('crm_roles_bulk_hint', 'local_subscriptions'), ['class' => 'text-muted']);
echo html_writer::tag('div', html_writer::tag('table',
    html_writer::tag('tbody', implode('', $rows)), ['class' => 'table table-hover mb-0']),
    ['class' => 'table-responsive']);
echo html_writer::end_div();
echo html_writer::start_div('card-footer text-right');
echo html_writer::empty_tag('input', ['type' => 'submit', 'class' => 'btn btn-primary',
    'value' => get_string('savechanges')]);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_tag('form');
echo html_writer::div(html_writer::link(new moodle_url('/local/subscriptions/admin/users/view.php', ['id' => $userid]),
    get_string('crm_roles_back', 'local_subscriptions'), ['class' => 'btn btn-link']), 'mb-3');
// Read-only visibility of contextual course roles; do not change course enrolments.
$sql = "SELECT ra.id, r.shortname, c.instanceid, co.fullname
          FROM {role_assignments} ra
          JOIN {role} r ON r.id = ra.roleid
          JOIN {context} c ON c.id = ra.contextid
          JOIN {course} co ON co.id = c.instanceid
         WHERE ra.userid = :userid AND c.contextlevel = :level
         ORDER BY co.fullname, r.shortname";
$courseassignments = $DB->get_records_sql($sql, ['userid' => $userid, 'level' => CONTEXT_COURSE]);
echo html_writer::tag('h3', get_string('crm_team_course_roles_editable', 'local_subscriptions'), ['class' => 'mt-4']);
if (!$courseassignments) {
    echo html_writer::tag('p', get_string('crm_team_no_courses', 'local_subscriptions'));
} else {
    foreach ($courseassignments as $assignment) {
        $coursecontext = context_course::instance((int)$assignment->instanceid);
        $name = format_string($assignment->fullname, true, ['context' => $coursecontext]);
        $details = $DB->get_record('role_assignments', ['id' => $assignment->id], 'id,roleid,component,itemid');
        $manual = $details && $details->component === '' && (int)$details->itemid === 0;
        $assignable = has_capability('moodle/role:assign', $coursecontext)
            ? get_assignable_roles($coursecontext, ROLENAME_SHORT) : [];
        $canedit = $manual && isset($assignable[(int)$details->roleid]);
        $body = html_writer::tag('strong', $name) . ' — ' .
            html_writer::tag('span', s($assignment->shortname), ['class' => 'badge badge-light']);
        if ($canedit) {
            $choices = '';
            foreach ($assignable as $roleid => $rolename) {
                $choices .= html_writer::tag('option', s($rolename), [
                    'value' => (int)$roleid,
                    'selected' => (int)$roleid === (int)$details->roleid ? 'selected' : null,
                ]);
            }
            $body .= html_writer::start_tag('form', ['method' => 'post', 'action' => $url,
                'class' => 'd-flex align-items-center flex-wrap mt-2 mb-0', 'style' => 'gap: 8px;']);
            foreach (['sesskey' => sesskey(), 'id' => $userid, 'action' => 'course',
                    'assignmentid' => $assignment->id] as $key => $value) {
                $body .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => $key, 'value' => $value]);
            }
            $body .= html_writer::tag('select', $choices, ['name' => 'newroleid',
                'class' => 'custom-select', 'style' => 'width: auto; min-width: 190px;',
                'aria-label' => get_string('role')]);
            $body .= html_writer::tag('button', get_string('savechanges'),
                ['type' => 'submit', 'class' => 'btn btn-primary btn-sm']);
            $body .= html_writer::end_tag('form');
        } else if (!$manual) {
            $body .= html_writer::div(get_string('crm_team_course_role_managed', 'local_subscriptions'),
                'text-muted small');
        }
        echo html_writer::div($body, 'border-bottom py-3');
    }
}
echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
