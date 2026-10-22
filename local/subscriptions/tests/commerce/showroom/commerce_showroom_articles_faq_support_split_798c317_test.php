<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockConfigurationPresenter;

final class commerce_showroom_articles_faq_support_split_798c317_test extends \advanced_testcase {
    public function test_article_faq_never_renders_embedded_support_card(): void {
        $reflection = new \ReflectionClass(CommerceShowroomBlockConfigurationPresenter::class);
        $presenter = $reflection->newInstance();
        $method = $reflection->getMethod('apply_faq');
        $method->setAccessible(true);

        $data = $method->invoke($presenter, [], [
            'presentation' => 'article_course',
            'title' => 'FAQ',
            'items' => "Question ?|Réponse.|fa-solid fa-circle-question",
            // Legacy 3.16 values must no longer produce the embedded card.
            'supporttitle' => 'Encore une question ?',
            'supporttext' => 'Écrivez-nous.',
            'supportbuttonlabel' => 'Contacter le support',
            'supportbuttontarget' => '#showroom-support',
        ]);

        self::assertTrue($data['faqisarticlecourse']);
        self::assertTrue($data['hasfaqsupportbutton']);
        self::assertFalse($data['hasfaqsupport']);
    }

    public function test_support_remains_a_distinct_showroom_block(): void {
        global $CFG;

        $registry = file_get_contents($CFG->dirroot . '/local/subscriptions/classes/commerce/showroom/cms/CommerceShowroomBlockEditorRegistry.php');
        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/showroom/default.mustache');

        self::assertStringContainsString("'support' => self::definition([", $registry);
        self::assertStringContainsString('{{#issupport}}', $template);
        self::assertStringContainsString('commerce-showroom-support__card', $template);
    }
}
