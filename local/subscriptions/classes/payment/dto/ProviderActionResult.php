<?php
namespace local_subscriptions\payment\dto;

final class ProviderActionResult {
    public function __construct(public bool $ok, public ?string $message = null) {}
}
