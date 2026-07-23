<?php

namespace local_subscriptions\commerce\domain\purchase;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\domain\CommerceItem;
use local_subscriptions\commerce\domain\CommercePayment;
use local_subscriptions\commerce\domain\CommercePurchase;

/**
 * Commerce representation of a historical digital-product purchase.
 */
final class DigitalPurchase extends CommercePurchase {

    public const TYPE = 'digital';

    public function __construct(
        string $reference,
        CommerceItem $item,
        CommercePayment $payment,
        ?int $userid,
        ?string $customeremail,
        string $status,
        private readonly int $legacypurchaseid,
        private readonly int $productid,
        private readonly ?string $downloadtoken = null,
        private readonly ?int $downloadtokenexpires = null,
        ?int $createdat = null,
        ?int $updatedat = null,
        array $metadata = []
    ) {
        if ($legacypurchaseid <= 0) {
            throw new \coding_exception('A legacy digital purchase identifier must be positive.');
        }

        if ($productid <= 0) {
            throw new \coding_exception('A digital product identifier must be positive.');
        }

        parent::__construct(
            $reference,
            $item,
            $payment,
            $userid,
            $customeremail,
            $status,
            $createdat,
            $updatedat,
            $metadata
        );
    }

    public function get_type(): string {
        return self::TYPE;
    }

    public function get_legacy_purchase_id(): int {
        return $this->legacypurchaseid;
    }

    public function get_product_id(): int {
        return $this->productid;
    }

    public function get_download_token(): ?string {
        return $this->downloadtoken;
    }

    public function get_download_token_expires(): ?int {
        return $this->downloadtokenexpires;
    }

    public function has_download_access(?int $timestamp = null): bool {
        if (!$this->is_paid() || empty($this->downloadtoken)) {
            return false;
        }

        if ($this->downloadtokenexpires === null) {
            return true;
        }

        $timestamp = $timestamp ?? time();

        return $this->downloadtokenexpires >= $timestamp;
    }
}