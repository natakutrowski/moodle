<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\template;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\mail\CommerceMailMessage;
use local_subscriptions\commerce\mail\CommerceMailRequest;
use local_subscriptions\commerce\mail\CommerceMailTemplate;
use local_subscriptions\commerce\mail\CommerceMailType;
use local_subscriptions\mail\MailRenderer;

/** Transactional six-digit Guest Checkout identity verification email. */
final class CommerceGuestIdentityOtpTemplate implements CommerceMailTemplate {
    public function get_type(): string {
        return CommerceMailType::GUEST_IDENTITY_OTP;
    }

    public function render(CommerceMailRequest $request): CommerceMailMessage {
        $previouslanguage = force_current_language($request->get_language());

        try {
            $context = $request->get_context();
            $code = trim((string)$context->require('code'));
            $minutes = (int)$context->get('expiresminutes', 10);
            $name = trim((string)$context->get('name', ''));

            if (!preg_match('/^\d{6}$/', $code)) {
                throw new \coding_exception(
                    'Guest identity OTP mail requires a six-digit code.'
                );
            }

            $subject = get_string(
                'commerce_guest_identity_otp_mail_subject',
                'local_subscriptions'
            );
            $greeting = $name !== ''
                ? get_string(
                    'commerce_guest_identity_otp_mail_greeting_name',
                    'local_subscriptions',
                    $name
                )
                : get_string(
                    'commerce_guest_identity_otp_mail_greeting',
                    'local_subscriptions'
                );

            $bodyhtml =
                '<p>' . s($greeting) . '</p>'
                . '<p>' . s(get_string(
                    'commerce_guest_identity_otp_mail_intro',
                    'local_subscriptions'
                )) . '</p>'
                . '<div style="margin:24px 0;text-align:center;">'
                . '<span style="display:inline-block;padding:14px 22px;'
                . 'border:1px solid #ead7e0;border-radius:12px;'
                . 'font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;'
                . 'font-size:30px;font-weight:800;letter-spacing:8px;color:#1f2937;'
                . 'background:#fff8fb;">'
                . s($code)
                . '</span></div>'
                . '<p>' . s(get_string(
                    'commerce_guest_identity_otp_mail_expiry',
                    'local_subscriptions',
                    $minutes
                )) . '</p>'
                . '<p style="color:#6b7280;font-size:13px;">'
                . s(get_string(
                    'commerce_guest_identity_otp_mail_ignore',
                    'local_subscriptions'
                )) . '</p>';

            [$html, $text] = MailRenderer::layout(
                $subject,
                $bodyhtml,
                null,
                null,
                [
                    'preheader' => get_string(
                        'commerce_guest_identity_otp_mail_preheader',
                        'local_subscriptions'
                    ),
                ]
            );

            return new CommerceMailMessage(
                $request->get_recipient(),
                $subject,
                $html,
                $text,
                [
                    'language' => $request->get_language(),
                    'template' => 'guest_identity_otp',
                ]
            );
        } finally {
            force_current_language($previouslanguage);
        }
    }
}
