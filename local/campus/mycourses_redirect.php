<?php
require(__DIR__.'/../../config.php');
if (is_siteadmin() && optional_param('campus', '', PARAM_ALPHA) === 'pass') {
    // L’admin peut accéder à l’ancienne page si besoin (debug).
    redirect(new moodle_url('/my/courses.php', ['campus' => 'pass']));
}
require_login();
redirect(new moodle_url('/local/campus/mycourses.php'));
