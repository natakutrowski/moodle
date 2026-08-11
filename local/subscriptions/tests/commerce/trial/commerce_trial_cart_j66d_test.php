<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/** J6.6D cart Trial hierarchy and clear redirect contract. */
final class commerce_trial_cart_j66d_test extends \advanced_testcase {

    public function test_cart_presenter_exposes_all_trial_price_stages(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/cart/presentation/'
            . 'CommerceCartPresenter.php'
        );

        $this->assertIsString($source);
        $this->assertStringContainsString(
            'hasproductpromotionbeforetrial',
            $source
        );
        $this->assertStringContainsString(
            'trialinitialpriceformatted',
            $source
        );
        $this->assertStringContainsString(
            'trialpromotedpriceformatted',
            $source
        );
        $this->assertStringContainsString(
            'trialfinalpriceformatted',
            $source
        );
    }

    public function test_cart_template_uses_one_ordered_trial_price_block(): void {
        global $CFG;
        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/cart/price.mustache');
        $this->assertSame(1, substr_count($template, 'commerce-cart-price--trial'));
        $this->assertStringContainsString('cartpricetrialbadge', $template);
        $this->assertStringContainsString('cartpricetrialdiscountbadge', $template);
        $this->assertStringContainsString('cartpricefinalformatted', $template);
    }

    public function test_successful_clear_redirects_to_storefront(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/cart_action.php'
        );

        $this->assertIsString($source);
        $this->assertStringContainsString(
            "\$action === 'clear' && \$result->has_changed()",
            $source
        );
        $this->assertStringContainsString(
            'UrlFactory::digital_catalog',
            $source
        );
    }
}
