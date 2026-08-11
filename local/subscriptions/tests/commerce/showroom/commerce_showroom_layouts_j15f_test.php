<?php

declare(strict_types=1);

namespace local_subscriptions;

/** @coversNothing */
final class commerce_showroom_layouts_j15f_test extends \advanced_testcase {
    public function test_layout_controls_are_shared_and_rendered(): void {
        $registry = file_get_contents(__DIR__ . '/../../../classes/commerce/showroom/cms/CommerceShowroomBlockEditorRegistry.php');
        $presenter = file_get_contents(__DIR__ . '/../../../classes/commerce/showroom/cms/CommerceShowroomBlockConfigurationPresenter.php');
        $template = file_get_contents(__DIR__ . '/../../../templates/showroom/third_group_verbs.mustache');
        self::assertStringContainsString("'sectionwidth'", $registry);
        self::assertStringContainsString("'sectionbackground'", $registry);
        self::assertStringContainsString('apply_layout', $presenter);
        self::assertStringContainsString('{{herolayoutclass}}', $template);
        self::assertStringContainsString('{{offerscardvariant}}', $template);
    }

    public function test_currency_formatter_uses_flags_and_parentheses(): void {
        $formatter = file_get_contents(__DIR__ . '/../../../classes/currency/CommerceCurrencyLabelFormatter.php');
        self::assertStringContainsString("'EUR' => '🇪🇺'", $formatter);
        self::assertStringContainsString("\$code . ' (' . \$symbol . ')'", $formatter);
    }
}
