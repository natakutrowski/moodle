<?php
// CampusFR D2.8 — word TTS on frozen, grant-protected published content ONLY.
declare(strict_types=1);
require_once(__DIR__ . '/../../config.php');
require_login();
use local_campusreader\local\vocabulary_word;
use local_campusreader\local\word_pronunciation;
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');
header('X-Robots-Tag: noindex');
function cfr_d28_tts_stop(int $code): never {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo '{"error":"unavailable"}'; exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') cfr_d28_tts_stop(405);
require_sesskey();
$slug = required_param('slug', PARAM_ALPHANUMEXT);
$chapter = required_param('chapter', PARAM_INT);
$page = required_param('page', PARAM_INT);
$word = required_param('text', PARAM_RAW_TRIMMED);
$voice = optional_param('voice', 'Lea', PARAM_ALPHA);
if (!word_pronunciation::supported_voice($voice)) cfr_d28_tts_stop(400);
try {
    [$normalized, $publication] = vocabulary_word::page($slug, $chapter, $page, $word);
} catch (\Throwable $e) { cfr_d28_tts_stop(404); }
// Release Moodle's session lock before the external call; parallel translation can proceed.
$userid = (int)$USER->id;
\core\session\manager::write_close();
$audio = word_pronunciation::audio($word, $userid, $voice);
try {
    $current = \local_campusreader\local\publication_access::require_customer($slug, $chapter);
    if ((int)$current->revisionid !== (int)$publication->revisionid) { cfr_d28_tts_stop(404); }
} catch (\Throwable $e) { cfr_d28_tts_stop(404); }
if ($audio === null) {
    // Only transient, explicitly classified failures may trigger client retry.
    $retry = word_pronunciation::retry_after_seconds();
    if ($retry > 0) { header('Retry-After: ' . $retry); }
    cfr_d28_tts_stop(503);
}
header('Content-Type: audio/mpeg');
header('Content-Length: ' . strlen($audio));
echo $audio;
