<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\template;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\mail\CommerceMailType;
use local_subscriptions\commerce\mail\CommerceMailRequest;

final class CommercePurchaseAccessTemplate extends AbstractCommerceMailTemplate {
    private const A1_TELEGRAM_CHANNEL_URL = 'https://t.me/+vSK_36VTpQ82NmEy';

    public function get_type(): string { return CommerceMailType::PURCHASE_ACCESS; }
    protected function subject_key(): string { return 'commerce_mail_purchase_access_subject'; }
    protected function template_name(): string { return 'purchase_access'; }

    /** @return array<string,mixed> */
    protected function additional_context(CommerceMailRequest $request): array {
        $pedagogy = $request->get_context()->get('pedagogical', []);
        if (!is_array($pedagogy) || empty($pedagogy['hasentries'])) {
            return [];
        }

        $labels = [
            'pedagogical_title' => get_string('commerce_mail_ped_title', 'local_subscriptions'),
            'pedagogical_group_label' => get_string('commerce_mail_ped_group', 'local_subscriptions'),
            'pedagogical_tutor_label' => get_string('commerce_mail_ped_tutor', 'local_subscriptions'),
            'pedagogical_lang_label' => get_string('commerce_mail_ped_language', 'local_subscriptions'),
            'pedagogical_telegram_label' => get_string('commerce_mail_ped_telegram', 'local_subscriptions'),
            'pedagogical_owned_message' => get_string('commerce_mail_ped_existing_owner', 'local_subscriptions'),
            'pedagogical_a1_channel_invite' => get_string('commerce_mail_ped_a1_channel_invite', 'local_subscriptions'),
            'pedagogical_a1_channel_url' => self::A1_TELEGRAM_CHANNEL_URL,
        ];
        $langnames = [
            'fr' => get_string('commerce_mail_ped_lang_fr', 'local_subscriptions'),
            'ru' => get_string('commerce_mail_ped_lang_ru', 'local_subscriptions'),
            'en' => get_string('commerce_mail_ped_lang_en', 'local_subscriptions'),
        ];
        $entries = [];
        foreach ($pedagogy['entries'] as $entry) {
            $value = strtolower(trim((string)($entry['supportlang'] ?? '')));
            $entry['supportlang'] = $langnames[$value] ?? (string)($entry['supportlang'] ?? '');
            $entries[] = $entry;
        }
        return $labels + [
            'haspedagogical' => true,
            'pedagogicalentries' => $entries,
            'pedagogicalalreadyowned' => !empty($pedagogy['alreadyowned']),
            'haspedagogicala1channel' => !empty($pedagogy['hasa1course']),
        ];
    }

    /** Personalised subject for a confirmed pedagogical enrollment. */
    protected function resolve_editorial(CommerceMailRequest $request, array $context, array $editorial): array {
        if (!empty($context['haspedagogical'])) {
            $editorial['subject'] = get_string('commerce_mail_ped_subject', 'local_subscriptions');
            $editorial['heading'] = get_string('commerce_mail_ped_heading', 'local_subscriptions');
        }
        return $editorial;
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
