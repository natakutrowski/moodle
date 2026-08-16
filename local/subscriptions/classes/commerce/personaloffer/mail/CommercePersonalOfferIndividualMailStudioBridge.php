<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\personaloffer\mail;

defined('MOODLE_INTERNAL') || die();

use context_system;
use local_subscriptions\commerce\mail\CommerceMailRequest;
use local_subscriptions\commerce\mail\builder\CommerceMailBuilderCtaRenderer;
use local_subscriptions\commerce\mail\library\CommerceMailLibrary;
use local_subscriptions\commerce\mail\library\CommerceMailLibraryRepository;

/**
 * Mail Studio bridge for individual Personal Offers.
 *
 * The selected reusable template is frozen into offer metadata at creation time,
 * so later edits/archive/delete of the library template never alter an existing
 * offer's future email.
 */
final class CommercePersonalOfferIndividualMailStudioBridge {
    public function __construct(
        private readonly \moodle_database $db,
        private readonly CommerceMailLibraryRepository $library
    ) {}

    public static function create(?\moodle_database $db = null): self {
        global $DB;
        $db ??= $DB;
        return new self($db, new CommerceMailLibraryRepository($db));
    }

    /** @return array<int,string> */
    public function template_options(): array {
        $options = [];
        foreach ($this->library->all(
            CommerceMailLibrary::CATEGORY_PERSONAL_OFFER,
            CommerceMailLibrary::STATUS_ACTIVE
        ) as $template) {
            $options[(int)$template->id] = (string)$template->name;
        }
        return $options;
    }

    /** @return array<string,mixed> */
    public function snapshot(int $templateid): array {
        if ($templateid <= 0) {
            return [];
        }

        $template = $this->library->get($templateid);
        if (
            (string)$template->category !== CommerceMailLibrary::CATEGORY_PERSONAL_OFFER
            || (string)$template->status !== CommerceMailLibrary::STATUS_ACTIVE
        ) {
            throw new \invalid_parameter_exception(
                'Only active Personal Offer Mail Studio templates can be selected.'
            );
        }

        $translations = [];
        foreach ($this->library->contents($templateid) as $language => $content) {
            $document = json_decode((string)$content->contentjson, true) ?: [];
            $translations[(string)$language] = [
                'subject' => (string)$content->subject,
                'preheader' => (string)$content->preheader,
                'bodyhtml' => (string)($document['bodyhtml'] ?? ''),
            ];
        }

        if ($translations === []) {
            throw new \coding_exception(
                'Selected Personal Offer Mail Studio template has no content.'
            );
        }

        return [
            'templateid' => (int)$template->id,
            'templatename' => (string)$template->name,
            'translations' => $translations,
        ];
    }

    /**
     * @param array<string,mixed> $snapshot
     * @param array<string,mixed> $context
     * @return array<string,mixed>|null
     */
    public function resolve(
        CommerceMailRequest $request,
        array $snapshot,
        array $context
    ): ?array {
        $translations = is_array($snapshot['translations'] ?? null)
            ? $snapshot['translations']
            : [];
        if ($translations === []) {
            return null;
        }

        $language = strtolower(substr($request->get_language(), 0, 2));
        $content = $translations[$language]
            ?? $translations['fr']
            ?? $translations['en']
            ?? $translations['ru']
            ?? reset($translations);
        if (!is_array($content)) {
            return null;
        }

        $offer = is_array($context['personaloffer'] ?? null)
            ? $context['personaloffer']
            : [];
        $customer = $request->get_context()->get('customer', []);
        $customer = is_array($customer) ? $customer : [];

        $firstname = trim((string)($customer['firstname'] ?? ''));
        $fullname = trim((string)($customer['fullname'] ?? ''));
        if ($fullname === '') {
            $fullname = $firstname;
        }
        if ($firstname === '' && $fullname !== '') {
            $firstname = trim((string)(
                preg_split('/\s+/u', $fullname, 2)[0] ?? ''
            ));
        }

        $values = [
            'firstname' => $firstname,
            'fullname' => $fullname,
            'product_name' => (string)($offer['productname'] ?? ''),
            'offer_start' => (string)($offer['validfromformatted'] ?? ''),
            'offer_end' => (string)($offer['expiresformatted'] ?? ''),
            'offer_price' => '',
            'regular_price' => '',
            'discount_amount' => '',
            'discount_percent' => '',
        ];

        $body = (string)($content['bodyhtml'] ?? '');
        $offerurl = trim((string)($offer['url'] ?? ''));
        $mailimageurl = trim((string)($offer['mailimageurl'] ?? ''));

        $offerplaceholder = '{{offer}}';
        $offersentinel = 'CAMPUSFR_INDIVIDUAL_OFFER_MARKER_4E86A2';
        $secondarysentinel = 'CAMPUSFR_INDIVIDUAL_SECONDARY_MARKER_9F31D7';
        $directpaysentinel = 'CAMPUSFR_INDIVIDUAL_DIRECTPAY_MARKER_83A410';
        $imagesentinel = 'CAMPUSFR_INDIVIDUAL_IMAGE_MARKER_6BC912';

        $body = str_replace($offerplaceholder, $offersentinel, $body);
        $body = str_replace('{{secondary_cta}}', $secondarysentinel, $body);
        $body = str_replace('{{direct_pay}}', $directpaysentinel, $body);
        $body = str_replace('{{image}}', $imagesentinel, $body);

        $body = CommercePersonalOfferCampaignMailVariableResolver::replace(
            $body,
            $values,
            true
        );
        $bodyhtml = format_text($body, FORMAT_HTML, [
            'context' => context_system::instance(),
            'filter' => false,
            'noclean' => false,
            'para' => false,
        ]);

        $ctarenderer = new CommerceMailBuilderCtaRenderer();
        $bodyhtml = $ctarenderer->render_tags($bodyhtml, $offerurl);

        // Individual offers do not carry an independent secondary CTA or
        // showroom direct-pay destination. Keep those optional builder markers
        // harmless when a reusable campaign-oriented template contains them.
        $bodyhtml = str_replace(
            [$secondarysentinel, $directpaysentinel],
            '',
            $bodyhtml
        );

        $imagehtml = $mailimageurl !== ''
            ? '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" '
                . 'style="margin:20px 0 24px;"><tr><td style="padding:0;">'
                . '<img src="' . s($mailimageurl) . '" alt="" width="752" '
                . 'style="display:block;width:100%;max-width:752px;height:auto;border:0;'
                . 'border-radius:14px;outline:none;text-decoration:none;">'
                . '</td></tr></table>'
            : '';
        $bodyhtml = str_replace($imagesentinel, $imagehtml, $bodyhtml);

        $bodyhtml = str_replace($offersentinel, $offerplaceholder, $bodyhtml);
        $parts = preg_split('/\{\{offer\}\}/i', $bodyhtml, 2);

        return [
            'subject' => clean_param(
                CommercePersonalOfferCampaignMailVariableResolver::replace(
                    (string)($content['subject'] ?? ''),
                    $values
                ),
                PARAM_TEXT
            ),
            'preheader' => clean_param(
                CommercePersonalOfferCampaignMailVariableResolver::replace(
                    (string)($content['preheader'] ?? ''),
                    $values
                ),
                PARAM_TEXT
            ),
            'heading' => '',
            'introhtml' => (string)($parts[0] ?? ''),
            'outrohtml' => (string)($parts[1] ?? ''),
            'signaturehtml' => '',
            'headerimage' => false,
            'positionablelayout' => preg_match(
                '/\{\{(?:offer|image)\}\}'
                    . '|\{\{cta(?:\|[a-z_]+)?\}\}.*?\{\{\/cta\}\}/is',
                (string)($content['bodyhtml'] ?? '')
            ) === 1,
            'headcss' => $ctarenderer->hover_css(),
            'templateid' => 0,
        ];
    }
}
