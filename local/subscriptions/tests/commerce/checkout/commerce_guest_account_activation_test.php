<?php

declare(strict_types=1);

namespace local_subscriptions;

use local_subscriptions\commerce\checkout\guest\CommerceGuestAccountActivationService;
use local_subscriptions\commerce\checkout\guest\CommerceGuestCheckoutSessionRepository;
use local_subscriptions\commerce\mail\CommerceMailRuntime;
use local_subscriptions\commerce\mail\CommerceMailType;

/** @covers \local_subscriptions\commerce\checkout\guest\CommerceGuestAccountActivationService */
final class commerce_guest_account_activation_test extends \advanced_testcase {
    public function test_paid_provisional_account_can_set_password_with_one_time_key(): void {
        global $DB, $CFG;

        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user([
            'auth' => 'manual',
            'confirmed' => 1,
            'suspended' => 0,
        ]);
        set_user_preference('auth_forcepasswordchange', 1, $user->id);

        $sessionid = $DB->insert_record('local_subs_commerce_guest', (object)[
            'reference' => 'gst-' . bin2hex(random_bytes(8)),
            'token' => hash('sha256', random_bytes(32)),
            'status' => 'active',
            'currency' => 'EUR',
            'userid' => $user->id,
            'email' => $user->email,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'purchasereference' => 'cmp_activation_test',
            'paymentreference' => 'pay_activation_test',
            'expiresat' => 0,
            'metadatajson' => json_encode([
                'account_origin' => 'guest_checkout',
                'account_state' => 'active',
                'activation_requires_password_reset' => true,
            ]),
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $repository = new CommerceGuestCheckoutSessionRepository($DB);
        $session = $repository->require_by_id((int)$sessionid);
        $service = new CommerceGuestAccountActivationService($DB, $repository);
        $url = $service->issue_activation_url($session);
        $params = $url->params();

        $this->assertSame((int)$user->id, (int)$params['uid']);
        $this->assertSame((int)$sessionid, (int)$params['sessionid']);
        $this->assertNotEmpty($params['key']);

        $password = 'CampusFR#Activation2026!';
        $completed = $service->complete(
            (string)$params['key'],
            (int)$user->id,
            (int)$sessionid,
            $password,
            false
        );

        require_once($CFG->libdir . '/moodlelib.php');
        $updateduser = $DB->get_record('user', ['id' => $user->id], '*', MUST_EXIST);
        $this->assertTrue(validate_internal_user_password($updateduser, $password));
        $this->assertFalse((bool)get_user_preferences('auth_forcepasswordchange', false, $user->id));
        $this->assertSame('ready', $completed->get_metadata()['account_state']);
        $this->assertFalse((bool)$completed->get_metadata()['activation_requires_password_reset']);
        $this->assertFalse($DB->record_exists('user_private_key', [
            'script' => CommerceGuestAccountActivationService::KEY_SCRIPT,
            'userid' => $user->id,
        ]));
    }

    public function test_activation_mail_is_registered_and_uses_transactional_outbox(): void {
        $this->assertContains(CommerceMailType::ACCOUNT_ACTIVATION, CommerceMailType::all());
        $this->assertTrue(CommerceMailRuntime::template_registry()->has(CommerceMailType::ACCOUNT_ACTIVATION));

        $formsource = file_get_contents(__DIR__ . '/../../../forms/commerce/checkout/CommerceGuestAccountActivationForm.php');
        $activatorsource = file_get_contents(__DIR__ . '/../../../classes/commerce/checkout/guest/CommerceGuestAccountActivator.php');
        $this->assertStringNotContainsString("get_string('confirmation')", $formsource);
        $this->assertStringContainsString('CommerceMailRuntime::queue_service()', $activatorsource);
        $this->assertStringContainsString('CommerceMailRuntime::processor()->process_ids', $activatorsource);
    }

    public function test_order_result_exposes_activation_cta_without_requiring_login(): void {
        $source = file_get_contents(__DIR__ . '/../../../order_result.php');
        $this->assertStringContainsString('guest_account_activation_start.php', $source);
        $this->assertStringContainsString('commerce_guest_activation_result_cta', $source);
        $this->assertStringContainsString('password_set_at', $source);
    }

    public function test_activation_page_uses_wide_vertical_password_layout(): void {
        $pagesource = file_get_contents(__DIR__ . '/../../../guest_account_activate.php');
        $formsource = file_get_contents(__DIR__ . '/../../../forms/commerce/checkout/CommerceGuestAccountActivationForm.php');
        $css = file_get_contents(__DIR__ . '/../../../styles/guest_account_activation.css');

        $this->assertStringContainsString('commerce-guest-activation__security', $formsource);
        $this->assertStringContainsString('commerce-guest-activation__secure-note', $pagesource);
        $this->assertStringContainsString('commerce_guest_activation_protected_title', $pagesource);
        $this->assertStringContainsString('local_subscriptions/guest_account_activation', $pagesource);
        $this->assertStringContainsString('commerce-guest-activation__password-toggle', $css);
        $this->assertStringContainsString('width: min(100%, 960px)', $css);
        $this->assertStringContainsString('.commerce-guest-activation .mform .row.fitem', $css);
        $this->assertStringContainsString('display: block', $css);
        $this->assertStringContainsString('width: 100%', $css);
    }
}
