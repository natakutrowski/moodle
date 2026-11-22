<?php
// CampusFR D1: protected, bounded neural pronunciation of text actually present in purchased books.
// GPL-3.0-or-later. No direct browser access to provider credentials.
declare(strict_types=1);
require_once(__DIR__ . '/../../config.php');
require_login();

use local_campusreader\local\collection_access;
use local_campusreader\local\collection_repository;

global $CFG, $USER;

// Never leak a book's existence when the user lacks access.
function cfr_d1_stop(int $code): never {
    http_response_code($code);
    header('Cache-Control: no-store, private');
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $code === 503 ? 'unavailable' : 'denied']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    cfr_d1_stop(405);
}
require_sesskey();
$id = required_param('book', PARAM_INT);
$index = required_param('page', PARAM_INT);
$text = required_param('text', PARAM_RAW_TRIMMED);
if ($id <= 0 || $index < 0 || !is_string($text) || $text === '' ||
        strlen($text) > 320 || mb_strlen($text, 'UTF-8') > 160 ||
        !mb_check_encoding($text, 'UTF-8') ||
        preg_match('/[\x00-\x1f\x7f]|\R/u', $text) ||
        !preg_match('/\p{L}/u', $text)) {
    cfr_d1_stop(400);
}
try {
    [$book, $collection, $slot] = collection_repository::book_bundle($id);
    if (!collection_access::can_read_book($book, $collection, $slot)) {
        cfr_d1_stop(404);
    }
    $pages = collection_repository::pages($id);
    if (!array_key_exists($index, $pages)) {
        cfr_d1_stop(404);
    }
    $found = false;
    foreach ($pages[$index]['paragraphs'] as $paragraph) {
        // Exact text from the server, not arbitrary user-supplied TTS text.
        if (mb_strpos((string)$paragraph, $text, 0, 'UTF-8') !== false) {
            $found = true;
            break;
        }
    }
    if (!$found) {
        cfr_d1_stop(404);
    }
} catch (moodle_exception $e) {
    cfr_d1_stop(404);
}

// Disabled unless an administrator supplies a secret in the private Moodle config.php.
$key = isset($CFG->campusreader_azure_tts_key) ? (string)$CFG->campusreader_azure_tts_key : '';
$region = isset($CFG->campusreader_azure_tts_region) ? (string)$CFG->campusreader_azure_tts_region : 'francecentral';
$voice = isset($CFG->campusreader_azure_tts_voice) ? (string)$CFG->campusreader_azure_tts_voice : 'fr-FR-DeniseNeural';
if ($key === '' || !in_array($region, ['francecentral', 'westeurope', 'northeurope', 'swedencentral'], true) ||
        !in_array($voice, ['fr-FR-DeniseNeural','fr-FR-HenriNeural','fr-FR-CelesteNeural','fr-FR-AlainNeural'], true)) {
    cfr_d1_stop(503);
}

$directory = $CFG->dataroot . '/local_campusreader_d1_tts';
if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
    cfr_d1_stop(503);
}
@chmod($directory, 0700);
$cachekey = hash('sha256', 'azure-v1|' . $region . '|' . $voice . '|' . $text);
$path = $directory . '/' . $cachekey . '.mp3';
$lockpath = $directory . '/' . $cachekey . '.lock';
$lock = @fopen($lockpath, 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
    if ($lock) fclose($lock);
    cfr_d1_stop(503);
}
try {
    if (!is_file($path) || filesize($path) < 100) {
        // Per-student limits count cache MISS requests only, and avoid paid abuse.
        $quota = $directory . '/quota-' . (int)$USER->id . '.json';
        $fh = fopen($quota, 'c+');
        if (!$fh || !flock($fh, LOCK_EX)) {
            if ($fh) fclose($fh);
            cfr_d1_stop(503);
        }
        $now = time();
        $raw = stream_get_contents($fh);
        $counts = json_decode($raw ?: '{}', true);
        if (!is_array($counts)) $counts = [];
        $min = (int)($counts['min'] ?? 0);
        $day = (int)($counts['day'] ?? 0);
        $minuteCount = ($now - $min < 60) ? (int)($counts['minuteCount'] ?? 0) : 0;
        $dayCount = ($now - $day < 86400) ? (int)($counts['dayCount'] ?? 0) : 0;
        if ($minuteCount >= 20 || $dayCount >= 300) {
            flock($fh, LOCK_UN); fclose($fh);
            cfr_d1_stop(429);
        }
        $counts = ['min' => $minuteCount ? $min : $now, 'minuteCount' => $minuteCount + 1,
            'day' => $dayCount ? $day : $now, 'dayCount' => $dayCount + 1];
        rewind($fh); ftruncate($fh, 0); fwrite($fh, json_encode($counts)); fflush($fh);
        flock($fh, LOCK_UN); fclose($fh);

        if (!function_exists('curl_init')) cfr_d1_stop(503);
        $ssml = '<?xml version="1.0" encoding="utf-8"?><speak version="1.0" ' .
            'xmlns="http://www.w3.org/2001/10/synthesis" xml:lang="fr-FR"><voice name="' .
            $voice . '"><prosody rate="-7%">' . htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8') .
            '</prosody></voice></speak>';
        $url = 'https://' . $region . '.tts.speech.microsoft.com/cognitiveservices/v1';
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $ssml,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => [
                'Ocp-Apim-Subscription-Key: ' . $key,
                'Content-Type: application/ssml+xml',
                'X-Microsoft-OutputFormat: audio-24khz-48kbitrate-mono-mp3',
                'User-Agent: CampusFR-Reader/1.0',
            ],
        ]);
        $audio = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        if ($httpcode !== 200 || !is_string($audio) || strlen($audio) < 100 || strlen($audio) > 600000 ||
                substr($audio, 0, 3) !== 'ID3' && substr($audio, 0, 2) !== "\xff\xfb" &&
                substr($audio, 0, 2) !== "\xff\xf3") {
            // Never output provider errors, tokens or book text in HTTP responses.
            cfr_d1_stop(503);
        }
        $temp = tempnam($directory, 'new-');
        if (!$temp) cfr_d1_stop(503);
        if (file_put_contents($temp, $audio) !== strlen($audio)) {
            @unlink($temp); cfr_d1_stop(503);
        }
        chmod($temp, 0600);
        if (!rename($temp, $path)) {
            @unlink($temp); cfr_d1_stop(503);
        }
    }
    header('Content-Type: audio/mpeg');
    header('Cache-Control: private, no-store');
    header('Content-Length: ' . filesize($path));
    readfile($path);
} finally {
    flock($lock, LOCK_UN);
    fclose($lock);
}
