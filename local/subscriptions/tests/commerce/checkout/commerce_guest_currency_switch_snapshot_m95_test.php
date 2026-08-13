<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\domain\CommerceCart;
use local_subscriptions\commerce\cart\domain\CommerceCartItem;
use local_subscriptions\commerce\cart\repository\CommerceInMemoryCartRepository;
use local_subscriptions\commerce\cart\service\CommerceCartSessionKeyResolver;
use local_subscriptions\commerce\checkout\guest\CommerceGuestAccountProvisioner;
use local_subscriptions\commerce\checkout\guest\CommerceGuestCartTransferService;
use local_subscriptions\commerce\checkout\guest\CommerceGuestCheckoutService;
use local_subscriptions\commerce\checkout\guest\CommerceGuestCheckoutSessionRepository;

final class commerce_guest_currency_switch_snapshot_m95_test extends \advanced_testcase {
    public function test_eur_to_rub_switch_replaces_durable_snapshot_with_transferred_rub_cart(): void {
        $this->resetAfterTest(true);

        $result = $this->switch_currency(
            'EUR',
            23,
            'RUB',
            24
        );

        $this->assertSame('RUB', $result['session']->get_currency());
        $this->assertSame('EUR', $result['metadata']['currency_switched_from']);
        $this->assertSame('RUB', $result['snapshot']['currency']);
        $this->assertSame(0, $result['snapshot']['customerid']);
        $this->assertSame(24, $result['snapshot']['items'][0]['priceid']);
        $this->assertSame(
            '0cf49f82d071adcc640f0bcaa3879023',
            $result['snapshot']['items'][0]['metadata']['personal_offer_uuid']
        );
        $this->assertSame(
            'campaign-test',
            $result['snapshot']['items'][0]['metadata']['personal_offer_campaign']
        );
        $this->assertSame($result['cart']->get_uuid(), $result['metadata']['cart_uuid']);
        $this->assertSame($result['cart']->get_uuid(), $result['snapshot']['uuid']);
    }

    public function test_rub_to_eur_switch_replaces_durable_snapshot_with_transferred_eur_cart(): void {
        $this->resetAfterTest(true);

        $result = $this->switch_currency(
            'RUB',
            24,
            'EUR',
            23
        );

        $this->assertSame('EUR', $result['session']->get_currency());
        $this->assertSame('RUB', $result['metadata']['currency_switched_from']);
        $this->assertSame('EUR', $result['snapshot']['currency']);
        $this->assertSame(0, $result['snapshot']['customerid']);
        $this->assertSame(23, $result['snapshot']['items'][0]['priceid']);
    }

    public function test_same_currency_switch_is_a_noop_and_does_not_rewrite_snapshot(): void {
        global $DB;

        $this->resetAfterTest(true);

        $sessions = new CommerceGuestCheckoutSessionRepository($DB);
        $session = $sessions->create('EUR', time() + 3600, [
            'guest_cart_snapshot' => $this->anonymous_snapshot('EUR', 23),
            'sentinel' => 'unchanged',
        ]);
        $session = $sessions->update_identity(
            $session,
            4242,
            'guest@example.test',
            'Guest',
            'Customer',
            'provisional'
        );

        $repository = new CommerceInMemoryCartRepository();
        $keys = new CommerceCartSessionKeyResolver();
        $service = new CommerceGuestCheckoutService(
            $sessions,
            new CommerceGuestAccountProvisioner($DB, $sessions),
            new CommerceGuestCartTransferService($repository, $keys)
        );

        $result = $service->switch_provisional_currency($session, 'EUR');

        $this->assertSame($session->get_id(), $result->get_id());
        $this->assertSame('EUR', $result->get_currency());
        $this->assertSame('unchanged', $result->get_metadata()['sentinel']);
        $this->assertSame(23, $result->get_metadata()['guest_cart_snapshot']['items'][0]['priceid']);
        $this->assertArrayNotHasKey('currency_switched_at', $result->get_metadata());
    }

    /**
     * @return array{
     *     session:\local_subscriptions\commerce\checkout\guest\CommerceGuestCheckoutSession,
     *     metadata:array<string,mixed>,
     *     snapshot:array<string,mixed>,
     *     cart:CommerceCart
     * }
     */
    private function switch_currency(
        string $fromcurrency,
        int $frompriceid,
        string $tocurrency,
        int $topriceid
    ): array {
        global $DB;

        $sessions = new CommerceGuestCheckoutSessionRepository($DB);
        $session = $sessions->create($fromcurrency, time() + 3600, [
            'guest_cart_snapshot' => $this->anonymous_snapshot($fromcurrency, $frompriceid),
            'checkout_source' => 'personaloffer',
        ]);
        $session = $sessions->update_identity(
            $session,
            4242,
            'guest@example.test',
            'Guest',
            'Customer',
            'provisional'
        );

        $repository = new CommerceInMemoryCartRepository();
        $keys = new CommerceCartSessionKeyResolver();

        // This is the authoritative cart already prepared by the signed offer
        // entry point for the currency selected by the customer.
        $targetguestcart = new CommerceCart(
            str_repeat($tocurrency === 'RUB' ? 'b' : 'c', 32),
            0,
            $tocurrency,
            [
                new CommerceCartItem(
                    'BUNDLE.THIRD_GROUP_VERBS_COURSE',
                    $topriceid,
                    1,
                    [
                        'operation' => 'personaloffer',
                        'personal_offer_uuid' => '0cf49f82d071adcc640f0bcaa3879023',
                        'personal_offer_campaign' => 'campaign-test',
                    ]
                ),
            ]
        );
        $repository->save($keys->resolve(0, $tocurrency), $targetguestcart);

        $service = new CommerceGuestCheckoutService(
            $sessions,
            new CommerceGuestAccountProvisioner($DB, $sessions),
            new CommerceGuestCartTransferService($repository, $keys)
        );

        $switched = $service->switch_provisional_currency($session, $tocurrency);
        $persisted = $repository->find($keys->resolve(4242, $tocurrency));

        $this->assertNotNull($persisted);
        $this->assertNull($repository->find($keys->resolve(0, $tocurrency)));
        $this->assertSame(4242, $persisted->get_customer_id());
        $this->assertSame($tocurrency, $persisted->get_currency());
        $this->assertSame($topriceid, $persisted->get_items()[0]->get_price_id());

        $metadata = $switched->get_metadata();
        $snapshot = $metadata['guest_cart_snapshot'];

        return [
            'session' => $switched,
            'metadata' => $metadata,
            'snapshot' => $snapshot,
            'cart' => $persisted,
        ];
    }

    /** @return array<string,mixed> */
    private function anonymous_snapshot(string $currency, int $priceid): array {
        return (new CommerceCart(
            str_repeat('a', 32),
            0,
            $currency,
            [
                new CommerceCartItem(
                    'BUNDLE.THIRD_GROUP_VERBS_COURSE',
                    $priceid,
                    1,
                    [
                        'operation' => 'personaloffer',
                        'personal_offer_uuid' => '0cf49f82d071adcc640f0bcaa3879023',
                        'personal_offer_campaign' => 'campaign-test',
                    ]
                ),
            ]
        ))->to_array();
    }
}
