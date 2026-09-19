<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\xp;

defined('MOODLE_INTERNAL') || die();

use core\lock\lock_config;

/**
 * Records promotion XP without altering the user's personal Level Up XP state.
 */
final class CommercePedagogicalXpService {
    public function __construct(
        private readonly \moodle_database $db,
        private readonly CommercePedagogicalXpRepository $repository
    ) {}

    public static function create(?\moodle_database $db = null): self {
        global $DB;
        $db = $db ?? $DB;
        return new self($db, CommercePedagogicalXpRepository::create($db));
    }

    public function record_once(
        int $promotionid,
        int $userid,
        string $sourcecomponent,
        string $sourcetype,
        string $sourcekey,
        int $points,
        int $timeearned,
        ?int $now = null
    ): CommercePedagogicalXpRecordResult {
        if ($promotionid <= 0 || $userid <= 0) {
            throw new \coding_exception('Promotion XP requires valid promotion and user ids.');
        }
        $sourcecomponent = trim($sourcecomponent);
        $sourcetype = trim($sourcetype);
        $sourcekey = trim($sourcekey);
        if ($sourcecomponent === '' || $sourcetype === '' || $sourcekey === '') {
            throw new \coding_exception('Promotion XP requires source provenance.');
        }
        if ($points <= 0) {
            throw new \coding_exception('Promotion XP points must be positive.');
        }
        if ($timeearned <= 0) {
            throw new \coding_exception('Promotion XP earned timestamp must be positive.');
        }
        $now ??= time();
        if ($now <= 0) {
            throw new \coding_exception('Promotion XP creation timestamp must be positive.');
        }

        $promotion = $this->db->get_record(
            'local_subs_commerce_ped_promo',
            ['id' => $promotionid],
            'id,courseid',
            MUST_EXIST
        );

        if (!$this->db->record_exists('local_subs_commerce_ped_join', [
            'promotionid' => $promotionid,
            'userid' => $userid,
            'state' => 'active',
        ])) {
            throw new \coding_exception('Promotion XP can only be recorded for an active participant.');
        }

        $sourcehash = hash('sha256', implode("\n", [
            $sourcecomponent,
            $sourcetype,
            $sourcekey,
        ]));

        $factory = lock_config::get_lock_factory('local_subscriptions_ped_xp');
        $lock = $factory->get_lock(
            'promotion:' . $promotionid . ':user:' . $userid . ':source:' . $sourcehash,
            10
        );
        if (!$lock) {
            throw new \coding_exception('Could not acquire promotion XP idempotency lock.');
        }

        try {
            $existing = $this->repository->find_by_source_hash($promotionid, $userid, $sourcehash);
            if ($existing !== null) {
                return new CommercePedagogicalXpRecordResult($existing, false);
            }

            $contribution = new CommercePedagogicalXpContribution(
                null,
                $promotionid,
                (int)$promotion->courseid,
                $userid,
                $sourcecomponent,
                $sourcetype,
                $sourcekey,
                $sourcehash,
                $points,
                $timeearned,
                $now
            );
            return new CommercePedagogicalXpRecordResult(
                $this->repository->insert($contribution),
                true
            );
        } finally {
            $lock->release();
        }
    }
}
