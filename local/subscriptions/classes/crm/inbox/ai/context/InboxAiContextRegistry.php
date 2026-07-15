<?php

namespace local_subscriptions\crm\inbox\ai\context;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\ai\contracts\InboxAiContextProviderInterface;

final class InboxAiContextRegistry {

    /**
     * @var InboxAiContextProviderInterface[]
     */
    private array $providers = [];

    public function register(
        InboxAiContextProviderInterface $provider
    ): void {
        $this->providers[$provider->key()] =
            $provider;
    }

    /**
     * @return InboxAiContextProviderInterface[]
     */
    public function providers(): array {
        $providers = array_values(
            $this->providers
        );

        usort(
            $providers,
            static fn(
                InboxAiContextProviderInterface $left,
                InboxAiContextProviderInterface $right
            ): int =>
                $left->priority() <=>
                $right->priority()
        );

        return $providers;
    }
}