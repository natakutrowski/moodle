<?php

declare(strict_types=1);
namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n118_mail_history_forward_test extends \advanced_testcase {
    public function test_user360_has_scoped_transactional_history_and_trial_padding(): void {
        $root = dirname(__DIR__, 3);
        $renderer = file_get_contents($root . '/classes/crm/user360/rendering/User360CommerceAccessRenderer.php');
        $css = file_get_contents($root . '/styles.css');
        self::assertStringContainsString('recent_transactional_for_user', $renderer);
        self::assertStringContainsString('VIEW_PAYMENTS', $renderer);
        self::assertStringContainsString("'transactionalonly' => 1", $renderer);
        self::assertStringContainsString('.crm-user360-n116a-trial-lifecycle .crm-user360-n116a-section-body', $css);
    }

    public function test_forward_is_post_only_and_protected_by_existing_moodle_security(): void {
        $root = dirname(__DIR__, 3);
        $action = file_get_contents($root . '/admin/commerce/mail/action.php');
        $service = file_get_contents($root . '/classes/commerce/mail/admin/CommerceMailAdminService.php');
        self::assertStringContainsString('AdminSecurity::require(Capabilities::MANAGE_SUBSCRIPTIONS)', $action);
        self::assertStringContainsString('require_sesskey()', $action);
        self::assertStringContainsString("'POST'", $action);
        self::assertStringContainsString('function can_forward(', $service);
    }

    public function test_forward_allows_transactional_mail_but_rejects_sensitive_types(): void {
        $service = \local_subscriptions\commerce\mail\admin\CommerceMailAdminService::class;
        $types = \local_subscriptions\commerce\mail\CommerceMailType::class;

        $mail = (object)[
            'userid' => 2437,
            'status' => 'sent',
            'mailtype' => $types::PURCHASE_RECEIPT,
            'idempotencykey' => 'mail:receipt:123',
        ];

        self::assertTrue($service::can_forward($mail));

        foreach ([
            $types::ACCOUNT_ACTIVATION,
            $types::GUEST_IDENTITY_OTP,
            $types::MARKETING_CAMPAIGN,
            $types::SALES_FOLLOWUP,
            $types::PERSONAL_OFFER,
            $types::TRIAL_WELCOME,
        ] as $blockedtype) {
            $mail->mailtype = $blockedtype;
            self::assertFalse($service::can_forward($mail), $blockedtype);
        }

        $mail->mailtype = $types::PURCHASE_ACCESS;
        $mail->idempotencykey = 'mail:receipt:audit';
        self::assertFalse($service::can_forward($mail), 'Audit copy');

        $mail->idempotencykey = 'mail:receipt:123';
        $mail->status = 'queued';
        self::assertFalse($service::can_forward($mail), 'Unsent mail');

        $mail->status = 'sent';
        $mail->userid = 0;
        self::assertFalse($service::can_forward($mail), 'Unattached mail');
    }
}
