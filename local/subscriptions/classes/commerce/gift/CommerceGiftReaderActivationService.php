<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\gift;

defined('MOODLE_INTERNAL') || die();

use core\lock\lock_config;
use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrant;
use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrantPlan;
use local_subscriptions\commerce\entitlement\persistence\CommerceEntitlementGrantPersister;
use local_subscriptions\commerce\entitlement\persistence\CommerceEntitlementGrantRecordMapper;
use local_subscriptions\commerce\entitlement\persistence\CommerceEntitlementGrantRepository;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentContext;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentHandlerRegistry;
use local_subscriptions\commerce\fulfillment\native\reader\CommerceReaderAccessFulfillmentHandler;
use local_subscriptions\commerce\fulfillment\native\persistence\CommercePersistentNativeFulfillmentExecutor;
use local_subscriptions\commerce\fulfillment\native\persistence\MoodleCommerceNativeFulfillmentPersistenceRepository;

/** Server-only activation of an already claimed Avent gift via the Native grant ledger. */
final class CommerceGiftReaderActivationService {
    public function __construct(private readonly \moodle_database $db) {}

    public function activate(int $giftid): \stdClass {
        if (!(new CommerceGiftFeatureToggle())->can_redeem()) {
            throw new \DomainException('Gift redemption runtime is disabled.');
        }
        if ($giftid < 1) { throw new \InvalidArgumentException('Invalid gift identifier.'); }
        global $USER;
        if (!isloggedin() || isguestuser() || (int)($USER->id ?? 0) <= 2) {
            throw new \DomainException('A confirmed Moodle login is required.');
        }
        $claimantuserid = (int)$USER->id;
        $lock = lock_config::get_lock_factory('local_subscriptions_commerce_gift_claim')
            ->get_lock('gift:' . $giftid, 10);
        if (!$lock) { throw new \RuntimeException('Gift activation lock unavailable.'); }
        try {
            return $this->activate_locked($giftid, $claimantuserid);
        } finally { $lock->release(); }
    }

    private function activate_locked(int $giftid, int $claimantuserid): \stdClass {
        $tx = $this->db->start_delegated_transaction();
        try {
            $gift = $this->db->get_record(CommerceGiftSchema::TABLE_GIFT,
                ['id' => $giftid], '*', MUST_EXIST);
            if ((string)$gift->status === CommerceGiftLifecycle::ACTIVE) {
                if ((int)$gift->rightsholderuserid !== $claimantuserid) {
                    throw new \DomainException('Gift belongs to another account.');
                }
                $this->assert_active_grant($gift);
                $tx->allow_commit();
                return $gift; // Idempotent successful replay, never changes the holder.
            }
            $userid = (int)$gift->intendedrecipientuserid;
            if ($userid !== $claimantuserid) {
                throw new \DomainException('Gift claim belongs to another account.');
            }
            CommerceGiftActivationPolicy::assert_activation($gift, $userid);
            $right = CommerceGiftActivationPolicy::reader_right($gift);
            $user = $this->db->get_record('user',
                ['id' => $userid, 'deleted' => 0], 'id,email,confirmed,suspended', MUST_EXIST);
            if (empty($user->confirmed) || !empty($user->suspended) || is_siteadmin($userid)) {
                throw new \DomainException('Gift claimant no longer has an eligible account.');
            }
            if (((int)($gift->buyeruserid ?? 0) > 0 && (int)$gift->buyeruserid === $userid)
                || strtolower(trim((string)$gift->buyeremail)) === strtolower(trim((string)$user->email))) {
                throw new \DomainException('The buyer cannot activate their own gift.');
            }
            CommerceGiftRecipientPolicy::validate_claim((string)$gift->deliverymode,
                $gift->intendedrecipientemail, $userid, (string)$user->email, true, $userid);
            (new CommerceGiftPaidSafety($this->db))->assert_still_paid($gift);
            $pub = \local_campusreader\local\publication_access::publication('avent-2026');
            if (!$pub || (string)$pub->status !== 'published'
                || (int)$pub->revisionid <= 0
                || strtoupper((string)$pub->productsku) !== 'AVENT_2026') {
                throw new \DomainException('The Reader publication is not ready for activation.');
            }
            $now = time();
            // Reject pre-existing ownership from other purchases/grants, not this gift's own retries.
            $reference = self::grant_reference((string)$gift->giftuuid);
            $already = $this->db->get_records_select('local_subs_commerce_grant',
                'type = :type AND resourcekey = :resource AND productsku = :sku ' .
                'AND beneficiaryuserid = :userid AND status = :active ' .
                'AND validfrom <= :now1 AND (validuntil IS NULL OR validuntil > :now2)',
                ['type' => 'reader_access', 'resource' => $right['resourcekey'],
                    'sku' => $right['sku'], 'userid' => $userid, 'active' => 'active',
                    'now1' => $now, 'now2' => $now]);
            foreach ($already as $existing) {
                if ((string)$existing->grantreference !== $reference) {
                    throw new \DomainException('Beneficiary already owns this Reader publication.');
                }
            }
            $duration = $right['durationseconds'];
            if ($duration !== null && $now > PHP_INT_MAX - $duration) {
                throw new \DomainException('Invalid gift validity duration.');
            }
            $grant = new CommerceEntitlementGrant(
                $reference,
                self::source_reference((string)$gift->giftuuid),
                'giftitem-' . (string)$gift->giftuuid,
                $right['sku'], 'reader_access', $right['resourcekey'], 1,
                $userid, (string)$user->email,
                $now, $duration === null ? null : $now + $duration,
                [], ['source' => 'gift_redemption', 'giftuuid' => (string)$gift->giftuuid,
                    'purchaseid' => (int)$gift->purchaseid, 'purchaseitemid' => (int)$gift->purchaseitemid]
            );
            $plan = new CommerceEntitlementGrantPlan($grant->get_purchase_reference(), [$grant], $now);
            $mapper = new CommerceEntitlementGrantRecordMapper();
            $repo = new CommerceEntitlementGrantRepository($this->db, $mapper);
            $this->db->update_record(CommerceGiftSchema::TABLE_GIFT, (object)[
                'id' => $giftid, 'status' => CommerceGiftLifecycle::GRANT_PENDING,
                'timemodified' => $now, 'rowversion' => (int)$gift->rowversion + 1,
            ]);
            (new CommerceEntitlementGrantPersister($this->db, $repo))->persist($plan, $now);
            $native = new CommercePersistentNativeFulfillmentExecutor(
                new CommerceNativeFulfillmentHandlerRegistry([new CommerceReaderAccessFulfillmentHandler()]),
                new MoodleCommerceNativeFulfillmentPersistenceRepository()
            );
            $result = $native->execute($grant, CommerceNativeFulfillmentContext::runtime(
                'gift-' . substr(hash('sha256', (string)$gift->giftuuid), 0, 24),
                $now, $userid, 'gift_redemption', ['giftuuid' => (string)$gift->giftuuid]
            ));
            if (!$result->is_completed() && $result->get_status() !== 'skipped') {
                throw new \RuntimeException('Native Reader gift fulfillment failed.');
            }
            $record = $repo->find_by_idempotency_key($grant->get_idempotency_key());
            if (!$record || (string)$record->status !== 'active'
                || (int)$record->beneficiaryuserid !== $userid
                || (string)$record->grantreference !== $reference) {
                throw new \RuntimeException('Native Reader gift grant is not active after fulfillment.');
            }
            $this->db->update_record(CommerceGiftSchema::TABLE_GIFT, (object)[
                'id' => $giftid, 'status' => CommerceGiftLifecycle::ACTIVE,
                'rightsholderuserid' => $userid, 'activatedat' => $now,
                'timemodified' => $now, 'rowversion' => (int)$gift->rowversion + 2,
            ]);
            $this->db->insert_record(CommerceGiftSchema::TABLE_EVENT, (object)[
                'giftid' => $giftid, 'eventtype' => 'activated', 'actoruserid' => $userid,
                'occurredat' => $now,
                'idempotencykey' => hash('sha256', 'campusfr:gift:active:v1:' . $gift->giftuuid),
                'detailjson' => '{"grant":"native_reader_access"}',
            ]);
            $after = $this->db->get_record(CommerceGiftSchema::TABLE_GIFT,
                ['id' => $giftid], '*', MUST_EXIST);
            $tx->allow_commit();
            return $after;
        } catch (\Throwable $e) {
            // No partial rights survive a failed SQL transaction. Do not automatically
            // change the claimant on retry; a separate failure state may be recorded.
            $tx->rollback($e);
            throw $e;
        }
    }

    private function assert_active_grant(\stdClass $gift): void {
        $userid = (int)$gift->rightsholderuserid;
        if ($userid <= 2 || (int)$gift->intendedrecipientuserid !== $userid
            || $gift->activatedat === null) {
            throw new \DomainException('Active gift holder is inconsistent.');
        }
        $record = $this->db->get_record('local_subs_commerce_grant',
            ['grantreference' => self::grant_reference((string)$gift->giftuuid)], '*', IGNORE_MISSING);
        if (!$record || (string)$record->status !== 'active'
            || (int)$record->beneficiaryuserid !== $userid) {
            throw new \DomainException('Active gift has no matching Native grant.');
        }
    }

    private static function source_reference(string $uuid): string {
        if (!preg_match('/\A[a-f0-9]{32}\z/D', $uuid)) {
            throw new \DomainException('Invalid gift UUID.');
        }
        return 'gift-' . $uuid;
    }

    private static function grant_reference(string $uuid): string {
        return 'ent-gift-' . self::source_reference($uuid);
    }
}
