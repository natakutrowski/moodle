<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\personaloffer\mail;

defined('MOODLE_INTERNAL') || die();

use context_system;
use local_subscriptions\commerce\mail\CommerceMailRequest;
use local_subscriptions\commerce\personaloffer\campaign\CommercePersonalOfferCampaignEmailService;

/** Resolves campaign-specific Personal Offer editorial copy with safe variables. */
final class CommercePersonalOfferCampaignMailRenderer {
    public function __construct(private readonly \moodle_database $db) {}
    public static function create(?\moodle_database $db = null): self { global $DB; return new self($db ?? $DB); }

    /** @param array<string,mixed> $context @return array<string,mixed>|null */
    public function resolve(CommerceMailRequest $request, int $campaignid, array $context): ?array {
        $language = $request->get_language();
        $content = CommercePersonalOfferCampaignEmailService::create($this->db)->resolve_content($campaignid, $language);
        if ($content === null) { return null; }

        $offer = is_array($context['personaloffer'] ?? null) ? $context['personaloffer'] : [];
        $customer = $request->get_context()->get('customer', []);
        if (!is_array($customer)) { $customer = []; }
        $firstname = trim((string)($customer['firstname'] ?? ''));
        $fullname = trim((string)($customer['fullname'] ?? ''));
        if ($fullname === '') { $fullname = $firstname; }
        if ($firstname === '' && $fullname !== '') {
            $firstname = trim((string)(preg_split('/\s+/u', $fullname, 2)[0] ?? ''));
        }
        $values = [
            'firstname' => $firstname,
            'fullname' => $fullname,
            'product_name' => (string)($offer['productname'] ?? ''),
            'offer_start' => (string)($offer['validfromformatted'] ?? ''),
            'offer_end' => (string)($offer['expiresformatted'] ?? ''),
            'offer_price' => (string)($offer['offerpriceformatted'] ?? ''),
            'regular_price' => (string)($offer['regularpriceformatted'] ?? ''),
            'discount_amount' => (string)($offer['discountamountformatted'] ?? ''),
            'discount_percent' => (string)($offer['discountpercentformatted'] ?? ''),
        ];

        $formatoptions = ['context' => context_system::instance(), 'filter' => false, 'noclean' => false, 'para' => false];

        // Preserve the layout marker through the closed campaign-variable resolver.
        // Unknown {{...}} variables are intentionally stripped by that resolver.
        $secondaryctamarker = '{{secondary_cta}}';
        $secondaryctasentinel = 'CAMPUSFR_SECONDARY_CTA_MARKER_9F31D7';
        $bodycontent = (string)$content->body;
        $markercount = substr_count($bodycontent, $secondaryctamarker);
        $bodycontent = str_replace($secondaryctamarker, $secondaryctasentinel, $bodycontent);

        $bodyraw = CommercePersonalOfferCampaignMailVariableResolver::replace(
            $bodycontent, $values, (int)$content->bodyformat === (int)FORMAT_HTML
        );
        $bodyhtml = format_text($bodyraw, (int)$content->bodyformat, $formatoptions);

        $secondarylabel = clean_param(
            CommercePersonalOfferCampaignMailVariableResolver::replace(
                (string)($content->secondaryctalabel ?? ''), $values
            ),
            PARAM_TEXT
        );
        $secondaryurl = trim((string)($content->secondaryctaurl ?? ''));
        $secondaryctahtml = '';
        if ($secondarylabel !== '' && $secondaryurl !== ''
                && filter_var($secondaryurl, FILTER_VALIDATE_URL)
                && in_array(strtolower((string)parse_url($secondaryurl, PHP_URL_SCHEME)), ['http', 'https'], true)) {
            $secondaryctahtml = '<table role="presentation" cellspacing="0" cellpadding="0" border="0" '
                . 'style="margin:22px auto 28px;"><tr><td align="center" '
                . 'style="border-radius:14px;background:#fff1f5;border:1px solid #f5c2d1;">'
                . '<a href="' . s($secondaryurl) . '" target="_blank" rel="noopener" '
                . 'style="display:inline-block;padding:13px 22px;color:#9d174d;font-weight:700;'
                . 'text-decoration:none;font-size:15px;line-height:1.25;">'
                . s($secondarylabel) . '</a></td></tr></table>';
        }

        if ($markercount > 1) {
            debugging(
                '[local_subscriptions][personal_offer_campaign_email] Multiple {{secondary_cta}} '
                . 'markers found; only the first one is rendered.',
                DEBUG_DEVELOPER
            );
        }

        $firstmarker = strpos($bodyhtml, $secondaryctasentinel);
        if ($firstmarker !== false) {
            $bodyhtml = substr_replace(
                $bodyhtml,
                $secondaryctahtml,
                $firstmarker,
                strlen($secondaryctasentinel)
            );
        }
        // Remove duplicate markers, or the marker when the CTA fields are empty.
        $bodyhtml = str_replace($secondaryctasentinel, '', $bodyhtml);

        $closinghtml = '';
        if (trim((string)($content->closing ?? '')) !== '') {
            $closingraw = CommercePersonalOfferCampaignMailVariableResolver::replace(
                (string)$content->closing, $values, (int)$content->closingformat === (int)FORMAT_HTML
            );
            $closinghtml = format_text($closingraw, (int)$content->closingformat, $formatoptions);
        }

        return [
            'subject' => clean_param(CommercePersonalOfferCampaignMailVariableResolver::replace((string)$content->subject, $values), PARAM_TEXT),
            'preheader' => '',
            'heading' => '',
            'introhtml' => $bodyhtml,
            'outrohtml' => '',
            // AbstractCommerceMailTemplate already moves Personal Offer signature after CTA.
            'signaturehtml' => $closinghtml,
            'headerimage' => false,
            'templateid' => 0,
            'ctalabel' => clean_param(CommercePersonalOfferCampaignMailVariableResolver::replace((string)$content->ctalabel, $values), PARAM_TEXT),
        ];
    }
}
