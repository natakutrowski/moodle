<?php
declare(strict_types=1);
namespace local_subscriptions\tests\commerce\showroom;
defined('MOODLE_INTERNAL') || die();
use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockConfigurationPresenter;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomRuntimeBlockSet;
/** @covers \local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockConfigurationPresenter */
final class commerce_showroom_a1_tutor_chat_798e87_test extends \advanced_testcase {
    public function test_runtime_supports_conversation_header_sidenote_and_times(): void {
        global $DB;
        $this->resetAfterTest(true);
        $repo = new CommerceShowroomCmsRepository($DB);
        $showroomid = $repo->save([
            'showroomkey' => 'a1-e87-runtime',
            'status' => 'draft',
            'name' => 'A1 E8.7 runtime',
            'template' => 'local_subscriptions/showroom/default',
            'slugfr' => '', 'slugen' => '', 'slugru' => '',
            'titlekey' => '', 'descriptionkey' => '',
            'productsjson' => '{}', 'settingsjson' => '{}',
        ], 2);
        $repo->save_block($showroomid, [
            'blocktype' => 'tutor_chat',
            'sortorder' => 10,
            'enabled' => true,
            'configjson' => json_encode([
                'eyebrow' => 'ПРАКТИКА С НОСИТЕЛЕМ',
                'title' => 'В твоём клане есть',
                'titleaccent' => 'свой француз!',
                'text' => 'Текст',
                'conversationtitle' => 'Notre clan 💙',
                'sidenote' => 'On parle de tout !',
                'features' => "Живое общение|Обсуждаете всё подряд.|fa-regular fa-comment\nФранция изнутри|Фото и аудио из Франции.|fa-solid fa-camera\nПрактика без стресса|Без страха ошибиться.|fa-regular fa-star\nПоддержка каждый день|Помощь каждый день.|fa-regular fa-heart",
                'messages' => "|Salut tout le monde !|tutor|10:24\n|Waouh !|student|10:26\n|Super !|tutor|10:31",
                'buttonlabel' => 'Познакомиться с тьютором',
                'buttontarget' => '#showroom-offers',
            ], JSON_THROW_ON_ERROR),
        ], 2);
        $blocks = CommerceShowroomRuntimeBlockSet::load_preview($DB, $showroomid);
        $data = (new CommerceShowroomBlockConfigurationPresenter())->apply([], $blocks);
        self::assertSame('Notre clan 💙', $data['tutorconversationtitle']);
        self::assertTrue($data['hastutorconversationtitle']);
        self::assertSame('On parle de tout !', $data['tutorsidenote']);
        self::assertTrue($data['hastutorsidenote']);
        self::assertCount(4, $data['tutorfeatures']);
        self::assertCount(3, $data['tutormessages']);
        self::assertSame('10:24', $data['tutormessages'][0]['time']);
        self::assertTrue($data['tutormessages'][0]['hastime']);
        self::assertSame('#showroom-offers', $data['tutorbuttontarget']);
    }
    public function test_markup_and_css_match_e87_contract(): void {
        global $CFG;
        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/showroom/block_tutor_chat.mustache');
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');
        self::assertIsString($template);
        self::assertStringContainsString('commerce-showroom-tutor-chat__badge', $template);
        self::assertStringContainsString('commerce-showroom-tutor-chat__conversation-header', $template);
        self::assertStringContainsString('{{tutorconversationtitle}}', $template);
        self::assertStringContainsString('commerce-showroom-tutor-chat__avatar', $template);
        self::assertStringContainsString('{{tutorsidenote}}', $template);
        self::assertStringContainsString('commerce-showroom-tutor-chat__time', $template);
        self::assertIsString($css);
        self::assertStringContainsString('CampusFR 7.98E.8.7', $css);
        self::assertStringContainsString('#showroom-tutor .commerce-showroom-tutor-chat__badge', $css);
        self::assertStringContainsString('#showroom-tutor .commerce-showroom-tutor-chat__conversation-header', $css);
        self::assertStringContainsString('#showroom-tutor .commerce-showroom-tutor-chat__sidenote', $css);
        self::assertStringContainsString('#showroom-tutor .commerce-showroom-tutor-chat__hero-card', $css);
    }
}
