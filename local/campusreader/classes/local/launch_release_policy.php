<?php
// CampusFR D2.21.0 — pure, fail-closed editorial release eligibility.
declare(strict_types=1);
namespace local_campusreader\local;
defined('MOODLE_INTERNAL') || die();

final class launch_release_policy {
    /** A title or placeholder alone never represents a releasable story. */
    public static function has_content(array $pages): bool {
        if (count($pages) < 2 || count($pages) > 100 ||
                !is_array($pages[0]) || ($pages[0]['kind'] ?? null) !== 'cover') {
            return false;
        }
        $hastext = false;
        foreach ($pages as $index => $page) {
            if (!is_array($page) || ($page['index'] ?? null) !== $index ||
                    !is_array($page['paragraphs'] ?? null) ||
                    !array_is_list($page['paragraphs'])) {
                return false;
            }
            if ($index > 0 && in_array($page['kind'] ?? '', ['text', 'mixed'], true)) {
                foreach ($page['paragraphs'] as $paragraph) {
                    if (is_string($paragraph) && trim($paragraph) !== '') {
                        $hastext = true;
                    }
                }
            }
        }
        return $hastext;
    }

    /** The explicit editorial checkbox is frozen in a signed approved revision. */
    public static function chapter_ready($chapter): bool {
        return is_array($chapter) && ($chapter['release_ready'] ?? false) === true &&
            is_array($chapter['pages'] ?? null) && self::has_content($chapter['pages']);
    }

    public static function allowed_at(int $unlockat, int $now, $chapter): bool {
        return $unlockat > 0 && $now >= $unlockat && self::chapter_ready($chapter);
    }
}
