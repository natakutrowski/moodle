<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/** J6.6C1 corrective Trial presentation contract. */
final class commerce_trial_storefront_product_j66c1_test
        extends \advanced_testcase {

    public function test_non_promoted_trial_price_uses_initial_price_label(): void {
        global $CFG;
        $price = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/storefront/product_price.mustache');
        $this->assertStringContainsString('commerce_storefront_price_discovery', $price);
        $this->assertStringContainsString('trialformatted', $price);
        $this->assertStringContainsString('trialbaseformatted', $price);
        $this->assertStringContainsString(
            'commerce-storefront-price--trial',
            $price
        );
    }

    public function test_french_countdown_copy_is_scope_specific(): void {
        global $CFG;
        $lang = file_get_contents($CFG->dirroot . '/local/campus/lang/fr/local_campus.php');
        $this->assertStringContainsString('cours inclus dans votre offre d’essai', $lang);
        $this->assertStringContainsString('Réduction disponible encore', $lang);
    }
}
