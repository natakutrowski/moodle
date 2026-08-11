<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\template;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\mail\CommerceMailType;

final class CommercePaymentPendingTemplate extends AbstractCommerceMailTemplate {
    public function get_type(): string { return CommerceMailType::PAYMENT_PENDING; }
    protected function subject_key(): string { return 'commerce_mail_payment_pending_subject'; }
    protected function template_name(): string { return 'payment_pending'; }

    protected function primary_action_icon(array $context): string {
        return 'receipt';
    }
}
