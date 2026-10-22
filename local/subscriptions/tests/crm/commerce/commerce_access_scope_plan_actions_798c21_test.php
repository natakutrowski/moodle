<?php

namespace local_subscriptions;

use local_subscriptions\commerce\catalog\accessscope\CommerceAccessScopePlanAttachmentService;

final class commerce_access_scope_plan_actions_798c21_test extends \advanced_testcase {
    public function test_scope_view_exposes_create_and_safe_attach_actions(): void {
        $root = dirname(__DIR__, 3);
        $view = file_get_contents($root . '/admin/commerce/accessscopes/view.php');
        $edit = file_get_contents($root . '/admin/commerce/plans/edit.php');
        $attach = file_get_contents($root . '/admin/commerce/accessscopes/attach_plan.php');

        self::assertStringContainsString('commerce_scope_create_plan', $view);
        self::assertStringContainsString("['accessscopeid' => \$id]", $view);
        self::assertStringContainsString('commerce_scope_attach_plan', $view);
        self::assertStringContainsString('attach_plan.php', $view);
        self::assertStringContainsString("optional_param('accessscopeid'", $edit);
        self::assertStringContainsString("\$formdata['accessscopeid']", $edit);
        self::assertStringContainsString("\$_SERVER['REQUEST_METHOD'] ?? ''", $attach);
        self::assertStringContainsString("!== 'POST'", $attach);
        self::assertStringContainsString('require_sesskey()', $attach);
    }

    public function test_service_lists_only_safe_plans_and_reassigns_one(): void {
        global $DB;
        $this->resetAfterTest(true);

        $now = time();
        $scopea = $DB->insert_record('subscription_access_scope', (object)[
            'name' => 'Scope A', 'course_ids' => '', 'creation_date' => $now, 'last_update' => $now,
        ]);
        $scopeb = $DB->insert_record('subscription_access_scope', (object)[
            'name' => 'Scope B', 'course_ids' => '', 'creation_date' => $now, 'last_update' => $now,
        ]);
        $safeplan = $DB->insert_record('subscription_plan', (object)[
            'name' => 'Safe plan', 'accessscopeid' => $scopea, 'duration_key' => 'lifetime',
            'highlight_type' => null, 'is_recurring' => 0, 'is_active' => 0,
            'creation_date' => $now, 'last_update' => $now,
        ]);
        $activeplan = $DB->insert_record('subscription_plan', (object)[
            'name' => 'Active plan', 'accessscopeid' => $scopea, 'duration_key' => 'lifetime',
            'highlight_type' => null, 'is_recurring' => 0, 'is_active' => 1,
            'creation_date' => $now, 'last_update' => $now,
        ]);

        $service = new CommerceAccessScopePlanAttachmentService($DB);
        $attachable = $service->list_attachable($scopeb);
        self::assertArrayHasKey($safeplan, $attachable);
        self::assertArrayNotHasKey($activeplan, $attachable);

        $service->attach($scopeb, $safeplan);
        self::assertSame($scopeb, (int)$DB->get_field('subscription_plan', 'accessscopeid', ['id' => $safeplan]));
    }

    public function test_service_refuses_plan_with_subscription_history(): void {
        global $DB;
        $this->resetAfterTest(true);

        $now = time();
        $scopea = $DB->insert_record('subscription_access_scope', (object)[
            'name' => 'Scope A', 'course_ids' => '', 'creation_date' => $now, 'last_update' => $now,
        ]);
        $scopeb = $DB->insert_record('subscription_access_scope', (object)[
            'name' => 'Scope B', 'course_ids' => '', 'creation_date' => $now, 'last_update' => $now,
        ]);
        $planid = $DB->insert_record('subscription_plan', (object)[
            'name' => 'Used plan', 'accessscopeid' => $scopea, 'duration_key' => 'lifetime',
            'highlight_type' => null, 'is_recurring' => 0, 'is_active' => 0,
            'creation_date' => $now, 'last_update' => $now,
        ]);

        $user = $this->getDataGenerator()->create_user();
        $DB->insert_record('user_subscription', (object)[
            'userid' => $user->id,
            'planid' => $planid,
            'status' => 'expired',
            'start_date' => $now - 3600,
            'end_date' => $now - 60,
            'creation_date' => $now - 7200,
            'last_update' => $now,
        ]);

        $service = new CommerceAccessScopePlanAttachmentService($DB);
        self::assertArrayNotHasKey($planid, $service->list_attachable($scopeb));

        $this->expectException(\RuntimeException::class);
        $service->attach($scopeb, $planid);
    }
}
