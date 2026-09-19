<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\checkout\unified\CommerceCheckoutPurchasePersister;
use local_subscriptions\commerce\domain\CommerceItem;
use local_subscriptions\commerce\legal\entity\CommerceLegalEntityRegistry;
use local_subscriptions\commerce\legal\entity\CommerceLegalEntitySnapshot;
use local_subscriptions\commerce\legal\merchant\CommerceMerchantResolutionContext;
use local_subscriptions\commerce\legal\merchant\CommerceMerchantResolver;
use local_subscriptions\commerce\persistence\CommercePurchasePersistenceMapper;
use local_subscriptions\commerce\persistence\sql\CommercePurchaseSqlRepositoryFactory;
use local_subscriptions\commerce\purchase\CommerceCustomer;
use local_subscriptions\commerce\purchase\CommercePurchaseRequest;
use local_subscriptions\commerce\purchase\CommercePurchaseRequestItem;

final class commerce_796i4_legal_entity_snapshot_test extends advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_snapshot_freezes_configured_entity_identity_and_resolution_evidence(): void {
        set_config('legal_entity_ru_name', 'Campus RU Original', 'local_subscriptions');
        set_config('legal_entity_ru_address', 'Original address', 'local_subscriptions');
        set_config('legal_entity_ru_registration', 'RU-REG-001', 'local_subscriptions');
        set_config('legal_entity_ru_tax_identifier', 'RU-TAX-001', 'local_subscriptions');

        $result = (new CommerceMerchantResolver())->resolve(
            new CommerceMerchantResolutionContext('RU', 'EUR', 'stripe')
        );
        $snapshot = CommerceLegalEntitySnapshot::from_resolution_result(
            $result,
            1700000000,
            'EUR',
            'stripe'
        );

        set_config('legal_entity_ru_name', 'Campus RU Changed Later', 'local_subscriptions');
        set_config('legal_entity_ru_address', 'New address', 'local_subscriptions');

        self::assertSame('Campus RU Original', $snapshot->to_array()['name']);
        self::assertSame('Original address', $snapshot->to_array()['address']);
        self::assertSame('RU-REG-001', $snapshot->to_array()['registration']);
        self::assertSame('RU-TAX-001', $snapshot->to_array()['tax_identifier']);
        self::assertSame('ru_main', $snapshot->get_entity_key());
        self::assertSame('RU', $snapshot->get_market_country());
        self::assertSame(CommerceMerchantResolver::RULE_RU_BY, $snapshot->get_resolution_rule());
        self::assertSame('EUR', $snapshot->get_currency());
        self::assertSame('stripe', $snapshot->get_provider());

        self::assertSame(
            'Campus RU Changed Later',
            (new CommerceLegalEntityRegistry())->get(CommerceLegalEntityRegistry::RU_MAIN)->get_name()
        );
    }

    public function test_snapshot_can_be_round_tripped_without_current_configuration(): void {
        set_config('legal_entity_fr_name', 'Campus France Snapshot', 'local_subscriptions');
        $result = (new CommerceMerchantResolver())->resolve(
            new CommerceMerchantResolutionContext('FR', 'USD', 'paypal')
        );
        $original = CommerceLegalEntitySnapshot::from_resolution_result($result, 1700000001, 'USD', 'paypal');

        $restored = CommerceLegalEntitySnapshot::from_array($original->to_array());

        self::assertSame($original->to_array(), $restored->to_array());
    }

    public function test_unified_checkout_persists_legal_entity_snapshot_in_purchase_snapshotjson(): void {
        global $DB;

        set_config('legal_entity_fr_name', 'Campus France At Sale', 'local_subscriptions');
        set_config('legal_entity_fr_address', 'Sale address', 'local_subscriptions');

        $user = $this->getDataGenerator()->create_user([
            'country' => 'FR',
            'email' => 'i4@example.test',
        ]);
        $this->setUser($user);

        $request = new CommercePurchaseRequest(
            'cmp_' . bin2hex(random_bytes(12)),
            new CommerceCustomer((int)$user->id, (string)$user->email, 'I4', 'Customer'),
            [
                new CommercePurchaseRequestItem(
                    new CommerceItem(CommerceItem::TYPE_DIGITAL, 'I4.TEST', 'I4 test product'),
                    1,
                    2900,
                    'EUR'
                ),
            ],
            preferredprovider: 'stripe',
            metadata: ['i4_test' => true]
        );

        $persister = new CommerceCheckoutPurchasePersister(
            CommercePurchaseSqlRepositoryFactory::create(),
            new CommercePurchasePersistenceMapper()
        );
        $purchaseid = $persister->persist($request);

        $record = $DB->get_record('local_subscriptions_commerce_purchase', ['id' => $purchaseid], '*', MUST_EXIST);
        $commercial = json_decode((string)$record->snapshotjson, true);
        self::assertIsArray($commercial);
        self::assertArrayHasKey('metadata', $commercial);
        self::assertArrayHasKey('legal_entity_snapshot', $commercial['metadata']);

        $legal = $commercial['metadata']['legal_entity_snapshot'];
        self::assertSame('fr_main', $legal['legal_entity_key']);
        self::assertSame('FR', $legal['registered_country']);
        self::assertSame('Campus France At Sale', $legal['name']);
        self::assertSame('Sale address', $legal['address']);
        self::assertSame('EUR', $legal['currency']);
        self::assertSame('stripe', $legal['provider']);
        self::assertNotEmpty($legal['resolution_rule']);
        self::assertGreaterThan(0, $legal['resolved_at']);

        set_config('legal_entity_fr_name', 'Campus France Changed Later', 'local_subscriptions');
        $recordagain = $DB->get_record('local_subscriptions_commerce_purchase', ['id' => $purchaseid], '*', MUST_EXIST);
        $commercialagain = json_decode((string)$recordagain->snapshotjson, true);
        self::assertSame('Campus France At Sale', $commercialagain['metadata']['legal_entity_snapshot']['name']);
    }

    public function test_legacy_commercial_snapshot_without_legal_entity_remains_valid(): void {
        $snapshot = new \local_subscriptions\commerce\domain\value\CommercePurchaseSnapshot(
            'legacy-offer',
            'Legacy offer',
            null,
            ['currency' => 'EUR'],
            [],
            [],
            ['legacy' => true]
        );

        self::assertNull($snapshot->get_metadata_value('legal_entity_snapshot'));
    }

    public function test_i4_keeps_schema_and_plugin_version_unchanged(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');
        $installxml = file_get_contents($root . '/db/install.xml');

        self::assertMatchesRegularExpression(
            '/\\$plugin->version = \\d+;/',
            $version
        );
        self::assertStringNotContainsString('legal_entity_snapshot', $installxml);
    }
}
