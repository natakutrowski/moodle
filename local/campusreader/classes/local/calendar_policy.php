<?php
// CampusFR D2.3.1 — canonical active-slot policy shared by CRM and Reader.
declare(strict_types=1);
namespace local_campusreader\local;

defined('MOODLE_INTERNAL') || die();

final class calendar_policy {
    public const MIN_COUNT = 1;
    public const MAX_COUNT = 31;

    /**
     * Legacy collections remain at 31 until explicitly configured.
     * An invalid config fails closed (zero visible slots), never back to 31.
     * Uses stable collection id and config_plugins, with no SQL schema change.
     */
    public static function active_count(\stdClass $collection): int {
        $id = (int)($collection->id ?? 0);
        if ($id <= 0) {
            return 0;
        }
        $value = get_config('local_campusreader', 'advent_slotcount_' . $id);
        if ($value === false) {
            return self::MAX_COUNT;
        }
        $raw = (string)$value;
        if (!preg_match('/\A(?:[1-9]|[12][0-9]|3[01])\z/D', $raw)) {
            return 0;
        }
        return (int)$raw;
    }

    public static function slot_active(\stdClass $collection, \stdClass $slot): bool {
        $position = (int)($slot->position ?? 0);
        return $position > 0 && $position <= self::active_count($collection)
            && (int)($slot->collectionid ?? 0) === (int)$collection->id;
    }
}
