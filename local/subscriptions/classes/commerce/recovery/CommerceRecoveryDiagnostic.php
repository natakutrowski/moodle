<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\recovery;

defined('MOODLE_INTERNAL') || die();

/** Immutable diagnostic and repair plan for one Native Commerce checkout. */
final class CommerceRecoveryDiagnostic {
    public function __construct(
        private readonly ?array $purchase,
        private readonly array $payments,
        private readonly array $fulfillments,
        private readonly ?array $guestsession,
        private readonly array $issues,
        private readonly array $actions
    ) {
    }

    public function get_purchase(): ?array { return $this->purchase; }
    public function get_payments(): array { return $this->payments; }
    public function get_fulfillments(): array { return $this->fulfillments; }
    public function get_guest_session(): ?array { return $this->guestsession; }
    public function get_issues(): array { return $this->issues; }
    public function get_actions(): array { return $this->actions; }
    public function is_found(): bool { return $this->purchase !== null; }
    public function is_healthy(): bool { return $this->is_found() && $this->issues === []; }
    public function is_repairable(): bool { return $this->is_found() && $this->actions !== []; }

    public function to_array(): array {
        return [
            'purchase' => $this->purchase,
            'payments' => $this->payments,
            'fulfillments' => $this->fulfillments,
            'guest_session' => $this->guestsession,
            'issues' => $this->issues,
            'actions' => $this->actions,
            'healthy' => $this->is_healthy(),
            'repairable' => $this->is_repairable(),
        ];
    }
}
