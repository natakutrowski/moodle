<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockConfigurationPresenter;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomRuntimeBlockSet;

/** @covers \local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockConfigurationPresenter */
final class commerce_showroom_a1_everyday_french_798e85_test extends \advanced_testcase {
    public function test_content_highlights_supports_badge_icon_and_optional_examples_without_numbers(): void {
        global $DB;
        $this->resetAfterTest(true);

        $repo = new CommerceShowroomCmsRepository($DB);
        $showroomid = $repo->save([
            'showroomkey' => 'a1-e85-runtime',
            'status' => 'draft',
            'name' => 'A1 E8.5 runtime',
            'template' => 'local_subscriptions/showroom/default',
            'slugfr' => '', 'slugen' => '', 'slugru' => '',
            'titlekey' => '', 'descriptionkey' => '',
            'productsjson' => '{}', 'settingsjson' => '{}',
        ], 2);

        $repo->save_block($showroomid, [
            'blocktype' => 'content_highlights',
            'sortorder' => 10,
            'enabled' => true,
            'configjson' => json_encode([
                'presentation' => 'standard',
                'eyebrow' => 'ФРАНЦУЗСКИЙ НА КАЖДЫЙ ДЕНЬ',
                'badgeicon' => 'fa-solid fa-comments',
                'sidenote' => 'Bon voyage!',
                'title' => 'Французский, который пригодится',
                'titleaccent' => 'в жизни',
                'text' => 'Подзаголовок',
                'columns' => '3',
                'items' => "Знакомиться|Описание|fa-solid fa-user-group|Moi aussi, j'adore voyager !\nЖильё|Описание|fa-solid fa-house|L'appartement est toujours disponible ?",
            ], JSON_THROW_ON_ERROR),
        ], 2);

        $blocks = CommerceShowroomRuntimeBlockSet::load_preview($DB, $showroomid);
        $data = (new CommerceShowroomBlockConfigurationPresenter())->apply([], $blocks);

        self::assertSame('ФРАНЦУЗСКИЙ НА КАЖДЫЙ ДЕНЬ', $data['contenteyebrow']);
        self::assertSame('fa-solid fa-comments', $data['contentbadgeicon']);
        self::assertTrue($data['hascontentbadgeicon']);
        self::assertSame('Bon voyage!', $data['contentsidenote']);
        self::assertTrue($data['hascontentsidenote']);
        self::assertSame('3', $data['contenthighlightcolumns']);
        self::assertCount(2, $data['contentstats']);
        self::assertSame("Moi aussi, j'adore voyager !", $data['contentstats'][0]['example']);
        self::assertTrue($data['contentstats'][0]['hasexample']);
        self::assertArrayNotHasKey('number', $data['contentstats'][0]);
    }

    public function test_public_markup_and_css_match_e85_contract(): void {
        global $CFG;

        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/showroom/default.mustache');
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');
        $editor = file_get_contents($CFG->dirroot . '/local/subscriptions/classes/commerce/showroom/cms/CommerceShowroomBlockEditorRegistry.php');

        self::assertIsString($template);
        self::assertStringContainsString('commerce-showroom-content-highlights__badge--standard', $template);
        self::assertStringContainsString('{{contentbadgeicon}}', $template);
        self::assertStringContainsString('commerce-showroom-content-highlights__example', $template);
        self::assertStringContainsString('commerce-showroom-content-highlights__sidenote', $template);
        self::assertStringContainsString('{{contentsidenote}}', $template);

        self::assertIsString($editor);
        self::assertStringContainsString("self::text('badgeicon', 'Icône du sur-titre (classe Font Awesome)')", $editor);
        self::assertStringContainsString("self::text('sidenote', 'Note manuscrite en haut à droite')", $editor);
        self::assertStringContainsString('exemple facultatif', $editor);

        self::assertIsString($css);
        self::assertStringContainsString('CampusFR 7.98E.8.5', $css);
        self::assertStringContainsString('#showroom-highlights .commerce-showroom-content-highlights__badge--standard', $css);
        self::assertStringContainsString('grid-template-columns: repeat(3, minmax(0, 1fr));', $css);
        self::assertStringContainsString('#showroom-highlights .commerce-showroom-content-highlights__example', $css);
        self::assertStringContainsString('transform: translateY(-5px)', $css);
        self::assertStringContainsString('CampusFR 7.98E.8.5.1', $css);
        self::assertStringContainsString('#showroom-highlights .commerce-showroom-content-highlights__sidenote', $css);
        self::assertStringContainsString('font-family: "Caveat", cursive;', $css);
    }
}
