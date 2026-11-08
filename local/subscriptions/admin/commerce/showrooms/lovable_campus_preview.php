<?php
// Gustave Builder — normal CampusFR showroom viewport, private source, no publication.
declare(strict_types=1);

// This file is included ONLY from admin/commerce/showrooms/preview.php, after
// require_login() and manage_showrooms capability checks. Never expose it as
// a standalone route that could skip the native admin preview authorisation.
defined('MOODLE_INTERNAL') || die();
if (!isset($lovablejob, $context) || !is_string($lovablejob)
        || $lovablejob === '' || !isset($USER, $SESSION)) {
    throw new moodle_exception('nopermissions', 'error');
}
require_once(__DIR__ . '/../../../classes/commerce/showroom/bridge/CommerceShowroomBridgeCompileService.php');
require_once(__DIR__ . '/../../../classes/commerce/showroom/bridge/CommerceShowroomBridgePreviewService.php');

use local_subscriptions\commerce\showroom\bridge\CommerceShowroomBridgeCompileService as Builder;
use local_subscriptions\commerce\showroom\bridge\CommerceShowroomBridgePreviewService as Preview;

$sessionjob = $SESSION->campusfrlovablejob ?? null;
if (!is_array($sessionjob)) {
    throw new moodle_exception('nopermissions', 'error', '', 'Aucun brouillon Lovable dans la session.');
}
$job = Builder::verify_session($sessionjob, (int)$USER->id, $lovablejob);
$id = $job['id'];
// Require the original non-publishable ZIP and a successful compilation state;
// the iframe endpoint independently performs exactly the same validation.
Preview::approved_archive($job['dir'], $id);

$url = new moodle_url('/local/subscriptions/admin/commerce/showrooms/preview.php', [
    'lovablejob' => $lovablejob,
]);
$frameurl = new moodle_url('/local/subscriptions/admin/commerce/showrooms/lovable_preview.php', [
    'job' => $lovablejob,
    'frame' => 1,
]);
$backurl = new moodle_url('/local/subscriptions/admin/commerce/showrooms/lovable_build.php', [
    'job' => $lovablejob,
]);
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('showroom');
$PAGE->add_body_class('commerce-showroom-page');
$PAGE->add_body_class('commerce-showroom-admin-preview');
$PAGE->add_body_class('campusfr-lovable-canvas-preview');
$PAGE->set_title('Aperçu CampusFR — ' . $id);
$PAGE->set_heading('Aperçu CampusFR — ' . $id);
$PAGE->requires->css(new moodle_url('/local/subscriptions/styles/showroom.css'));
$PAGE->requires->css(new moodle_url('/local/subscriptions/styles/lovable_campus_preview.css'));

// The frame is intentionally viewport-sized, not a small CRM iframe. Its own
// scrollbar and viewport preserve sticky sections and 100vh breakpoints, while
// the opaque-origin sandbox prevents imported React from reading Moodle cookies.
// A floating status/control bar is in the TRUSTED parent document only.

header('Cache-Control: private, no-store, max-age=0');
header('X-Robots-Tag: noindex, nofollow, noarchive');
header('Referrer-Policy: no-referrer');

echo $OUTPUT->header();
echo html_writer::tag('iframe', '', [
    'class' => 'campusfr-lovable-campus-canvas-preview-frame',
    'src' => $frameurl->out(false),
    'title' => 'Showroom Articles / Alf — prévisualisation CampusFR',
    'sandbox' => 'allow-scripts',
    'referrerpolicy' => 'no-referrer',
    'loading' => 'eager',
]);
echo html_writer::start_div('campusfr-lovable-campus-toolbar', [
    'role' => 'note',
    'aria-label' => 'Mode aperçu, non publié',
]);
echo html_writer::tag('span', 'Aperçu CampusFR · non publié');
echo html_writer::link($backurl, 'Retour au CRM');
echo html_writer::end_div();
echo $OUTPUT->footer();
