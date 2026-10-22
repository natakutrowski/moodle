<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockConfigurationPresenter;

final class commerce_showroom_articles_faq_slide9_798c316_test extends \advanced_testcase {
    public function test_presenter_exposes_article_faq_fields_and_icons(): void {
        $reflection = new \ReflectionClass(CommerceShowroomBlockConfigurationPresenter::class);
        $presenter = $reflection->newInstance();
        $method = $reflection->getMethod('apply_faq');
        $method->setAccessible(true);

        $data = $method->invoke($presenter, [], [
            'presentation' => 'article_course',
            'eyebrow' => 'FAQ',
            'title' => 'Tout savoir',
            'titleaccent' => 'avant l’enquête.',
            'text' => 'Les réponses essentielles.',
            'items' => "Niveau ?|A1 à B1.|fa-solid fa-graduation-cap\nDurée ?|À votre rythme.|fa-solid fa-clock",
            'supporttitle' => 'Encore une question ?',
            'supporttext' => 'Écrivez-nous.',
            'supportbuttonlabel' => 'Contacter le support',
            'supportbuttontarget' => '#support',
            'supportleftimageurl' => '/media/alf.webp',
            'supportrightimageurl' => '/media/books.webp',
            'footernote' => 'À très vite pour l’enquête !',
        ]);

        self::assertSame('article_course', $data['faqpresentation']);
        self::assertTrue($data['faqisarticlecourse']);
        self::assertSame('commerce-showroom-faq--article-course', $data['faqpresentationclass']);
        self::assertSame('avant l’enquête.', $data['faqtitleaccent']);
        self::assertCount(2, $data['faqs']);
        self::assertSame('fa-solid fa-graduation-cap', $data['faqs'][0]['icon']);
        self::assertFalse($data['hasfaqsupport']);
        self::assertTrue($data['hasfaqsupportbutton']);
        self::assertTrue($data['hasfaqsupportleftimage']);
        self::assertTrue($data['hasfaqsupportrightimage']);
        self::assertTrue($data['hasfaqfooternote']);
    }

    public function test_slide_nine_markup_css_and_registry_include_article_variant(): void {
        global $CFG;

        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/showroom/default.mustache');
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');
        $registry = file_get_contents($CFG->dirroot . '/local/subscriptions/classes/commerce/showroom/cms/CommerceShowroomBlockEditorRegistry.php');

        self::assertStringContainsString('{{faqpresentationclass}}', $template);
        self::assertStringContainsString('commerce-showroom-faq__item-icon', $template);
        self::assertStringContainsString('commerce-showroom-faq__support', $template);
        self::assertStringContainsString('commerce-showroom-faq__footer-note', $template);
        self::assertStringContainsString('.commerce-showroom-faq--article-course', $css);
        self::assertStringContainsString("'article_course' => 'Articles / Alf — FAQ enquête'", $registry);
        self::assertStringNotContainsString("self::text('supporttitle', 'Support — titre')", $registry);
        self::assertStringNotContainsString("self::media('supportleftimageurl', 'Support — image gauche'", $registry);
    }
}
