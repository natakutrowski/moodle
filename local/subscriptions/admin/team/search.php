<?php
/** Authorized JSON suggestions for the existing CRM team search boxes. */
require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/accesslib.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\admin\CrmRoleTemplates;
use local_subscriptions\admin\CrmRoleAssignments;

$context = AdminSecurity::require(Capabilities::MANAGE_CRM_ROLES);
require_capability('moodle/role:assign', $context);
require_sesskey();
$q = trim(required_param('q', PARAM_TEXT));
$scope = optional_param('scope', 'all', PARAM_ALPHA);
if (!in_array($scope, ['all', 'members'], true)) {
    throw new moodle_exception('invalidparameter');
}
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if (core_text::strlen($q) < 2 || core_text::strlen($q) > 100) {
    echo json_encode(['items' => []]);
    exit;
}
$like = '%' . $DB->sql_like_escape($q) . '%';
$params = ['first' => $like, 'last' => $like, 'email' => $like, 'guest' => $CFG->siteguest];
$where = 'u.deleted = 0 AND u.suspended = 0 AND u.id <> :guest AND (' .
    $DB->sql_like('u.firstname', ':first', false) . ' OR ' .
    $DB->sql_like('u.lastname', ':last', false) . ' OR ' .
    $DB->sql_like('u.email', ':email', false) . ')';
$join = '';
if ($scope === 'members') {
    $roleids = [];
    foreach (CrmRoleTemplates::definitions() as $shortname => $definition) {
        try {
            $roleids[] = (int)CrmRoleAssignments::resolve_role($shortname)->id;
        } catch (Exception $e) {
            // Role not installed.
        }
    }
    if (!$roleids) {
        echo json_encode(['items' => []]);
        exit;
    }
    [$insql, $inparams] = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED, 'rid');
    $params += $inparams;
    $params['ctx'] = $context->id;
    $join = ' JOIN {role_assignments} ra ON ra.userid = u.id';
    $where .= " AND ra.contextid = :ctx AND ra.roleid $insql";
}
$users = $DB->get_records_sql("SELECT DISTINCT u.* FROM {user} u $join WHERE $where
    ORDER BY u.lastname, u.firstname, u.id", $params, 0, 12);
$items = [];
foreach ($users as $user) {
    if (is_siteadmin($user->id) || (int)$user->id === (int)$USER->id) {
        continue;
    }
    $items[] = [
        'label' => fullname($user),
        'email' => $user->email,
        'url' => (new moodle_url('/local/subscriptions/admin/users/crm_roles.php', ['id' => $user->id]))->out(false),
    ];
}
echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE);
