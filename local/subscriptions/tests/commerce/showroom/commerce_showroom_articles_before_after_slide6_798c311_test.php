<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockConfigurationPresenter;

final class commerce_showroom_articles_before_after_slide6_798c311_test extends \advanced_testcase {
    public function test_presenter_exposes_article_outcome_variant_and_notes(): void {
        $reflection = new \ReflectionClass(CommerceShowroomBlockConfigurationPresenter::class);
        $presenter = $reflection->newInstance();
        $method = $reflection->getMethod('apply_before_after');
        $method->setAccessible(true);

        $data = $method->invoke($presenter, [], [
            'presentation' => 'article_outcome',
            'eyebrow' => 'DOSSIER CLASSÉ',
            'title' => 'Après le cours,',
            'titleaccent' => 'tout devient clair.',
            'text' => 'Vous comprenez la logique.',
            'beforelabel' => 'AVANT',
            'beforetitle' => 'Les articles vous font hésiter…',
            'beforeitems' => "Doute|Quel article choisir ?|fa-solid fa-circle-question\nPause|Vous cherchez la règle.|fa-solid fa-pause",
            'beforenote' => 'Un, le, du, de… toujours un doute ?',
            'afterlabel' => 'APRÈS',
            'aftertitle' => 'Vous choisissez avec assurance !',
            'afteritems' => "Sens|Vous comprenez pourquoi.|fa-solid fa-brain\nConfiance|Vous parlez plus librement.|fa-solid fa-comment-dots",
            'imageurl' => '/media/gustave-alf.webp',
            'medianote' => 'On y est ensemble !',
            'summarytitle' => 'Plus de compréhension.',
            'summarytext' => 'Plus de confiance.',
            'summaryaccent' => 'Plus de liberté pour parler français.',
        ]);

        self::assertSame('article_outcome', $data['beforeafterpresentation']);
        self::assertSame('commerce-showroom-before-after--article_outcome', $data['beforeafterpresentationclass']);
        self::assertTrue($data['beforeafterisarticleoutcome']);
        self::assertSame('Un, le, du, de… toujours un doute ?', $data['beforenote']);
        self::assertTrue($data['hasbeforenote']);
        self::assertSame('On y est ensemble !', $data['beforeaftermedianote']);
        self::assertTrue($data['hasbeforeaftermedianote']);
        self::assertTrue($data['hasbeforeafterimage']);
        self::assertCount(2, $data['beforeitems']);
        self::assertCount(2, $data['afteritems']);
    }

    public function test_slide_six_markup_and_css_include_ppt_variant(): void {
        global $CFG;

        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/showroom/default.mustache');
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');
        $registry = file_get_contents($CFG->dirroot . '/local/subscriptions/classes/commerce/showroom/cms/CommerceShowroomBlockEditorRegistry.php');

        self::assertStringContainsString('{{beforeafterpresentationclass}}', $template);
        self::assertStringContainsString('commerce-showroom-before-after__handnote--before', $template);
        self::assertStringContainsString('commerce-showroom-before-after__handnote--media', $template);
        self::assertStringContainsString('commerce-showroom-before-after__summary-icon', $template);
        self::assertStringContainsString('fa-solid fa-compass', $template);
        self::assertStringContainsString('.commerce-showroom-before-after--article_outcome', $css);
        self::assertStringContainsString('grid-template-columns: minmax(17rem, .88fr) minmax(20rem, 1.12fr) minmax(17rem, .88fr);', $css);
        self::assertStringContainsString('.commerce-showroom-before-after--article_outcome .commerce-showroom-before-after__after-stack', $css);
        self::assertStringContainsString('width: 100%;', $css);
        self::assertStringContainsString("'article_outcome' => 'Articles — dossier classé'", $registry);
    }
}
