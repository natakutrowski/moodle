<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

/** Final release-gate coverage for the 7.97 M7 pedagogy admin UX. */
final class commerce_797m76_admin_pedagogy_final_certification_test extends advanced_testcase {
    public function test_all_promotion_pages_share_the_cockpit_and_back_navigation(): void {
        $root = dirname(__DIR__, 3);
        $renderer = file_get_contents(
            $root . '/classes/commerce/education/admin/CommercePedagogicalPromotionAdminRenderer.php'
        );

        foreach ([
            'promotion_edit.php' => 'OVERVIEW',
            'offers.php' => 'OFFERS',
            'calendar.php' => 'CALENDAR',
            'groups.php' => 'GROUPS',
            'participants.php' => 'PARTICIPANTS',
        ] as $page => $active) {
            $source = file_get_contents($root . '/admin/commerce/education/' . $page);
            self::assertStringContainsString(
                'CommercePedagogicalPromotionAdminRenderer::render(',
                $source,
                $page
            );
            self::assertStringContainsString(
                'CommercePedagogicalPromotionAdminRenderer::' . $active,
                $source,
                $page
            );
        }

        self::assertStringContainsString('commerce_education_m75_back_to_promotions', $renderer);
        self::assertStringContainsString('/admin/commerce/education/promotions.php', $renderer);
    }

    public function test_pedagogy_has_first_class_crm_and_local_navigation(): void {
        $root = dirname(__DIR__, 3);
        $primary = file_get_contents($root . '/classes/crm/navigation/CrmNavigationRegistry.php');
        $commerce = file_get_contents($root . '/classes/crm/commerce/navigation/CommerceSectionNavigationRegistry.php');
        $localnav = file_get_contents(
            $root . '/classes/commerce/education/admin/CommerceEducationNavigationRenderer.php'
        );
        $promotions = file_get_contents($root . '/admin/commerce/education/promotions.php');
        $courses = file_get_contents($root . '/admin/commerce/education/courses.php');

        self::assertStringContainsString('crm_commerce_nav_education', $primary);
        self::assertStringContainsString('/admin/commerce/education/promotions.php', $primary);
        self::assertStringContainsString('/admin/commerce/education/promotions.php', $commerce);
        self::assertStringContainsString('commerce-ped-m75-local-nav', $localnav);
        self::assertStringContainsString('CommerceEducationNavigationRenderer::PROMOTIONS', $promotions);
        self::assertStringContainsString('CommerceEducationNavigationRenderer::COURSES', $courses);
    }

    public function test_promotion_editor_offers_and_calendar_keep_all_business_controls(): void {
        $root = dirname(__DIR__, 3);
        $editor = file_get_contents($root . '/admin/commerce/education/promotion_edit.php');
        $offers = file_get_contents($root . '/admin/commerce/education/offers.php');
        $calendar = file_get_contents($root . '/admin/commerce/education/calendar.php');

        foreach ([
            'commerce_education_m72_identity_title',
            'commerce_education_m72_cycle_title',
            'commerce_education_m72_sales_title',
            'commerce_education_m72_capacity_title',
            "'name' => 'promotionkey'",
            "'name' => 'capacitytotal'",
            "'name' => 'salesopensat'",
            "'name' => 'salesclosesat'",
        ] as $needle) {
            self::assertStringContainsString($needle, $editor, $needle);
        }

        foreach ([
            'commerce-ped-offer-card',
            "\$action === 'link' || \$action === 'update'",
            "'name' => 'action', 'value' => 'joinprice'",
            "'name' => 'action', 'value' => 'unlink'",
            "'name' => 'joinprice[' . \$currency . ']'",
        ] as $needle) {
            self::assertStringContainsString($needle, $offers, $needle);
        }

        foreach ([
            'commerce-ped-calendar-timeline',
            'is_unlocked_at($now)',
            '$scheduledbysection[(int)$section->id]',
            '!isset($scheduledbysection[(int)$section->id])',
            "'calpage'",
        ] as $needle) {
            self::assertStringContainsString($needle, $calendar, $needle);
        }
    }

    public function test_groups_and_participants_are_editable_scalable_and_xp_is_read_only(): void {
        $root = dirname(__DIR__, 3);
        $groups = file_get_contents($root . '/admin/commerce/education/groups.php');
        $participants = file_get_contents($root . '/admin/commerce/education/participants.php');

        foreach ([
            "\$action === 'edit'",
            'update_group(',
            "'name' => 'displayname'",
            "'name' => 'tutorname'",
            "'supportlang'",
            "'name' => 'telegramref'",
            'get_telegram_reference()',
            "'name' => 'action', 'value' => 'backfill'",
            'assign_first_available_for_product',
            '$groupperpage = 12;',
            "'gpage'",
            'paging_bar(',
        ] as $needle) {
            self::assertStringContainsString($needle, $groups, $needle);
        }

        self::assertStringNotContainsString("optional_param('levelupxp'", $groups);
        self::assertStringNotContainsString("'name' => 'levelupxp'", $groups);

        foreach ([
            "optional_param('q', '', PARAM_TEXT)",
            'CommercePedagogicalXpRepository',
            'points_by_user($promotionid)',
            'move_group(',
            'CommercePedagogicalParticipantMoveException::GROUP_FULL',
            '$participantperpage = 25;',
            "'page'",
            'paging_bar(',
        ] as $needle) {
            self::assertStringContainsString($needle, $participants, $needle);
        }
    }

    public function test_large_surfaces_page_after_full_dataset_is_computed(): void {
        $root = dirname(__DIR__, 3);
        $groups = file_get_contents($root . '/admin/commerce/education/groups.php');
        $participants = file_get_contents($root . '/admin/commerce/education/participants.php');
        $calendar = file_get_contents($root . '/admin/commerce/education/calendar.php');

        self::assertStringContainsString('array_slice($groups, $groupoffset, $groupperpage)', $groups);
        self::assertStringContainsString('array_slice($visibleparticipants, $participantoffset, $participantperpage)', $participants);
        self::assertStringContainsString('array_slice($scheduled, $calendaroffset, $calendarperpage)', $calendar);
        self::assertStringContainsString('$calendaroffset + $index + 1', $calendar);
    }

    public function test_final_icon_alignment_and_responsive_admin_styles_are_present(): void {
        $root = dirname(__DIR__, 3);
        $css = file_get_contents($root . '/styles.css');

        foreach ([
            '.commerce-ped-admin-metric-icon',
            '.commerce-ped-promotion-editor-section-icon',
            '.commerce-ped-m73-panel-icon',
            '.commerce-ped-m74-panel-icon',
        ] as $selector) {
            self::assertStringContainsString($selector, $css);
        }

        self::assertStringContainsString('display: inline-grid;', $css);
        self::assertStringContainsString('place-items: center;', $css);
        self::assertStringContainsString('vertical-align: middle;', $css);
        self::assertStringContainsString('align-items: center;', $css);
        self::assertStringContainsString('justify-content: center;', $css);
        self::assertStringContainsString('width: 100%;', $css);
        self::assertStringContainsString('height: 100%;', $css);
        self::assertStringContainsString('@media (max-width: 575.98px)', $css);
    }
}
