<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomRuntimeBlockSet;

final class commerce_showroom_articles_hero_cleanup_798c31_test extends \advanced_testcase {
    public function test_modern_hero_stats_do_not_resurrect_legacy_sections(): void {
        global $DB;
        $this->resetAfterTest(true);

        $repository = new CommerceShowroomCmsRepository($DB);
        $showroomid = $repository->save([
            'showroomkey' => 'articles-c31',
            'status' => 'draft',
            'name' => 'Articles C31',
            'template' => 'local_subscriptions/showroom/default',
            'titlekey' => '',
            'descriptionkey' => '',
            'productsjson' => '{}',
            'settingsjson' => '{}',
        ], 2);

        $repository->save_block($showroomid, [
            'blocktype' => 'hero',
            'blockkey' => 'hero',
            'sortorder' => 10,
            'enabled' => true,
            'configjson' => '{}',
        ], 2);
        $repository->save_block($showroomid, [
            'blocktype' => 'stats',
            'blockkey' => 'hero-stats',
            'sortorder' => 20,
            'enabled' => true,
            'configjson' => '{}',
        ], 2);

        $runtime = CommerceShowroomRuntimeBlockSet::load_preview($DB, $showroomid);
        $data = $runtime->to_template_data();

        self::assertTrue($data['showblockhero']);
        self::assertTrue($data['showblockstats']);
        self::assertFalse($data['showblockcontenthighlights']);
        self::assertFalse($data['showblockstagemethod']);
        self::assertFalse($data['showblockexerciseexplorer']);
        self::assertCount(1, $data['showroomorderedblocks']);
        self::assertTrue($data['showroomorderedblocks'][0]['ishero']);
    }

    public function test_generic_cover_hides_historical_devices_and_builder_menu_can_escape_card(): void {
        global $CFG;

        $showroomcss = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');
        $buildercss = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom_builder.css');

        self::assertIsString($showroomcss);
        self::assertIsString($buildercss);
        self::assertStringContainsString(
            '.commerce-showroom--generic .commerce-showroom-hero--cover .commerce-showroom-hero__visual',
            $showroomcss
        );
        self::assertStringContainsString('width: min(1380px, calc(100% - 3rem));', $showroomcss);
        self::assertStringContainsString('.commerce-showroom-block:has(.commerce-showroom-block__more[open])', $buildercss);
        self::assertStringContainsString('z-index: 230;', $buildercss);

        $c32marker = '/* 7.98C.3.2 — dark cinematic generic cover Hero; scoped away from Verbs expedition profile. */';
        $c32start = strpos($showroomcss, $c32marker);
        self::assertNotFalse($c32start);
        $c32css = substr($showroomcss, $c32start);
        self::assertStringContainsString(
            '.commerce-showroom--generic .commerce-showroom-hero--cover.has-cms-background::before',
            $c32css
        );
        self::assertStringContainsString('rgba(8, 12, 31, .64)', $c32css);
        self::assertStringContainsString('background: transparent;', $c32css);
        self::assertStringContainsString('background: rgba(17, 12, 39, .68);', $c32css);
        self::assertStringContainsString('min-height: 68px;', $c32css);
        self::assertStringNotContainsString('.commerce-showroom--expedition', $c32css);
    }
}
