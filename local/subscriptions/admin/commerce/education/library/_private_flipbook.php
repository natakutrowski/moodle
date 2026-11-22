<?php
// CampusFR D2.4.2b — shared CRM-only flipbook loader. Not a public entry point.
declare(strict_types=1);
defined('MOODLE_INTERNAL') || die();

/** Register exactly the existing CampusReader B2 styles and page-flip AMD engine. */
function cfr_d242b_require_reader(): void {
    global $PAGE;
    // Resolve from Moodle instead of relying on a different filesystem layout.
    global $CFG;
    $readerroot = $CFG->dirroot . '/local/campusreader';
    $cssfile = $readerroot . '/styles/reader.css';
    $vendorfile = $readerroot . '/vendor/stpageflip/page-flip.browser.js';
    if (!is_readable($cssfile) || !is_readable($vendorfile)) {
        throw new \moodle_exception('invalidparameter');
    }
    $PAGE->requires->css(new \moodle_url('/local/campusreader/styles/reader.css',
        ['v' => (string)filemtime($cssfile)]));
    $vendorurl = (new \moodle_url('/local/campusreader/vendor/stpageflip/page-flip.browser.js',
        ['v' => (string)filemtime($vendorfile)]))->out(false);
    $PAGE->requires->js_call_amd('local_campusreader/reader', 'init', [$vendorurl]);
}

/** Resolve each single book-level media through the already ACL-guarded CRM file gateway. */
function cfr_d242b_draft_extras(int $bookid): array {
    $fs = get_file_storage();
    $ctx = (int)\context_system::instance()->id;
    $extras = [];
    foreach (['narration', 'video', 'pdf'] as $area) {
        $files = array_values(array_filter(
            $fs->get_area_files($ctx, 'local_campusreader', $area, $bookid, 'id ASC', false),
            static fn($f): bool => !$f->is_directory()
        ));
        if (count($files) === 1 && $files[0]->get_filepath() === '/') {
            $extras[$area] = cfr_d21_url('preview.php', ['id' => $bookid, 'area' => $area])->out(false);
        }
    }
    return $extras;
}

/** Exact matching by version-internal chapter/area/path, never original story file IDs. */
function cfr_d242b_snapshot_media(array $snapshot, int $chapter, int $id, int $rev): array {
    $images = [];
    $extras = [];
    $extrascounts = [];
    foreach ($snapshot['media'] as $i => $f) {
        if ($f['chapter'] !== $chapter) { continue; }
        $url = cfr_d21_url('anthology_revision_media.php', ['id' => $id, 'rev' => $rev,
            'media' => $i])->out(false);
        if ($f['area'] === 'pageimage') {
            $images[$f['filepath'] . $f['filename']] = $url;
        } else if (in_array($f['area'], ['narration', 'video', 'pdf'], true)) {
            // Do not display an ambiguous book-level asset if more than one is present.
            $extrascounts[$f['area']] = ($extrascounts[$f['area']] ?? 0) + 1;
            if ($extrascounts[$f['area']] === 1) {
                $extras[$f['area']] = $url;
            } else {
                unset($extras[$f['area']]);
            }
        }
    }
    return [$images, $extras];
}

/** D2.4.2c: load CRM-only immersive and chapter-toolbar interactions. */
function cfr_d242c_require_ui(): void {
    global $CFG, $PAGE;
    $relative = '/local/subscriptions/admin/commerce/education/library/preview_ui.js';
    $file = $CFG->dirroot . $relative;
    if (!is_readable($file)) {
        throw new \moodle_exception('invalidparameter');
    }
    $PAGE->requires->js(new \moodle_url($relative, ['v' => (string)filemtime($file)]), true);
}
