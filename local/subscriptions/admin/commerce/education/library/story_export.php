<?php
// CampusFR D2.5 — CRM-only authenticated ZIP response, no grant or publication.
declare(strict_types=1);
require_once(__DIR__ . '/_bootstrap.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { throw new \moodle_exception('invalidrequest'); }
require_sesskey();
if (!has_capability('moodle/site:config', \context_system::instance())) {
    throw new \moodle_exception('nopermissions', 'error', '', 'exporter les histoires');
}
$bookid = required_param('bookid', PARAM_INT);
$zip = \local_subscriptions\crm\library\ReaderStoryTransferService::export_story($bookid);
if (headers_sent()) { throw new \RuntimeException(get_string('cfr_fix_story_export_01', 'local_subscriptions')); }
while (ob_get_level() > 0) { ob_end_clean(); }
header('Content-Type: application/zip');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store, max-age=0');
header('Content-Disposition: attachment; filename="' . $zip['filename'] . '"');
header('Content-Length: ' . filesize($zip['path']));
readfile($zip['path']);
exit;
