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

$search = trim(optional_param('search', '', PARAM_TEXT));
$page = max(0, optional_param('page', 0, PARAM_INT));
$perpage = 25;
$url = new moodle_url('/local/subscriptions/admin/team/index.php', $search !== '' ? ['search' => $search] : []);
$title = get_string('crm_team_members', 'local_subscriptions');
CrmPageConfigurator::configure($PAGE, $context, $url, $title, 'local-subscriptions-crm-team-page');
$roleids = [];
foreach (CrmRoleTemplates::definitions() as $shortname => $definition) {
    try {
        $roleids[] = (int)CrmRoleAssignments::resolve_role($shortname)->id;
    } catch (Exception $e) {
        // Roles are not installed yet.
    }
}
$members = [];
$total = 0;
// Site administrators are always part of the team, even without CRM role assignments.
$siteadmins = [];
foreach (get_admins() as $siteadmin) {
    $name = fullname($siteadmin);
    if ($search === '' || core_text::strpos(
            core_text::strtolower($name . ' ' . $siteadmin->email), core_text::strtolower($search)) !== false) {
        $siteadmins[(int)$siteadmin->id] = $siteadmin;
    }
}

if ($roleids) {
    [$insql, $params] = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED, 'crrole');
    $params['systemctx'] = $context->id;
    $where = "ra.contextid = :systemctx AND ra.roleid $insql AND u.deleted = 0";
    if ($search !== '') {
        $params['queryname'] = '%' . $DB->sql_like_escape($search) . '%';
        $params['queryemail'] = $params['queryname'];
        $params['querylast'] = $params['queryname'];
        $where .= ' AND (' . $DB->sql_like('u.firstname', ':queryname', false) .
            ' OR ' . $DB->sql_like('u.lastname', ':querylast', false) .
            ' OR ' . $DB->sql_like('u.email', ':queryemail', false) . ')';
    }
    $from = "{user} u JOIN {role_assignments} ra ON ra.userid = u.id";
    $total = (int)$DB->count_records_sql("SELECT COUNT(DISTINCT u.id) FROM $from WHERE $where", $params);
    $members = $DB->get_records_sql("SELECT DISTINCT u.*
        FROM $from WHERE $where ORDER BY u.lastname, u.firstname, u.id", $params, $page * $perpage, $perpage);
}
echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::TEAM, $context);
echo CrmPageHeader::render($title, get_string('crm_team_search', 'local_subscriptions'));
echo html_writer::div(
    html_writer::link(new moodle_url('/local/subscriptions/admin/team/add.php'),
        get_string('crm_team_find_any', 'local_subscriptions'), ['class' => 'btn btn-primary mr-2 mb-3']) .
    html_writer::link(new moodle_url('/local/subscriptions/admin/team/roles.php'),
        get_string('crm_team_permissions', 'local_subscriptions'), ['class' => 'btn btn-outline-secondary mb-3']),
    'mb-2');
if (!$members && !$siteadmins) {
    echo $OUTPUT->notification(get_string('crm_team_no_members', 'local_subscriptions'), 'info');
} else {
    echo html_writer::start_div('row');
    // Show site administrators first, without duplicating a CRM-assigned administrator.
    foreach ($siteadmins as $siteadmin) {
        unset($members[$siteadmin->id]);
        $body = html_writer::tag('h3', s(fullname($siteadmin)), ['class' => 'h5 card-title']) .
            html_writer::tag('p', s($siteadmin->email), ['class' => 'text-muted small']) .
            html_writer::div(html_writer::span(
                get_string('crm_team_super_admin', 'local_subscriptions'),
                'badge badge-primary mr-1'), 'mb-3') .
            html_writer::tag('p', get_string('crm_team_super_admin_help', 'local_subscriptions'),
                ['class' => 'text-muted small']) .
            html_writer::link(new moodle_url('/local/subscriptions/admin/users/view.php',
                ['id' => $siteadmin->id]), get_string('crm_team_view_profile', 'local_subscriptions'),
                ['class' => 'btn btn-outline-primary btn-sm']);
        echo html_writer::div(html_writer::div(html_writer::div($body, 'card-body'), 'card h-100'),
            'col-12 col-md-6 col-xl-4 mb-3');
    }
    foreach ($members as $member) {
        $assignedroles = CrmRoleAssignments::assigned((int)$member->id);
        $badges = '';
        foreach ($assignedroles as $rolename) {
            $badges .= html_writer::tag('span', s($rolename), ['class' => 'badge badge-light border mr-1 mb-1']);
        }
        $body = html_writer::tag('h3', s(fullname($member)), ['class' => 'h5 card-title']) .
            html_writer::tag('p', s($member->email), ['class' => 'text-muted small']) .
            html_writer::div($badges, 'mb-3') .
            html_writer::link(new moodle_url('/local/subscriptions/admin/users/crm_roles.php', ['id' => $member->id]),
                get_string('crm_team_manage_member', 'local_subscriptions'), ['class' => 'btn btn-outline-primary btn-sm']);
        echo html_writer::div(html_writer::div(html_writer::div($body, 'card-body'), 'card h-100'),
            'col-12 col-md-6 col-xl-4 mb-3');
    }
    echo html_writer::end_div();
    echo $OUTPUT->paging_bar($total, $page, $perpage, $url);
}

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
