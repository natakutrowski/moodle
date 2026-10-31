<?php
// CampusFR opt-in persistent sign-in authentication extension.
// GPL v3 or later.
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/authlib.php');

class auth_plugin_campusremember extends auth_plugin_base {
    public function __construct() {
        $this->authtype = 'campusremember';
        $this->config = get_config('auth_campusremember');
    }

    // This plugin NEVER authenticates user/password pairs; Moodle auth_email still does that.
    public function user_login($username, $password) {
        return false;
    }

    public function pre_loginpage_hook() {
        \auth_campusremember\manager::try_restore();
    }

    public function loginpage_hook() {
        global $PAGE;
        // Restore only on GET; never intercept a submitted login form.
        \auth_campusremember\manager::try_restore();
        $PAGE->requires->js(new moodle_url('/auth/campusremember/js/login.js'));
    }

    public function prelogout_hook() {
        \auth_campusremember\manager::logout_this_device();
    }
}
