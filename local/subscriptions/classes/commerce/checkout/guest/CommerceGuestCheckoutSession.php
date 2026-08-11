<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\checkout\guest;

defined('MOODLE_INTERNAL') || die();

/** Immutable Guest Checkout session snapshot. */
final class CommerceGuestCheckoutSession {
    public function __construct(private readonly \stdClass $record) {}

    public function get_id(): int { return (int) $this->record->id; }
    public function get_reference(): string { return (string) $this->record->reference; }
    public function get_token(): string { return (string) $this->record->token; }
    public function get_status(): string { return (string) $this->record->status; }
    public function get_currency(): string { return (string) $this->record->currency; }
    public function get_user_id(): ?int { return $this->record->userid === null ? null : (int) $this->record->userid; }
    public function get_email(): ?string { return $this->normalise($this->record->email ?? null); }
    public function get_first_name(): ?string { return $this->normalise($this->record->firstname ?? null); }
    public function get_last_name(): ?string { return $this->normalise($this->record->lastname ?? null); }
    public function get_purchase_reference(): ?string { return $this->normalise($this->record->purchasereference ?? null); }
    public function get_payment_reference(): ?string { return $this->normalise($this->record->paymentreference ?? null); }
    public function get_expires_at(): int { return (int) $this->record->expiresat; }
    public function is_expired(?int $now = null): bool { return $this->get_expires_at() <= ($now ?? time()); }
    public function get_metadata(): array {
        $decoded = json_decode((string) $this->record->metadatajson, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function normalise(mixed $value): ?string {
        if ($value === null) { return null; }
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }
}
