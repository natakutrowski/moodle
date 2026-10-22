<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockConfigurationPresenter;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomRuntimeBlockSet;

/** @covers \local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockConfigurationPresenter */
final class commerce_showroom_a1_lavender_798e81_test extends \advanced_testcase {
    public function test_a1_lavender_hero_keeps_stats_and_does_not_change_legacy_markup(): void {
        global $CFG, $DB;
        $this->resetAfterTest(true);

        $repo = new CommerceShowroomCmsRepository($DB);
        $showroomid = $repo->save([
            'showroomkey' => 'a1-course',
            'status' => 'draft',
            'name' => 'A1 Hero E8.1 test',
            'template' => 'local_subscriptions/showroom/default',
            'slugfr' => '', 'slugen' => '', 'slugru' => '',
            'titlekey' => '', 'descriptionkey' => '',
            'productsjson' => '{}', 'settingsjson' => '{}',
        ], 2);
        $repo->save_block($showroomid, [
            'blocktype' => 'hero', 'blockkey' => 'hero', 'sortorder' => 10,
            'enabled' => true,
            'configjson' => json_encode([
                'herovariant' => 'a1_lavender',
                'title' => 'Французский с нуля —',
                'titleaccent' => 'путешествие',
                'titlesuffix' => 'по Провансу, Лазурному Берегу и Корсике',
                'startlabel' => 'Старт курса — 2 ноября 2027 года',
                'expeditionlabel' => 'КУРС А1',
                'stagelabel' => 'БОЛЬШОЕ ПУТЕШЕСТВИЕ ПО ЮГУ ФРАНЦИИ',
            ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        ], 2);
        $repo->save_block($showroomid, [
            'blocktype' => 'stats', 'blockkey' => 'stats', 'sortorder' => 20,
            'enabled' => true,
            'configjson' => json_encode([
                'items' => "30 остановок|2 урока каждую неделю|fa-solid fa-location-dot\n"
                    . "Новые друзья|3,5 месяца общения|fa-solid fa-users\n"
                    . "База языка|Фонетика|fa-solid fa-book-open\n"
                    . "Поддержка|Преподаватель|fa-solid fa-comments\n"
                    . "Практика|Интерактивные задания|fa-solid fa-list-check",
            ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        ], 2);

        $blocks = CommerceShowroomRuntimeBlockSet::load_preview($DB, $showroomid);
        $data = (new CommerceShowroomBlockConfigurationPresenter())->apply([
            'showroomkey' => 'a1-course',
        ], $blocks);
        self::assertTrue($data['heroislavendera1']);
        self::assertSame('по Провансу, Лазурному Берегу и Корсике', $data['herotitlesuffix']);
        self::assertTrue($data['hasherotitlesuffix']);
        self::assertSame('Старт курса — 2 ноября 2027 года', $data['herostartlabel']);
        self::assertSame('a1_lavender', $data['herovariant']);
        self::assertTrue($data['hasheromobileimage']);
        self::assertStringContainsString('a1_hero_mobile_radio.webp', $data['heromobileimageurl']);
        self::assertArrayNotHasKey('heromobilebackgroundurl', $data);
        self::assertCount(5, $data['heroa1statitems']);
        self::assertSame('fa-solid fa-book-open', $data['heroa1statitems'][2]['icon']);
        self::assertStringContainsString(
            '/local/subscriptions/pix/showroom/a1_hero_lavender.webp', $data['herobackgroundurl']
        );
        self::assertFileExists($CFG->dirroot . '/local/subscriptions/pix/showroom/a1_hero_lavender.webp');

        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/showroom/default.mustache');
        self::assertIsString($template);
        self::assertStringContainsString('{{#heroislavendera1}}', $template);
        self::assertStringContainsString('{{#hasherotitlesuffix}}', $template);
        self::assertStringContainsString('has-a1-tablet', $template);
        self::assertStringContainsString('{{^heroislavendera1}}', $template);
        self::assertStringContainsString('<div role="listitem"><strong {{#statsanimate}}data-showroom-counter', $template);
        self::assertStringNotContainsString('herostat1icon', $template);
    }

    public function test_legacy_hero_does_not_activate_a1_variant(): void {
        global $DB;
        $this->resetAfterTest(true);
        $repo = new CommerceShowroomCmsRepository($DB);
        $showroomid = $repo->save([
            'showroomkey' => 'articles', 'status' => 'draft', 'name' => 'Articles',
            'template' => 'local_subscriptions/showroom/default',
            'slugfr' => '', 'slugen' => '', 'slugru' => '',
            'titlekey' => '', 'descriptionkey' => '',
            'productsjson' => '{}', 'settingsjson' => '{}',
        ], 2);
        $repo->save_block($showroomid, [
            'blocktype' => 'hero', 'blockkey' => 'hero', 'sortorder' => 10,
            'enabled' => true,
            'configjson' => json_encode(['herovariant' => 'cover']),
        ], 2);
        $data = (new CommerceShowroomBlockConfigurationPresenter())->apply(
            ['showroomkey' => 'articles'],
            CommerceShowroomRuntimeBlockSet::load_preview($DB, $showroomid)
        );
        self::assertFalse($data['heroislavendera1']);
        self::assertSame('cover', $data['herovariant']);
        self::assertSame([], $data['heroa1statitems']);
    }
}
