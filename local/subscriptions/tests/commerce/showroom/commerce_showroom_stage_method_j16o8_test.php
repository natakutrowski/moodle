<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockConfigurationPresenter;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomRuntimeBlockSet;

final class commerce_showroom_stage_method_j16o8_test extends \advanced_testcase {
    public function test_two_column_stage_method_json_reaches_public_presenter(): void {
        global $DB;

        $this->resetAfterTest(true);

        $repository = new CommerceShowroomCmsRepository($DB);
        $showroomid = $repository->save([
            'showroomkey' => 'third-group-verbs',
            'status' => 'published',
            'name' => 'Verbes',
            'template' => 'local_subscriptions/showroom/third_group_verbs',
            'titlekey' => '',
            'descriptionkey' => '',
            'productsjson' => '{}',
            'settingsjson' => '{}',
        ], 2);

        $repository->save_block($showroomid, [
            'blocktype' => 'stage_method',
            'blockkey' => 'stage-method',
            'sortorder' => 10,
            'enabled' => true,
            'configjson' => json_encode([
                'title' => 'Как проходит',
                'titlehighlight' => 'каждый этап?',
                'items' => "Первый этап|Первый текст\nПривал|Текст привала",
            ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        ], 2);

        $blocks = CommerceShowroomRuntimeBlockSet::load($DB, 'third-group-verbs');
        $data = (new CommerceShowroomBlockConfigurationPresenter())->apply([], $blocks);

        self::assertSame('Как проходит', $data['journeytitle']);
        self::assertSame('каждый этап?', $data['journeytitlehighlight']);
        self::assertCount(2, $data['journeysteps']);
        self::assertSame('Первый этап', $data['journeysteps'][0]['title']);
        self::assertSame('Первый текст', $data['journeysteps'][0]['text']);
        self::assertFalse($data['journeysteps'][0]['isreststop']);
        self::assertTrue($data['journeysteps'][1]['isreststop']);
    }

    public function test_journey_css_has_rest_stop_and_uncoloured_background_contract(): void {
        global $CFG;

        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');
        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/showroom/third_group_verbs.mustache'
        );

        self::assertStringContainsString(
            'commerce-showroom-ascent__badge commerce-showroom-stage-method__badge',
            $template
        );
        self::assertStringContainsString('fa-solid fa-mug-hot', $template);
        self::assertStringContainsString(
            'grid-template-columns: 48px minmax(0, 1fr) 68px;',
            $css
        );
        self::assertStringContainsString(
            'background: rgba(255, 238, 247, .90);',
            $css
        );
        self::assertStringContainsString(
            'object-position: right center;',
            $css
        );
        self::assertStringNotContainsString(
            'J16O7 — Journey final consolidation',
            $css
        );
    }
}
