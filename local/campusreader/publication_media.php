<?php
// CampusFR D2.4.3 — Never expose immutable media without a fresh Native Commerce rights check.
declare(strict_types=1);
require_once(__DIR__ . '/../../config.php');
require_login();
use local_campusreader\local\publication_access as Access;

$slug = required_param('slug', PARAM_ALPHANUMEXT);
$chapter = required_param('chapter', PARAM_INT);
$index = required_param('media', PARAM_INT);
header('Cache-Control: private, no-store, max-age=0');
header('X-Robots-Tag: noindex, nofollow');
header('X-Content-Type-Options: nosniff');
try {
    $pub = Access::require_customer($slug, $chapter);
    $snapshot = Access::frozen($pub);
    if ($chapter < 1 || $chapter > count($snapshot['chapters']) ||
            !array_key_exists($index, $snapshot['media']) || $index < 0) {
        throw new \RuntimeException('Unknown media');
    }
    $meta = $snapshot['media'][$index];
    if ((int)$meta['chapter'] !== $chapter) { throw new \RuntimeException('Wrong chapter'); }
    $fs = get_file_storage();
    $file = $fs->get_file((int)context_system::instance()->id, 'local_campusreader',
        'anthrev', (int)$pub->revisionid, (string)$meta['filepath'], (string)$meta['filename']);
    if (!$file || $file->is_directory() || (string)$file->get_contenthash() !== (string)$meta['sha1'] ||
            (int)$file->get_filesize() !== (int)$meta['size'] ||
            (string)$file->get_mimetype() !== (string)$meta['mimetype']) {
        throw new \RuntimeException('Media integrity mismatch');
    }
} catch (\Throwable $e) {
    http_response_code(404);
    exit;
}
send_stored_file($file, 0, 0, (string)$meta['area'] === 'pdf',
    ['cacheability' => 'private', 'immutable' => false]);
