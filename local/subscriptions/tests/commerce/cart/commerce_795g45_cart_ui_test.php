<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\cart;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\domain\CommerceCalculatedCartItem;
use local_subscriptions\commerce\cart\domain\CommerceCart;
use local_subscriptions\commerce\cart\domain\CommerceCartItem;
use local_subscriptions\commerce\cart\domain\CommerceCartSnapshot;
use local_subscriptions\commerce\cart\domain\CommerceCartTotals;
use local_subscriptions\commerce\cart\presentation\CommerceCartPresenter;
use local_subscriptions\commerce\domain\value\CommerceMoney;

final class commerce_795g45_cart_ui_test extends \advanced_testcase {
    public function test_presenter_exposes_locked_single_quantity_and_totals(): void {
        $cart = new CommerceCart(str_repeat('a', 32), 42, 'EUR', [new CommerceCartItem('COURSE-A1', 7, 1)]);
        $money = CommerceMoney::from_minor(12900, 'EUR');
        $snapshot = new CommerceCartSnapshot($cart, [
            new CommerceCalculatedCartItem($cart->get_items()[0], 'Cours A1', $money, $money, 1, 1, 1),
        ], new CommerceCartTotals($money, CommerceMoney::zero('EUR'), CommerceMoney::zero('EUR'), $money), time());

        $data = CommerceCartPresenter::present($snapshot);
        $this->assertTrue($data['hasitems']);
        $this->assertSame(1, $data['linecount']);
        $this->assertSame(1, $data['quantitytotal']);
        $this->assertTrue($data['items'][0]['quantitylocked']);
        $this->assertSame('COURSE-A1', $data['items'][0]['productsku']);
    }

    public function test_storefront_templates_use_post_cart_action_and_cart_page_exists(): void {
        global $CFG;
        $card = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/storefront/product_card.mustache');
        $page = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/cart/page.mustache');
        $this->assertStringContainsString('method="post"', $card);
        $this->assertStringContainsString('name="sesskey"', $card);
        $this->assertStringContainsString('local_subscriptions/cart/summary', $page);
        $this->assertFileExists($CFG->dirroot . '/local/subscriptions/cart.php');
        $this->assertFileExists($CFG->dirroot . '/local/subscriptions/cart_action.php');
    }


    public function test_cart_page_initialises_page_context_before_presenting_snapshot(): void {
        global $CFG;

        $source = file_get_contents($CFG->dirroot . '/local/subscriptions/cart.php');
        $contextposition = strpos($source, '$PAGE->set_context(');
        $presenterposition = strpos($source, 'CommerceCartPresenter::present(');

        $this->assertNotFalse($contextposition);
        $this->assertNotFalse($presenterposition);
        $this->assertLessThan($presenterposition, $contextposition);
    }

    public function test_cart_strings_exist_in_all_supported_languages(): void {
        global $CFG;

        foreach (['fr', 'en', 'ru'] as $language) {
            $source = file_get_contents(
                $CFG->dirroot . '/local/subscriptions/lang/' . $language . '/local_subscriptions.php'
            );

            $this->assertStringContainsString("\$string['commerce_cart_title']", $source);
            $this->assertStringContainsString("\$string['commerce_cart_add']", $source);
            $this->assertStringContainsString("\$string['commerce_cart_message_error']", $source);
        }
    }
}
