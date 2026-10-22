<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockConfigurationPresenter;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockEditorRegistry;

/** @covers \local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockConfigurationPresenter */
final class commerce_showroom_a1_faq_798e12_test extends \advanced_testcase {
    public function test_a1_faq_paragraphs_title_and_support_are_safe(): void {
        $config = [
            'presentation' => 'a1_travel',
            'singleopen' => true,
            'supportbuttontarget' => '#showroom-support',
            'translations' => [
                'fr' => [
                    'eyebrow' => 'BRIEFING',
                    'title' => 'Gustave répond à tes',
                    'titleaccent' => 'questions',
                    'entriesjson' => json_encode([
                        [
                            'question' => 'Je commence de zéro ?',
                            'answer' => "Oui.\n\nA0 est disponible tout de suite.",
                            'icon' => 'fa-solid fa-seedling',
                        ],
                        [
                            'question' => 'Des questions ?',
                            'answer' => 'Tu peux nous écrire.',
                            'icon' => 'fa-solid fa-comments',
                        ],
                    ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                    'supporttitle' => 'Encore des questions ?',
                    'supporttext' => 'Écris-nous.',
                    'supportbuttonlabel' => 'Nous écrire',
                ],
            ],
        ];
        $normalised = CommerceShowroomBlockEditorRegistry::normalise('faq', $config);
        self::assertSame(
            $config['translations']['fr']['entriesjson'],
            $normalised['translations']['fr']['entriesjson']
        );
        $presenter = new CommerceShowroomBlockConfigurationPresenter();
        $method = (new \ReflectionClass($presenter))->getMethod('apply_faq');
        $result = $method->invoke($presenter, [], $normalised);

        self::assertTrue($result['faqistravela1']);
        self::assertFalse($result['faqisarticlecourse']);
        self::assertSame('commerce-showroom-faq--a1-travel', $result['faqpresentationclass']);
        self::assertTrue($result['faqsingleopen']);
        self::assertCount(2, $result['faqs']);
        self::assertSame('Je commence de zéro ?', $result['faqs'][0]['question']);
        self::assertSame(
            [['text' => 'Oui.'], ['text' => 'A0 est disponible tout de suite.']],
            $result['faqs'][0]['answerparagraphs']
        );
        self::assertSame('fa-solid fa-seedling', $result['faqs'][0]['icon']);
        self::assertTrue($result['hasfaqsupport']);
        self::assertTrue($result['hasfaqsupportbutton']);
        self::assertSame('#showroom-support', $result['faqsupportbuttontarget']);
    }

    public function test_legacy_faq_and_articles_remain_unchanged(): void {
        $presenter = new CommerceShowroomBlockConfigurationPresenter();
        $method = (new \ReflectionClass($presenter))->getMethod('apply_faq');
        $config = [
            'presentation' => 'article_course',
            'items' => 'Question ?|Réponse.|fa-solid fa-circle-question',
            'supporttitle' => 'Ancien support',
            'supportbuttonlabel' => 'Contact',
            'supportbuttontarget' => '#showroom-support',
        ];
        $article = $method->invoke($presenter, [], $config);
        self::assertTrue($article['faqisarticlecourse']);
        self::assertFalse($article['faqistravela1']);
        self::assertFalse($article['hasfaqsupport']);
        self::assertCount(1, $article['faqs']);
        self::assertSame('Réponse.', $article['faqs'][0]['answer']);

        $config['presentation'] = 'standard';
        $standard = $method->invoke($presenter, [], $config);
        self::assertSame('', $standard['faqpresentationclass']);
        self::assertFalse($standard['faqisarticlecourse']);
        self::assertFalse($standard['faqistravela1']);
        self::assertSame('Question ?', $standard['faqs'][0]['question']);
    }

    public function test_new_faq_is_opt_in_and_stays_in_original_section(): void {
        global $CFG;
        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/showroom/default.mustache');
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');
        self::assertStringContainsString('{{#faqistravela1}}', $template);
        self::assertStringContainsString('{{#answerparagraphs}}<p>{{text}}</p>', $template);
        self::assertStringContainsString('{{#faqisarticlecourse}}', $template);
        self::assertStringContainsString('data-showroom-faq-list', $template);
        self::assertStringContainsString('faq--a1-travel', $css);
        self::assertStringContainsString('showroom-journey-map', $css);
        self::assertStringContainsString('showroom-preparation', $css);
    }
}
