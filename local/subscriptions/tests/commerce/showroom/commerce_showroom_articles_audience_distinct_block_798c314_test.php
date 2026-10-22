<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockConfigurationPresenter;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomRuntimeBlockSet;

final class commerce_showroom_articles_audience_distinct_block_798c314_test extends \advanced_testcase {
    public function test_runtime_keeps_content_highlights_and_audience_profiles_as_distinct_blocks(): void {
        $method = (new \ReflectionClass(CommerceShowroomRuntimeBlockSet::class))->getMethod('from_blocks');
        $method->setAccessible(true);

        $content = (object)[
            'id' => 101,
            'blocktype' => 'content_highlights',
            'enabled' => 1,
            'configjson' => json_encode(['presentation' => 'article_meaning', 'title' => 'Slide 3']),
        ];
        $audience = (object)[
            'id' => 107,
            'blocktype' => 'audience_profiles',
            'enabled' => 1,
            'configjson' => json_encode([
                'eyebrow' => 'POUR QUI ?',
                'title' => 'Ce mini-cours est',
                'titleaccent' => 'pour vous si…',
                'text' => 'Quatre profils.',
                'items' => "Débutant|Comprendre la logique.|fa-solid fa-1\nA2-B1|Fermer les lacunes.|fa-solid fa-4",
                'image1' => '/media/a1.webp',
            ]),
        ];

        /** @var CommerceShowroomRuntimeBlockSet $blocks */
        $blocks = $method->invoke(null, [$content, $audience]);

        self::assertSame(['content_highlights', 'audience_profiles'], $blocks->sequence());
        self::assertSame('Slide 3', $blocks->config('content_highlights')['title']);
        self::assertSame('Ce mini-cours est', $blocks->config('audience_profiles')['title']);

        $ordered = $blocks->to_template_data()['showroomorderedblocks'];
        self::assertCount(2, $ordered);
        self::assertTrue($ordered[0]['iscontenthighlights']);
        self::assertTrue($ordered[1]['isaudienceprofiles']);

        $data = (new CommerceShowroomBlockConfigurationPresenter())->apply([], $blocks);
        self::assertSame('Slide 3', $data['contenttitle']);
        self::assertSame('Ce mini-cours est', $data['audiencetitle']);
        self::assertCount(2, $data['audiencestats']);
        self::assertSame('/media/a1.webp', $data['audiencestats'][0]['imageurl']);
    }

    public function test_builder_and_template_expose_dedicated_audience_block(): void {
        global $CFG;

        $types = file_get_contents($CFG->dirroot . '/local/subscriptions/classes/commerce/showroom/cms/CommerceShowroomBlockTypeRegistry.php');
        $editor = file_get_contents($CFG->dirroot . '/local/subscriptions/classes/commerce/showroom/cms/CommerceShowroomBlockEditorRegistry.php');
        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/showroom/default.mustache');

        self::assertStringContainsString("'audience_profiles' => ['label' => 'Audience — 4 profils illustrés'", $types);
        self::assertStringContainsString("'audience_profiles' => self::definition([", $editor);
        self::assertStringContainsString('{{#isaudienceprofiles}}', $template);
        self::assertStringContainsString('id="showroom-audience-profiles"', $template);
    }
}
