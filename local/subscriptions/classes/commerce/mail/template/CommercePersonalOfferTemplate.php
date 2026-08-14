<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\template;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\mail\CommerceMailRequest;
use local_subscriptions\commerce\mail\CommerceMailType;
use local_subscriptions\commerce\mail\CommerceMailTerminalCancellationException;
use local_subscriptions\commerce\personaloffer\domain\CommercePersonalOffer;
use local_subscriptions\commerce\personaloffer\mail\CommercePersonalOfferCampaignMailRenderer;
use local_subscriptions\commerce\personaloffer\mail\CommercePersonalOfferMailPricingPresentationService;
use local_subscriptions\commerce\personaloffer\campaign\CommercePersonalOfferCampaignMailBannerService;

final class CommercePersonalOfferTemplate extends AbstractCommerceMailTemplate {
    public function get_type(): string { return CommerceMailType::PERSONAL_OFFER; }
    protected function subject_key(): string { return 'commerce_mail_personal_offer_subject'; }
    protected function template_name(): string { return 'personal_offer'; }

    protected function additional_context(CommerceMailRequest $request): array {
        global $CFG;

        $offer = $request->get_context()->get('personaloffer', []);
        if (!is_array($offer)) { $offer = []; }

        // M12b: revalidate the offer immediately before rendering/sending.
        // A queued campaign message can become stale while waiting in the
        // throttled outbox (e.g. the customer purchases before the retry).
        // Terminal offers must be cancelled, never rendered and never retried.
        $this->assert_offer_is_deliverable($offer);

        $language = $request->get_language();
        $offer['offerlabel'] = $this->local_string('commerce_mail_personal_offer_card_label', $language);
        $offer['expirylabel'] = $this->local_string('commerce_mail_personal_offer_expiry_label', $language);
        $offer['validitylabel'] = $this->local_string('commerce_mail_personal_offer_validity_label', $language);
        $offer['validfromlabel'] = $this->local_string('commerce_mail_personal_offer_valid_from_label', $language);
        $offer['fromlabel'] = $this->local_string('commerce_mail_personal_offer_from_label', $language);
        $offer['tolabel'] = $this->local_string('commerce_mail_personal_offer_to_label', $language);
        $offer['priceformatted'] = $this->format_pricing($offer['pricing'] ?? []);
        $offer['prices'] = $this->pricing_cards($offer['pricing'] ?? []);
        $offer['hasprices'] = $offer['prices'] !== [];
        $offer['mailimageurl'] = trim((string)($offer['mailimageurl'] ?? ''));
        if ($offer['mailimageurl'] === '') {
            $offer['mailimageurl'] = rtrim((string)$CFG->wwwroot, '/')
                . '/local/subscriptions/pix/email/personal-offer-default.jpg';
        }
        $offer['hasmailimage'] = $offer['mailimageurl'] !== '';

        $validitymode = (string)($offer['validitymode'] ?? 'legacy');
        $validitytimezone = (string)($offer['validitytimezone'] ?? 'Europe/Paris');
        $validityformat = $validitymode === 'legacy'
            ? get_string('strftimedate', 'langconfig')
            : get_string('strftimedatetimeshort', 'langconfig');
        $validitydisplaytimezone = $validitymode === 'legacy' ? 'UTC' : $validitytimezone;
        $offer['validfromformatted'] = !empty($offer['validfrom'])
            ? userdate((int)$offer['validfrom'], $validityformat, $validitydisplaytimezone)
            : '';
        $offer['expiresformatted'] = !empty($offer['expiresat'])
            ? userdate((int)$offer['expiresat'], $validityformat, $validitydisplaytimezone)
            : '';
        $offer['hasvalidityperiod'] =
            $offer['validfromformatted'] !== '' && $offer['expiresformatted'] !== '';

        $offer['iscampaignemail'] = false;
        $campaignid = (int)($offer['campaignid'] ?? 0);
        if ($campaignid > 0) {
            global $DB;
            $campaignemailservice = \local_subscriptions\commerce\personaloffer\campaign\CommercePersonalOfferCampaignEmailService::create($DB);
            $content = $campaignemailservice->resolve_content($campaignid, $request->get_language());
            if ($content !== null) {
                $pricing = !empty($offer['campaignpreview']) && is_array($offer['previewpricing'] ?? null)
                    ? $offer['previewpricing']
                    : CommercePersonalOfferMailPricingPresentationService::create($DB)->resolve(
                        (string)($offer['offeruuid'] ?? ''),
                        $request->get_language()
                    );
                if ($pricing === null) {
                    // A custom campaign email must never fall back to an editorial price
                    // representation when the authoritative checkout price cannot be resolved.
                    throw new \coding_exception(
                        'Personal Offer campaign email authoritative pricing could not be resolved.'
                    );
                }
                $offer['iscampaignemail'] = true;
                $offer['campaignctalabel'] = clean_param((string)$content->ctalabel, PARAM_TEXT);
                $offer['currency'] = (string)$pricing['currency'];
                $offer['offerpriceformatted'] = (string)$pricing['offerformatted'];
                $offer['regularpriceformatted'] = (string)$pricing['regularformatted'];
                $offer['discountamountformatted'] = (string)$pricing['discountformatted'];
                $offer['discountpercentformatted'] = (string)$pricing['discountpercentformatted'];
                $offer['hasregularprice'] = (int)$pricing['regularminor'] > (int)$pricing['offerminor'];
                $offer['hasdiscountpercent'] = (int)$pricing['discountpercent'] > 0;
                $offer['campaignprices'] = is_array($pricing['prices'] ?? null) ? $pricing['prices'] : [];
                $offer['hascampaignprices'] = $offer['campaignprices'] !== [];
                if (!empty($offer['url'])) {
                    $url = new \moodle_url((string)$offer['url']);
                    $url->param('currency', (string)$pricing['currency']);
                    $offer['url'] = $url->out(false);

                    // M3H: a Showroom campaign keeps the immersive primary CTA but also exposes
                    // the historical direct-checkout path through the same signed Personal Offer.
                    $destination = $campaignemailservice->resolve_destination($campaignid);
                    if (($destination['destination'] ?? '') ===
                            \local_subscriptions\commerce\personaloffer\campaign\CommercePersonalOfferCampaignEmailService::DESTINATION_SHOWROOM) {
                        $directurl = new \moodle_url($offer['url']);
                        $directurl->param('destination', 'checkout');
                        $offer['hasdirectcheckout'] = true;
                        $offer['directcheckouturl'] = $directurl->out(false);
                        $offer['directcheckoutlabel'] = $this->local_string(
                            'commerce_mail_personal_offer_direct_checkout',
                            $language
                        );
                    }
                }
            }
        }
        return ['personaloffer' => $offer];
    }

    protected function resolve_editorial(CommerceMailRequest $request, array $context, array $editorial): array {
        $offer = is_array($context['personaloffer'] ?? null) ? $context['personaloffer'] : [];
        $campaignid = (int)($offer['campaignid'] ?? 0);
        if ($campaignid <= 0 || empty($offer['iscampaignemail'])) {
            return $editorial;
        }
        $resolved = CommercePersonalOfferCampaignMailRenderer::create()->resolve($request, $campaignid, $context)
            ?? $editorial;
        $bannerurl = (new CommercePersonalOfferCampaignMailBannerService())->url($campaignid);
        if ($bannerurl !== '') {
            $resolved['headerimageurl'] = $bannerurl;
        }
        return $resolved;
    }

    /**
     * Cancel a stale Personal Offer message before pricing/editorial rendering.
     *
     * @param array<string,mixed> $offer
     */
    private function assert_offer_is_deliverable(array $offer): void {
        global $DB;

        $offeruuid = trim((string)($offer['offeruuid'] ?? ''));
        if ($offeruuid === '') {
            return;
        }

        $record = $DB->get_record(
            'local_subs_commerce_offer',
            ['offeruuid' => $offeruuid],
            'id,status,expiresat,redeemedpurchaseid,revokedat',
            IGNORE_MISSING
        );
        if (!$record) {
            return;
        }

        $status = (string)$record->status;
        if ($status === CommercePersonalOffer::STATUS_REDEEMED) {
            $purchase = !empty($record->redeemedpurchaseid)
                ? ' purchase #' . (int)$record->redeemedpurchaseid
                : '';
            throw new CommerceMailTerminalCancellationException(
                'personal_offer_redeemed',
                'Personal Offer has already been redeemed' . $purchase . '.'
            );
        }

        if ($status === CommercePersonalOffer::STATUS_REVOKED || !empty($record->revokedat)) {
            throw new CommerceMailTerminalCancellationException(
                'personal_offer_revoked',
                'Personal Offer has been revoked.'
            );
        }

        if ($status === CommercePersonalOffer::STATUS_ISSUED
                && !empty($record->expiresat)
                && time() > (int)$record->expiresat) {
            throw new CommerceMailTerminalCancellationException(
                'personal_offer_expired',
                'Personal Offer has expired.'
            );
        }
    }

    /**
     * Resolve a Commerce-owned string in the mail language even when the corresponding
     * Moodle language pack is not installed (notably PHPUnit/certification environments).
     *
     * FR/EN/RU are plugin-owned campaign languages, so their local_subscriptions strings
     * are authoritative and may be loaded directly from the component catalogue.
     */
    private function local_string(string $identifier, string $language): string {
        global $CFG;

        $language = strtolower(trim($language));
        $language = explode('_', str_replace('-', '_', $language))[0];
        if (!in_array($language, ['fr', 'en', 'ru'], true)) {
            $language = 'fr';
        }

        // FR/EN/RU are first-class Commerce campaign languages owned by this plugin.
        // Moodle's string manager may deliberately fall back to English when the full
        // core language pack is not installed (notably PHPUnit). For these component-
        // local strings, read the plugin catalogue itself so rendering stays deterministic.
        $catalogue = [];
        $langfile = $CFG->dirroot
            . '/local/subscriptions/lang/' . $language . '/local_subscriptions.php';

        if (is_readable($langfile)) {
            $string = [];
            include($langfile);
            if (isset($string) && is_array($string)) {
                $catalogue = $string;
            }
        }

        if (array_key_exists($identifier, $catalogue)) {
            return (string)$catalogue[$identifier];
        }

        return get_string($identifier, 'local_subscriptions');
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
        if ($this->campaign_has_embedded_cta($context)) { return null; }
        if (empty($context['personaloffer']['url'])) {
            return null;
        }
        $campaignlabel = trim((string)($context['personaloffer']['campaignctalabel'] ?? ''));
        return $campaignlabel !== ''
            ? $campaignlabel
            : get_string('commerce_mail_personal_offer_cta', 'local_subscriptions');
    }

    protected function primary_action_variant(array $context): string {
        return 'premium';
    }

    protected function primary_action_url(array $context): ?string {
        return !empty($context['personaloffer']['url']) ? (string)$context['personaloffer']['url'] : null;
    }

    protected function primary_action_after_html(array $context): string {
        $html = '';

        $positionablelayout = !empty($context['personaloffer_positionable_layout']);

        $offer = $context['personaloffer'] ?? [];
        if (!$positionablelayout && is_array($offer) && !empty($offer['hasdirectcheckout'])) {
            $directurl = trim((string)($offer['directcheckouturl'] ?? ''));
            $directlabel = trim((string)($offer['directcheckoutlabel'] ?? ''));
            if ($directurl !== '' && $directlabel !== '') {
                $html .= '<div style="margin:-10px 0 22px;text-align:center;font-size:13px;line-height:1.5;">'
                    . '<a href="' . s($directurl) . '" style="color:#6b7280;text-decoration:underline;">'
                    . s($directlabel) . '</a></div>';
            }
        }

        $signature = trim((string)($context['personaloffer_after_cta_signature'] ?? ''));
        if ($signature !== '') {
            $html .= '<div style="margin:0 0 24px;">' . $signature . '</div>';
        }

        if ($positionablelayout) {
            return $html;
        }

        $offer = $context['personaloffer'] ?? [];
        $campaignfooter = trim((string)($context['editorial_footerimageurl'] ?? ''));
        $imageurl = $campaignfooter;
        if ($imageurl === '' && is_array($offer) && !empty($offer['hasmailimage'])) {
            // Backward-compatible fallback for historical Personal Offers.
            $imageurl = trim((string)($offer['mailimageurl'] ?? ''));
        }
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

    private function campaign_has_embedded_cta(array $context): bool {
        global $DB;
        $offer = is_array($context['personaloffer'] ?? null) ? $context['personaloffer'] : [];
        $campaignid = (int)($offer['campaignid'] ?? 0);
        if ($campaignid <= 0 || empty($offer['iscampaignemail'])) { return false; }
        $language = strtolower((string)($context['language'] ?? current_language()));
        try {
            $content = \local_subscriptions\commerce\personaloffer\campaign\CommercePersonalOfferCampaignEmailService::create($DB)
                ->resolve_content($campaignid, $language);
            return $content !== null && preg_match('/\{\{cta(?:\|[a-z_]+)?\}\}.*?\{\{\/cta\}\}/is', (string)$content->body) === 1;
        } catch (\Throwable) { return false; }
    }

}
