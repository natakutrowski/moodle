<?php
// Verify data-only Commerce linkage for persistent Lovable drafts.
declare(strict_types=1);
namespace local_subscriptions\tests\commerce\showroom;
defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\bridge\CommerceShowroomLovableCommerceLink as Link;
use local_subscriptions\commerce\showroom\bridge\CommerceShowroomLovableDraftService as Draft;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;

final class commerce_gustave_lovable_commerce_link_test extends \advanced_testcase {
    private function create_draft(): int {
        global $DB;
        $user = $this->getDataGenerator()->create_user();
        $meta = [
            'version' => 1, 'designid' => 'articles_alf_reimport',
            'catalogues' => ['ru' => ['header_001' => 'Campus'], 'fr' => [], 'en' => []],
            'commerce_connected' => false, 'manual_attributes_to_review' => 17,
        ];
        return (new CommerceShowroomCmsRepository($DB))->save([
            'showroomkey' => Draft::draft_key('articles_alf_reimport'),
            'status' => 'draft', 'name' => 'Gustave Commerce test',
            'template' => 'local_subscriptions/showroom/default',
            'productsjson' => '{}',
            'settingsjson' => json_encode([Draft::SETTINGS_KEY => $meta], JSON_THROW_ON_ERROR),
        ], (int)$user->id);
    }

    public function test_reject_unknown_card_roles(): void {
        global $DB;
        $this->resetAfterTest(true);
        $id = $this->create_draft();
        $this->expectException(\invalid_parameter_exception::class);
        Link::save($DB, $id, ['checkout' => 'arbitrary_payment'], 2);
    }

    public function test_empty_mappings_remain_nonpublishable_and_keep_texts(): void {
        global $DB;
        $this->resetAfterTest(true);
        $id = $this->create_draft();
        Link::save($DB, $id, ['course' => '', 'bundle' => '', 'donation' => ''], 2);
        [$record, $meta] = Draft::require_draft($DB, $id);
        self::assertSame('draft', $record->status);
        self::assertSame('', (string)$record->slugru);
        self::assertSame('Campus', $meta['catalogues']['ru']['header_001']);
        self::assertFalse($meta['commerce_connected']);
        self::assertTrue($meta['commerce_mapping_saved']);
        self::assertSame(['course' => '', 'bundle' => '', 'donation' => ''], Link::selected($record));
    }

    public function test_only_native_commerce_resolver_can_supply_prices(): void {
        global $CFG;
        $root = $CFG->dirroot . '/local/subscriptions/';
        $service = file_get_contents($root . 'classes/commerce/showroom/bridge/CommerceShowroomLovableCommerceLink.php');
        $page = file_get_contents($root . 'admin/commerce/showrooms/lovable_cms_commerce.php');
        $preview = file_get_contents($root . 'admin/commerce/showrooms/lovable_cms_preview.php');
        $frame = file_get_contents($root . 'admin/commerce/showrooms/lovable_cms_frame.php');
        self::assertStringContainsString('CommerceShowroomProductResolver::create($db)->resolve', $service);
        self::assertStringContainsString('CommerceShowroomProductConfig::to_json', $service);
        self::assertStringContainsString('normalise_role_sku', $service);
        self::assertStringContainsString('require_sesskey()', $page);
        self::assertStringContainsString("require_capability('local/subscriptions:manage_showrooms'", $page);
        // V2 had a manual JSX-pricing warning. V3 removes that toolbar because
        // the preview now receives READ-ONLY prices from native Commerce.
        // Keep a meaningful invariant for BOTH supported preview layouts.
        self::assertStringContainsString('Link::read_only_review($DB, $id, $currency)', $frame);
        self::assertStringContainsString('Currency::active_currencies($DB)', $frame);
        self::assertStringContainsString("connect-src 'none'", $frame);
        self::assertStringContainsString("form-action 'none'", $frame);
        self::assertStringContainsString("'sandbox' => 'allow-scripts'", $preview);
        self::assertStringNotContainsString('allow-same-origin', $preview);
        self::assertStringNotContainsString('cart_action.php', $frame);
        if (str_contains($preview, 'campusfr-preview-currency-v41')) {
            // V4.1: currency refresh is data-only through the authenticated
            // parent and read-only Commerce resolver. Neither frame navigation
            // nor a client-derived price is accepted.
            $endpoint = file_get_contents($root . 'admin/commerce/showrooms/lovable_cms_currency_v41.php');
            self::assertStringContainsString('Currency::active_currencies($DB)', $preview);
            self::assertStringContainsString('event.source!==frame.contentWindow', $preview);
            self::assertStringContainsString('event.origin!=="null"', $preview);
            self::assertStringContainsString('data.type!=="campusfr-preview-currency-v41"', $preview);
            self::assertStringContainsString('!allowed.includes(next)', $preview);
            self::assertStringContainsString('credentials:"same-origin"', $preview);
            self::assertStringContainsString('campusfr-preview-currency-result-v41', $preview);
            self::assertStringNotContainsString('window.location.assign', $preview);
            self::assertStringContainsString('require_login()', $endpoint);
            self::assertStringContainsString("require_capability('local/subscriptions:manage_showrooms'", $endpoint);
            self::assertStringContainsString('Link::read_only_review($DB, $id, $currency)', $endpoint);
            self::assertStringContainsString('Currency::active_currencies($DB)', $endpoint);
            self::assertStringNotContainsString('cartaction', $endpoint);
            self::assertStringNotContainsString('sesskey', $endpoint);
            self::assertStringNotContainsString('tarifs JSX non synchronisés', $preview);
        } else if (str_contains($preview, 'campusfr-preview-currency-v3')) {
            // A sandboxed, opaque-origin iframe may only REQUEST a currency
            // reload. The parent revalidates the source, origin and currency;
            // it never accepts prices, cart actions, or arbitrary navigation.
            self::assertStringContainsString('Currency::active_currencies($DB)', $preview);
            self::assertStringContainsString('event.source!==frame.contentWindow', $preview);
            self::assertStringContainsString("event.origin!=='null'", $preview);
            self::assertStringContainsString("data.type!=='campusfr-preview-currency-v3'", $preview);
            self::assertStringContainsString('!allowed.includes(next)', $preview);
            self::assertStringContainsString("url.searchParams.set('currency',next)", $preview);
            self::assertStringNotContainsString('tarifs JSX non synchronisés', $preview);
        } else {
            // The original V2 warning remains required until V3 is installed.
            self::assertStringContainsString('tarifs JSX non synchronisés', $preview);
            self::assertStringContainsString('campusfr-lovable-currency-form', $preview);
        }
        self::assertStringContainsString("$" . "meta['commerce_connected'] = false", $service);
        self::assertStringNotContainsString('cartaction\' =>', $service);
        self::assertStringNotContainsString('redirect($cart', $page);
    }
}
