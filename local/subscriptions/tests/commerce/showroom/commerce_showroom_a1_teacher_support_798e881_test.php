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
final class commerce_showroom_a1_teacher_support_798e881_test extends \advanced_testcase {
    public function test_teacher_help_fields_survive_normalisation_and_runtime(): void {
        global $DB;

        $config = CommerceShowroomBlockEditorRegistry::normalise('support', [
            'presentation' => 'teacher_help',
            'translations' => [
                'ru' => [
                    'eyebrow' => 'ЕСТЬ У КОГО СПРОСИТЬ',
                    'note' => 'Pas a pas !♡',
                    'title' => 'Преподаватель CampusFR —',
                    'titleaccent' => 'рядом, когда нужно',
                    'text' => 'Подзаголовок',
                    'items' => "Вопросы по грамматике|Если правило не укладывается в голове, преподаватель поможет разобраться.|fa-solid fa-book-open\nПомощь с заданиями|Понятно объяснит, что и как делать в упражнениях.|fa-regular fa-clipboard\nОтветы по курсу|Подскажет по структуре курса, навигации и материалам.|fa-regular fa-comment\nТы не застрянешь|Всегда есть к кому обратиться, чтобы спокойно идти дальше.|fa-solid fa-users",
                    'conversationtitle' => 'Преподаватель CampusFR',
                    'messages' => "|Почему «я скучаю по тебе» — это tu me manques, а не je te manque? 🤔|tutor|10:24\n|Потому что французы буквально говорят: «Ты мне недостаёшь» ❤️ Tu me manques — я по тебе скучаю. Je te manque — ты по мне скучаешь.|student|10:26\n|Ого! Получается, всё наоборот! 😂|tutor|10:27",
                    'sidenote' => 'Une question ? Toujours une réponse !♡',
                ],
                'fr' => [],
                'en' => [],
            ],
        ]);

        self::assertSame('teacher_help', $config['presentation']);
        self::assertSame('Pas a pas !♡', $config['translations']['ru']['note']);
        self::assertSame('рядом, когда нужно', $config['translations']['ru']['titleaccent']);
        self::assertStringContainsString('|10:26', $config['translations']['ru']['messages']);

        $this->resetAfterTest(true);
        $repo = new CommerceShowroomCmsRepository($DB);
        $showroomid = $repo->save([
            'showroomkey' => 'a1-e881-runtime',
            'status' => 'draft',
            'name' => 'A1 E8.8.1 runtime',
            'template' => 'local_subscriptions/showroom/default',
            'slugfr' => '', 'slugen' => '', 'slugru' => '',
            'titlekey' => '', 'descriptionkey' => '',
            'productsjson' => '{}', 'settingsjson' => '{}',
        ], 2);
        $repo->save_block($showroomid, [
            'blocktype' => 'support',
            'sortorder' => 10,
            'enabled' => true,
            'configjson' => json_encode([
                'presentation' => 'teacher_help',
                'eyebrow' => 'ЕСТЬ У КОГО СПРОСИТЬ',
                'note' => 'Pas a pas !♡',
                'title' => 'Преподаватель CampusFR —',
                'titleaccent' => 'рядом, когда нужно',
                'text' => 'Подзаголовок',
                'items' => "Вопросы по грамматике|Если правило не укладывается в голове, преподаватель поможет разобраться.|fa-solid fa-book-open\nПомощь с заданиями|Понятно объяснит, что и как делать в упражнениях.|fa-regular fa-clipboard\nОтветы по курсу|Подскажет по структуре курса, навигации и материалам.|fa-regular fa-comment\nТы не застрянешь|Всегда есть к кому обратиться, чтобы спокойно идти дальше.|fa-solid fa-users",
                'conversationtitle' => 'Преподаватель CampusFR',
                'messages' => "|Почему «я скучаю по тебе» — это tu me manques, а не je te manque? 🤔|tutor|10:24\n|Потому что французы буквально говорят: «Ты мне недостаёшь» ❤️ Tu me manques — я по тебе скучаю. Je te manque — ты по мне скучаешь.|student|10:26\n|Ого! Получается, всё наоборот! 😂|tutor|10:27",
                'sidenote' => 'Une question ? Toujours une réponse !♡',
            ], JSON_THROW_ON_ERROR),
        ], 2);

        $blocks = CommerceShowroomRuntimeBlockSet::load_preview($DB, $showroomid);
        $data = (new CommerceShowroomBlockConfigurationPresenter())->apply([], $blocks);

        self::assertTrue($data['issupportteacherstyle']);
        self::assertSame('Pas a pas !♡', $data['supportnote']);
        self::assertSame('рядом, когда нужно', $data['supporttitleaccent']);
        self::assertCount(2, $data['supportleftitems']);
        self::assertCount(2, $data['supportrightitems']);
        self::assertSame('Преподаватель CampusFR', $data['supportconversationtitle']);
        self::assertCount(3, $data['supportmessages']);
        self::assertSame('10:26', $data['supportmessages'][1]['time']);
        self::assertSame('Une question ? Toujours une réponse !♡', $data['supportsidenote']);
    }

    public function test_standard_support_contract_remains_available(): void {
        $config = CommerceShowroomBlockEditorRegistry::normalise('support', [
            'title' => 'Support historique',
            'text' => 'Texte historique',
            'buttonlabel' => 'Contacter',
            'telegramurl' => 'https://t.me/example',
        ]);

        self::assertSame('standard', $config['presentation']);
        self::assertSame('Support historique', $config['title']);
        self::assertSame('Texte historique', $config['text']);
        self::assertSame('Contacter', $config['buttonlabel']);
    }

    public function test_e881_keeps_e87_polish_and_adds_teacher_support_markup(): void {
        global $CFG;

        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/showroom/default.mustache');
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');

        self::assertIsString($template);
        self::assertStringContainsString('issupportteacherstyle', $template);
        self::assertStringContainsString('commerce-showroom-support-teacher__note', $template);
        self::assertStringContainsString('commerce-showroom-support-teacher__conversation', $template);
        self::assertStringContainsString('{{supportsidenote}}', $template);

        self::assertIsString($css);
        self::assertStringContainsString('CampusFR 7.98E.8.7.4', $css);
        self::assertStringContainsString('CampusFR 7.98E.8.8.1', $css);
        self::assertStringContainsString('#showroom-support .commerce-showroom-support-teacher__layout', $css);
        self::assertStringContainsString('#showroom-support .commerce-showroom-support-teacher__conversation', $css);
        self::assertStringContainsString('#showroom-support .commerce-showroom-support-teacher__sidenote', $css);
    }
}
