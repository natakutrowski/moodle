<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\personaloffer\mail;

defined('MOODLE_INTERNAL') || die();

use context_system;
use local_subscriptions\commerce\mail\CommerceMailRequest;
use local_subscriptions\commerce\mail\builder\CommerceMailBuilderCtaRenderer;
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

        // Preserve structural layout tags through the closed campaign-variable
        // resolver. Simple tags such as {{offer}} / {{secondary_cta}} would otherwise
        // be removed as unknown variables before the layout renderer sees them.
        $offerplaceholder = '{{offer}}';
        $offersentinel = 'CAMPUSFR_OFFER_MARKER_4E86A2';
        $secondaryctamarker = '{{secondary_cta}}';
        $secondaryctasentinel = 'CAMPUSFR_SECONDARY_CTA_MARKER_9F31D7';
        $directpaymarker = '{{direct_pay}}';
        $directpaysentinel = 'CAMPUSFR_DIRECT_PAY_MARKER_83A410';
        $imagemarker = '{{image}}';
        $imagesentinel = 'CAMPUSFR_IMAGE_MARKER_6BC912';

        $bodycontent = (string)$content->body;
        $offercount = substr_count($bodycontent, $offerplaceholder);
        $secondarycount = substr_count($bodycontent, $secondaryctamarker);
        $directpaycount = substr_count($bodycontent, $directpaymarker);
        $imagecount = substr_count($bodycontent, $imagemarker);

        $bodycontent = str_replace($offerplaceholder, $offersentinel, $bodycontent);
        $bodycontent = str_replace($secondaryctamarker, $secondaryctasentinel, $bodycontent);
        $bodycontent = str_replace($directpaymarker, $directpaysentinel, $bodycontent);
        $bodycontent = str_replace($imagemarker, $imagesentinel, $bodycontent);

        $bodyraw = CommercePersonalOfferCampaignMailVariableResolver::replace(
            $bodycontent,
            $values,
            (int)$content->bodyformat === (int)FORMAT_HTML
        );
        $bodyhtml = format_text($bodyraw, (int)$content->bodyformat, $formatoptions);

        // CTA tags are intentionally rendered in place and may be repeated.
        $offerurl = trim((string)($offer['url'] ?? ''));
        $ctarenderer = new CommerceMailBuilderCtaRenderer();
        $bodyhtml = $ctarenderer->render_tags($bodyhtml, $offerurl);

        $secondarylabel = clean_param(
            CommercePersonalOfferCampaignMailVariableResolver::replace(
                (string)($content->secondaryctalabel ?? ''),
                $values
            ),
            PARAM_TEXT
        );
        $secondaryurl = trim((string)($content->secondaryctaurl ?? ''));
        $secondaryctahtml = '';
        if ($secondarylabel !== '' && $secondaryurl !== ''
                && filter_var($secondaryurl, FILTER_VALIDATE_URL)
                && in_array(strtolower((string)parse_url($secondaryurl, PHP_URL_SCHEME)), ['http', 'https'], true)) {
            $secondaryctahtml = '<table role="presentation" cellspacing="0" cellpadding="0" border="0" '
                . 'align="center" style="margin:18px auto 20px;"><tr><td align="center" '
                . 'style="padding:0;background:transparent;border:0;">'
                . '<a class="campusfr-campaign-cta campusfr-campaign-cta-secondary" href="' . s($secondaryurl) . '" target="_blank" rel="noopener noreferrer" '
                . 'style="display:inline-block;padding:9px 20px;color:#f72585;font-weight:800;'
                . 'text-decoration:none;font-family:Nunito,Segoe UI,Arial,Helvetica,sans-serif;'
                . 'font-size:14px;line-height:18px;border:1px solid #f72585;border-radius:11px;'
                . 'background:#ffffff;white-space:nowrap;">'
                . s($secondarylabel) . '</a></td></tr></table>';
        }

        if ($secondarycount > 1) {
            debugging(
                '[local_subscriptions][personal_offer_campaign_email] Multiple {{secondary_cta}} '
                . 'markers found; only the first one is rendered.',
                DEBUG_DEVELOPER
            );
        }

        $firstsecondary = strpos($bodyhtml, $secondaryctasentinel);
        if ($firstsecondary !== false) {
            $bodyhtml = substr_replace(
                $bodyhtml,
                $secondaryctahtml,
                $firstsecondary,
                strlen($secondaryctasentinel)
            );
        }
        $bodyhtml = str_replace($secondaryctasentinel, '', $bodyhtml);

        if ($directpaycount > 1) {
            debugging(
                '[local_subscriptions][personal_offer_campaign_email] Multiple {{direct_pay}} '
                . 'markers found; only the first one is rendered.',
                DEBUG_DEVELOPER
            );
        }
        $directpayhtml = $this->direct_pay_html($offer);
        $firstdirectpay = strpos($bodyhtml, $directpaysentinel);
        if ($firstdirectpay !== false) {
            $bodyhtml = substr_replace(
                $bodyhtml,
                $directpayhtml,
                $firstdirectpay,
                strlen($directpaysentinel)
            );
        }
        $bodyhtml = str_replace($directpaysentinel, '', $bodyhtml);

        if ($imagecount > 1) {
            debugging(
                '[local_subscriptions][personal_offer_campaign_email] Multiple {{image}} '
                . 'markers found; only the first one is rendered.',
                DEBUG_DEVELOPER
            );
        }
        $campaignimageurl = (new \local_subscriptions\commerce\personaloffer\campaign\CommercePersonalOfferCampaignMailFooterImageService())
            ->url($campaignid);
        $imagehtml = $campaignimageurl !== ''
            ? $this->campaign_image_html($campaignimageurl)
            : '';
        $firstimage = strpos($bodyhtml, $imagesentinel);
        if ($firstimage !== false) {
            $bodyhtml = substr_replace(
                $bodyhtml,
                $imagehtml,
                $firstimage,
                strlen($imagesentinel)
            );
        }
        $bodyhtml = str_replace($imagesentinel, '', $bodyhtml);

        // Restore the offer marker only after variable resolution/HTML formatting,
        // then split the body around the exact requested position. The Mustache
        // template places the native offer card between introhtml and outrohtml.
        // Hover is progressive enhancement for email clients that preserve embedded CSS.
        // Inline button styles remain the authoritative fallback (notably for Outlook).
        $bodyhtml = str_replace($offersentinel, $offerplaceholder, $bodyhtml);
        if ($offercount > 1) {
            debugging(
                '[local_subscriptions][personal_offer_campaign_email] Multiple {{offer}} markers '
                . 'found; only the first one controls placement.',
                DEBUG_DEVELOPER
            );
        }
        $offerparts = preg_split('/\{\{offer\}\}/i', $bodyhtml, 2);
        $bodyhtml = (string)($offerparts[0] ?? '');
        $outrohtml = (string)($offerparts[1] ?? '');
        $outrohtml = str_replace($offerplaceholder, '', $outrohtml);

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
            'outrohtml' => $outrohtml,
            // AbstractCommerceMailTemplate already moves Personal Offer signature after CTA.
            'signaturehtml' => $closinghtml,
            'headerimage' => false,
            // M14 positionable layout owns {{image}} / {{direct_pay}} itself.
            // Keep the legacy automatic footer only for historical campaign bodies
            // that do not use any M14 structural marker.
            'footerimageurl' => $this->uses_positionable_layout((string)$content->body)
                ? ''
                : (new \local_subscriptions\commerce\personaloffer\campaign\CommercePersonalOfferCampaignMailFooterImageService())->url($campaignid),
            'positionablelayout' => $this->uses_positionable_layout((string)$content->body),
            // M14.2c: embedded hover CSS belongs in the document <head>, not in email body.
            'headcss' => $ctarenderer->hover_css(),
            'templateid' => 0,
            'ctalabel' => clean_param(CommercePersonalOfferCampaignMailVariableResolver::replace((string)$content->ctalabel, $values), PARAM_TEXT),
        ];
    }

    /** @param array<string,mixed> $offer */
    private function direct_pay_html(array $offer): string {
        global $CFG;

        if (empty($offer['hasdirectcheckout'])) {
            return '';
        }
        $url = trim((string)($offer['directcheckouturl'] ?? ''));
        $label = trim((string)($offer['directcheckoutlabel'] ?? ''));
        if ($url === '' || $label === '') {
            return '';
        }

        $base = rtrim((string)$CFG->wwwroot, '/') . '/local/subscriptions/pix/email/';
        $visa = $base . 'visa.png';
        $mastercard = $base . 'mastercard.png';
        $stripe = $base . 'stripe.png';
        $alfa = $base . 'alfa.png';

        return '<table role="presentation" cellspacing="0" cellpadding="0" border="0" '
            . 'align="center" style="margin:22px auto 24px;">'
            . '<tr><td align="center" style="padding:0;text-align:center;">'
            . '<a href="' . s($url) . '" target="_blank" rel="noopener noreferrer" '
            . 'style="color:#4b5563;text-decoration:underline;font-family:Nunito,Segoe UI,Arial,Helvetica,sans-serif;'
            . 'font-size:14px;font-weight:700;line-height:20px;">' . s($label) . '</a>'
            . '<div style="margin-top:10px;line-height:1;">'
            . '<img src="' . s($visa) . '" alt="Visa" height="30" '
            . 'style="display:inline-block;width:auto;height:30px;border:0;vertical-align:middle;margin:0 6px;">'
            . '<img src="' . s($mastercard) . '" alt="Mastercard" height="30" '
            . 'style="display:inline-block;width:auto;height:30px;border:0;vertical-align:middle;margin:0 6px;">'
            . '<img src="' . s($stripe) . '" alt="Stripe" height="30" '
            . 'style="display:inline-block;width:auto;height:30px;border:0;vertical-align:middle;margin:0 6px;">'
            . '<img src="' . s($alfa) . '" alt="Alfa-Bank" height="30" '
            . 'style="display:inline-block;width:auto;height:30px;border:0;vertical-align:middle;margin:0 6px;">'
            . '</div></td></tr></table>';
    }

    private function campaign_image_html(string $url): string {
        return '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" '
            . 'style="margin:20px 0 24px;">'
            . '<tr><td style="padding:0;">'
            . '<img src="' . s($url) . '" alt="" width="752" '
            . 'style="display:block;width:100%;max-width:752px;height:auto;border:0;'
            . 'border-radius:14px;outline:none;text-decoration:none;">'
            . '</td></tr></table>';
    }

    private function uses_positionable_layout(string $body): bool {
        return preg_match(
            '/\{\{(?:offer|secondary_cta|direct_pay|image)\}\}'
                . '|\{\{cta(?:\|[a-z_]+)?\}\}.*?\{\{\/cta\}\}/is',
            $body
        ) === 1;
    }

}
