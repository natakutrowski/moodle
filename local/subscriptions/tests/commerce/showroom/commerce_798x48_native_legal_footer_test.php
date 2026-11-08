<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\showroom\legal\CommerceShowroomLegalFooterPresenter;
use local_subscriptions\commerce\legal\merchant\CommerceMerchantResolver;
use local_subscriptions\commerce\legal\merchant\CommerceMerchantResolutionContext;

/** P2E isolated native Commerce footer: no extra merchant policy. */
final class commerce_798x48_native_legal_footer_test extends advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_template_data_displays_only_present_legal_fields(): void {
        $data = (new CommerceShowroomLegalFooterPresenter())->present([
            'fields' => [
                'name' => 'Entity Test', 'address' => "Line 1\nLine 2",
                'legal' => '', 'registration' => 'REG-123', 'taxidentifier' => '',
                'email' => 'contact@example.test', 'phone' => '', 'website' => '',
                'taxnotice' => '', 'footer' => 'Footer note',
            ],
            'urls' => [
                'privacy' => 'https://example.test/privacy',
                'terms' => '/local/subscriptions/pages/terms_fr.php',
                'offer' => '',
            ],
        ]);
        self::assertTrue($data['shownativelegalfooter']);
        self::assertSame('Entity Test', $data['finallegalname']);
        self::assertTrue($data['finallegalshowname']);
        self::assertFalse($data['finallegalshowlegal']);
        self::assertTrue($data['finallegalshowregistration']);
        self::assertFalse($data['finallegalshowtaxidentifier']);
        self::assertTrue($data['finallegalshowprivacy']);
        self::assertTrue($data['finallegalshowterms']);
        self::assertFalse($data['finallegalshowoffer']);
    }

    public function test_legal_links_reject_unsafe_protocols(): void {
        $this->expectException(\coding_exception::class);
        (new CommerceShowroomLegalFooterPresenter())->present([
            'fields' => [], 'urls' => ['privacy' => 'javascript:alert(1)'],
        ]);
    }

    public function test_existing_checkout_seller_policy_is_currency_independent(): void {
        $resolver = new CommerceMerchantResolver();
        foreach (['FR' => 'fr_main', 'RU' => 'ru_main', 'BY' => 'ru_main', 'ZZ' => 'fr_main'] as $country => $expected) {
            foreach (['EUR', 'RUB'] as $currency) {
                $merchant = $resolver->resolve(new CommerceMerchantResolutionContext($country, $currency));
                self::assertSame($expected, $merchant->get_entity_key());
            }
        }
    }

    public function test_bridge_and_classic_share_same_native_mustache_partial(): void {
        $root = dirname(__DIR__, 3);
        $bridge = file_get_contents($root . '/templates/showroom/bridge.mustache');
        $classic = file_get_contents($root . '/templates/showroom/default.mustache');
        $shared = file_get_contents($root . '/templates/showroom/legal_footer.mustache');
        self::assertStringContainsString('{{> local_subscriptions/showroom/legal_footer}}', $bridge);
        self::assertStringContainsString('{{> local_subscriptions/showroom/legal_footer}}', $classic);
        self::assertStringContainsString('data-showroom-final-legal', $shared);
        self::assertStringContainsString('finallegalprivacyurl', $shared);
        self::assertStringContainsString('finallegalofferurl', $shared);
    }
}
