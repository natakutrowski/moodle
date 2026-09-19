<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_797m75_scalability_navigation_test extends advanced_testcase {
    public function test_pedagogy_navigation_is_reachable_and_contextual(): void {
        $root = dirname(__DIR__, 3);
        $primary = file_get_contents($root . '/classes/crm/navigation/CrmNavigationRegistry.php');
        $secondary = file_get_contents($root . '/classes/crm/commerce/navigation/CommerceSectionNavigationRegistry.php');
        $localrenderer = file_get_contents($root . '/classes/commerce/education/admin/CommerceEducationNavigationRenderer.php');
        $promotions = file_get_contents($root . '/admin/commerce/education/promotions.php');
        $courses = file_get_contents($root . '/admin/commerce/education/courses.php');
        $cockpit = file_get_contents($root . '/classes/commerce/education/admin/CommercePedagogicalPromotionAdminRenderer.php');

        self::assertStringContainsString("crm_commerce_nav_education", $primary);
        self::assertStringContainsString("/admin/commerce/education/promotions.php", $primary);
        self::assertStringContainsString("/admin/commerce/education/promotions.php", $secondary);
        self::assertStringContainsString('CommerceEducationNavigationRenderer::PROMOTIONS', $promotions);
        self::assertStringContainsString('CommerceEducationNavigationRenderer::COURSES', $courses);
        self::assertStringContainsString('commerce-ped-m75-local-nav', $localrenderer);
        self::assertStringContainsString('commerce_education_m75_back_to_promotions', $cockpit);
    }

    public function test_large_admin_surfaces_are_paginated(): void {
        $root = dirname(__DIR__, 3);
        $participants = file_get_contents($root . '/admin/commerce/education/participants.php');
        $groups = file_get_contents($root . '/admin/commerce/education/groups.php');
        $calendar = file_get_contents($root . '/admin/commerce/education/calendar.php');

        self::assertStringContainsString('$participantperpage = 25;', $participants);
        self::assertStringContainsString("'page'", $participants);
        self::assertStringContainsString('paging_bar(', $participants);

        self::assertStringContainsString('$groupperpage = 12;', $groups);
        self::assertStringContainsString("'gpage'", $groups);
        self::assertStringContainsString('paging_bar(', $groups);

        self::assertStringContainsString('$calendarperpage = 10;', $calendar);
        self::assertStringContainsString("'calpage'", $calendar);
        self::assertStringContainsString('paging_bar(', $calendar);
        self::assertStringContainsString('$calendaroffset + $index + 1', $calendar);
    }

    public function test_admin_icons_are_centered_in_both_axes(): void {
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
        self::assertStringContainsString('align-items: center;', $css);
        self::assertStringContainsString('justify-content: center;', $css);
        self::assertStringContainsString('height: 100%;', $css);
    }
}
