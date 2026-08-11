<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\personaloffer\campaign;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\personaloffer\domain\CommercePersonalOfferTerms;

final class CommercePersonalOfferCampaignRequest {
    public function __construct(
        private readonly string $campaignkey,
        private readonly string $sourceproductsku,
        private readonly int $targetproductid,
        private readonly CommercePersonalOfferTerms $terms,
        private readonly ?int $validfrom = null,
        private readonly ?int $expiresat = null,
        private readonly bool $excludealreadyownedtarget = true,
        private readonly ?int $issuedbyuserid = null
    ) {
        if (!preg_match('/^[a-z0-9][a-z0-9._:-]{2,190}$/i', trim($campaignkey))) {
            throw new \coding_exception('Invalid Personal Offer campaign key.');
        }
        if (trim($sourceproductsku) === '' || $targetproductid <= 0) {
            throw new \coding_exception('A source SKU and target product are required.');
        }
        if ($validfrom !== null && $expiresat !== null && $expiresat <= $validfrom) {
            throw new \coding_exception('Personal Offer campaign expiration must be after its start.');
        }
    }

    public function campaign_key(): string { return trim($this->campaignkey); }
    public function source_product_sku(): string { return strtoupper(trim($this->sourceproductsku)); }
    public function target_product_id(): int { return $this->targetproductid; }
    public function terms(): CommercePersonalOfferTerms { return $this->terms; }
    public function valid_from(): ?int { return $this->validfrom; }
    public function expires_at(): ?int { return $this->expiresat; }
    public function exclude_already_owned_target(): bool { return $this->excludealreadyownedtarget; }
    public function issued_by_user_id(): ?int { return $this->issuedbyuserid; }
}
