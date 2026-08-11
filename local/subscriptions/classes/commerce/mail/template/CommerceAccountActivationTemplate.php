<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\template;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\mail\CommerceMailRequest;
use local_subscriptions\commerce\mail\CommerceMailType;

/** CampusFR account activation message for paid Guest Checkout customers. */
final class CommerceAccountActivationTemplate extends AbstractCommerceMailTemplate {
    public function get_type(): string { return CommerceMailType::ACCOUNT_ACTIVATION; }
    protected function subject_key(): string { return 'commerce_guest_checkout_activation_subject'; }
    protected function template_name(): string { return 'account_activation'; }

    protected function additional_context(CommerceMailRequest $request): array {
        global $CFG;

        $mailcontext = $request->get_context();
        $activationurl = trim((string)$mailcontext->get('activationurl', ''));
        $expirestimestamp = (int)$mailcontext->get('activationexpirestimestamp', 0);
        $legacyexpires = trim((string)$mailcontext->get('activationexpires', ''));
        $accountemail = trim((string)$mailcontext->get('accountemail', ''));

        return [
            'activationurl' => $activationurl,
            'hasactivationurl' => $activationurl !== '',
            'activationexpires' => $expirestimestamp > 0
                ? userdate($expirestimestamp, get_string('strftimedatetime'))
                : $legacyexpires,
            'accountemail' => $accountemail,
            'hasaccountemail' => $accountemail !== '',
            'telegramchannelurl' => 'https://t.me/+tXrnh5eHmzszNWNk',
            'telegramgroupurl' => 'https://t.me/+Ze_-_1hWxgJlYWZk',
            'telegramiconurl' => rtrim((string)$CFG->wwwroot, '/')
                . '/local/subscriptions/pix/email/telegram-white.png',
            'keyiconurl' => rtrim((string)$CFG->wwwroot, '/')
                . '/local/subscriptions/pix/email/key-white.png',
            'welcomeimageurl' => rtrim((string)$CFG->wwwroot, '/')
                . '/local/subscriptions/pix/email/welcome-campus.jpg',
        ];
    }

    protected function primary_action_label(array $context): ?string {
        return null;
    }

    protected function primary_action_url(array $context): ?string {
        return null;
    }
}
