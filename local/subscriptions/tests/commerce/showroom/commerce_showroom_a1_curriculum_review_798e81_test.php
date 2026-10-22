<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockConfigurationPresenter;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomRuntimeBlockSet;

/** @covers \local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockConfigurationPresenter */
final class commerce_showroom_a1_curriculum_review_798e81_test extends \advanced_testcase {
    public function test_curriculum_supports_six_groups_per_tab_and_check_rows_without_numbers(): void {
        global $DB;
        $this->resetAfterTest(true);

        $repo = new CommerceShowroomCmsRepository($DB);
        $showroomid = $repo->save([
            'showroomkey' => 'a1-e81-runtime',
            'status' => 'draft',
            'name' => 'A1 E8.1 runtime',
            'template' => 'local_subscriptions/showroom/default',
            'slugfr' => '', 'slugen' => '', 'slugru' => '',
            'titlekey' => '', 'descriptionkey' => '',
            'productsjson' => '{}', 'settingsjson' => '{}',
        ], 2);

        $topics = [];
        $grammar = [];
        for ($group = 1; $group <= 6; $group++) {
            for ($item = 1; $item <= 5; $item++) {
                $number = str_pad((string)(($group - 1) * 5 + $item), 2, '0', STR_PAD_LEFT);
                $topics[] = "Group {$group}|fa-solid fa-star|{$number}|Topic {$number}";
                $grammar[] = "Grammar {$group}|fa-solid fa-book||Rule {$number}";
            }
        }

        $repo->save_block($showroomid, [
            'blocktype' => 'curriculum_tabs',
            'sortorder' => 10,
            'enabled' => true,
            'configjson' => json_encode([
                "eyebrow" => "WHAT'S AHEAD",
                'badgeicon' => 'fa-solid fa-list-check',
                'tab1label' => 'Topics',
                'tab2label' => 'A1 grammar',
                'topics' => implode("\n", $topics),
                'grammar' => implode("\n", $grammar),
                'defaulttab' => 'topics',
            ], JSON_THROW_ON_ERROR),
        ], 2);

        $blocks = CommerceShowroomRuntimeBlockSet::load_preview($DB, $showroomid);
        $data = (new CommerceShowroomBlockConfigurationPresenter())->apply([], $blocks);

        self::assertSame("WHAT'S AHEAD", $data['curriculumeyebrow']);
        self::assertSame('fa-solid fa-list-check', $data['curriculumbadgeicon']);
        self::assertTrue($data['hascurriculumtopics']);
        self::assertTrue($data['hascurriculumgrammar']);
        self::assertCount(6, $data['curriculumtopicsgroups']);
        self::assertCount(6, $data['curriculumgrammargroups']);
        self::assertTrue($data['curriculumtopicsgroups'][0]['items'][0]['hasnumber']);
        self::assertFalse($data['curriculumgrammargroups'][0]['items'][0]['hasnumber']);
        self::assertSame('', $data['curriculumgrammargroups'][0]['items'][0]['number']);
    }

    public function test_public_markup_and_css_match_e81_review_contract(): void {
        global $CFG;

        $block = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/showroom/block_curriculum_tabs.mustache'
        );
        $group = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/showroom/curriculum_group.mustache'
        );
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');

        self::assertIsString($block);
        self::assertStringContainsString('{{#curriculumeyebrow}}', $block);
        self::assertStringContainsString('commerce-showroom-curriculum__badge', $block);
        self::assertStringContainsString('{{curriculumbadgeicon}}', $block);
        self::assertStringContainsString('data-showroom-tab-target="topics"', $block);
        self::assertStringContainsString('data-showroom-tab-target="grammar"', $block);
        self::assertStringContainsString('data-showroom-tab-panel="topics"', $block);
        self::assertStringContainsString('data-showroom-tab-panel="grammar"', $block);

        self::assertIsString($group);
        self::assertStringContainsString('{{#hasnumber}}', $group);
        self::assertStringContainsString('{{^hasnumber}}', $group);
        self::assertStringContainsString('commerce-showroom-curriculum__check', $group);

        self::assertIsString($css);
        self::assertStringContainsString('CampusFR 7.98E.8.1', $css);
        self::assertStringContainsString('@keyframes campusfr-a1-curriculum-tab-in', $css);
        self::assertStringContainsString('@media (prefers-reduced-motion: reduce)', $css);
        self::assertStringContainsString('.commerce-showroom-curriculum__group:nth-child(6)', $css);
        self::assertStringContainsString('CampusFR 7.98E.8.1.4', $css);
        self::assertStringContainsString('#showroom-program .commerce-showroom-curriculum__badge', $css);
        self::assertStringContainsString('border: 1.5px solid #f31375 !important', $css);
        self::assertStringContainsString('background: #fff !important', $css);
        self::assertStringContainsString('CampusFR 7.98E.8.1.5', $css);
        self::assertStringContainsString('#showroom-program .commerce-showroom-curriculum__group', $css);
        self::assertStringContainsString('border: 1px solid rgba(243, 19, 117, .18)', $css);
        self::assertStringContainsString('CampusFR 7.98E.8.1.6', $css);
        self::assertStringContainsString('border: 1px solid rgba(243, 19, 117, .32) !important', $css);
        self::assertStringContainsString('transform: translateY(-4px)', $css);
        self::assertStringContainsString('@media (hover: hover) and (pointer: fine)', $css);
    }
}
