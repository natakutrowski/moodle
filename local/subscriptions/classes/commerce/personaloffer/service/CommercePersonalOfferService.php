<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\personaloffer\service;

defined('MOODLE_INTERNAL') || die();

use dml_exception;
use local_subscriptions\commerce\personaloffer\domain\CommercePersonalOffer;
use local_subscriptions\commerce\personaloffer\dto\CommercePersonalOfferIssueRequest;
use local_subscriptions\commerce\personaloffer\dto\CommercePersonalOfferIssueResult;
use local_subscriptions\commerce\personaloffer\dto\CommercePersonalOfferValidationResult;
use local_subscriptions\commerce\personaloffer\repository\CommercePersonalOfferRepository;
use local_subscriptions\commerce\personaloffer\security\CommercePersonalOfferTokenCodec;
use local_subscriptions\commerce\personaloffer\security\CommercePersonalOfferTokenRepository;

final class CommercePersonalOfferService {
    private const PURCHASE_TABLE = 'local_subscriptions_commerce_purchase';
    private const PAYMENT_TABLE = 'local_subscriptions_commerce_payment';
    private const PRODUCT_TABLE = 'local_subs_commerce_product';
    private const SUCCESS_PAYMENT_STATUSES = ['paid', 'completed', 'captured', 'succeeded', 'success'];

    public function __construct(
        private readonly \moodle_database $db,
        private readonly CommercePersonalOfferRepository $offers,
        private readonly CommercePersonalOfferTokenRepository $tokens
    ) {}

    public function issue(CommercePersonalOfferIssueRequest $request): CommercePersonalOfferIssueResult {
        $this->assert_issue_references($request);
        $existing = $this->tokens->get_by_issuance_key($request->get_issuance_key());
        if ($existing !== null) {
            return $this->replay_issue($existing->get_offer_id(), $existing->get_request_hash(), $request);
        }

        $transaction = $this->db->start_delegated_transaction();
        try {
            $offer = new CommercePersonalOffer(
                null,
                bin2hex(random_bytes(16)),
                $request->get_campaign_key(),
                $request->get_source_purchase_id(),
                $request->get_target_product_id(),
                $request->get_beneficiary_user_id(),
                $request->get_beneficiary_email(),
                CommercePersonalOffer::STATUS_ISSUED,
                $request->get_terms(),
                $request->get_valid_from(),
                $request->get_expires_at(),
                $request->get_metadata(),
                null, null, null, null, null,
                $request->get_issued_by_user_id()
            );
            $offer = $this->offers->save($offer);
            $token = CommercePersonalOfferTokenCodec::build($offer->get_offer_uuid());
            $this->tokens->create(
                $offer->get_id() ?? 0,
                CommercePersonalOfferTokenCodec::VERSION,
                CommercePersonalOfferTokenCodec::fingerprint($token),
                $request->get_issuance_key(),
                $request->request_hash()
            );
            $transaction->allow_commit();
            return new CommercePersonalOfferIssueResult($offer, $token, false);
        } catch (dml_exception $exception) {
            try { $transaction->rollback($exception); } catch (\Throwable) {}
            $existing = $this->tokens->get_by_issuance_key($request->get_issuance_key());
            if ($existing !== null) {
                return $this->replay_issue($existing->get_offer_id(), $existing->get_request_hash(), $request);
            }
            throw $exception;
        } catch (\Throwable $exception) {
            try { $transaction->rollback($exception); } catch (\Throwable) {}
            throw $exception;
        }
    }

    public function validate_token(string $token, ?int $timestamp = null): CommercePersonalOfferValidationResult {
        $timestamp ??= time();
        $parsed = CommercePersonalOfferTokenCodec::parse_and_verify($token);
        if ($parsed === null) {
            return new CommercePersonalOfferValidationResult(CommercePersonalOfferValidationResult::INVALID);
        }
        $security = $this->tokens->get_by_hash(CommercePersonalOfferTokenCodec::fingerprint($token));
        if ($security === null || $security->get_token_version() !== $parsed['version']) {
            return new CommercePersonalOfferValidationResult(CommercePersonalOfferValidationResult::INVALID);
        }
        $offer = $this->offers->get_by_id($security->get_offer_id());
        if ($offer === null || !hash_equals($offer->get_offer_uuid(), $parsed['offeruuid'])) {
            return new CommercePersonalOfferValidationResult(CommercePersonalOfferValidationResult::INVALID);
        }
        if ($offer->get_status() === CommercePersonalOffer::STATUS_REDEEMED) {
            return new CommercePersonalOfferValidationResult(CommercePersonalOfferValidationResult::REDEEMED, $offer);
        }
        if ($offer->get_status() === CommercePersonalOffer::STATUS_REVOKED) {
            return new CommercePersonalOfferValidationResult(CommercePersonalOfferValidationResult::REVOKED, $offer);
        }
        if ($offer->is_expired($timestamp)) {
            return new CommercePersonalOfferValidationResult(CommercePersonalOfferValidationResult::EXPIRED, $offer);
        }
        if ($offer->get_valid_from() !== null && $timestamp < $offer->get_valid_from()) {
            return new CommercePersonalOfferValidationResult(CommercePersonalOfferValidationResult::NOT_YET_VALID, $offer);
        }
        return new CommercePersonalOfferValidationResult(CommercePersonalOfferValidationResult::VALID, $offer);
    }

    public function revoke(string $offeruuid, ?int $byuserid = null, ?string $reason = null, ?int $timestamp = null): CommercePersonalOffer {
        $offer = $this->offers->get_by_uuid($offeruuid) ?? throw new \moodle_exception('commerce_personal_offer_not_found', 'local_subscriptions');
        if ($offer->get_status() === CommercePersonalOffer::STATUS_REVOKED) {
            return $offer;
        }
        if ($offer->get_status() !== CommercePersonalOffer::STATUS_ISSUED) {
            throw new \moodle_exception('commerce_personal_offer_not_revocable', 'local_subscriptions');
        }
        return $this->with_lock($offeruuid, function() use ($offeruuid, $offer, $timestamp, $byuserid, $reason): CommercePersonalOffer {
            $current = $this->offers->get_by_uuid($offeruuid) ?? $offer;
            if ($current->get_status() === CommercePersonalOffer::STATUS_REVOKED) {
                return $current;
            }
            if ($current->get_status() !== CommercePersonalOffer::STATUS_ISSUED) {
                throw new \moodle_exception('commerce_personal_offer_not_revocable', 'local_subscriptions');
            }
            return $this->offers->save($current->as_revoked($timestamp ?? time(), $byuserid, $reason));
        });
    }

    public function redeem_by_offer_uuid(string $offeruuid, int $purchaseid, ?int $timestamp = null): CommercePersonalOffer {
        $offer = $this->offers->get_by_uuid($offeruuid)
            ?? throw new \moodle_exception('commerce_personal_offer_not_found', 'local_subscriptions');
        if ($offer->get_status() === CommercePersonalOffer::STATUS_REDEEMED
                && $offer->get_redeemed_purchase_id() === $purchaseid) {
            return $offer;
        }
        if (!$offer->is_available_at($timestamp ?? time())) {
            throw new \moodle_exception('commerce_personal_offer_not_redeemable', 'local_subscriptions');
        }
        $this->assert_redemption_purchase($offer, $purchaseid);
        return $this->with_lock($offeruuid, function() use ($offeruuid, $purchaseid, $timestamp): CommercePersonalOffer {
            $current = $this->offers->get_by_uuid($offeruuid)
                ?? throw new \RuntimeException('Personal Offer disappeared during redemption.');
            if ($current->get_status() === CommercePersonalOffer::STATUS_REDEEMED
                    && $current->get_redeemed_purchase_id() === $purchaseid) {
                return $current;
            }
            if (!$current->is_available_at($timestamp ?? time())) {
                throw new \moodle_exception('commerce_personal_offer_not_redeemable', 'local_subscriptions');
            }
            return $this->offers->save($current->as_redeemed($purchaseid, $timestamp ?? time()));
        });
    }

    public function redeem(string $token, int $purchaseid, ?int $timestamp = null): CommercePersonalOffer {
        $validation = $this->validate_token($token, $timestamp);
        if (!$validation->is_valid() || $validation->get_offer() === null) {
            throw new \moodle_exception('commerce_personal_offer_not_redeemable', 'local_subscriptions');
        }
        $offer = $validation->get_offer();
        $this->assert_redemption_purchase($offer, $purchaseid);
        return $this->with_lock($offer->get_offer_uuid(), function() use ($offer, $purchaseid, $timestamp): CommercePersonalOffer {
            $current = $this->offers->get_by_uuid($offer->get_offer_uuid()) ?? throw new \RuntimeException('Personal Offer disappeared during redemption.');
            if ($current->get_status() === CommercePersonalOffer::STATUS_REDEEMED && $current->get_redeemed_purchase_id() === $purchaseid) {
                return $current;
            }
            if (!$current->is_available_at($timestamp ?? time())) {
                throw new \moodle_exception('commerce_personal_offer_not_redeemable', 'local_subscriptions');
            }
            return $this->offers->save($current->as_redeemed($purchaseid, $timestamp ?? time()));
        });
    }

    public function bind_beneficiary_user(string $email, int $userid): int {
        $email = strtolower(trim($email));
        if ($userid <= 0 || !validate_email($email)) {
            return 0;
        }
        $records = $this->offers->find(['email' => $email, 'status' => CommercePersonalOffer::STATUS_ISSUED], 10000, 0);
        $updated = 0;
        foreach ($records as $offer) {
            if ($offer->get_beneficiary_user_id() === null) {
                $this->offers->save($offer->with_beneficiary_user($userid));
                $updated++;
            }
        }
        return $updated;
    }

    private function replay_issue(int $offerid, string $requesthash, CommercePersonalOfferIssueRequest $request): CommercePersonalOfferIssueResult {
        if (!hash_equals($requesthash, $request->request_hash())) {
            throw new \RuntimeException('Personal Offer issuance key reused with a different payload.');
        }
        $offer = $this->offers->get_by_id($offerid) ?? throw new \RuntimeException('Idempotent Personal Offer points to a missing offer.');
        return new CommercePersonalOfferIssueResult($offer, CommercePersonalOfferTokenCodec::build($offer->get_offer_uuid()), true);
    }

    private function assert_issue_references(CommercePersonalOfferIssueRequest $request): void {
        if (!$this->db->record_exists(self::PRODUCT_TABLE, ['id' => $request->get_target_product_id()])) {
            throw new \coding_exception('Personal Offer target product does not exist.');
        }
        if ($request->get_source_purchase_id() !== null && !$this->db->record_exists(self::PURCHASE_TABLE, ['id' => $request->get_source_purchase_id()])) {
            throw new \coding_exception('Personal Offer source purchase does not exist.');
        }
        if ($request->get_beneficiary_user_id() !== null) {
            $user = $this->db->get_record('user', ['id' => $request->get_beneficiary_user_id(), 'deleted' => 0]);
            if (!$user || strcasecmp((string)$user->email, $request->get_beneficiary_email()) !== 0) {
                throw new \coding_exception('Personal Offer beneficiary user and email do not match.');
            }
        }
    }

    private function assert_redemption_purchase(CommercePersonalOffer $offer, int $purchaseid): void {
        $purchase = $this->db->get_record(self::PURCHASE_TABLE, ['id' => $purchaseid], '*', MUST_EXIST);
        $paid = false;
        foreach (self::SUCCESS_PAYMENT_STATUSES as $status) {
            if ($this->db->record_exists(self::PAYMENT_TABLE, ['purchaseid' => $purchaseid, 'status' => $status])) {
                $paid = true; break;
            }
        }
        if (!$paid) {
            throw new \moodle_exception('commerce_personal_offer_purchase_not_paid', 'local_subscriptions');
        }
        $emailmatches = !empty($purchase->customeremail) && strcasecmp((string)$purchase->customeremail, $offer->get_beneficiary_email()) === 0;
        $usermatches = $offer->get_beneficiary_user_id() !== null && (int)$purchase->userid === $offer->get_beneficiary_user_id();
        if (!$emailmatches && !$usermatches) {
            throw new \moodle_exception('commerce_personal_offer_identity_mismatch', 'local_subscriptions');
        }
    }

    private function with_lock(string $offeruuid, callable $operation): mixed {
        $factory = \core\lock\lock_config::get_lock_factory('local_subscriptions_personaloffer');
        $lock = $factory->get_lock('offer:' . $offeruuid, 10);
        if ($lock === false) {
            throw new \RuntimeException('Unable to acquire Personal Offer lifecycle lock.');
        }
        try { return $operation(); } finally { $lock->release(); }
    }
}
