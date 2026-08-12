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
        $bodyraw = CommercePersonalOfferCampaignMailVariableResolver::replace(
            (string)$content->body, $values, (int)$content->bodyformat === (int)FORMAT_HTML
        );
        $bodyhtml = format_text($bodyraw, (int)$content->bodyformat, $formatoptions);
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
