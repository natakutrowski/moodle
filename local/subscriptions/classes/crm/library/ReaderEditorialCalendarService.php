<?php
// CampusFR D2.3.1 — configurable editorial Advent calendar, DEV-only rollout.
declare(strict_types=1);
namespace local_subscriptions\crm\library;

defined('MOODLE_INTERNAL') || die();

use local_campusreader\local\calendar_policy;

final class ReaderEditorialCalendarService {
    private const ZONE = 'Europe/Paris';
    private const SIZES = ['normal', 'wide', 'tall', 'feature'];

    public static function count(\stdClass $collection): int {
        return calendar_policy::active_count($collection);
    }

    /** All rows, including temporarily inactive positions: never silently delete data. */
    public static function slots(int $collectionid): array {
        global $DB;
        $result = [];
        foreach ($DB->get_records('local_cr_slot', ['collectionid' => $collectionid], 'position ASC') as $slot) {
            $day = (int)$slot->position;
            if ($day < 1 || $day > calendar_policy::MAX_COUNT || isset($result[$day])) {
                throw new \moodle_exception('invalidparameter', 'error', '', 'Positions calendrier incohérentes');
            }
            $result[$day] = $slot;
        }
        return $result;
    }

    public static function release_at(int $year, int $day): int {
        if ($year < 2026 || $year > 2035 || $day < 1 || $day > calendar_policy::MAX_COUNT) {
            throw new \moodle_exception('invalidparameter');
        }
        return (new \DateTimeImmutable(sprintf('%04d-12-%02d 00:00:00', $year, $day),
            new \DateTimeZone(self::ZONE)))->getTimestamp();
    }

    public static function switch_at(int $year): int {
        if ($year < 2026 || $year > 2035) {
            throw new \moodle_exception('invalidparameter');
        }
        return (new \DateTimeImmutable(($year + 1) . '-01-01 00:00:00',
            new \DateTimeZone(self::ZONE)))->getTimestamp();
    }

    /** Strict local time parsing; refuse normalized-invalid dates and DST gaps. */
    public static function parse_local(string $value): int {
        if (!preg_match('/\A20(?:2[6-9]|3[0-9]|40)-(?:0[1-9]|1[0-2])-(?:0[1-9]|[12][0-9]|3[01])T(?:[01][0-9]|2[0-3]):[0-5][0-9]\z/D', $value)) {
            throw new \moodle_exception('invalidparameter', 'error', '', 'Date/heure invalide');
        }
        $zone = new \DateTimeZone(self::ZONE);
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value, $zone);
        if (!$parsed || $parsed->format('Y-m-d\TH:i') !== $value) {
            throw new \moodle_exception('invalidparameter', 'error', '', 'Date inexistante ou invalide (heure de Paris)');
        }
        return $parsed->getTimestamp();
    }

    private static function draft_collection(int $collectionid): \stdClass {
        $collection = ReaderEditorialService::collection($collectionid);
        if ((int)$collection->published !== 0 || (string)$collection->timezone !== self::ZONE) {
            throw new \moodle_exception('invalidparameter', 'error', '',
                'Collection publiée ou fuseau incompatible : lecture seule');
        }
        return $collection;
    }

    private static function assert_timeline(array $slots, int $count): void {
        $previous = 0;
        for ($day = 1; $day <= $count; $day++) {
            if (!isset($slots[$day])) { continue; }
            $at = (int)$slots[$day]->unlockat;
            if ($at <= $previous) {
                throw new \moodle_exception('invalidparameter', 'error', '',
                    'Les dates de déblocage doivent être strictement croissantes dans l’ordre des cases');
            }
            $previous = $at;
        }
    }

    public static function configure_count(int $collectionid, int $newcount, int $expected): void {
        global $DB, $USER;
        $collection = self::draft_collection($collectionid);
        $current = self::count($collection);
        if ($current !== $expected || $newcount < calendar_policy::MIN_COUNT
            || $newcount > calendar_policy::MAX_COUNT || $current === 0) {
            throw new \moodle_exception('invalidparameter', 'error', '', 'Configuration modifiée ou nombre de cases invalide');
        }
        if ($current === $newcount) { return; }
        $slots = self::slots($collectionid);
        // Reducing visibility must never silently detach a populated story.
        if ($newcount < $current) {
            foreach ($slots as $position => $slot) {
                if ($position > $newcount && $position <= $current && !empty($slot->bookid)) {
                    throw new \moodle_exception('invalidparameter', 'error', '',
                        'Une case à masquer contient une histoire : libère-la explicitement avant de réduire');
                }
            }
        }
        self::assert_timeline($slots, $newcount);
        if ((string)$collection->presentationmode === 'auto' && (int)$collection->libraryafter > 0) {
            foreach ($slots as $position => $slot) {
                if ($position <= $newcount && (int)$slot->unlockat >= (int)$collection->libraryafter) {
                    throw new \moodle_exception('invalidparameter', 'error', '',
                        'Le passage en bibliothèque doit suivre toutes les ouvertures');
                }
            }
        }
        if (!set_config('advent_slotcount_' . $collectionid, (string)$newcount, 'local_campusreader')) {
            throw new \moodle_exception('invalidparameter', 'error', '', 'Impossible d’enregistrer le nombre de cases');
        }
        error_log('[CampusFR D2.3.1 calendar] event=count actor=' . (int)$USER->id .
            ' collection=' . $collectionid . ' before=' . $current . ' after=' . $newcount);
    }

    /** Fill only missing ACTIVE slots; no alteration of existing dates, books, progress or media. */
    public static function complete(int $collectionid, int $year): int {
        global $DB, $USER;
        self::release_at($year, 1);
        $collection = self::draft_collection($collectionid);
        $count = self::count($collection);
        if ($count < 1) { throw new \moodle_exception('invalidparameter'); }
        $slots = self::slots($collectionid);
        $planned = $slots;
        for ($day = 1; $day <= $count; $day++) {
            if (!isset($planned[$day])) {
                $planned[$day] = (object)['position' => $day, 'unlockat' => self::release_at($year, $day)];
            }
        }
        self::assert_timeline($planned, $count);
        if ((string)$collection->presentationmode === 'auto' && (int)$collection->libraryafter > 0) {
            foreach ($planned as $day => $slot) {
                if ($day <= $count && (int)$slot->unlockat >= (int)$collection->libraryafter) {
                    throw new \moodle_exception('invalidparameter', 'error', '',
                        'La bascule en bibliothèque doit suivre toutes les ouvertures');
                }
            }
        }
        $tx = $DB->start_delegated_transaction();
        $created = 0;
        try {
            $now = time();
            for ($day = 1; $day <= $count; $day++) {
                if (isset($slots[$day])) { continue; }
                $DB->insert_record('local_cr_slot', (object)[
                    'collectionid' => $collectionid, 'position' => $day,
                    'unlockat' => self::release_at($year, $day), 'bookid' => null,
                    'teaser' => 'Une surprise à découvrir', 'layoutsize' => 'normal',
                    'timecreated' => $now, 'timemodified' => $now,
                ]);
                $created++;
            }
            $tx->allow_commit();
        } catch (\Throwable $exception) {
            $tx->rollback($exception);
        }
        error_log('[CampusFR D2.3.1 calendar] event=complete actor=' . (int)$USER->id .
            ' collection=' . $collectionid . ' slots_created=' . $created);
        return $created;
    }

    /** Update a slot with an optimistic timestamp guard; never change user rights or publication. */
    public static function save_slot(int $collectionid, int $day, string $teaser, int $bookid,
        string $layoutsize, string $localunlock, int $expectedunlock): void {
        global $DB, $USER;
        $collection = self::draft_collection($collectionid);
        $count = self::count($collection);
        if ($day < 1 || $day > $count || !in_array($layoutsize, self::SIZES, true)) {
            throw new \moodle_exception('invalidparameter');
        }
        $unlockat = self::parse_local($localunlock);
        $teaser = trim(strip_tags($teaser));
        if ($teaser === '' || \core_text::strlen($teaser) > 191) {
            throw new \moodle_exception('invalidparameter', 'error', '', 'Texte d’attente manquant ou trop long');
        }
        $tx = $DB->start_delegated_transaction();
        try {
            $slot = $DB->get_record('local_cr_slot', ['collectionid' => $collectionid,
                'position' => $day], '*', MUST_EXIST);
            if ((int)$slot->unlockat !== $expectedunlock) {
                throw new \moodle_exception('invalidparameter', 'error', '',
                    'Date modifiée depuis l’ouverture de la fiche : recharge la page');
            }
            $previous = (int)($slot->bookid ?? 0);
            if ($bookid < 0) { throw new \moodle_exception('invalidparameter'); }
            if ($bookid > 0) {
                $book = ReaderEditorialService::book($bookid);
                if ((int)$book->collectionid !== $collectionid) {
                    throw new \moodle_exception('invalidparameter', 'error', '', 'Histoire d’une autre collection');
                }
                if ($DB->record_exists_select('local_cr_slot',
                    'bookid = :bookid AND id <> :slotid', ['bookid' => $bookid, 'slotid' => $slot->id])) {
                    throw new \moodle_exception('invalidparameter', 'error', '', 'Histoire déjà associée à une autre case');
                }
            }
            if ($previous !== $bookid && (($previous > 0 && $DB->record_exists('local_cr_progress', ['bookid' => $previous]))
                || ($bookid > 0 && $DB->record_exists('local_cr_progress', ['bookid' => $bookid])))) {
                throw new \moodle_exception('invalidparameter', 'error', '', 'Progression existante : association protégée');
            }
            if ($unlockat !== (int)$slot->unlockat && $previous > 0 &&
                $DB->record_exists('local_cr_progress', ['bookid' => $previous])) {
                throw new \moodle_exception('invalidparameter', 'error', '', 'Progression existante : déblocage protégé');
            }
            $slots = self::slots($collectionid);
            $slots[$day]->unlockat = $unlockat;
            self::assert_timeline($slots, $count);
            if ((string)$collection->presentationmode === 'auto' && (int)$collection->libraryafter > 0
                && $unlockat >= (int)$collection->libraryafter) {
                throw new \moodle_exception('invalidparameter', 'error', '',
                    'Date postérieure au passage en bibliothèque');
            }
            $DB->update_record('local_cr_slot', (object)[
                'id' => (int)$slot->id, 'bookid' => $bookid ?: null,
                'teaser' => $teaser, 'layoutsize' => $layoutsize, 'unlockat' => $unlockat,
                'timemodified' => time(),
            ]);
            $tx->allow_commit();
        } catch (\Throwable $exception) {
            $tx->rollback($exception);
        }
        error_log('[CampusFR D2.3.1 calendar] event=save actor=' . (int)$USER->id .
            ' collection=' . $collectionid . ' day=' . $day);
    }

    /** Explicit switch date; never changes the publication state or grants. */
    public static function configure_switch(int $collectionid, string $localdate): void {
        global $DB, $USER;
        $collection = self::draft_collection($collectionid);
        $at = self::parse_local($localdate);
        $count = self::count($collection);
        $slots = self::slots($collectionid);
        if ($count < 1 || count(array_filter(array_keys($slots), static fn($d) => $d <= $count)) !== $count) {
            throw new \moodle_exception('invalidparameter', 'error', '', 'Crée les cases actives avant la bascule');
        }
        self::assert_timeline($slots, $count);
        foreach ($slots as $day => $slot) {
            if ($day <= $count && (int)$slot->unlockat >= $at) {
                throw new \moodle_exception('invalidparameter', 'error', '',
                    'La bascule doit avoir lieu après la dernière ouverture');
            }
        }
        $tx = $DB->start_delegated_transaction();
        try {
            $DB->update_record('local_cr_collection', (object)[
                'id' => $collectionid, 'presentationmode' => 'auto',
                'libraryafter' => $at, 'timemodified' => time(),
            ]);
            $tx->allow_commit();
        } catch (\Throwable $exception) {
            $tx->rollback($exception);
        }
        error_log('[CampusFR D2.3.1 calendar] event=switch actor=' . (int)$USER->id .
            ' collection=' . $collectionid);
    }
}
