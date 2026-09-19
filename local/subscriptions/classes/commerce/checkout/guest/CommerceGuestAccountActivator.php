<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\checkout\guest;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\mail\CommerceMailContext;
use local_subscriptions\commerce\mail\CommerceMailIdempotencyKey;
use local_subscriptions\commerce\mail\CommerceMailRecipient;
use local_subscriptions\commerce\mail\CommerceMailRequest;
use local_subscriptions\commerce\mail\CommerceMailRuntime;
use local_subscriptions\commerce\mail\CommerceMailType;
use local_subscriptions\commerce\persistence\CommercePersistenceSchema;

/** Activates a provisional Moodle account after a successful Native payment. */
final class CommerceGuestAccountActivator {
    public function __construct(
        private readonly \moodle_database $database,
        private readonly CommerceGuestCheckoutSessionRepository $sessions
    ) {}

    public function activate_for_purchase(string $purchasereference): ?CommerceGuestCheckoutSession {
        global $CFG;

        $session = $this->sessions->find_by_purchase_reference($purchasereference);
        if ($session === null) {
            return null;
        }

        // L7.3.10: the Guest Checkout session status is not an account-activation
        // state. Cart/result reconciliation may already have moved the durable
        // checkout row to active while the provisional Moodle user is still
        // suspended/unconfirmed. A successful payment must therefore reconcile
        // the account from its own durable metadata/user state, not only from
        // payment_pending / paid_pending_activation.
        if (!in_array(
            $session->get_status(),
            ['payment_pending', 'paid_pending_activation', 'payment_failed', 'active'],
            true
        )) {
            return $session;
        }

        $userid = $session->get_user_id();
        if ($userid === null) {
            throw new \RuntimeException('Guest Checkout activation requires a resolved Moodle user.');
        }

        $user = $this->database->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);
        $metadata = $session->get_metadata();
        $isprovisional = ($metadata['account_origin'] ?? '') === 'guest_checkout';

        if ($isprovisional) {
            $passwordisset = !empty($metadata['password_set_at']);
            $accountstate = (string)($metadata['account_state'] ?? '');
            $userneedsactivation = (int)$user->suspended !== 0 || (int)$user->confirmed !== 1;
            $stateneedsactivation = !in_array($accountstate, ['active', 'ready'], true);

            if ($userneedsactivation || $stateneedsactivation) {
                require_once($CFG->dirroot . '/user/lib.php');
                $user->suspended = 0;
                $user->confirmed = 1;
                user_update_user($user, false, false);

                // A normal freshly-paid provisional account still needs to let
                // the customer choose a password. If a previous activation
                // attempt already stored that password but failed before login,
                // repair the Moodle account without forcing another reset/email.
                if (!$passwordisset) {
                    set_user_preference('auth_forcepasswordchange', 1, $userid);
                    $this->send_activation_email($user, $session);
                    $session = $this->sessions->require_by_reference($session->get_reference());
                    $metadata = $session->get_metadata();
                }
            }

            $metadata = array_replace($metadata, [
                'account_state' => $passwordisset ? 'ready' : 'active',
                'activated_at' => (int)($metadata['activated_at'] ?? 0) > 0
                    ? (int)$metadata['activated_at']
                    : time(),
                'activation_requires_password_reset' => !$passwordisset,
            ]);
        }

        return $this->sessions->transition($session, 'active', [
            'expiresat' => 0,
            'metadatajson' => $metadata,
        ]);
    }
    private function send_activation_email(\stdClass $user, CommerceGuestCheckoutSession $session): void {
        $activationurl = (new CommerceGuestAccountActivationService($this->database, $this->sessions))
            ->issue_activation_url($session);

        $purchasereference = (string)($session->get_purchase_reference() ?? '');
        $purchaseid = null;
        $publicreference = '';
        if ($purchasereference !== '') {
            $purchase = $this->database->get_record(
                CommercePersistenceSchema::TABLE_PURCHASE,
                ['reference' => $purchasereference],
                'id, metadatajson',
                IGNORE_MISSING
            );
            if ($purchase !== false) {
                $purchaseid = (int)$purchase->id;
                $metadata = json_decode((string)($purchase->metadatajson ?? ''), true);
                if (is_array($metadata)) {
                    $publicreference = trim((string)($metadata['commercialreference'] ?? $metadata['publicreference'] ?? ''));
                }
            }
        }

        $request = new CommerceMailRequest(
            CommerceMailType::ACCOUNT_ACTIVATION,
            new CommerceMailRecipient(
                (string)$user->email,
                fullname($user),
                (int)$user->id
            ),
            new CommerceMailContext([
                'customer' => [
                    'firstname' => (string)$user->firstname,
                    'fullname' => fullname($user),
                ],
                'purchase' => [
                    'reference' => $publicreference,
                ],
                'activationurl' => $activationurl->out(false),
                'activationexpires' => userdate(time() + CommerceGuestAccountActivationService::KEY_TTL),
                'links' => [],
            ]),
            clean_param((string)($user->lang ?? current_language()), PARAM_LANG),
            CommerceMailIdempotencyKey::normalise(
                'guest-account-activation:' . $session->get_id() . ':' . hash('sha256', $activationurl->out(false))
            ),
            $purchaseid
        );

        $record = CommerceMailRuntime::queue_service()->queue($request);
        CommerceMailRuntime::processor()->process_ids([(int)$record->id]);
    }


}
