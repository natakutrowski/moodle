<?php
require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/accesslib.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\crm\help\CrmPageHeader;

$context = AdminSecurity::require(Capabilities::MANAGE_CRM_ROLES);
require_capability('moodle/role:assign', $context);

$search = trim(optional_param('search', '', PARAM_TEXT));
$url = new moodle_url('/local/subscriptions/admin/team/add.php');
$title = get_string('crm_team_add_member', 'local_subscriptions');
CrmPageConfigurator::configure($PAGE, $context, $url, $title, 'local-subscriptions-crm-team-page');
$users = [];
if (core_text::strlen($search) >= 2) {
    $like = '%' . $DB->sql_like_escape($search) . '%';
    $users = $DB->get_records_sql('SELECT * FROM {user}
        WHERE deleted = 0 AND suspended = 0 AND id <> :guest AND (' .
        $DB->sql_like('firstname', ':first', false) . ' OR ' .
        $DB->sql_like('lastname', ':last', false) . ' OR ' .
        $DB->sql_like('email', ':email', false) . ')
        ORDER BY lastname, firstname, id',
        ['guest' => $CFG->siteguest, 'first' => $like, 'last' => $like, 'email' => $like], 0, 30);
}
echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::TEAM, $context);
echo CrmPageHeader::render($title, get_string('crm_team_search', 'local_subscriptions'));
echo html_writer::link(new moodle_url('/local/subscriptions/admin/team/index.php'),
    get_string('crm_team_back', 'local_subscriptions'), ['class' => 'btn btn-outline-secondary mb-3']);
echo html_writer::start_tag('form', ['method' => 'get', 'action' => $url, 'class' => 'mb-3', 'data-crm-team-search' => 'all']);
echo html_writer::empty_tag('input', ['autocomplete' => 'off', 'type' => 'search', 'name' => 'search', 'value' => $search, 'class' => 'form-control', 'placeholder' => get_string('crm_team_search', 'local_subscriptions')]);
echo html_writer::empty_tag('input', ['type' => 'submit', 'class' => 'btn btn-primary mt-2', 'value' => get_string('crm_team_search_button', 'local_subscriptions')]);
echo html_writer::div('', 'crm-team-suggestions', ['id' => 'crm-team-suggestions', 'role' => 'status', 'aria-live' => 'polite']);
echo html_writer::end_tag('form');
if (core_text::strlen($search) >= 2 && !$users) {
    echo $OUTPUT->notification(get_string('crm_team_user_search_empty', 'local_subscriptions'), 'info');
}
foreach ($users as $user) {
    if (is_siteadmin($user->id) || (int)$user->id === (int)$USER->id) { continue; }
    echo html_writer::div(s(fullname($user)) . ' — ' . s($user->email) . ' ' .
        html_writer::link(new moodle_url('/local/subscriptions/admin/users/crm_roles.php', ['id' => $user->id]),
            get_string('crm_team_add_member', 'local_subscriptions'), ['class' => 'btn btn-sm btn-outline-primary']),
        'border-bottom py-2');
}
require_once(__DIR__ . '/search_widget.php');
crm_team_init_search($PAGE);
echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
