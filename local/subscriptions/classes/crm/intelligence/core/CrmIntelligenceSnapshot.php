<?php

namespace local_subscriptions\crm\intelligence\core;

defined('MOODLE_INTERNAL') || die();

final class CrmIntelligenceSnapshot {

    public function __construct(
        public readonly int $userid,
        public readonly bool $suspended,
        public readonly int $timecreated,
        public readonly int $lastaccess,
        public readonly int $lastactivity,
        public readonly int $activeSubscriptions,
        public readonly int $trialSubscriptions,
        public readonly int $expiredSubscriptions,
        public readonly int $paidSubscriptions,
        public readonly int $paidDigitalPurchases,
        public readonly float $spentEur,
        public readonly float $spentRub,
        public readonly int $notesCount,
        public readonly int $tagsCount,
        public readonly array $tags
    ) {
    }

    public function days_since_last_activity(): ?int {
        if ($this->lastactivity <= 0) {
            return null;
        }

        return max(0, (int)floor((time() - $this->lastactivity) / DAYSECS));
    }

    public function is_customer(): bool {
        return $this->activeSubscriptions > 0 || $this->paidSubscriptions > 0 || $this->paidDigitalPurchases > 0;
    }

    public function is_trial(): bool {
        return $this->trialSubscriptions > 0;
    }

    public function total_spent_normalized(): float {
        return $this->spentEur + ($this->spentRub / 100);
    }
}