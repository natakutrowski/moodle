<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom\legal;

use local_subscriptions\commerce\legal\document\CommerceLegalDocumentResolver;
use local_subscriptions\commerce\legal\merchant\CommerceMerchantMarketResolver;

defined('MOODLE_INTERNAL') || die();

/** Display-only legal footer for Commerce showrooms; never resolves sellers by currency. */
final class CommerceShowroomLegalFooterPresenter {
    /**
     * Uses the exact market/merchant and document resolvers used by the checkout.
     * The supplied currency is context only, NOT a seller selection key.
     * @return array{entitykey:string,marketcountry:string,fields:array<string,string>,urls:array<string,string>}
     */
    public function resolve(string $currency, string $language): array {
        $merchant = (new CommerceMerchantMarketResolver())->resolve($currency, '');
        $documents = (new CommerceLegalDocumentResolver())->resolve($merchant->get_market_country(), $language);
        if ($merchant->get_entity_key() !== $documents->get_legal_entity_key()) {
            throw new \coding_exception('Commerce footer seller disagrees with legal documents.');
        }
        $entity = $merchant->get_entity();
        return [
            'entitykey' => $merchant->get_entity_key(),
            'marketcountry' => $merchant->get_market_country(),
            'fields' => [
                'name' => $entity->get_name(),
                'address' => $entity->get_address(),
                'legal' => $entity->get_legal(),
                'registration' => $entity->get_registration(),
                'taxidentifier' => $entity->get_tax_identifier(),
                'email' => $entity->get_email(),
                'phone' => $entity->get_phone(),
                'website' => $entity->get_website(),
                'taxnotice' => $entity->get_tax_notice(),
                'footer' => $entity->get_footer(),
            ],
            'urls' => [
                'privacy' => $documents->get_privacy_url(),
                'terms' => $documents->get_terms_url(),
                'offer' => $documents->get_offer_url(),
            ],
        ];
    }

    /**
     * Safe, consistent template data for both native templates and Bridge adapter.
     * @param array<string,mixed> $resolved Server-resolved market and legal documents.
     * @return array<string,mixed>
     */
    public function present(array $resolved): array {
        $data = ['shownativelegalfooter' => true];
        foreach (['name', 'address', 'legal', 'registration', 'taxidentifier', 'email',
            'phone', 'website', 'taxnotice', 'footer'] as $field) {
            $value = trim((string)($resolved['fields'][$field] ?? ''));
            $data['finallegal' . $field] = $value;
            $data['hasfinallegal' . $field] = $value !== '';
            $data['finallegalshow' . $field] = $value !== '';
        }
        foreach (['privacy', 'terms', 'offer'] as $field) {
            $url = trim((string)($resolved['urls'][$field] ?? ''));
            // No unverified javascript: or protocol-relative URL in the legal footer.
            if ($url !== '' && !preg_match('~^https?://[^\s]+$~i', $url)
                && !preg_match('~^/[^/]~', $url)) {
                throw new \coding_exception('Invalid Commerce legal document URL.');
            }
            $data['finallegal' . $field . 'url'] = $url;
            $data['finallegalshow' . $field] = $url !== '';
        }
        $data['finallegalprivacylabel'] = get_string('privacy_policy', 'local_subscriptions');
        $data['finallegaltermslabel'] = get_string('terms_cgu', 'local_subscriptions');
        $data['finallegalofferlabel'] = get_string('terms_cgv', 'local_subscriptions');
        $data['finallegalnavlabel'] = implode(' · ', [
            $data['finallegalprivacylabel'],
            $data['finallegaltermslabel'],
            $data['finallegalofferlabel'],
        ]);
        return $data;
    }
}
