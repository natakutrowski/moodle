<?php
declare(strict_types=1);
namespace local_subscriptions\payment\stripe\webhook;
defined('MOODLE_INTERNAL') || die();
use local_subscriptions\payment\dto\InternalEvent;
final class StripeVerifiedWebhook {
    public function __construct(public readonly string $profile, public readonly InternalEvent $event) {}
}
