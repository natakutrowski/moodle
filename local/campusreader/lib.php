<?php
// CampusFR Reader C2.2b.1 — private files, checked on EACH request. GPL-3.0-or-later.
defined('MOODLE_INTERNAL') || die();

/**
 * Serve protected files with the exact same authorization as the reader page.
 * No preview bypass for guests; only system administrators can preview drafts.
 */
function local_campusreader_pluginfile($course, $cm, $context, $filearea, $args,
        $forcedownload, array $options = []): bool {
    global $DB;

    if ((int)$context->contextlevel !== CONTEXT_SYSTEM || !is_array($args)
        || !in_array($filearea, ['cover', 'pageimage', 'narration', 'video', 'pdf'], true)) {
        return false;
    }
    if (count($args) < 2 || count($args) > 3 ||
        !preg_match('/\A[1-9][0-9]{0,9}\z/D', (string)$args[0])) {
        return false;
    }
    $bookid = (int)array_shift($args);
    $filename = (string)array_pop($args);
    if (!preg_match('/\A[a-zA-Z0-9][a-zA-Z0-9_.-]{0,119}\z/D', $filename)
        || str_contains($filename, '..')) {
        return false;
    }
    $filepath = '/';
    if ($filearea === 'pageimage') {
        if (count($args) !== 1 || !preg_match('/\Ap(0|[1-9][0-9]{0,2})\z/D', (string)$args[0], $m)) {
            return false;
        }
        $index = (int)$m[1];
        $filepath = '/p' . $index . '/';
    } else if (count($args) !== 0) {
        return false;
    }

    // Never trust a URL, guessed filename, file metadata, or a cached page state as permission.
    require_login();
    try {
        [$book, $collection, $slot] = \local_campusreader\local\collection_repository::book_bundle($bookid);
        if (!\local_campusreader\local\collection_access::can_read_book($book, $collection, $slot)) {
            return false;
        }
    } catch (\Throwable $exception) {
        return false;
    }
    if ($filearea === 'pageimage') {
        $page = $DB->get_record('local_cr_page', [
            'bookid' => $bookid,
            'pageindex' => $index,
        ], 'id, imagefile');
        if (!$page || (string)$page->imagefile !== $filename) {
            return false;
        }
    }

    $types = [
        'cover' => ['png' => ['image/png'], 'jpg' => ['image/jpeg'], 'jpeg' => ['image/jpeg'], 'webp' => ['image/webp']],
        'pageimage' => ['png' => ['image/png'], 'jpg' => ['image/jpeg'], 'jpeg' => ['image/jpeg'], 'webp' => ['image/webp']],
        'narration' => ['mp3' => ['audio/mpeg', 'audio/mp3'], 'm4a' => ['audio/mp4', 'audio/x-m4a'],
            'ogg' => ['audio/ogg', 'application/ogg']],
        'video' => ['mp4' => ['video/mp4'], 'webm' => ['video/webm']],
        'pdf' => ['pdf' => ['application/pdf']],
    ];
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if (!array_key_exists($extension, $types[$filearea])) {
        return false;
    }
    $fs = get_file_storage();
    $file = $fs->get_file((int)$context->id, 'local_campusreader', $filearea, $bookid, $filepath, $filename);
    if (!$file || $file->is_directory() || $file->get_filesize() < 1
        || !in_array($file->get_mimetype(), $types[$filearea][$extension], true)) {
        return false;
    }

    // Ordinary media stays private, not a public/cdn long-lived URL. PDF is attachment-only.
    $options['cacheability'] = 'private';
    $options['immutable'] = false;
    header('X-Content-Type-Options: nosniff');
    send_stored_file($file, 0, 0, $filearea === 'pdf' || (bool)$forcedownload, $options);
    return true;
}
