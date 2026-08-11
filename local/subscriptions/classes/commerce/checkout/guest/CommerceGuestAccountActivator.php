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
        if ($session === null || !in_array($session->get_status(), ['payment_pending', 'paid_pending_activation'], true)) {
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
            require_once($CFG->dirroot . '/user/lib.php');
            $user->suspended = 0;
            $user->confirmed = 1;
            user_update_user($user, false, false);
            set_user_preference('auth_forcepasswordchange', 1, $userid);
            $this->send_activation_email($user, $session);
            $session = $this->sessions->require_by_reference($session->get_reference());
            $metadata = $session->get_metadata();
        }

        return $this->sessions->transition($session, 'active', [
            'expiresat' => 0,
            'metadatajson' => array_replace($metadata, [
                'account_state' => 'active',
                'activated_at' => time(),
                'activation_requires_password_reset' => $isprovisional,
            ]),
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
