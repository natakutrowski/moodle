<?php
namespace local_subscriptions\payment\dto;

final class ProviderCapabilities {
    public bool $supports_recurring = false;
    public bool $supports_portal = false;
    public array $currencies = [];
}
