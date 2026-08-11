<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\template;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\mail\CommerceMailType;

final class CommercePaymentFailedTemplate extends AbstractCommerceMailTemplate {
    public function get_type(): string { return CommerceMailType::PAYMENT_FAILED; }
    protected function subject_key(): string { return 'commerce_mail_payment_failed_subject'; }
    protected function template_name(): string { return 'payment_failed'; }

    protected function primary_action_label(array $context): ?string {
        return !empty($context['links']['haspurchases'])
            ? get_string('commerce_mail_view_purchases', 'local_subscriptions')
            : parent::primary_action_label($context);
    }

    protected function primary_action_url(array $context): ?string {
        return !empty($context['links']['haspurchases'])
            ? (string)$context['links']['purchases']
            : parent::primary_action_url($context);
    }

    protected function primary_action_icon(array $context): string {
        return 'receipt';
    }
}
