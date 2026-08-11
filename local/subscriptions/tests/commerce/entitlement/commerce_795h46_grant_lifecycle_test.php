<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrant;
use local_subscriptions\commerce\entitlement\lifecycle\CommerceGrantLifecycleReconciler;
use local_subscriptions\commerce\fulfillment\native\persistence\MoodleCommerceNativeFulfillmentPersistenceRepository;

final class commerce_795h46_grant_lifecycle_test extends advanced_testcase {
    public function test_repository_activates_only_planned_grant(): void {
        global $DB;
        $this->resetAfterTest(true);

        $grant = $this->grant('grant-h46-repo');
        $this->insert_grant($grant, 'planned');

        $repository = new MoodleCommerceNativeFulfillmentPersistenceRepository();
        self::assertTrue($repository->activate_grant_if_planned($grant, 123456));
        self::assertSame('active', $DB->get_field('local_subs_commerce_grant', 'status', ['grantreference' => $grant->get_reference()]));
        self::assertFalse($repository->activate_grant_if_planned($grant, 123457));
    }

    public function test_reconciler_only_activates_completed_fulfillments(): void {
        global $DB;
        $this->resetAfterTest(true);

        $completed = $this->grant('grant-h46-completed');
        $failed = $this->grant('grant-h46-failed');
        $active = $this->grant('grant-h46-active');

        $this->insert_grant($completed, 'planned');
        $this->insert_grant($failed, 'planned');
        $this->insert_grant($active, 'active');
        $this->insert_state($completed, 'completed');
        $this->insert_state($failed, 'failed');
        $this->insert_state($active, 'completed');

        $reconciler = new CommerceGrantLifecycleReconciler($DB);
        $candidates = $reconciler->inspect();
        self::assertCount(1, $candidates);
        self::assertSame($completed->get_reference(), $candidates[0]['grantreference']);

        $result = $reconciler->execute(0, 222222);
        self::assertSame(1, $result['activated']);
        self::assertSame('active', $DB->get_field('local_subs_commerce_grant', 'status', ['grantreference' => $completed->get_reference()]));
        self::assertSame('planned', $DB->get_field('local_subs_commerce_grant', 'status', ['grantreference' => $failed->get_reference()]));
        self::assertSame(0, $reconciler->execute()['activated']);
    }

    private function grant(string $reference): CommerceEntitlementGrant {
        return new CommerceEntitlementGrant(
            $reference,
            'purchase-' . $reference,
            'item-' . $reference,
            'COURSE.A1',
            'course_access',
            'course:17',
            1,
            2,
            'student@campusfr.test',
            time()
        );
    }

    private function insert_grant(CommerceEntitlementGrant $grant, string $status): void {
        global $DB;
        $now = time();
        $DB->insert_record('local_subs_commerce_grant', (object) [
            'grantreference' => $grant->get_reference(),
            'idempotencykey' => $grant->get_idempotency_key(),
            'purchasereference' => $grant->get_purchase_reference(),
            'itemreference' => $grant->get_item_reference(),
            'productsku' => $grant->get_product_sku(),
            'type' => $grant->get_type(),
            'resourcekey' => $grant->get_resource_key(),
            'quantity' => 1,
            'beneficiaryuserid' => 2,
            'beneficiaryemail' => 'student@campusfr.test',
            'validfrom' => $now,
            'validuntil' => null,
            'status' => $status,
            'configurationjson' => '{}',
            'metadatajson' => '{}',
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }

    private function insert_state(CommerceEntitlementGrant $grant, string $status): void {
        global $DB;
        $now = time();
        $DB->insert_record('local_subs_commerce_ful_state', (object) [
            'grantreference' => $grant->get_reference(),
            'idempotencykey' => $grant->get_idempotency_key(),
            'granttype' => $grant->get_type(),
            'handlerclass' => 'TestHandler',
            'status' => $status,
            'attempts' => 1,
            'lastexecutionreference' => 'run-' . $grant->get_reference(),
            'lastsource' => 'phpunit',
            'lastactoruserid' => null,
            'lastpayloadjson' => '{}',
            'lastmessage' => null,
            'lasterrorclass' => null,
            'timecreated' => $now,
            'timestarted' => $now,
            'timecompleted' => $status === 'completed' ? $now : null,
            'timemodified' => $now,
        ]);
    }
}
