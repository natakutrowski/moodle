<?php
// CampusFR D2.11 — admin-only, POST+sesskey, on-demand PDF download; never published.
declare(strict_types=1);
require_once(__DIR__ . '/_bootstrap.php');

use local_subscriptions\crm\library\ReaderEditorialService as Editorial;
use local_subscriptions\crm\library\ReaderEditorialPageService as Pages;
use local_subscriptions\crm\library\ReaderStoryLayoutPdf as Exporter;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    throw new moodle_exception('invalidparameter');
}
require_sesskey();
$id = required_param('id', PARAM_INT);
if ($id < 1) { throw new moodle_exception('invalidparameter'); }
$book = Editorial::book($id);
Editorial::collection((int)$book->collectionid);
$pages = Pages::pages($id);
// Always require the exact local source: Moodle classmaps may be stale until purge.
require_once(__DIR__ . '/../../../../classes/crm/library/ReaderStoryLayoutPdf.php');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store, max-age=0');
header('X-Robots-Tag: noindex, nofollow');
$mode = required_param('mode', PARAM_ALPHA);
if (!in_array($mode, ['reading', 'booklet'], true)) {
    throw new moodle_exception('invalidparameter');
}
Exporter::download($id, $book, $pages, $mode);
exit;
