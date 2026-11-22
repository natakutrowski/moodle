<?php
// CampusFR D2.4.2b — private CRM rendition of CampusReader's flipbook markup.
declare(strict_types=1);
namespace local_subscriptions\crm\library;
defined('MOODLE_INTERNAL') || die();

final class ReaderPrivatePreviewRenderer {
    /** Turn current Moodle page records into the same page shape as immutable snapshots. */
    public static function draft_pages(int $bookid): array {
        global $DB;
        $records = array_values($DB->get_records('local_cr_page', ['bookid' => $bookid], 'pageindex ASC', '*', 0, 101));
        if (!$records || count($records) > 100) {
            throw new \moodle_exception('invalidparameter');
        }
        $pages = [];
        foreach ($records as $i => $row) {
            if ((int)$row->pageindex !== $i) { throw new \moodle_exception('invalidparameter'); }
            $paragraphs = json_decode((string)$row->paragraphsjson, true);
            if (!is_array($paragraphs) || !array_is_list($paragraphs) || count($paragraphs) > 8) {
                throw new \moodle_exception('invalidparameter');
            }
            $pages[] = [
                'index' => $i,
                'kind' => (string)$row->kind,
                'title' => (string)$row->title,
                'paragraphs' => $paragraphs,
                'imagefile' => (string)($row->imagefile ?? ''),
                'imagealt' => (string)($row->imagealt ?? ''),
            ];
        }
        return $pages;
    }

    /**
     * This HTML is deliberately compatible with local_campusreader/reader AMD and styles/reader.css.
     * No TTS key/book id and no commerce or learner authorization is sent to the browser.
     * Caller must enforce CRM ACL and provide only CRM-authenticated media URLs.
     *
     * @param callable $imageurl Receives page array and returns an authenticated URL (string).
     * @param array $extras Authenticated audio/video/PDF URLs keyed by filearea.
     */
    public static function render(string $title, string $subtitle, array $pages,
        callable $imageurl, array $extras, string $localkey, string $notice): string {
        if (!$pages || count($pages) > 100 || !preg_match('/\A[a-zA-Z0-9-]{1,120}\z/D', $localkey)) {
            throw new \moodle_exception('invalidparameter');
        }
        $out = '<div id="cfr-reader" class="cfr-reader cfr-d242b-reader" data-cfr-book-id="' . s($localkey) .
            '" data-cfr-tts-enabled="0" data-cfr-tts-book="">';
        $out .= '<header class="cfr-reader__intro"><div class="cfr-reader__eyebrow">CAMPUSFR · ' .
            s(get_string('cfr_d242b_privatelabel', 'local_subscriptions')) . '</div><h2>' . s($title) .
            '</h2><p>' . s($subtitle) . '</p></header>';
        $out .= '<p class="cfr-reader__boot" data-cfr-boot role="status">' .
            s(get_string('cfr_d242b_loading', 'local_subscriptions')) . '</p>';
        $out .= '<div class="cfr-reader__stage"><div id="cfr-book" class="cfr-book">';
        $total = count($pages);
        foreach ($pages as $i => $page) {
            if (!is_array($page) || ($page['index'] ?? null) !== $i ||
                !in_array(($page['kind'] ?? ''), ['cover', 'back', 'text', 'image', 'mixed'], true) ||
                !isset($page['title'], $page['paragraphs']) || !is_string($page['title']) ||
                !is_array($page['paragraphs']) || !array_is_list($page['paragraphs']) ||
                count($page['paragraphs']) > 8) {
                throw new \moodle_exception('invalidparameter');
            }
            $kind = $page['kind'];
            $out .= '<article class="cfr-book-page cfr-book-page--' . s($kind) . '" data-page-index="' . $i . '"' .
                (in_array($kind, ['cover', 'back'], true) ? ' data-density="hard"' : '') .
                '><div class="cfr-book-page__inside">';
            if (in_array($kind, ['cover', 'back', 'image', 'mixed'], true)) {
                if (!empty($page['imagefile'])) {
                    $url = $imageurl($page);
                    if (!is_string($url) || $url === '') { throw new \moodle_exception('invalidparameter'); }
                    $out .= '<div class="cfr-art cfr-art--image"><img loading="lazy" decoding="async" src="' .
                        s($url) . '" alt="' . s((string)($page['imagealt'] ?? '')) . '"></div>';
                } else {
                    $out .= '<div class="cfr-art" aria-hidden="true"><span class="cfr-art__moon"></span>' .
                        '<span class="cfr-art__star cfr-art__star--one">✦</span>' .
                        '<span class="cfr-art__star cfr-art__star--two">✧</span>' .
                        '<span class="cfr-art__star cfr-art__star--three">✦</span>' .
                        '<span class="cfr-art__hill"></span><span class="cfr-art__house"></span></div>';
                }
            }
            $out .= '<div class="cfr-book-page__copy"><div class="cfr-book-page__kicker">' . s($title) .
                '</div><h3>' . s($page['title']) . '</h3>';
            foreach ($page['paragraphs'] as $paragraph) {
                if (!is_string($paragraph) || strlen($paragraph) > 5000) {
                    throw new \moodle_exception('invalidparameter');
                }
                $out .= '<p class="cfr-text" lang="fr">' . s($paragraph) . '</p>';
            }
            $out .= '<span class="cfr-book-page__number">' . ($i + 1) . ' / ' . $total .
                '</span></div></div></article>';
        }
        $out .= '</div></div><nav class="cfr-reader__controls" aria-label="' .
            s(get_string('cfr_d242b_navigation', 'local_subscriptions')) . '">';
        $out .= '<button type="button" class="cfr-btn cfr-btn--secondary" data-cfr-prev>← ' .
            s(get_string('cfr_d242b_previous', 'local_subscriptions')) . '</button>';
        $out .= '<span class="cfr-reader__counter" data-cfr-counter aria-live="polite">Page 1 / ' . $total . '</span>';
        $out .= '<button type="button" class="cfr-btn cfr-btn--primary" data-cfr-next>' .
            s(get_string('cfr_d242b_next', 'local_subscriptions')) . ' →</button></nav>';
        $out .= '<div class="cfr-reader__options"><label><input type="checkbox" data-cfr-reduced> ' .
            s(get_string('cfr_d242b_reduce', 'local_subscriptions')) . '</label></div>';
        if (isset($extras['narration']) || isset($extras['video']) || isset($extras['pdf'])) {
            $out .= '<section class="cfr-reader-media" aria-label="' .
                s(get_string('cfr_d242b_media', 'local_subscriptions')) . '">';
            if (isset($extras['video'])) {
                $out .= '<div class="cfr-reader-media__video"><h3>' .
                    s(get_string('cfr_d242b_video', 'local_subscriptions')) .
                    '</h3><video controls playsinline preload="none"><source src="' .
                    s($extras['video']) . '"></video></div>';
            }
            if (isset($extras['narration'])) {
                $out .= '<div class="cfr-reader-audio" data-cfr-audio-player><audio controls preload="none" src="' .
                    s($extras['narration']) . '"></audio></div>';
            }
            if (isset($extras['pdf'])) {
                $out .= '<p><a class="cfr-reader-download" href="' . s($extras['pdf']) . '">' .
                    s(get_string('cfr_d242b_pdf', 'local_subscriptions')) . '</a></p>';
            }
            $out .= '</section>';
        }
        $out .= '<p class="cfr-reader__notice">' . s($notice) . '</p></div>';
        return $out;
    }
}
