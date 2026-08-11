<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_production_ready_j13f5_test extends \advanced_testcase {
    public function test_showroom_exposes_production_seo_and_tracking(): void {
        global $CFG;

        $root = $CFG->dirroot . '/local/subscriptions';
        $seo = file_get_contents(
            $root . '/classes/commerce/showroom/CommerceShowroomSeoService.php'
        );
        $controller = file_get_contents($root . '/showroom.php');
        $template = file_get_contents(
            $root . '/templates/showroom/third_group_verbs.mustache'
        );
        $javascript = file_get_contents($root . '/amd/src/showroom.js');
        $css = file_get_contents($root . '/styles/showroom.css');

        self::assertIsString($seo);
        self::assertIsString($controller);
        self::assertIsString($template);
        self::assertIsString($javascript);
        self::assertIsString($css);

        self::assertStringContainsString('hreflang', $seo);
        self::assertStringContainsString('application/ld+json', $seo);
        self::assertStringContainsString('twitter:card', $seo);
        self::assertStringContainsString(
            '$seoservice->present($definition, $offers)',
            $controller
        );
        self::assertStringContainsString(
            'data-showroom-production-ready',
            $template
        );
        self::assertStringContainsString(
            'data-showroom-track="hero_cta"',
            $template
        );
        self::assertStringContainsString('campusfr:showroom', $javascript);
        self::assertStringContainsString('window.dataLayer', $javascript);
        self::assertStringContainsString('@media print', $css);
        self::assertStringContainsString(':focus-visible', $css);
    }
}
