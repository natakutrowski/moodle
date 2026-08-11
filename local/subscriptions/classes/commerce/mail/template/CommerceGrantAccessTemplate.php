<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\template;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\mail\CommerceMailType;

final class CommerceGrantAccessTemplate extends AbstractCommerceMailTemplate {
    public function get_type(): string {
        return CommerceMailType::GRANT_ACCESS;
    }

    protected function subject_key(): string {
        return 'commerce_mail_grant_access_subject';
    }

    protected function template_name(): string {
        return 'grant_access';
    }

    protected function primary_action_label(array $context): ?string {
        return get_string('commerce_mail_access_my_campus', 'local_subscriptions');
    }

    protected function primary_action_icon(array $context): string {
        return 'external';
    }

    protected function primary_action_url(array $context): ?string {
        return !empty($context['links']['hascampus'])
            ? (string)$context['links']['campus']
            : (new \moodle_url('/mon-campus'))->out(false);
    }
}
