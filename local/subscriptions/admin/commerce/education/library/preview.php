<?php
// CampusFR D2.2.1 — CRM-only private media preview, no public file access.
declare(strict_types=1);
require_once(__DIR__ . '/_bootstrap.php');

use local_subscriptions\crm\library\ReaderEditorialService as Editorial;

$id = required_param('id', PARAM_INT);
$area = required_param('area', PARAM_ALPHA);
$page = optional_param('page', 0, PARAM_INT);

// Never accept filenames, paths, contexts or user IDs from the request.
$types = [
    'cover' => ['image/png', 'image/jpeg', 'image/webp'],
    'pageimage' => ['image/png', 'image/jpeg', 'image/webp'],
    'narration' => ['audio/mpeg', 'audio/mp3', 'audio/mp4', 'audio/x-m4a', 'audio/ogg', 'application/ogg'],
    'video' => ['video/mp4', 'video/webm'],
    'pdf' => ['application/pdf'],
];
if ($id < 1 || !isset($types[$area]) || $page < 0 || $page > 99 || ($area !== 'pageimage' && $page !== 0)) {
    throw new moodle_exception('invalidparameter');
}
// The CRM bootstrap above already requires the editorial capability + login.
Editorial::book($id);
$fs = get_file_storage();
$context = \context_system::instance();
$filepath = $area === 'pageimage' ? '/p' . $page . '/' : '/';
$matches = [];
foreach ($fs->get_area_files((int)$context->id, 'local_campusreader', $area, $id, 'id ASC', false) as $file) {
    if (!$file->is_directory() && $file->get_filepath() === $filepath) {
        $matches[] = $file;
    }
}
if (count($matches) !== 1) {
    http_response_code(404);
    exit;
}
$file = $matches[0];
if ($file->get_filesize() < 1 || !in_array($file->get_mimetype(), $types[$area], true)) {
    http_response_code(404);
    exit;
}
// No page image if the page doesn't explicitly reference this exact file.
if ($area === 'pageimage') {
    global $DB;
    $row = $DB->get_record('local_cr_page', ['bookid' => $id, 'pageindex' => $page], 'id, imagefile');
    if (!$row || (string)$row->imagefile !== $file->get_filename()) {
        http_response_code(404);
        exit;
    }
}
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
header('X-Robots-Tag: noindex, nofollow');
// PDFs always download, even for CRM admins: never render active PDF content inline.
send_stored_file($file, 0, 0, $area === 'pdf', ['cacheability' => 'private', 'immutable' => false]);
