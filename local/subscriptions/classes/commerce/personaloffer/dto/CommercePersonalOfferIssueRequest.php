<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\personaloffer\dto;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\personaloffer\domain\CommercePersonalOfferTerms;

final class CommercePersonalOfferIssueRequest {
    public function __construct(
        private readonly string $issuancekey,
        private readonly int $targetproductid,
        private readonly string $beneficiaryemail,
        private readonly CommercePersonalOfferTerms $terms,
        private readonly ?string $campaignkey = null,
        private readonly ?int $sourcepurchaseid = null,
        private readonly ?int $beneficiaryuserid = null,
        private readonly ?int $validfrom = null,
        private readonly ?int $expiresat = null,
        private readonly array $metadata = [],
        private readonly ?int $issuedbyuserid = null
    ) {
        if (trim($issuancekey) === '' || strlen(trim($issuancekey)) > 191) {
            throw new \coding_exception('A Personal Offer issuance key between 1 and 191 characters is required.');
        }
    }

    public function get_issuance_key(): string { return trim($this->issuancekey); }
    public function get_target_product_id(): int { return $this->targetproductid; }
    public function get_beneficiary_email(): string { return strtolower(trim($this->beneficiaryemail)); }
    public function get_terms(): CommercePersonalOfferTerms { return $this->terms; }
    public function get_campaign_key(): ?string { return $this->campaignkey; }
    public function get_source_purchase_id(): ?int { return $this->sourcepurchaseid; }
    public function get_beneficiary_user_id(): ?int { return $this->beneficiaryuserid; }
    public function get_valid_from(): ?int { return $this->validfrom; }
    public function get_expires_at(): ?int { return $this->expiresat; }
    public function get_metadata(): array { return $this->metadata; }
    public function get_issued_by_user_id(): ?int { return $this->issuedbyuserid; }

    public function request_hash(): string {
        $payload = [
            'targetproductid' => $this->targetproductid,
            'beneficiaryemail' => $this->get_beneficiary_email(),
            'campaignkey' => $this->campaignkey,
            'sourcepurchaseid' => $this->sourcepurchaseid,
            'beneficiaryuserid' => $this->beneficiaryuserid,
            'validfrom' => $this->validfrom,
            'expiresat' => $this->expiresat,
            'termsversion' => $this->terms->get_version(),
            'terms' => $this->terms->get_data(),
            'metadata' => $this->metadata,
        ];
        return hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
    }
}
