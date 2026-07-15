<?php

namespace local_subscriptions\crm\inbox\ai\providers;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\ai\contracts\InboxAiProviderInterface;

final class InboxAiProviderRegistry {

    /**
     * @var array<string,InboxAiProviderInterface>
     */
    private array $providers = [];

    /**
     * @param InboxAiProviderInterface[] $providers
     */
    public function __construct(
        array $providers = []
    ) {
        foreach ($providers as $provider) {
            $this->register($provider);
        }
    }

    public function register(
        InboxAiProviderInterface $provider
    ): void {
        $this->providers[
            $provider->key()
        ] = $provider;
    }

    public function get(
        string $providerkey
    ): ?InboxAiProviderInterface {
        return $this->providers[
            $providerkey
        ] ?? null;
    }

    public function resolve(
        string $capability,
        ?string $preferredprovider = null
    ): ?InboxAiProviderInterface {
        if ($preferredprovider !== null) {
            $preferred =
                $this->get($preferredprovider);

            if (
                $preferred &&
                $preferred->is_available() &&
                $preferred->supports($capability)
            ) {
                return $preferred;
            }
        }

        /*
         * Les providers distants enregistrés avant
         * le fallback seront prioritaires.
         */
        foreach ($this->providers as $provider) {
            if (
                $provider->key() === 'fallback'
            ) {
                continue;
            }

            if (
                $provider->is_available() &&
                $provider->supports($capability)
            ) {
                return $provider;
            }
        }

        $fallback = $this->get('fallback');

        if (
            $fallback &&
            $fallback->is_available() &&
            $fallback->supports($capability)
        ) {
            return $fallback;
        }

        return null;
    }

    public function keys(): array {
        return array_keys($this->providers);
    }
}