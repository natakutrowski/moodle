<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\gift;

defined('MOODLE_INTERNAL') || die();

use core\lock\lock_config;

/** Trusted internal invitation issuer. Never expose its raw token to logs or JSON audits. */
final class CommerceGiftInvitationService {
    public function __construct(private readonly \moodle_database $db) {}

    /**
     * Generates a *new* secret returned once. Caller is responsible for secure delivery.
     * Rotates all previous tokens; intentionally no public route in G3C.
     */
    public function issue(int $giftid, int $ttlseconds): string {
        if (!(new CommerceGiftFeatureToggle())->can_issue()) {
            throw new \DomainException('Gift invitation runtime is disabled.');
        }
        if ($giftid <= 0 || $ttlseconds < 300 || $ttlseconds > 90 * 86400) {
            throw new \InvalidArgumentException('Invalid gift or invitation lifetime.');
        }
        $lock = lock_config::get_lock_factory('local_subscriptions_commerce_gift_claim')
            ->get_lock('gift:' . $giftid, 10);
        if (!$lock) { throw new \RuntimeException('Gift invitation lock unavailable.'); }
        try {
            $tx = $this->db->start_delegated_transaction();
            try {
                $gift = $this->db->get_record(CommerceGiftSchema::TABLE_GIFT,
                    ['id' => $giftid], '*', MUST_EXIST);
                $now = time();
                CommerceGiftActivationPolicy::assert_issuable($gift, $now);
                CommerceGiftActivationPolicy::reader_right($gift);
                (new CommerceGiftPaidSafety($this->db))->assert_still_paid($gift);
                $until = $now + $ttlseconds;
                if ($gift->expiresat !== null) {
                    $until = min($until, (int)$gift->expiresat);
                }
                if ($until <= $now) { throw new \DomainException('Gift already expired.'); }
                foreach ($this->db->get_records(CommerceGiftSchema::TABLE_TOKEN,
                    ['giftid' => $giftid]) as $old) {
                    if ($old->revokedat === null && $old->usedat === null) {
                        $this->db->update_record(CommerceGiftSchema::TABLE_TOKEN,
                            (object)['id' => (int)$old->id, 'revokedat' => $now]);
                    }
                }
                $secret = CommerceGiftToken::generate();
                $hash = CommerceGiftToken::fingerprint($secret);
                $this->db->insert_record(CommerceGiftSchema::TABLE_TOKEN, (object)[
                    'giftid' => $giftid, 'tokenhash' => $hash,
                    'issuedat' => $now, 'expiresat' => $until,
                    'revokedat' => null, 'usedat' => null,
                ]);
                $this->db->insert_record(CommerceGiftSchema::TABLE_EVENT, (object)[
                    'giftid' => $giftid, 'eventtype' => 'invitation_created',
                    'actoruserid' => null, 'occurredat' => $now,
                    'idempotencykey' => hash('sha256', 'campusfr:gift:invite:v1:' . $hash),
                    'detailjson' => '{"delivery":"external_pending"}',
                ]);
                $tx->allow_commit();
                return $secret;
            } catch (\Throwable $e) { $tx->rollback($e); throw $e; }
        } finally { $lock->release(); }
    }
}
