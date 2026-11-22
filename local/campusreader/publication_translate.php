<?php
// CampusFR D2.8.1 — server-side proposal for an exact word in a protected, released page.
declare(strict_types=1);
require_once(__DIR__ . '/../../config.php');
require_login();
use local_campusreader\local\vocabulary_word;
use local_campusreader\local\word_translation;
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo '{"success":false}'; exit; }
require_sesskey();
$slug = required_param('slug', PARAM_ALPHANUMEXT);
$chapter = required_param('chapter', PARAM_INT);
$page = required_param('page', PARAM_INT);
$word = required_param('text', PARAM_RAW_TRIMMED);
$target = required_param('target', PARAM_ALPHA);
$paragraphindex = optional_param('paragraph', -1, PARAM_INT);
try {
    [$normalized, $pub] = vocabulary_word::page($slug, $chapter, $page, $word);
    $lang = word_translation::language($target);
    $snapshot = \local_campusreader\local\publication_access::frozen($pub);
    $paragraphs = $snapshot['chapters'][$chapter - 1]['pages'][$page]['paragraphs'] ?? [];
    if ($paragraphindex < -1) { throw new \invalid_parameter_exception('Invalid paragraph'); }
    $context = vocabulary_word::context($paragraphs, $word, $paragraphindex);
    // Store preference before releasing the session: never save a remote translation automatically.
    set_user_preference('campusreader_vocab_language', $lang);
    $userid = (int)$USER->id;
    \core\session\manager::write_close();
    $answer = word_translation::propose($word, $context, $lang, $userid);
    $current = \local_campusreader\local\publication_access::require_customer($slug, $chapter);
    if ((int)$current->revisionid !== (int)$pub->revisionid) { throw new \invalid_parameter_exception('Edition changed'); }
    if ($answer === null) { http_response_code(503); echo '{"success":false,"reason":"unavailable"}'; exit; }
    echo json_encode(['success' => true, 'translation' => $answer, 'language' => $lang], JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    http_response_code(404); echo '{"success":false,"reason":"unavailable"}';
}
