<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/** J6.6B1 Boutique Trial banner contract. */
final class commerce_trial_storefront_banner_j66b1_test
        extends \advanced_testcase {

    public function test_boutique_renders_trial_banner_without_cta(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/digital_catalog.php'
        );

        $this->assertIsString($source);
        $this->assertStringContainsString(
            "local_campus_render_trial_discount_banner(false);",
            $source
        );
        $this->assertStringContainsString(
            "function_exists('local_campus_render_trial_discount_banner')",
            $source
        );
    }

    public function test_french_copy_limits_discount_to_trial_courses(): void {
        global $CFG;
        $lang = file_get_contents($CFG->dirroot . '/local/campus/lang/fr/local_campus.php');
        $this->assertStringContainsString('cours inclus dans votre offre d’essai', $lang);
        $this->assertStringContainsString('Réduction disponible encore', $lang);
        $this->assertStringNotContainsString('sur tous les achats de cours', $lang);
    }
}
