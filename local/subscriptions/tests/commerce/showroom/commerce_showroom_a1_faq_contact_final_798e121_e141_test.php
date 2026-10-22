<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockConfigurationPresenter;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockEditorRegistry;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockTypeRegistry;

final class commerce_showroom_a1_faq_contact_final_798e121_e141_test extends \advanced_testcase {
    public function test_new_faq_contact_is_addable_and_does_not_replace_support(): void {
        self::assertTrue(CommerceShowroomBlockTypeRegistry::exists('faq_contact'));
        self::assertNotEmpty(CommerceShowroomBlockEditorRegistry::schema('faq_contact'));
        self::assertTrue(CommerceShowroomBlockTypeRegistry::exists('support'));

        $presenter = new CommerceShowroomBlockConfigurationPresenter();
        $method = (new \ReflectionClass($presenter))->getMethod('apply_faq_contact');
        $config = [
            'title' => 'Остались вопросы?',
            'text' => "Строка 1\nСтрока 2",
            'buttonlabel' => 'Написать нам',
            'buttonurl' => 'https://t.me/natakutrowski',
            'telegramurl' => 'https://t.me/natakutrowski',
            'whatsappurl' => 'https://wa.me/33759647925?text=Bonjour%20CampusFR',
        ];
        $config = CommerceShowroomBlockEditorRegistry::normalise('faq_contact', $config);
        $data = $method->invoke($presenter, ['supportemail' => 'support@campusfr.fr', 'supporturl' => '/help'], $config);
        self::assertSame('Остались вопросы?', $data['faqcontacttitle']);
        self::assertTrue($data['hasfaqcontacttelegram']);
        self::assertTrue($data['hasfaqcontactwhatsapp']);
        self::assertTrue($data['hasfaqcontactemail']);
        self::assertSame('https://t.me/natakutrowski', $data['faqcontactbuttonurl']);
        self::assertSame("Строка 1\nСтрока 2", $data['faqcontacttext']);
    }

    public function test_contact_url_rejects_javascript_and_template_keeps_legacy_blocks(): void {
        global $CFG;
        $presenter = new CommerceShowroomBlockConfigurationPresenter();
        $method = (new \ReflectionClass($presenter))->getMethod('apply_faq_contact');
        $data = $method->invoke($presenter, ['supporturl' => '/help'], [
            'buttonurl' => 'javascript:alert(1)', 'telegramurl' => 'javascript:alert(1)',
        ]);
        self::assertSame('/help', $data['faqcontactbuttonurl']);
        self::assertFalse($data['hasfaqcontacttelegram']);

        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/showroom/default.mustache');
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');
        self::assertStringContainsString('{{#issupport}}', $template);
        self::assertStringContainsString('{{#isfaqcontact}}', $template);
        self::assertStringContainsString('{{#faqistravela1}}', $template);
        self::assertStringContainsString('showroom-faq-contact', $template);
        self::assertStringContainsString('showroom-final', $template);
        self::assertStringContainsString('showroom-faq-contact', $css);
        self::assertStringContainsString('a1_lavender', $css);
    }
}
