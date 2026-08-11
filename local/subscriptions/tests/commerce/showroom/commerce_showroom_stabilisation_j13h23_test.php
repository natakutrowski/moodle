<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_stabilisation_j13h23_test extends \advanced_testcase {
    public function test_builder_uses_autonomous_crm_shell(): void {
        global $CFG;
        $source = file_get_contents($CFG->dirroot . '/local/subscriptions/admin/commerce/showrooms/edit.php');
        self::assertIsString($source);
        self::assertStringContainsString('CrmPageConfigurator::configure(', $source);
        self::assertStringContainsString('CrmWorkspaceRenderer::start(CrmNavigationKeys::SHOWROOMS', $source);
        self::assertStringContainsString('CrmWorkspaceRenderer::end()', $source);
        self::assertStringNotContainsString("set_pagelayout('admin')", $source);
    }

    public function test_currency_options_include_symbols(): void {
        global $CFG;
        $page = file_get_contents($CFG->dirroot . '/local/subscriptions/showroom.php');
        self::assertStringContainsString('CommerceCurrencyLabelFormatter::format($candidate)', $page);
        self::assertStringContainsString("'label' => CommerceCurrencyLabelFormatter::format(\$candidate)", $page);


    }

    public function test_offers_are_bounded_and_compact(): void {
        global $CFG;
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');
        self::assertIsString($css);
        self::assertStringContainsString('max-width: 74rem;', $css);
        self::assertStringContainsString('minmax(0, 21.5rem)', $css);
        self::assertStringContainsString('aspect-ratio: 16 / 7;', $css);
    }
}
