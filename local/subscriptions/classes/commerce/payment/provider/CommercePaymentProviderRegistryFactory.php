<?php

namespace local_subscriptions\commerce\payment\provider;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\payment\legacy\LegacyPaymentRequestAdapter;
use local_subscriptions\commerce\payment\provider\alfa\AlfaCommercePaymentProvider;
use local_subscriptions\commerce\payment\provider\alfa\AlfaPaymentGateway;
use local_subscriptions\commerce\payment\provider\alfa\AlfaPaymentProviderConfiguration;
use local_subscriptions\commerce\payment\provider\alfa\LegacyAlfaPaymentGateway;
use local_subscriptions\commerce\payment\provider\stripe\LegacyStripePaymentGateway;
use local_subscriptions\commerce\payment\provider\stripe\StripeCommercePaymentProvider;
use local_subscriptions\commerce\payment\provider\stripe\StripePaymentGateway;
use local_subscriptions\commerce\payment\provider\stripe\StripePaymentProviderConfiguration;

/**
 * Builds the Commerce payment provider registry.
 *
 * Both providers are always registered. Their availability is determined
 * independently from their registration.
 */
final class CommercePaymentProviderRegistryFactory {

    /**
     * Creates the Commerce payment provider registry.
     *
     * Optional gateway arguments are intended for PHPUnit and controlled
     * integrations. Production callers should normally pass no argument.
     */
    public static function create(
        ?\moodle_database $db = null,
        ?StripePaymentGateway $stripegateway = null,
        ?AlfaPaymentGateway $alfagateway = null
    ): CommercePaymentProviderRegistry {
        global $DB;

        $db ??= $DB;

        $adapter =
            new LegacyPaymentRequestAdapter(
                $db
            );

        $stripegateway ??=
            new LegacyStripePaymentGateway(
                $adapter
            );

        $alfagateway ??=
            new LegacyAlfaPaymentGateway(
                $adapter
            );

        $stripeprovider =
            new StripeCommercePaymentProvider(
                $stripegateway,
                new StripePaymentProviderConfiguration(
                    true
                )
            );

        $alfaprovider =
            new AlfaCommercePaymentProvider(
                $alfagateway,
                new AlfaPaymentProviderConfiguration(
                    true
                )
            );

        return new CommercePaymentProviderRegistry([
            $stripeprovider,
            $alfaprovider,
        ]);
    }
}