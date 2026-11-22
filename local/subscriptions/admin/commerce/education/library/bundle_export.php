<?php
// D2.6 — Private CRM export, explicit POST only.
declare(strict_types=1);
require_once(__DIR__ . '/_bootstrap.php');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { throw new \moodle_exception('invalidrequest'); }
require_sesskey();
if (!has_capability('moodle/site:config', \context_system::instance())) {
    throw new \moodle_exception('nopermissions', 'error', '', 'export bibliothèque');
}
$kind = required_param('kind', PARAM_ALPHA);
$id = required_param('id', PARAM_INT);
$archive = \local_subscriptions\crm\library\ReaderLibraryBundleService::export($kind, $id);
if (headers_sent()) { throw new \RuntimeException(get_string('cfr_fix_bundle_export_01', 'local_subscriptions')); }
while (ob_get_level() > 0) { ob_end_clean(); }
header('Content-Type: application/zip');
header('X-Content-Type-Options: nosniff');
header('Content-Security-Policy: sandbox');
header('Cache-Control: private, no-store, max-age=0');
header('Content-Disposition: attachment; filename="' . $archive['filename'] . '"');
header('Content-Length: ' . filesize($archive['path']));
readfile($archive['path']);
exit;
