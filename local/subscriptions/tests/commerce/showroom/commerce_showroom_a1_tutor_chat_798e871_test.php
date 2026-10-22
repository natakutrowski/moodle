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
final class commerce_showroom_a1_tutor_chat_798e871_test extends \advanced_testcase {
    public function test_tutor_json_fields_survive_normalisation(): void {
        $config = CommerceShowroomBlockEditorRegistry::normalise('tutor_chat', [
            'imageurl' => '',
            'buttontarget' => '#showroom-support',
            'translations' => [
                'ru' => [
                    'eyebrow' => 'ПРАКТИКА С НОСИТЕЛЕМ',
                    'title' => 'В твоём клане есть',
                    'titleaccent' => 'свой француз!',
                    'text' => 'Подзаголовок',
                    'features' => "Живое общение|Текст|fa-regular fa-comment\nФранция изнутри|Текст|fa-solid fa-camera",
                    'conversationtitle' => 'Notre clan 💙',
                    'messages' => "|Salut !|tutor|10:24\n|Waouh !|student|10:26",
                    'sidenote' => 'On parle de tout !',
                    'buttonlabel' => 'Познакомиться с тьютором',
                ],
                'fr' => [],
                'en' => [],
            ],
        ]);

        self::assertSame('Notre clan 💙', $config['translations']['ru']['conversationtitle']);
        self::assertStringContainsString('Живое общение', $config['translations']['ru']['features']);
        self::assertStringContainsString('|10:24', $config['translations']['ru']['messages']);
        self::assertSame('On parle de tout !', $config['translations']['ru']['sidenote']);
        self::assertSame('#showroom-support', $config['buttontarget']);
    }

    public function test_runtime_supports_header_avatar_sidenote_and_authorless_timed_messages(): void {
        global $DB;
        $this->resetAfterTest(true);

        $repo = new CommerceShowroomCmsRepository($DB);
        $showroomid = $repo->save([
            'showroomkey' => 'a1-e871-runtime',
            'status' => 'draft',
            'name' => 'A1 E8.7.1 runtime',
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
            'blocktype' => 'tutor_chat',
            'sortorder' => 10,
            'enabled' => true,
            'configjson' => json_encode([
                'eyebrow' => 'ПРАКТИКА С НОСИТЕЛЕМ',
                'title' => 'В твоём клане есть',
                'titleaccent' => 'свой француз!',
                'text' => 'Подзаголовок',
                'imageurl' => 'https://example.test/tutor.jpg',
                'features' => "Живое общение|Текст|fa-regular fa-comment\nФранция изнутри|Текст|fa-solid fa-camera\nПрактика без стресса|Текст|fa-regular fa-star\nПоддержка каждый день|Текст|fa-regular fa-heart",
                'conversationtitle' => 'Notre clan 💙',
                'messages' => "|Salut !|tutor|10:24\n|Waouh !|student|10:26\n|Super !|tutor|10:31",
                'sidenote' => 'On parle de tout !',
                'buttonlabel' => 'Познакомиться с тьютором',
                'buttontarget' => '#showroom-support',
            ], JSON_THROW_ON_ERROR),
        ], 2);

        $blocks = CommerceShowroomRuntimeBlockSet::load_preview($DB, $showroomid);
        $data = (new CommerceShowroomBlockConfigurationPresenter())->apply([], $blocks);

        self::assertSame('Notre clan 💙', $data['tutorconversationtitle']);
        self::assertTrue($data['hastutorconversationtitle']);
        self::assertSame('On parle de tout !', $data['tutorsidenote']);
        self::assertTrue($data['hastutorsidenote']);
        self::assertTrue($data['hastutorimage']);
        self::assertCount(4, $data['tutorfeatures']);
        self::assertCount(3, $data['tutormessages']);
        self::assertFalse($data['tutormessages'][0]['hasauthor']);
        self::assertSame('10:24', $data['tutormessages'][0]['time']);
        self::assertTrue($data['tutormessages'][0]['hastime']);
        self::assertSame('#showroom-support', $data['tutorbuttontarget']);
    }

    public function test_e871_keeps_previous_a1_review_layers_and_adds_tutor_layer(): void {
        global $CFG;

        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/showroom/block_tutor_chat.mustache');
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');

        self::assertIsString($template);
        self::assertStringContainsString('commerce-showroom-tutor-chat__conversation-header', $template);
        self::assertStringContainsString('commerce-showroom-tutor-chat__avatar', $template);
        self::assertStringContainsString('{{tutorimageurl}}', $template);
        self::assertStringContainsString('{{tutorsidenote}}', $template);
        self::assertStringContainsString('commerce-showroom-tutor-chat__time', $template);

        self::assertIsString($css);
        self::assertStringContainsString('CampusFR 7.98E.8.3', $css);
        self::assertStringContainsString('CampusFR 7.98E.8.4', $css);
        self::assertStringContainsString('CampusFR 7.98E.8.4.4', $css);
        self::assertStringContainsString('CampusFR 7.98E.8.5', $css);
        self::assertStringContainsString('CampusFR 7.98E.8.5.1', $css);
        self::assertStringContainsString('CampusFR 7.98E.8.6.1', $css);
        self::assertStringContainsString('CampusFR 7.98E.8.7.1', $css);
    }
}
