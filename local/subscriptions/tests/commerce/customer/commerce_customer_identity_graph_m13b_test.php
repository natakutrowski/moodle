<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\customer\identity\CommerceCustomerIdentityGraphService;

final class commerce_customer_identity_graph_m13b_test extends advanced_testcase {
    public function test_graph_exposes_current_and_historical_commerce_email_without_linking_accounts(): void {
        global $DB;
        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user(['email' => 'current@example.test', 'firstname' => 'Case', 'lastname' => 'Ivanova']);
        $DB->insert_record('local_subscriptions_commerce_purchase', (object)[
            'purchaseuuid' => bin2hex(random_bytes(16)), 'reference' => 'cmp_m13b', 'type' => 'digital',
            'legacyfamily' => null, 'legacyid' => null, 'userid' => $user->id, 'customeremail' => 'old@example.test',
            'status' => 'fulfilled', 'currency' => 'EUR', 'subtotalminor' => 100, 'discountminor' => 0, 'totalminor' => 100,
            'customerjson' => '{}', 'snapshotjson' => '{}', 'metadatajson' => '{}', 'snapshotversion' => 1,
            'timecreated' => time(), 'timemodified' => time(),
        ]);
        $graph = (new CommerceCustomerIdentityGraphService($DB))->for_user((int)$user->id);
        $emails = array_column($graph['emails'], 'email');
        $this->assertContains('current@example.test', $emails);
        $this->assertContains('old@example.test', $emails);
        $this->assertSame((int)$user->id, $graph['primary']['userid']);
    }

    public function test_identity_navigation_and_user360_register_relationship_views(): void {
        global $CFG;
        $root = $CFG->dirroot . '/local/subscriptions/';
        $nav = file_get_contents($root . 'classes/commerce/customer/identity/CommerceCustomerIdentityNavigationRenderer.php');
        $workspace = file_get_contents($root . 'classes/crm/user360/workspace/User360WorkspaceFactory.php');
        $this->assertStringContainsString("RELATIONSHIPS = 'relationships'", $nav);
        $this->assertStringContainsString('relationships.php', $nav);
        $this->assertStringContainsString('ITEM_IDENTITY_GRAPH', $workspace);
        $this->assertStringContainsString('User360IdentityGraphRenderer::render', $workspace);
        $this->assertStringContainsString('User360IdentityGraphRenderer::render_email', $workspace);
    }
}
