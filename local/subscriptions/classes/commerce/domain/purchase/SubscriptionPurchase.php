<?php

namespace local_subscriptions\commerce\domain\purchase;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\domain\CommerceItem;
use local_subscriptions\commerce\domain\CommercePayment;
use local_subscriptions\commerce\domain\CommercePurchase;

/**
 * Commerce representation of a historical subscription purchase.
 */
final class SubscriptionPurchase extends CommercePurchase {

    public const TYPE = 'subscription';

    public function __construct(
        string $reference,
        CommerceItem $item,
        CommercePayment $payment,
        ?int $userid,
        ?string $customeremail,
        string $status,
        private readonly int $legacysubscriptionid,
        private readonly int $planid,
        private readonly ?int $startdate = null,
        private readonly ?int $enddate = null,
        ?int $createdat = null,
        ?int $updatedat = null,
        array $metadata = []
    ) {
        if ($legacysubscriptionid <= 0) {
            throw new \coding_exception('A legacy subscription identifier must be positive.');
        }

        if ($planid <= 0) {
            throw new \coding_exception('A subscription plan identifier must be positive.');
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

    public function get_legacy_subscription_id(): int {
        return $this->legacysubscriptionid;
    }

    public function get_plan_id(): int {
        return $this->planid;
    }

    public function get_start_date(): ?int {
        return $this->startdate;
    }

    public function get_end_date(): ?int {
        return $this->enddate;
    }

    public function is_current_at(?int $timestamp = null): bool {
        $timestamp = $timestamp ?? time();

        if ($this->startdate !== null && $this->startdate > $timestamp) {
            return false;
        }

        if ($this->enddate !== null && $this->enddate < $timestamp) {
            return false;
        }

        return in_array($this->get_status(), [
            'active',
            'queued',
        ], true);
    }
}