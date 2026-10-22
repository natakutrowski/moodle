<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockConfigurationPresenter;

final class commerce_showroom_articles_video_slide5_798c310_test extends \advanced_testcase {
    public function test_presenter_exposes_article_investigation_video_content(): void {
        $reflection = new \ReflectionClass(CommerceShowroomBlockConfigurationPresenter::class);
        $presenter = $reflection->newInstance();
        $method = $reflection->getMethod('apply_video');
        $method->setAccessible(true);

        $data = $method->invoke($presenter, [], [
            'presentation' => 'article_investigation',
            'eyebrow' => 'КАК ЭТО РАБОТАЕТ?',
            'title' => 'Вы не смотрите урок со стороны.',
            'titleaccent' => 'Вы ведёте расследование вместе с Гюставом и Альфом.',
            'text' => 'Каждый эпизод — это короткое интерактивное видео.',
            'textaccent' => 'Смотрите → находите подсказки → отвечаете → сразу применяете правило.',
            'note' => 'Apprendre en jouant, c’est plus motivant !',
            'steps' => implode("\n", [
                '01|Смотрите мини-видео|Гюстав и Альф получают новую улику.|fa-solid fa-play',
                '02|Расследование останавливается|Прямо внутри видео появляется вопрос.|fa-solid fa-magnifying-glass',
                '03|Вы принимаете решение|Сразу видите, почему один вариант подходит.|fa-solid fa-puzzle-piece',
                '04|Получаете часть сердца|Расследование продолжается.|fa-solid fa-heart',
            ]),
        ]);

        self::assertTrue($data['videoisinvestigation']);
        self::assertSame('commerce-showroom-video--article-investigation', $data['videovariantclass']);
        self::assertSame('КАК ЭТО РАБОТАЕТ?', $data['videoeyebrow']);
        self::assertCount(4, $data['videosteps']);
        self::assertTrue($data['videosteps'][0]['hasarrow']);
        self::assertFalse($data['videosteps'][3]['hasarrow']);
    }

    public function test_template_and_css_contain_slide_five_ppt_structure(): void {
        global $CFG;

        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/showroom/default.mustache');
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');

        self::assertStringContainsString('commerce-showroom-video-investigation__badge', $template);
        self::assertStringContainsString('commerce-showroom-video-investigation__steps', $template);
        self::assertStringContainsString('commerce-showroom-video-investigation__step-arrow', $template);
        self::assertStringContainsString('commerce-showroom-video--article-investigation', $css);
        self::assertStringContainsString('grid-template-columns: repeat(4, minmax(0, 1fr));', $css);
    }
}
