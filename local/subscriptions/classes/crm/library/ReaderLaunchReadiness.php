<?php
// CampusFR D2.13.1 — pure checks for editor-only launch cockpit.
declare(strict_types=1);
namespace local_subscriptions\crm\library;
defined('MOODLE_INTERNAL') || die();

final class ReaderLaunchReadiness {
    /** Checks an exact day/book/date mapping against an APPROVED immutable chapter and calendar. */
    public static function frozen_matches(array $chapters, array $calendar,
        int $day, int $bookid, int $unlockat): bool {
        if ($day < 1 || $bookid < 1 || $unlockat < 1 ||
                ($calendar['schema'] ?? null) !== 1 ||
                ($calendar['timezone'] ?? null) !== 'Europe/Paris' ||
                !isset($calendar['slots']) || !is_array($calendar['slots']) ||
                !is_array($chapters)) {
            return false;
        }
        $chapter = null;
        $matches = 0;
        foreach ($chapters as $entry) {
            if (!is_array($entry)) { return false; }
            if ((int)($entry['bookid'] ?? 0) === $bookid) {
                $chapter = (int)($entry['position'] ?? 0);
                $matches++;
            }
        }
        if ($chapter === null || $chapter < 1 || $matches !== 1) { return false; }
        $matched = 0;
        foreach ($calendar['slots'] as $slot) {
            if (!is_array($slot)) { return false; }
            if ((int)($slot['position'] ?? 0) === $day) {
                if ((int)($slot['chapter'] ?? 0) !== $chapter ||
                        (int)($slot['unlockat'] ?? 0) !== $unlockat) {
                    return false;
                }
                $matched++;
            }
        }
        return $matched === 1;
    }

    /** A record of an exported PDF is not mandatory: exports are generated on demand. */
    public static function launch_ready(array $status, bool $alignment, bool $anthology,
        bool $publishedfrozen): bool {
        return !empty($status['text']) && !empty($status['visuals']) &&
            !empty($status['audio_unique']) && $alignment && $anthology && $publishedfrozen;
    }
}
