<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\personaloffer\audience;

defined('MOODLE_INTERNAL') || die();

final class CommercePersonalOfferAudienceProviderRegistry {
    /** @var array<string,CommercePersonalOfferAudienceProvider> */
    private array $providers = [];

    /** @param CommercePersonalOfferAudienceProvider[] $providers */
    public function __construct(array $providers) {
        foreach ($providers as $provider) {
            $this->providers[$provider->get_type()] = $provider;
        }
    }

    public static function create(\moodle_database $db): self {
        $resolver = new CommercePersonalOfferAudienceCandidateResolver($db);

        return new self([
            new CommercePersonalOfferLegacyPlanAudienceProvider($db, $resolver),
            new CommercePersonalOfferLegacyDigitalAudienceProvider($db, $resolver),
            new CommercePersonalOfferNativeProductAudienceProvider($db, $resolver),
        ]);
    }

    public function get(string $type): CommercePersonalOfferAudienceProvider {
        $type = strtolower(trim($type));
        if (!isset($this->providers[$type])) {
            throw new \moodle_exception(
                'commerce_personal_offer_invalid_source_type',
                'local_subscriptions'
            );
        }
        return $this->providers[$type];
    }

    /** @return string[] */
    public function types(): array {
        return array_keys($this->providers);
    }
}
