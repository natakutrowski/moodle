<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

/**
 * 7.98C.4.18 — Articles/Alf article-meaning card alignment.
 *
 * @coversNothing
 */
final class commerce_showroom_articles_learning_cards_centered_798c418_test extends \advanced_testcase {
    public function test_article_meaning_cards_center_copy_without_centering_side_checklist(): void {
        global $CFG;

        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');

        $this->assertIsString($css);
        $this->assertStringContainsString(
            '7.98C.4.18 — Articles/Alf: center the pedagogical copy inside the three article-meaning cards.',
            $css
        );
        $this->assertStringContainsString(
            '.commerce-showroom-content-highlights--article_meaning .commerce-showroom-content-highlights__learning-card h3,',
            $css
        );
        $this->assertStringContainsString(
            '.commerce-showroom-content-highlights--article_meaning .commerce-showroom-content-highlights__learning-meaning {',
            $css
        );
        $this->assertStringContainsString('text-align: center;', $css);
        $this->assertStringContainsString(
            '.commerce-showroom-content-highlights--article_meaning .commerce-showroom-content-highlights__learning-side-icon {',
            $css
        );
        $this->assertStringContainsString(
            '.commerce-showroom-content-highlights--article_meaning .commerce-showroom-content-highlights__learning-side > h3 {',
            $css
        );

        // The checklist itself must stay left-aligned/readable: do not introduce a side-items centering rule.
        $this->assertStringNotContainsString(
            '.commerce-showroom-content-highlights--article_meaning .commerce-showroom-content-highlights__learning-side-items {\n    text-align: center;',
            $css
        );
    }
}
