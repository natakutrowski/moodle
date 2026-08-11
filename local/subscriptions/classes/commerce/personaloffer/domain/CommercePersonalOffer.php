<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\personaloffer\domain;

defined('MOODLE_INTERNAL') || die();

/** Immutable assignment of individual commercial terms to one customer identity. */
final class CommercePersonalOffer {
    public const STATUS_ISSUED = 'issued';
    public const STATUS_REDEEMED = 'redeemed';
    public const STATUS_REVOKED = 'revoked';
    public const EFFECTIVE_EXPIRED = 'expired';

    public function __construct(
        private readonly ?int $id,
        private readonly string $offeruuid,
        private readonly ?string $campaignkey,
        private readonly ?int $sourcepurchaseid,
        private readonly int $targetproductid,
        private readonly ?int $beneficiaryuserid,
        private readonly string $beneficiaryemail,
        private readonly string $status,
        private readonly CommercePersonalOfferTerms $terms,
        private readonly ?int $validfrom = null,
        private readonly ?int $expiresat = null,
        private readonly array $metadata = [],
        private readonly ?int $redeemedat = null,
        private readonly ?int $redeemedpurchaseid = null,
        private readonly ?int $revokedat = null,
        private readonly ?int $revokedbyuserid = null,
        private readonly ?string $revokereason = null,
        private readonly ?int $issuedbyuserid = null,
        private readonly ?int $timecreated = null,
        private readonly ?int $timemodified = null
    ) {
        if (!preg_match('/^[a-f0-9]{32}$/', strtolower(trim($offeruuid)))) {
            throw new \coding_exception('A Personal Offer UUID must be a 32-character hexadecimal identifier.');
        }
        if ($targetproductid <= 0) {
            throw new \coding_exception('A Personal Offer requires a target Native Commerce product.');
        }
        if ($sourcepurchaseid !== null && $sourcepurchaseid <= 0) {
            throw new \coding_exception('A Personal Offer source purchase identifier must be positive.');
        }
        if ($beneficiaryuserid !== null && $beneficiaryuserid <= 0) {
            throw new \coding_exception('A Personal Offer beneficiary user identifier must be positive.');
        }
        $email = strtolower(trim($beneficiaryemail));
        if ($email === '' || !validate_email($email)) {
            throw new \coding_exception('A Personal Offer requires a valid beneficiary email.');
        }
        if (!in_array($status, [self::STATUS_ISSUED, self::STATUS_REDEEMED, self::STATUS_REVOKED], true)) {
            throw new \coding_exception('Unsupported Personal Offer status.');
        }
        if ($validfrom !== null && $expiresat !== null && $expiresat <= $validfrom) {
            throw new \coding_exception('A Personal Offer expiration must be after its validity start.');
        }
        if ($status === self::STATUS_REDEEMED && ($redeemedat === null || $redeemedpurchaseid === null)) {
            throw new \coding_exception('A redeemed Personal Offer requires redemption time and purchase.');
        }
        if ($status !== self::STATUS_REDEEMED && ($redeemedat !== null || $redeemedpurchaseid !== null)) {
            throw new \coding_exception('Only redeemed Personal Offers may contain redemption evidence.');
        }
        if ($status === self::STATUS_REVOKED && $revokedat === null) {
            throw new \coding_exception('A revoked Personal Offer requires a revocation time.');
        }
        if ($status !== self::STATUS_REVOKED && ($revokedat !== null || $revokedbyuserid !== null || $revokereason !== null)) {
            throw new \coding_exception('Only revoked Personal Offers may contain revocation evidence.');
        }
    }

    public function get_id(): ?int { return $this->id; }
    public function get_offer_uuid(): string { return strtolower($this->offeruuid); }
    public function get_campaign_key(): ?string { return $this->campaignkey; }
    public function get_source_purchase_id(): ?int { return $this->sourcepurchaseid; }
    public function get_target_product_id(): int { return $this->targetproductid; }
    public function get_beneficiary_user_id(): ?int { return $this->beneficiaryuserid; }
    public function get_beneficiary_email(): string { return strtolower(trim($this->beneficiaryemail)); }
    public function get_status(): string { return $this->status; }
    public function get_terms(): CommercePersonalOfferTerms { return $this->terms; }
    public function get_valid_from(): ?int { return $this->validfrom; }
    public function get_expires_at(): ?int { return $this->expiresat; }
    public function get_metadata(): array { return $this->metadata; }
    public function get_redeemed_at(): ?int { return $this->redeemedat; }
    public function get_redeemed_purchase_id(): ?int { return $this->redeemedpurchaseid; }
    public function get_revoked_at(): ?int { return $this->revokedat; }
    public function get_revoked_by_user_id(): ?int { return $this->revokedbyuserid; }
    public function get_revoke_reason(): ?string { return $this->revokereason; }
    public function get_issued_by_user_id(): ?int { return $this->issuedbyuserid; }
    public function get_time_created(): ?int { return $this->timecreated; }
    public function get_time_modified(): ?int { return $this->timemodified; }

    public function with_beneficiary_user(int $userid): self {
        return new self(
            $this->id, $this->offeruuid, $this->campaignkey, $this->sourcepurchaseid, $this->targetproductid,
            $userid, $this->beneficiaryemail, $this->status, $this->terms, $this->validfrom, $this->expiresat,
            $this->metadata, $this->redeemedat, $this->redeemedpurchaseid, $this->revokedat,
            $this->revokedbyuserid, $this->revokereason, $this->issuedbyuserid, $this->timecreated, $this->timemodified
        );
    }

    public function as_redeemed(int $purchaseid, int $timestamp): self {
        if ($this->status !== self::STATUS_ISSUED) {
            throw new \coding_exception('Only issued Personal Offers may be redeemed.');
        }
        return new self(
            $this->id, $this->offeruuid, $this->campaignkey, $this->sourcepurchaseid, $this->targetproductid,
            $this->beneficiaryuserid, $this->beneficiaryemail, self::STATUS_REDEEMED, $this->terms,
            $this->validfrom, $this->expiresat, $this->metadata, $timestamp, $purchaseid,
            null, null, null, $this->issuedbyuserid, $this->timecreated, $this->timemodified
        );
    }

    public function as_revoked(int $timestamp, ?int $byuserid = null, ?string $reason = null): self {
        if ($this->status !== self::STATUS_ISSUED) {
            throw new \coding_exception('Only issued Personal Offers may be revoked.');
        }
        return new self(
            $this->id, $this->offeruuid, $this->campaignkey, $this->sourcepurchaseid, $this->targetproductid,
            $this->beneficiaryuserid, $this->beneficiaryemail, self::STATUS_REVOKED, $this->terms,
            $this->validfrom, $this->expiresat, $this->metadata, null, null,
            $timestamp, $byuserid, $reason, $this->issuedbyuserid, $this->timecreated, $this->timemodified
        );
    }

    public function is_expired(int $timestamp): bool {
        return $this->status === self::STATUS_ISSUED && $this->expiresat !== null && $timestamp > $this->expiresat;
    }

    public function is_available_at(int $timestamp): bool {
        if ($this->status !== self::STATUS_ISSUED || $this->is_expired($timestamp)) {
            return false;
        }
        return $this->validfrom === null || $timestamp >= $this->validfrom;
    }

    public function get_effective_status(int $timestamp): string {
        return $this->is_expired($timestamp) ? self::EFFECTIVE_EXPIRED : $this->status;
    }
}
