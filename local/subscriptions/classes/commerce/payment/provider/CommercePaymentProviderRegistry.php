<?php

namespace local_subscriptions\commerce\payment\provider;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\payment\CommercePaymentRequest;

/**
 * Registry and resolver for Commerce payment providers.
 */
final class CommercePaymentProviderRegistry {

    /**
     * @var array<string,CommercePaymentProvider>
     */
    private array $providers = [];

    /**
     * @param CommercePaymentProvider[] $providers
     */
    public function __construct(
        array $providers = []
    ) {
        foreach ($providers as $provider) {
            if (!$provider instanceof CommercePaymentProvider) {
                throw new \coding_exception(
                    'The Commerce payment provider registry received an invalid provider.'
                );
            }

            $this->register($provider);
        }
    }

    public function register(
        CommercePaymentProvider $provider
    ): void {
        $key = $this->normalise_key(
            $provider->get_key()
        );

        if (isset($this->providers[$key])) {
            throw new CommercePaymentProviderConflictException(
                'A Commerce payment provider is already registered for key: '
                . $key,
                $key
            );
        }

        $this->providers[$key] = $provider;
    }

    public function has(
        string $key
    ): bool {
        return isset(
            $this->providers[
                $this->normalise_key($key)
            ]
        );
    }

    public function get(
        string $key
    ): CommercePaymentProvider {
        $key = $this->normalise_key(
            $key
        );

        if (!isset($this->providers[$key])) {
            throw new CommercePaymentProviderNotFoundException(
                'No Commerce payment provider is registered for key: '
                . $key,
                $key
            );
        }

        return $this->providers[$key];
    }

    public function resolve(
        CommercePaymentRequest $request
    ): CommercePaymentProvider {
        $preferredprovider =
            $request->get_preferred_provider();

        if ($preferredprovider !== null) {
            return $this->resolve_preferred(
                $preferredprovider,
                $request
            );
        }

        return $this->resolve_automatic(
            $request
        );
    }

    /**
     * @return CommercePaymentProvider[]
     */
    public function all(): array {
        return array_values(
            $this->providers
        );
    }

    /**
     * @return CommercePaymentProvider[]
     */
    public function available(): array {
        return array_values(
            array_filter(
                $this->providers,
                static fn(
                    CommercePaymentProvider $provider
                ): bool => $provider->is_available()
            )
        );
    }

    /**
     * @return string[]
     */
    public function keys(): array {
        return array_keys(
            $this->providers
        );
    }

    private function resolve_preferred(
        string $preferredprovider,
        CommercePaymentRequest $request
    ): CommercePaymentProvider {
        $provider = $this->get(
            $preferredprovider
        );

        if (!$provider->is_available()) {
            throw new CommercePaymentProviderUnavailableException(
                sprintf(
                    'The requested Commerce payment provider "%s" is unavailable.',
                    $provider->get_key()
                ),
                $provider->get_key()
            );
        }

        if (!$provider->supports($request)) {
            throw new CommercePaymentProviderNotFoundException(
                sprintf(
                    'The requested Commerce payment provider "%s" does not support payment "%s".',
                    $provider->get_key(),
                    $request->get_reference()
                ),
                $provider->get_key()
            );
        }

        return $provider;
    }

    private function resolve_automatic(
        CommercePaymentRequest $request
    ): CommercePaymentProvider {
        $candidates = array_values(
            array_filter(
                $this->providers,
                static fn(
                    CommercePaymentProvider $provider
                ): bool =>
                    $provider->is_available()
                    && $provider->supports($request)
            )
        );

        if ($candidates === []) {
            throw new CommercePaymentProviderNotFoundException(
                sprintf(
                    'No available Commerce payment provider supports payment "%s" in %s.',
                    $request->get_reference(),
                    $request->get_currency()
                )
            );
        }

        usort(
            $candidates,
            static fn(
                CommercePaymentProvider $left,
                CommercePaymentProvider $right
            ): int =>
                $right->get_priority()
                <=> $left->get_priority()
        );

        $winner = $candidates[0];

        if (
            isset($candidates[1])
            && $candidates[1]->get_priority()
                === $winner->get_priority()
        ) {
            $conflictingkeys = array_map(
                static fn(
                    CommercePaymentProvider $provider
                ): string => $provider->get_key(),
                array_values(
                    array_filter(
                        $candidates,
                        static fn(
                            CommercePaymentProvider $provider
                        ): bool =>
                            $provider->get_priority()
                            === $winner->get_priority()
                    )
                )
            );

            throw new CommercePaymentProviderConflictException(
                sprintf(
                    'Several Commerce payment providers have the same winning priority for payment "%s": %s',
                    $request->get_reference(),
                    implode(', ', $conflictingkeys)
                ),
                null,
                'provider_priority_conflict',
                [
                    'requestreference' =>
                        $request->get_reference(),

                    'providers' =>
                        $conflictingkeys,

                    'priority' =>
                        $winner->get_priority(),
                ]
            );
        }

        return $winner;
    }

    private function normalise_key(
        string $key
    ): string {
        $key = strtolower(
            trim($key)
        );

        if (
            $key === ''
            || !preg_match(
                '/^[a-z][a-z0-9_]*$/',
                $key
            )
        ) {
            throw new \coding_exception(
                'Invalid Commerce payment provider key: '
                . $key
            );
        }

        return $key;
    }
}