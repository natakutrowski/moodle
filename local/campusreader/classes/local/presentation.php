<?php
// CampusFR reader C2.1 — presentation choices; no entitlement decisions here.
namespace local_campusreader\local;

defined('MOODLE_INTERNAL') || die();

final class presentation {
    public static function mode(\stdClass $collection, ?int $now = null): string {
        $configured = $collection->presentationmode ?? 'auto';
        if (!in_array($configured, ['auto', 'advent', 'library'], true)) {
            // Fail to a non-revealing view; access is always checked elsewhere.
            $configured = 'library';
        }
        if ($configured !== 'auto') {
            return $configured;
        }
        $libraryafter = (int) ($collection->libraryafter ?? 0);
        return $libraryafter > 0 && ($now ?? time()) >= $libraryafter ? 'library' : 'advent';
    }

    public static function size(\stdClass $slot): string {
        $size = $slot->layoutsize ?? 'normal';
        return in_array($size, ['normal', 'wide', 'tall', 'feature'], true) ? $size : 'normal';
    }
}
