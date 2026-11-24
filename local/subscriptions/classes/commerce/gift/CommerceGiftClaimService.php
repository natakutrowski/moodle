<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\gift;

defined('MOODLE_INTERNAL') || die();

use core\lock\lock_config;

/** One-time invitation claim; reserves a beneficiary but NEVER creates a grant. */
final class CommerceGiftClaimService {
    public function __construct(private readonly \moodle_database $db) {}

    public function claim(string $rawtoken): \stdClass {
        return $this->claim_verified_fingerprint(CommerceGiftToken::fingerprint($rawtoken));
    }

    /**
     * Internal-only session handoff after a separately inspected gift invitation.
     * The caller MUST verify recipient identity; the original transaction still
     * checks the active invitation, the confirmed Moodle account and the paid gift.
     */
    public function claim_verified_fingerprint(string $hash): \stdClass {
        if (preg_match('/\A[a-f0-9]{64}\z/D', $hash) !== 1) {
            throw new \DomainException('Invalid internal gift fingerprint.');
        }
        if (!(new CommerceGiftFeatureToggle())->can_redeem()) {
            throw new \DomainException('Gift redemption runtime is disabled.');
        }
        // Claimant is always the current authenticated Moodle account, never a request parameter.
        global $USER;
        if (!isloggedin() || isguestuser() || (int)($USER->id ?? 0) <= 2) {
            throw new \DomainException('A confirmed Moodle login is required.');
        }
        $userid = (int)$USER->id;
        $token = $this->db->get_record(CommerceGiftSchema::TABLE_TOKEN,
            ['tokenhash' => $hash], '*', IGNORE_MISSING);
        if (!$token) { throw new \DomainException('Invalid or expired invitation.'); }
        $giftid = (int)$token->giftid;
        $lock = lock_config::get_lock_factory('local_subscriptions_commerce_gift_claim')
            ->get_lock('gift:' . $giftid, 10);
        if (!$lock) { throw new \RuntimeException('Gift claim lock unavailable.'); }
        try {
            $tx = $this->db->start_delegated_transaction();
            try {
                // Reload token and gift under the same lock: protects from rotation and double claim.
                $token = $this->db->get_record(CommerceGiftSchema::TABLE_TOKEN,
                    ['tokenhash' => $hash, 'giftid' => $giftid], '*', MUST_EXIST);
                $gift = $this->db->get_record(CommerceGiftSchema::TABLE_GIFT,
                    ['id' => $giftid], '*', MUST_EXIST);
                $now = time();
                CommerceGiftActivationPolicy::assert_claimable($gift, $token, $now);
                CommerceGiftActivationPolicy::reader_right($gift);
                $user = $this->db->get_record('user',
                    ['id' => $userid, 'deleted' => 0], 'id,email,confirmed,suspended', MUST_EXIST);
                if (!empty($user->suspended) || is_siteadmin($userid)) {
                    throw new \DomainException('Gift claimant is not an eligible customer account.');
                }
                CommerceGiftRecipientPolicy::validate_claim((string)$gift->deliverymode,
                    $gift->intendedrecipientemail, $userid, (string)$user->email,
                    !empty($user->confirmed), $gift->intendedrecipientuserid === null
                        ? null : (int)$gift->intendedrecipientuserid);
                if (((int)($gift->buyeruserid ?? 0) > 0 && (int)$gift->buyeruserid === $userid)
                    || strtolower(trim((string)$gift->buyeremail)) === strtolower(trim((string)$user->email))) {
                    throw new \DomainException('A gift cannot be claimed by its buyer.');
                }
                (new CommerceGiftPaidSafety($this->db))->assert_still_paid($gift);
                // Do not consume an invitation for a beneficiary already holding this publication.
                if ($this->db->record_exists_select('local_subs_commerce_grant',
                    'type = :type AND resourcekey = :resource AND productsku = :sku ' .
                    'AND beneficiaryuserid = :uid AND status = :active ' .
                    'AND validfrom <= :n1 AND (validuntil IS NULL OR validuntil > :n2)',
                    ['type' => 'reader_access', 'resource' => 'reader:publication:avent-2026',
                     'sku' => 'AVENT_2026', 'uid' => $userid, 'active' => 'active',
                     'n1' => $now, 'n2' => $now])) {
                    throw new \DomainException('Beneficiary already owns this publication.');
                }
                $this->db->update_record(CommerceGiftSchema::TABLE_TOKEN,
                    (object)['id' => (int)$token->id, 'usedat' => $now]);
                foreach ($this->db->get_records(CommerceGiftSchema::TABLE_TOKEN,
                    ['giftid' => $giftid]) as $other) {
                    if ((int)$other->id !== (int)$token->id
                        && $other->revokedat === null && $other->usedat === null) {
                        $this->db->update_record(CommerceGiftSchema::TABLE_TOKEN,
                            (object)['id' => (int)$other->id, 'revokedat' => $now]);
                    }
                }
                $this->db->update_record(CommerceGiftSchema::TABLE_GIFT, (object)[
                    'id' => $giftid,
                    'intendedrecipientuserid' => $userid,
                    'claimedat' => $now,
                    'status' => CommerceGiftLifecycle::CLAIMED,
                    'timemodified' => $now,
                    'rowversion' => (int)$gift->rowversion + 1,
                ]);
                $this->db->insert_record(CommerceGiftSchema::TABLE_EVENT, (object)[
                    'giftid' => $giftid, 'eventtype' => 'claimed',
                    'actoruserid' => $userid, 'occurredat' => $now,
                    'idempotencykey' => hash('sha256', 'campusfr:gift:claim:v1:' . $gift->giftuuid),
                    'detailjson' => '{"grant":"not_yet_issued"}',
                ]);
                $after = $this->db->get_record(CommerceGiftSchema::TABLE_GIFT,
                    ['id' => $giftid], '*', MUST_EXIST);
                $tx->allow_commit();
                return $after;
            } catch (\Throwable $e) { $tx->rollback($e); throw $e; }
        } finally { $lock->release(); }
    }
}
