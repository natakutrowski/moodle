<?php
// CampusFR D2.8 — authenticated per-user vocabulary write API.
declare(strict_types=1);
require_once(__DIR__ . '/../../config.php');
require_login();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');
header('X-Robots-Tag: noindex');
function cfr_d28_vocab_stop(int $code): never {
    http_response_code($code); echo '{"error":"unavailable"}'; exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') cfr_d28_vocab_stop(405);
require_sesskey();
if (isguestuser() || !empty($USER->suspended) || !empty($USER->deleted)) cfr_d28_vocab_stop(403);
$action = required_param('action', PARAM_ALPHA);
try {
    if ($action === 'add') {
        $slug = required_param('slug', PARAM_ALPHANUMEXT);
        $chapter = required_param('chapter', PARAM_INT);
        $page = required_param('page', PARAM_INT);
        $word = required_param('word', PARAM_RAW_TRIMMED);
        $translation = optional_param('translation', '', PARAM_RAW_TRIMMED);
        \local_campusreader\local\vocabulary_word::page($slug, $chapter, $page, $word);
        $result = \local_campusreader\local\personal_vocabulary::save(
            (int)$USER->id, $word, $translation, $slug, $chapter, $page);
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    } else if ($action === 'delete') {
        $id = required_param('id', PARAM_INT);
        \local_campusreader\local\personal_vocabulary::remove((int)$USER->id, $id);
        echo '{"deleted":true}';
    } else { cfr_d28_vocab_stop(400); }
} catch (\Throwable $e) { cfr_d28_vocab_stop(404); }
