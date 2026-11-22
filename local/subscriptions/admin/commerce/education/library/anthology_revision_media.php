<?php
// D2.4.2a — CRM only. Never expose filearea anthrev via public pluginfile.
declare(strict_types=1);
require_once(__DIR__ . '/_bootstrap.php');
use local_subscriptions\crm\library\ReaderAnthologyRevisionService as Revs;
$id = required_param('id', PARAM_INT);
$rev = required_param('rev', PARAM_INT);
$media = required_param('media', PARAM_INT);
if ($id < 1 || $rev < 1 || $media < 0 || $media > 12000) {
    throw new moodle_exception('invalidparameter');
}
$file = Revs::media($id, $rev, $media);
// Disable executable or text-based formats inline, even if metadata is corrupt.
$mimetype = $file->get_mimetype();
$allowed = [
    'image/png', 'image/jpeg', 'image/webp', 'audio/mpeg', 'audio/mp3',
    'audio/mp4', 'audio/x-m4a', 'audio/ogg', 'application/ogg',
    'video/mp4', 'video/webm', 'application/pdf',
];
if (!in_array($mimetype, $allowed, true)) {
    http_response_code(404);
    exit;
}
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
header('X-Robots-Tag: noindex, nofollow');
send_stored_file($file, 0, 0, $mimetype === 'application/pdf',
    ['cacheability' => 'private', 'immutable' => false]);
