<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_797m11_offer_capacity_admin_edit_test extends \advanced_testcase {
    public function test_offer_admin_exposes_in_place_capacity_update_without_unlinking(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents($root . '/admin/commerce/education/offers.php');

        self::assertIsString($source);
        self::assertStringContainsString("\$action === 'link' || \$action === 'update'", $source);
        self::assertStringContainsString("'name' => 'action', 'value' => 'update'", $source);
        self::assertStringContainsString("'name' => 'productid', 'value' => (int)\$link->productid", $source);
        self::assertStringContainsString("'name' => 'capacity'", $source);
        self::assertStringContainsString("'value' => \$link->capacity !== null ? (int)\$link->capacity : ''", $source);
        self::assertStringContainsString("get_string('savechanges')", $source);
        self::assertStringContainsString("'name' => 'action', 'value' => 'unlink'", $source);
    }

    public function test_offer_repository_update_remains_an_upsert(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/classes/commerce/education/promotion/CommercePedagogicalPromotionOfferRepository.php'
        );

        self::assertIsString($source);
        self::assertStringContainsString("['promotionid' => \$promotionid, 'productid' => \$productid]", $source);
        self::assertStringContainsString('$this->db->update_record(self::TABLE, $record);', $source);
        self::assertStringContainsString('$this->db->insert_record(self::TABLE, $record);', $source);
    }
}
