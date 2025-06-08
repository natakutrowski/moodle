<?php
defined('MOODLE_INTERNAL') || die();
echo $OUTPUT->doctype();

include($CFG->dirroot . '/theme/edly/inc/edly_themehandler.php');

$bodyattributes = $OUTPUT->body_attributes();
include($CFG->dirroot . '/theme/edly/inc/edly_themehandler_context.php');

echo $OUTPUT->render_from_template('theme_edly/edly_dashboard', $templatecontext);