<?php
require_once(__DIR__ . '/../../config.php');
require_login();
require_capability('moodle/site:config', context_system::instance());

$userid = required_param('userid', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);

$course = get_course($courseid);
$user = core_user::get_user($userid);

$instances = enrol_get_instances($courseid, true);
foreach ($instances as $instance) {
    if ($instance->enrol === 'manual') {
        $plugin = enrol_get_plugin('manual');
        if ($plugin) {
            $plugin->unenrol_user($instance, $userid);
        }
    }
}

redirect(new moodle_url($returnurl ?: '/local/subscriptions/manage.php'));
