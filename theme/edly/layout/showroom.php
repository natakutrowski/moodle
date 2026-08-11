<?php
// Dedicated CampusFR Showroom shell for Edly.
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/behat/lib.php');

$bodyattributes = $OUTPUT->body_attributes([
    'edly-showroom-shell',
    'edly-storefront-shell',
    'commerce-showroom-layout',
]);

include($CFG->dirroot . '/theme/edly/inc/edly_themehandler.php');
include($CFG->dirroot . '/theme/edly/inc/edly_themehandler_context.php');

$templatecontext['bodyattributes'] = $bodyattributes;
$templatecontext['showroomfullwidth'] = true;
if (!empty($templatecontext['customernavigation']['enabled'])) {
    $templatecontext['customernavigation']['showcart'] = false;
}

// The Showroom keeps the CampusFR topbar but deliberately omits Moodle's
// primary navbar, context header, breadcrumb, drawers and block regions.
echo $OUTPUT->render_from_template('theme_edly/showroom_shell', $templatecontext);
