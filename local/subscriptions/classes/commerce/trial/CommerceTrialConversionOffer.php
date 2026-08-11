<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\trial;

defined('MOODLE_INTERNAL') || die();

/** Immutable bridge between the Legacy trial and a Native Commerce offer. */
final class CommerceTrialConversionOffer {
    public function __construct(
        private readonly int $userid,
        private readonly int $discountpercent,
        private readonly int $expiresat,
        private readonly ?string $productsku,
        private readonly \moodle_url $url
    ) {
        if ($userid <= 0) {
            throw new \coding_exception('A trial conversion offer requires a valid user.');
        }
        if ($discountpercent <= 0 || $discountpercent > 100) {
            throw new \coding_exception('A trial conversion discount must be between 1 and 100 percent.');
        }
        if ($expiresat <= 0) {
            throw new \coding_exception('A trial conversion offer requires an expiry timestamp.');
        }
    }

    public function get_user_id(): int {
        return $this->userid;
    }

    public function get_discount_percent(): int {
        return $this->discountpercent;
    }

    public function get_expires_at(): int {
        return $this->expiresat;
    }

    public function get_product_sku(): ?string {
        return $this->productsku;
    }

    public function get_url(): \moodle_url {
        return new \moodle_url($this->url);
    }

    public function targets_product(): bool {
        return $this->productsku !== null;
    }
}
