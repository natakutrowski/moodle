<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\template;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\mail\CommerceMailType;

final class CommercePaymentCancelledTemplate extends AbstractCommerceMailTemplate {
    public function get_type(): string { return CommerceMailType::PAYMENT_CANCELLED; }
    protected function subject_key(): string { return 'commerce_mail_payment_cancelled_subject'; }
    protected function template_name(): string { return 'payment_cancelled'; }

    protected function primary_action_icon(array $context): string {
        return 'receipt';
    }
}
