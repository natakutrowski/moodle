<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\checkout\unified\CommerceCheckoutPurchasePersister;
use local_subscriptions\commerce\domain\CommerceItem;
use local_subscriptions\commerce\legal\document\CommerceLegalDocumentResolver;
use local_subscriptions\commerce\persistence\CommercePurchasePersistenceMapper;
use local_subscriptions\commerce\persistence\sql\CommercePurchaseSqlRepositoryFactory;
use local_subscriptions\commerce\purchase\CommerceCustomer;
use local_subscriptions\commerce\purchase\CommercePurchaseRequest;
use local_subscriptions\commerce\purchase\CommercePurchaseRequestItem;

final class commerce_796i5_legal_documents_consent_test extends advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_row_legal_documents_do_not_switch_to_ru_entity_from_russian_language(): void {
        $documents = (new CommerceLegalDocumentResolver())->resolve('FR', 'ru');

        self::assertSame('fr_main', $documents->get_legal_entity_key());
        self::assertSame('FR', $documents->get_market_country());
        self::assertSame('ru', $documents->get_language());
        self::assertStringContainsString('/pages/terms_en.php', $documents->get_terms_url());
        self::assertStringNotContainsString('/pages/terms_ru.php', $documents->get_terms_url());
    }

    public function test_ru_by_documents_follow_market_not_currency_or_interface_language(): void {
        $documents = (new CommerceLegalDocumentResolver())->resolve('BY', 'en');

        self::assertSame('ru_main', $documents->get_legal_entity_key());
        self::assertSame('BY', $documents->get_market_country());
        self::assertStringContainsString('/pages/terms_ru.php', $documents->get_terms_url());
        self::assertStringContainsString('/pages/policy_ru.php', $documents->get_privacy_url());
    }

    public function test_document_fingerprint_changes_when_explicit_version_changes(): void {
        set_config('legal_documents_row_version', '2026-09-v1', 'local_subscriptions');
        $v1 = (new CommerceLegalDocumentResolver())->resolve('FR', 'fr');
        set_config('legal_documents_row_version', '2026-10-v2', 'local_subscriptions');
        $v2 = (new CommerceLegalDocumentResolver())->resolve('FR', 'fr');

        self::assertNotSame($v1->get_fingerprint(), $v2->get_fingerprint());
        self::assertSame('2026-09-v1', $v1->get_version());
        self::assertSame('2026-10-v2', $v2->get_version());
    }

    public function test_purchase_snapshots_accepted_document_set_in_terms(): void {
        global $DB;

        set_config('legal_entity_fr_name', 'Campus France I5', 'local_subscriptions');
        set_config('legal_documents_row_version', '2026-09-v1', 'local_subscriptions');

        $user = $this->getDataGenerator()->create_user([
            'country' => 'FR',
            'email' => 'i5@example.test',
        ]);
        $this->setUser($user);
        // I5 tests the legal document set against the Commerce market signal,
        // not against the customer's Moodle profile country. In CLI/PHPUnit there
        // is no real request geography, so make the H market resolver deterministic.
        $_SERVER['HTTP_CF_IPCOUNTRY'] = 'FR';
        $acceptedat = 1770000000;

        $request = new CommercePurchaseRequest(
            'cmp_' . bin2hex(random_bytes(12)),
            new CommerceCustomer(
                (int)$user->id,
                (string)$user->email,
                'I5',
                'Customer',
                ['language' => 'fr']
            ),
            [
                new CommercePurchaseRequestItem(
                    new CommerceItem(CommerceItem::TYPE_DIGITAL, 'I5.TEST', 'I5 test product'),
                    1,
                    3900,
                    'EUR'
                ),
            ],
            preferredprovider: 'stripe',
            metadata: [
                'legal_acceptance' => [
                    'accepted' => true,
                    'accepted_at' => $acceptedat,
                    'source' => 'checkout_checkbox',
                ],
            ]
        );

        $purchaseid = (new CommerceCheckoutPurchasePersister(
            CommercePurchaseSqlRepositoryFactory::create(),
            new CommercePurchasePersistenceMapper()
        ))->persist($request);

        $record = $DB->get_record('local_subscriptions_commerce_purchase', ['id' => $purchaseid], 'snapshotjson', MUST_EXIST);
        $snapshot = json_decode((string)$record->snapshotjson, true);
        self::assertIsArray($snapshot);
        self::assertArrayHasKey('legal_consent', $snapshot['terms']);

        $consent = $snapshot['terms']['legal_consent'];
        self::assertTrue($consent['accepted']);
        self::assertSame($acceptedat, $consent['accepted_at']);
        self::assertSame('checkout_checkbox', $consent['source']);
        self::assertSame('legal_consent_v1', $consent['schema']);
        self::assertSame('fr_main', $consent['document_set']['legal_entity_key']);
        self::assertSame('FR', $consent['document_set']['market_country']);
        self::assertSame('2026-09-v1', $consent['document_set']['version']);
        self::assertNotEmpty($consent['document_set']['fingerprint']);
        self::assertSame(
            $snapshot['metadata']['legal_entity_snapshot']['legal_entity_key'],
            $consent['document_set']['legal_entity_key']
        );
    }

    public function test_checkout_and_express_use_versioned_legal_contract(): void {
        $root = dirname(__DIR__, 3);
        $action = file_get_contents($root . '/commerce_checkout_action.php');
        $checkout = file_get_contents($root . '/commerce_checkout.php');
        $express = file_get_contents($root . '/classes/commerce/checkout/express/CommerceCheckoutExpressService.php');
        $region = file_get_contents($root . '/classes/support/Region.php');

        self::assertStringContainsString("'legal_acceptance' => [", $action);
        self::assertStringContainsString('CommerceLegalDocumentResolver', $checkout);
        self::assertStringContainsString('CommerceLegalDocumentResolver', $express);
        self::assertStringNotContainsString('$urls = Region::policyUrls();', $express);
        self::assertStringContainsString('CommerceLegalDocumentResolver', $region);
    }

    public function test_i5_replaces_placeholders_without_database_migration(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');
        $installxml = file_get_contents($root . '/db/install.xml');
        $termsfr = file_get_contents($root . '/pages/terms_fr.php');
        $termsru = file_get_contents($root . '/pages/terms_ru.php');

        self::assertMatchesRegularExpression(
            '/\\$plugin->version = \\d+;/',
            $version
        );
        self::assertStringNotContainsString('legal_consent', $installxml);
        self::assertStringNotContainsString('À préciser', $termsfr);
        self::assertStringNotContainsString('Укажите по вашей политике', $termsru);
    }
}
