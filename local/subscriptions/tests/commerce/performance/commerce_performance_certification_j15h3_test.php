<?php

namespace local_subscriptions\commerce\performance;

defined('MOODLE_INTERNAL') || die();

/**
 * Static certification checks for the J15H.3 performance pass.
 *
 * @coversNothing
 */
final class commerce_performance_certification_j15h3_test extends \advanced_testcase {
    public function test_showroom_prioritises_one_lcp_image(): void {
        global $CFG;

        $page = file_get_contents($CFG->dirroot . '/local/subscriptions/showroom.php');
        $offer = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/showroom/offer.mustache'
        );

        $this->assertStringContainsString("'fetchpriority' => 'high'", $page);
        $this->assertStringContainsString("'imagesrcset'", $page);
        $this->assertStringContainsString('loading="eager" fetchpriority="high"', $offer);
        $this->assertStringContainsString('loading="lazy" fetchpriority="low"', $offer);
    }

    public function test_storefront_cover_has_valid_optional_srcset_and_priority(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/storefront/product_card.mustache'
        );

        $this->assertStringContainsString('{{#coverresponsive}} srcset=', $template);
        $this->assertStringContainsString('{{/coverresponsive}} width=', $template);
        $this->assertStringContainsString('{{#featured}}loading="eager"', $template);
        $this->assertStringContainsString('{{^featured}}loading="lazy"', $template);
    }

    public function test_video_and_below_fold_content_are_deferred(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/showroom/third_group_verbs.mustache'
        );
        $styles = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/showroom.css'
        );

        $this->assertStringNotContainsString('preload="auto"', $template);
        $this->assertStringContainsString('controls preload="none"', $template);
        $this->assertStringContainsString('@supports (content-visibility: auto)', $styles);
        $this->assertStringContainsString('contain-intrinsic-size: auto 900px', $styles);
    }

    public function test_responsive_resolution_is_memoized_and_duplicate_css_removed(): void {
        global $CFG;

        $service = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/catalog/assets/'
            . 'CommerceCatalogResponsiveImageService.php'
        );
        $styles = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/showroom.css'
        );

        $this->assertStringContainsString('private static array $requestcache', $service);
        $this->assertStringContainsString('array_key_exists($cachekey, self::$requestcache)', $service);
        $this->assertSame(
            1,
            substr_count($styles, 'J13F4 — reassurance, support and conversion.')
        );
    }
}
