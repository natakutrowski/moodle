<?php
// CampusFR D2.12.2 — private PDF downloads for purchased published stories.
// No course-based fallback, no draft/admin bypass, no public/CDN URL.
declare(strict_types=1);
require_once(__DIR__ . '/../../config.php');
require_login();

use local_campusreader\local\publication_pdf;
use local_subscriptions\crm\library\ReaderStoryLayoutPdf;

$slug = required_param('slug', PARAM_RAW_TRIMMED);
$chapter = required_param('chapter', PARAM_INT);
$mode = required_param('mode', PARAM_ALPHA);

try {
    $source = publication_pdf::resolve($slug, $chapter, $mode);
} catch (\Throwable $e) {
    // Do not disclose whether the slug exists, which chapter is locked, or whether
    // the student owns a Commerce grant. Also avoid leaking internals via Moodle debug.
    http_response_code(404);
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: private, no-store, max-age=0');
    header('X-Content-Type-Options: nosniff');
    header('X-Robots-Tag: noindex, nofollow');
    echo '<!doctype html><html lang="' . s(current_language()) . '"><head><meta charset="utf-8">' .
        '<meta name="viewport" content="width=device-width,initial-scale=1">' .
        '<title>' . s(get_string('cfr_fix_pdf_unavailable_title', 'local_campusreader')) . '</title></head><body>' .
        '<main><h1>' . s(get_string('cfr_fix_pdf_unavailable_heading', 'local_campusreader')) . '</h1><p>' . s(get_string('cfr_fix_pdf_unavailable_reason', 'local_campusreader')) . '</p></main>' .
        '</body></html>';
    exit;
}

// PDF generation happens only after permissions, frozen snapshot and media
// metadata have been checked. TCPDF sends the attachment to the browser.
header('Cache-Control: private, no-store, max-age=0');
header('X-Content-Type-Options: nosniff');
header('X-Robots-Tag: noindex, nofollow');
ReaderStoryLayoutPdf::download($source['bookid'], $source['book'], $source['pages'],
    $source['mode'], $source['imageprovider']);
exit;
