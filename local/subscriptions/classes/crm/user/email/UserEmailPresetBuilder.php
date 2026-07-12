<?php

namespace local_subscriptions\crm\user\email;

defined('MOODLE_INTERNAL') || die();

final class UserEmailPresetBuilder {

    public const DIGITAL_PAYMENT_HELP = 'digital_payment_help';

    public static function allowed(): array {
        return [
            '',
            self::DIGITAL_PAYMENT_HELP,
        ];
    }

    public static function normalize(string $preset): string {
        return in_array($preset, self::allowed(), true) ? $preset : '';
    }

    public function build(
        string $preset,
        \stdClass $user,
        ?\stdClass $purchase = null
    ): ?UserEmailPreset {
        $preset = self::normalize($preset);

        if ($preset === self::DIGITAL_PAYMENT_HELP) {
            return $this->build_digital_payment_help($user, $purchase);
        }

        return null;
    }

    private function build_digital_payment_help(
        \stdClass $user,
        ?\stdClass $purchase
    ): UserEmailPreset {
        if ($purchase === null) {
            throw new \coding_exception(
                'A digital purchase is required for the digital payment help preset.'
            );
        }

        $language = $this->resolve_language($purchase, $user);

        $data = (object)[
            'firstname' => s((string)$user->firstname),
            'purchaseid' => (int)$purchase->id,
            'product' => s((string)($purchase->productname ?? '')),
        ];

        return new UserEmailPreset(
            get_string_manager()->get_string(
                'digital_payment_help_email_subject',
                'local_subscriptions',
                $data,
                $language
            ),
            get_string_manager()->get_string(
                'digital_payment_help_email_body',
                'local_subscriptions',
                $data,
                $language
            )
        );
    }

    private function resolve_language(
        \stdClass $purchase,
        \stdClass $user
    ): string {
        $language = trim((string)($purchase->buyer_lang ?? ''));

        if ($language === '') {
            $language = trim((string)($user->lang ?? ''));
        }

        if ($language === '') {
            return current_language();
        }

        $language = clean_param($language, PARAM_LANG);

        return get_string_manager()->translation_exists(
            $language,
            false
        ) ? $language : current_language();
    }
}