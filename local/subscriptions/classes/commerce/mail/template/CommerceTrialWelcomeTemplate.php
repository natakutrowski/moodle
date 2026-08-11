<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\template;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\mail\CommerceMailRequest;
use local_subscriptions\commerce\mail\CommerceMailType;

final class CommerceTrialWelcomeTemplate extends AbstractCommerceMailTemplate {
    public function get_type(): string {
        return CommerceMailType::TRIAL_WELCOME;
    }

    protected function subject_key(): string {
        return 'commerce_mail_trial_welcome_subject';
    }

    protected function template_name(): string {
        return 'trial_welcome';
    }

    protected function additional_context(CommerceMailRequest $request): array {
        $context = $request->get_context();
        $email = trim((string)$context->get('accountemail', ''));
        $trialurl = trim((string)$context->get('trialurl', ''));
        $reseturl = trim((string)$context->get('reseturl', ''));

        return [
            'accountemail' => $email,
            'hasaccountemail' => $email !== '',
            'trialurl' => $trialurl,
            'hastrialurl' => $trialurl !== '',
            'reseturl' => $reseturl,
            'hasreseturl' => $reseturl !== '',
        ];
    }

    protected function primary_action_label(array $context): ?string {
        return !empty($context['hastrialurl'])
            ? get_string('commerce_mail_trial_welcome_cta', 'local_subscriptions')
            : null;
    }

    protected function primary_action_url(array $context): ?string {
        return !empty($context['hastrialurl']) ? (string)$context['trialurl'] : null;
    }

    protected function primary_action_icon(array $context): string {
        return 'external';
    }

    protected function primary_action_after_html(array $context): string {
        global $CFG;

        $imageurl = rtrim((string)$CFG->wwwroot, '/')
            . '/local/subscriptions/pix/email/trial-welcome.jpg';

        return '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" '
            . 'style="margin:0 0 26px;">'
            . '<tr><td style="padding:0;">'
            . '<img src="' . s($imageurl) . '" alt="" width="752" '
            . 'style="display:block;width:100%;max-width:752px;height:auto;border:0;border-radius:14px;'
            . 'outline:none;text-decoration:none;">'
            . '</td></tr></table>';
    }
}
