<?php
// This file is part of Moodle - http://moodle.org/

declare(strict_types=1);

namespace theme_edly;

defined('MOODLE_INTERNAL') || die();

use theme_edly\local\customer_navigation;

/**
 * CR.7: staff CRM visibility must not require Moodle site configuration privileges.
 *
 * @covers \theme_edly\local\customer_navigation
 */
final class customer_navigation_cr7_roles_test extends \advanced_testcase {
    public function test_student_does_not_see_crm_or_moodle_administration(): void {
        global $PAGE;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');
        $this->setUser($user);
        $PAGE->set_url('/my/index.php');

        $nav = customer_navigation::build($PAGE);
        $this->assertFalse($nav['isadmin']);
        $this->assertFalse($nav['hascrmaccess']);
        $this->assertSame([], $nav['crmitems']);
        $this->assertSame([], $nav['adminitems']);
    }

    public function test_support_with_crm_capabilities_sees_filtered_crm_menu_only(): void {
        global $PAGE;
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $context = \context_system::instance();
        $roleid = create_role('CR7 Test Support', 'cr7testsupport', 'CRM navigation test role');
        assign_capability('local/subscriptions:view_dashboard', CAP_ALLOW, $roleid, $context->id);
        assign_capability('local/subscriptions:view_inbox', CAP_ALLOW, $roleid, $context->id);
        role_assign($roleid, $user->id, $context->id);
        $this->setUser($user);
        $PAGE->set_url('/my/index.php');

        $nav = customer_navigation::build($PAGE);
        $this->assertFalse($nav['isadmin']);
        $this->assertTrue($nav['hascrmaccess']);
        $this->assertNotEmpty($nav['crmitems']);
        $this->assertSame([], $nav['adminitems']);
        $this->assertSame([], $nav['adminshortcuts']);
        $this->assertFalse($nav['canswitchrole']);
        $this->assertSame($nav['crmitems'][0]['url'], $nav['crmurl']);
    }

    public function test_moodle_admin_keeps_both_administration_menus(): void {
        global $PAGE;
        $this->resetAfterTest();
        $this->setAdminUser();
        $PAGE->set_url('/my/index.php');

        $nav = customer_navigation::build($PAGE);
        $this->assertTrue($nav['isadmin']);
        $this->assertTrue($nav['hascrmaccess']);
        $this->assertNotEmpty($nav['crmitems']);
        $this->assertNotEmpty($nav['adminitems']);
        $this->assertNotEmpty($nav['adminshortcuts']);
    }

    public function test_desktop_and_mobile_templates_separate_crm_from_moodle_admin(): void {
        $root = dirname(__DIR__, 2);
        foreach (['customer_navigation.mustache', 'customer_navigation_mobile.mustache'] as $name) {
            $template = file_get_contents($root . '/templates/' . $name);
            $this->assertIsString($template);
            $this->assertStringContainsString('{{#customernavigation.hascrmaccess}}', $template);
            $this->assertStringContainsString('{{#customernavigation.isadmin}}', $template);
            $this->assertStringContainsString('customernavigation.crmitems', $template);
            $this->assertStringContainsString('customernavigation.adminitems', $template);
        }
    }
}
