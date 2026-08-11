<?php

declare(strict_types=1);

namespace block_edly_banner_campus;

final class landing_navigation_j12c_test extends \advanced_testcase {
    public function test_navigation_wrappers_are_closed_before_hero_content(): void {
        $root = dirname(__DIR__);
        $source = file_get_contents($root . '/block_edly_banner_campus.php');

        $close = strpos($source, '</div> <!-- .campus-hero-nav -->');
        $content = strpos($source, '// === CONTENU PRINCIPAL CENTRÉ ===');

        self::assertNotFalse($close);
        self::assertNotFalse($content);
        self::assertLessThan($content, $close);
    }
}
