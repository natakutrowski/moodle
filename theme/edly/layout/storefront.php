<?php
// Native CampusFR Storefront shell for Edly.
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/behat/lib.php');

$bodyattributes = $OUTPUT->body_attributes([
    'edly-storefront-shell',
    'edly-storefront-shell--' . $PAGE->pagelayout,
]);

include($CFG->dirroot . '/theme/edly/inc/edly_themehandler.php');
include($CFG->dirroot . '/theme/edly/inc/edly_themehandler_context.php');

$minimal = in_array($PAGE->pagelayout, ['storefront_landing', 'storefront_immersive'], true);
$templatecontext['storefrontminimal'] = $minimal;
$templatecontext['storefrontfullwidth'] = $PAGE->pagelayout !== 'storefront';
$templatecontext['bodyattributes'] = $bodyattributes;

echo $OUTPUT->render_from_template('theme_edly/storefront_shell', $templatecontext);
