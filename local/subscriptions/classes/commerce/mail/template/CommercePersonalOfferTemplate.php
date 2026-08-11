<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\template;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\mail\CommerceMailRequest;
use local_subscriptions\commerce\mail\CommerceMailType;

final class CommercePersonalOfferTemplate extends AbstractCommerceMailTemplate {
    public function get_type(): string { return CommerceMailType::PERSONAL_OFFER; }
    protected function subject_key(): string { return 'commerce_mail_personal_offer_subject'; }
    protected function template_name(): string { return 'personal_offer'; }

    protected function additional_context(CommerceMailRequest $request): array {
        global $CFG;

        $offer = $request->get_context()->get('personaloffer', []);
        if (!is_array($offer)) { $offer = []; }
        $offer['offerlabel'] = get_string('commerce_mail_personal_offer_card_label', 'local_subscriptions');
        $offer['expirylabel'] = get_string('commerce_mail_personal_offer_expiry_label', 'local_subscriptions');
        $offer['validitylabel'] = get_string('commerce_mail_personal_offer_validity_label', 'local_subscriptions');
        $offer['validfromlabel'] = get_string('commerce_mail_personal_offer_valid_from_label', 'local_subscriptions');
        $offer['fromlabel'] = get_string('commerce_mail_personal_offer_from_label', 'local_subscriptions');
        $offer['tolabel'] = get_string('commerce_mail_personal_offer_to_label', 'local_subscriptions');
        $offer['priceformatted'] = $this->format_pricing($offer['pricing'] ?? []);
        $offer['prices'] = $this->pricing_cards($offer['pricing'] ?? []);
        $offer['hasprices'] = $offer['prices'] !== [];
        $offer['mailimageurl'] = trim((string)($offer['mailimageurl'] ?? ''));
        if ($offer['mailimageurl'] === '') {
            $offer['mailimageurl'] = rtrim((string)$CFG->wwwroot, '/')
                . '/local/subscriptions/pix/email/personal-offer-default.jpg';
        }
        $offer['hasmailimage'] = $offer['mailimageurl'] !== '';

        // Offer validity is date-only business data. Render it in UTC so the selected
        // CRM calendar day cannot move to the previous/next day with the viewer timezone.
        $offer['validfromformatted'] = !empty($offer['validfrom'])
            ? userdate((int)$offer['validfrom'], get_string('strftimedate', 'langconfig'), 'UTC')
            : '';
        $offer['expiresformatted'] = !empty($offer['expiresat'])
            ? userdate((int)$offer['expiresat'], get_string('strftimedate', 'langconfig'), 'UTC')
            : '';
        $offer['hasvalidityperiod'] =
            $offer['validfromformatted'] !== '' && $offer['expiresformatted'] !== '';
        return ['personaloffer' => $offer];
    }

    private function pricing_cards(mixed $pricing): array {
        if (!is_array($pricing)) {
            return [];
        }
        $strategy = (string)($pricing['strategy'] ?? '');
        if ($strategy === 'percentage_discount') {
            return [];
        }

        $symbols = ['EUR' => '€', 'RUB' => '₽', 'USD' => '$', 'GBP' => '£'];
        $flags = ['EUR' => '🇪🇺', 'RUB' => '🇷🇺', 'USD' => '🇺🇸', 'GBP' => '🇬🇧'];
        $cards = [];
        foreach (($pricing['amounts'] ?? []) as $currency => $minor) {
            if (!is_numeric($minor)) {
                continue;
            }
            $code = strtoupper(trim((string)$currency));
            $amount = format_float(((int)$minor) / 100, 2);
            $cards[] = [
                'currency' => $code,
                'flag' => $flags[$code] ?? '',
                'amount' => ($strategy === 'fixed_discount' ? '− ' : '') . $amount,
                'symbol' => $symbols[$code] ?? $code,
            ];
        }
        return $cards;
    }

    private function format_pricing(mixed $pricing): string {
        if (!is_array($pricing)) { return ''; }
        $strategy = (string)($pricing['strategy'] ?? '');
        if ($strategy === 'percentage_discount') {
            $basispoints = (int)($pricing['basispoints'] ?? 0);
            return $basispoints > 0 ? '-' . format_float($basispoints / 100, 0) . ' %' : '';
        }
        $parts = [];
        foreach (($pricing['amounts'] ?? []) as $currency => $minor) {
            if (!is_numeric($minor)) { continue; }
            $value = format_float(((int)$minor) / 100, 2) . ' ' . strtoupper((string)$currency);
            $parts[] = $strategy === 'fixed_discount' ? '-' . $value : $value;
        }
        return implode(' · ', $parts);
    }

    protected function primary_action_label(array $context): ?string {
        return !empty($context['personaloffer']['url'])
            ? get_string('commerce_mail_personal_offer_cta', 'local_subscriptions')
            : null;
    }

    protected function primary_action_variant(array $context): string {
        return 'premium';
    }

    protected function primary_action_url(array $context): ?string {
        return !empty($context['personaloffer']['url']) ? (string)$context['personaloffer']['url'] : null;
    }

    protected function primary_action_after_html(array $context): string {
        $html = '';

        $signature = trim((string)($context['personaloffer_after_cta_signature'] ?? ''));
        if ($signature !== '') {
            $html .= '<div style="margin:0 0 24px;">' . $signature . '</div>';
        }

        $offer = $context['personaloffer'] ?? [];
        if (!is_array($offer) || empty($offer['hasmailimage'])) {
            return $html;
        }

        $imageurl = trim((string)($offer['mailimageurl'] ?? ''));
        if ($imageurl === '') {
            return $html;
        }

        $html .= '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" '
            . 'style="margin:0 0 26px;">'
            . '<tr><td style="padding:0;">'
            . '<img src="' . s($imageurl) . '" alt="" width="752" '
            . 'style="display:block;width:100%;max-width:752px;height:auto;border:0;'
            . 'border-radius:14px;outline:none;text-decoration:none;">'
            . '</td></tr></table>';

        return $html;
    }
}
