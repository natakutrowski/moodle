<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockConfigurationPresenter;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomRuntimeBlockSet;

/** @covers \local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockConfigurationPresenter */
final class commerce_showroom_a1_audience_profiles_798e83_test extends \advanced_testcase {
    public function test_runtime_supports_badge_icon_and_side_note_for_audience_profiles(): void {
        global $DB;
        $this->resetAfterTest(true);

        $repo = new CommerceShowroomCmsRepository($DB);
        $showroomid = $repo->save([
            'showroomkey' => 'a1-e83-runtime',
            'status' => 'draft',
            'name' => 'A1 E8.3 runtime',
            'template' => 'local_subscriptions/showroom/default',
            'slugfr' => '', 'slugen' => '', 'slugru' => '',
            'titlekey' => '', 'descriptionkey' => '',
            'productsjson' => '{}', 'settingsjson' => '{}',
        ], 2);

        $repo->save_block($showroomid, [
            'blocktype' => 'audience_profiles',
            'sortorder' => 10,
            'enabled' => true,
            'configjson' => json_encode([
                'eyebrow' => 'УЗНАЁШЬ СЕБЯ?',
                'badgeicon' => 'fa-solid fa-compass',
                'sidenote' => "Bienvenue\nà tous !♡",
                'title' => 'Кому',
                'titleaccent' => 'подойдёт',
                'titlesuffix' => 'этот курс',
                'text' => 'Подзаголовок',
                'items' => "Карточка 1|Текст 1|fa-solid fa-seedling
Карточка 2|Текст 2|fa-solid fa-rotate
Карточка 3|Текст 3|fa-solid fa-house
Карточка 4|Текст 4|fa-solid fa-suitcase-rolling",
            ], JSON_THROW_ON_ERROR),
        ], 2);

        $blocks = CommerceShowroomRuntimeBlockSet::load_preview($DB, $showroomid);
        $data = (new CommerceShowroomBlockConfigurationPresenter())->apply([], $blocks);

        self::assertSame('УЗНАЁШЬ СЕБЯ?', $data['audienceeyebrow']);
        self::assertSame('fa-solid fa-compass', $data['audiencebadgeicon']);
        self::assertSame("Bienvenue\nà tous !♡", $data['audiencesidenote']);
        self::assertSame('подойдёт', $data['audiencetitleaccent']);
        self::assertSame('этот курс', $data['audiencetitlesuffix']);
        self::assertTrue($data['hasaudiencesidenote']);
        self::assertCount(4, $data['audiencestats']);
        self::assertSame('fa-solid fa-seedling', $data['audiencestats'][0]['icon']);
    }

    public function test_public_markup_and_css_match_e83_contract(): void {
        global $CFG;

        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/showroom/default.mustache');
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');

        self::assertIsString($template);
        self::assertStringContainsString('commerce-showroom-audience-note', $template);
        self::assertStringContainsString('commerce-showroom-audience-profiles__badge', $template);
        self::assertStringContainsString('{{audiencebadgeicon}}', $template);
        self::assertStringContainsString('{{audiencesidenote}}', $template);
        self::assertStringContainsString('{{audiencetitlesuffix}}', $template);

        self::assertIsString($css);
        self::assertStringContainsString('CampusFR 7.98E.8.3', $css);
        self::assertStringContainsString('#showroom-audience-profiles .commerce-showroom-audience-note', $css);
        self::assertStringContainsString('white-space: pre-line;', $css);
        self::assertStringContainsString('#showroom-audience-profiles .commerce-showroom-audience-profiles__badge', $css);
        self::assertStringContainsString('grid-template-columns: repeat(2, minmax(0, 1fr));', $css);
        self::assertStringContainsString('#showroom-audience-profiles .commerce-showroom-stat:nth-child(4)', $css);
        self::assertStringContainsString('@media (prefers-reduced-motion: reduce)', $css);
    }
}
