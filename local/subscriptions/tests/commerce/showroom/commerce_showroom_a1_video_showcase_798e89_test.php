<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockConfigurationPresenter;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockEditorRegistry;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomRuntimeBlockSet;

/**
 * @covers \local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockConfigurationPresenter
 * @covers \local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockEditorRegistry
 */
final class commerce_showroom_a1_video_showcase_798e89_test extends \advanced_testcase {
    public function test_a1_showcase_fields_survive_normalisation(): void {
        $config = CommerceShowroomBlockEditorRegistry::normalise('video', [
            'presentation' => 'a1_showcase',
            'badgeicon' => 'fa-solid fa-circle-play',
            'posterurl' => '',
            'videourl' => '',
            'modal' => true,
            'translations' => [
                'ru' => [
                    'eyebrow' => 'CAMPUSFR В ДЕЙСТВИИ',
                    'title' => 'Загляни внутрь CampusFR',
                    'text' => 'Подзаголовок',
                    'checklisttitle' => 'В видео ты увидишь:',
                    'checklistitems' => "Первый пункт\nВторой пункт\nТретий пункт\nЧетвёртый пункт",
                    'posterlabel' => '',
                    'note' => 'Tout est plus clair en vidéo ! ♡',
                ],
                'fr' => [],
                'en' => [],
            ],
        ]);

        self::assertSame('a1_showcase', $config['presentation']);
        self::assertSame('fa-solid fa-circle-play', $config['badgeicon']);
        self::assertSame('В видео ты увидишь:', $config['translations']['ru']['checklisttitle']);
        self::assertStringContainsString('Четвёртый пункт', $config['translations']['ru']['checklistitems']);
        self::assertSame('Tout est plus clair en vidéo ! ♡', $config['translations']['ru']['note']);
    }

    public function test_runtime_builds_a1_showcase_with_empty_video_slot(): void {
        global $DB;
        $this->resetAfterTest(true);

        $repo = new CommerceShowroomCmsRepository($DB);
        $showroomid = $repo->save([
            'showroomkey' => 'a1-e89-runtime',
            'status' => 'draft',
            'name' => 'A1 E8.9 runtime',
            'template' => 'local_subscriptions/showroom/default',
            'slugfr' => '',
            'slugen' => '',
            'slugru' => '',
            'titlekey' => '',
            'descriptionkey' => '',
            'productsjson' => '{}',
            'settingsjson' => '{}',
        ], 2);

        $repo->save_block($showroomid, [
            'blocktype' => 'video',
            'sortorder' => 10,
            'enabled' => true,
            'configjson' => json_encode([
                'presentation' => 'a1_showcase',
                'eyebrow' => 'CAMPUSFR В ДЕЙСТВИИ',
                'badgeicon' => 'fa-solid fa-circle-play',
                'title' => 'Загляни внутрь CampusFR',
                'text' => 'Подзаголовок',
                'checklisttitle' => 'В видео ты увидишь:',
                'checklistitems' => "Как выглядит первое приключение в Ницце\nКакие интересные задания ждут тебя внутри\nКак работают проверка ответов и обратная связь от ИИ\nКакие сюрпризы приготовил Гюстав",
                'posterlabel' => '',
                'note' => 'Tout est plus clair en vidéo ! ♡',
                'posterurl' => '',
                'videourl' => '',
                'modal' => true,
            ], JSON_THROW_ON_ERROR),
        ], 2);

        $blocks = CommerceShowroomRuntimeBlockSet::load_preview($DB, $showroomid);
        $data = (new CommerceShowroomBlockConfigurationPresenter())->apply([], $blocks);

        self::assertSame('a1_showcase', $data['videopresentation']);
        self::assertTrue($data['videoisa1showcase']);
        self::assertFalse($data['videoisinvestigation']);
        self::assertSame('fa-solid fa-circle-play', $data['videobadgeicon']);
        self::assertSame('В видео ты увидишь:', $data['videochecklisttitle']);
        self::assertCount(4, $data['videochecklistitems']);
        self::assertSame('Tout est plus clair en vidéo ! ♡', $data['videonote']);
        self::assertFalse($data['hasvideourl']);
        self::assertFalse($data['hasvideoposter']);
    }

    public function test_e89_keeps_previous_review_layers_and_exposes_video_layout(): void {
        global $CFG;

        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/showroom/default.mustache');
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');

        self::assertIsString($template);
        self::assertStringContainsString('{{#videoisa1showcase}}', $template);
        self::assertStringContainsString('commerce-showroom-video-a1__badge', $template);
        self::assertStringContainsString('commerce-showroom-video-a1__media', $template);
        self::assertStringContainsString('commerce-showroom-video-a1__checklist', $template);
        self::assertStringContainsString('{{videonote}}', $template);

        self::assertIsString($css);
        self::assertStringContainsString('CampusFR 7.98E.8.7.4', $css);
        self::assertStringContainsString('CampusFR 7.98E.8.8.1', $css);
        self::assertStringContainsString('CampusFR 7.98E.8.9', $css);
        self::assertStringContainsString('#showroom-video .commerce-showroom-video-a1__layout', $css);
        self::assertStringContainsString('#showroom-video .commerce-showroom-video-a1__checklist', $css);
    }
}
