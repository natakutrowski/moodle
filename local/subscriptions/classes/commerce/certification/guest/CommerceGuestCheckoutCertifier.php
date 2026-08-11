<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\certification\guest;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\checkout\guest\CommerceGuestCheckoutSessionRepository;

/** Certifies Guest Checkout lifecycle, persistence and integration wiring. */
final class CommerceGuestCheckoutCertifier {
    public function __construct(
        private readonly \moodle_database $database,
        private readonly string $plugindir
    ) {}

    /** @return array<string, mixed> */
    public function certify(?string $reference = null): array {
        $checks = [];
        $checks[] = $this->source_check(
            'checkout_recovery',
            'commerce_checkout.php',
            'CommerceGuestCartRecoveryService'
        );
        $checks[] = $this->source_check(
            'checkout_action_recovery',
            'commerce_checkout_action.php',
            'CommerceGuestCartRecoveryService'
        );
        $checks[] = $this->source_check(
            'payment_success_activation',
            'classes/payment/EventRouter.php',
            'activate_guest_account'
        );
        $checks[] = $this->source_check(
            'payment_failure_lifecycle',
            'classes/payment/EventRouter.php',
            'update_guest_checkout_failure'
        );
        $checks[] = $this->source_check(
            'safe_cleanup_cli',
            'cli/commerce/checkout/purge_expired_guest_accounts.php',
            '--execute'
        );

        $sessionresult = null;
        if ($reference !== null && trim($reference) !== '') {
            $sessionresult = $this->certify_session(trim($reference));
            foreach ($sessionresult['checks'] as $check) {
                $checks[] = $check;
            }
        }

        $certified = !in_array('FAIL', array_column($checks, 'status'), true);
        return [
            'phase' => '7.95H5.4',
            'certified' => $certified,
            'checks' => $checks,
            'session' => $sessionresult,
        ];
    }

    /** @return array<string, mixed> */
    private function certify_session(string $reference): array {
        $session = (new CommerceGuestCheckoutSessionRepository($this->database))->require_by_reference($reference);
        $checks = [];
        $checks[] = $this->check(
            'session.token',
            strlen($session->get_token()) === 64,
            'The durable token uses the expected 64-character format.'
        );
        $checks[] = $this->check(
            'session.currency',
            preg_match('/^[A-Z]{3}$/', $session->get_currency()) === 1,
            'The session currency is a valid three-letter code.'
        );
        $checks[] = $this->check(
            'session.identity',
            $session->get_user_id() !== null || $session->get_status() === 'identity_pending',
            'Resolved sessions have a Moodle user identity.'
        );

        if ($session->get_status() === 'active') {
            $userid = $session->get_user_id();
            $user = $userid === null ? false : $this->database->get_record('user', ['id' => $userid]);
            $checks[] = $this->check(
                'session.active_user',
                $user !== false && (int) $user->deleted === 0 && (int) $user->suspended === 0,
                'The active Guest Checkout session belongs to an active Moodle account.'
            );
            $checks[] = $this->check(
                'session.purchase',
                $session->get_purchase_reference() !== null,
                'The active session is attached to a Native Purchase.'
            );
            $checks[] = $this->check(
                'session.payment',
                $session->get_payment_reference() !== null,
                'The active session is attached to a Native Payment.'
            );
        }

        return [
            'reference' => $session->get_reference(),
            'status' => $session->get_status(),
            'checks' => $checks,
        ];
    }

    /** @return array<string, string> */
    private function source_check(string $key, string $relativepath, string $needle): array {
        $path = $this->plugindir . '/' . $relativepath;
        $source = is_file($path) ? file_get_contents($path) : false;
        return $this->check(
            $key,
            is_string($source) && str_contains($source, $needle),
            $relativepath . ' contains the required Guest Checkout integration.'
        );
    }

    /** @return array<string, string> */
    private function check(string $key, bool $passed, string $message): array {
        return ['key' => $key, 'status' => $passed ? 'OK' : 'FAIL', 'message' => $message];
    }
}
