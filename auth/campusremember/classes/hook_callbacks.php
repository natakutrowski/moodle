<?php
// Restore a persistent Moodle session before course/activity enrolment checks.
namespace auth_campusremember;

defined('MOODLE_INTERNAL') || die();

final class hook_callbacks {
    public static function after_config(\core\hook\after_config $hook): void {
        global $CFG;

        // The hook also runs during bootstrap, CLI, upgrades and tests.
        // Never attempt a database-backed web login in those contexts.
        if ((defined('CLI_SCRIPT') && CLI_SCRIPT) ||
                (defined('AJAX_SCRIPT') && AJAX_SCRIPT) ||
                (defined('WS_SERVER') && WS_SERVER) ||
                (defined('NO_MOODLE_COOKIES') && NO_MOODLE_COOKIES) ||
                (defined('PHPUNIT_TEST') && PHPUNIT_TEST) ||
                (defined('BEHAT_SITE_RUNNING') && BEHAT_SITE_RUNNING) ||
                !empty($CFG->upgraderunning) ||
                ($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET' ||
                empty($_COOKIE['__Host-CampusFRRemember'])) {
            return;
        }
        // Do nothing if authentication plugin is disabled or not installed.
        if (empty($CFG->auth) ||
                !in_array('campusremember', explode(',', $CFG->auth), true) ||
                !get_config('auth_campusremember', 'version')) {
            return;
        }

        // Start narrowly with normal course/activity and CampusFR PHP pages.
        // Login/logout and admin routes continue through Moodle's native flow.
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        if (!is_string($script) ||
                !preg_match('~^/(?:course|mod|local|my|user|grade|report|enrol)/[A-Za-z0-9_./-]+\.php$~D', $script)) {
            return;
        }

        // Preserve the requested page: login state is restored in-place.
        // Failure must never make an otherwise accessible page fail globally.
        try {
            manager::try_restore(false);
        } catch (\Throwable $e) {
            error_log('CampusFR Remember early restore failed: ' . get_class($e));
        }
    }
}
