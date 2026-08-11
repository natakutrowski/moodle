<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\checkout\guest;

defined('MOODLE_INTERNAL') || die();

/** Moodle persistence for Guest Checkout sessions. */
final class CommerceGuestCheckoutSessionRepository {
    private const TABLE = 'local_subs_commerce_guest';

    public function __construct(private readonly \moodle_database $database) {}

    public function create(string $currency, int $expiresat, array $metadata = []): CommerceGuestCheckoutSession {
        $now = time();
        $record = (object) [
            'reference' => 'gcs_' . bin2hex(random_bytes(12)),
            'token' => bin2hex(random_bytes(32)),
            'status' => 'identity_pending',
            'currency' => strtoupper(trim($currency)),
            'userid' => null,
            'email' => null,
            'firstname' => null,
            'lastname' => null,
            'purchasereference' => null,
            'paymentreference' => null,
            'expiresat' => $expiresat,
            'metadatajson' => $this->encode($metadata),
            'timecreated' => $now,
            'timemodified' => $now,
        ];
        $record->id = $this->database->insert_record(self::TABLE, $record);
        return new CommerceGuestCheckoutSession($record);
    }

    public function find_by_token(string $token): ?CommerceGuestCheckoutSession {
        $record = $this->database->get_record(self::TABLE, ['token' => trim($token)]);
        return $record === false ? null : new CommerceGuestCheckoutSession($record);
    }

    public function find_by_purchase_reference(string $reference): ?CommerceGuestCheckoutSession {
        $record = $this->database->get_record(self::TABLE, ['purchasereference' => trim($reference)]);
        return $record === false ? null : new CommerceGuestCheckoutSession($record);
    }

    public function find_by_user_id(int $userid, string $currency): ?CommerceGuestCheckoutSession {
        $record = $this->database->get_record_sql(
            'SELECT * FROM {' . self::TABLE . '} WHERE userid = :userid AND currency = :currency ORDER BY id DESC',
            ['userid' => $userid, 'currency' => strtoupper(trim($currency))],
            IGNORE_MULTIPLE
        );
        return $record === false ? null : new CommerceGuestCheckoutSession($record);
    }

    public function attach_payment(
        CommerceGuestCheckoutSession $session,
        string $purchasereference,
        string $paymentreference
    ): CommerceGuestCheckoutSession {
        return $this->transition($session, 'payment_pending', [
            'purchasereference' => trim($purchasereference),
            'paymentreference' => trim($paymentreference),
            'expiresat' => time() + CommerceGuestCheckoutService::PAYMENT_FAILURE_TTL,
            'metadatajson' => array_replace($session->get_metadata(), [
                'payment_started_at' => time(),
            ]),
        ]);
    }

    public function require_by_id(int $id): CommerceGuestCheckoutSession {
        $record = $this->database->get_record(self::TABLE, ['id' => $id], '*', MUST_EXIST);
        return new CommerceGuestCheckoutSession($record);
    }

    public function require_by_reference(string $reference): CommerceGuestCheckoutSession {
        $record = $this->database->get_record(self::TABLE, ['reference' => trim($reference)], '*', MUST_EXIST);
        return new CommerceGuestCheckoutSession($record);
    }

    public function update_identity(
        CommerceGuestCheckoutSession $session,
        ?int $userid,
        string $email,
        string $firstname,
        string $lastname,
        string $status,
        array $metadata = []
    ): CommerceGuestCheckoutSession {
        $record = (object) [
            'id' => $session->get_id(),
            'userid' => $userid,
            'email' => \core_text::strtolower(trim($email)),
            'firstname' => trim($firstname),
            'lastname' => trim($lastname),
            'status' => trim($status),
            'metadatajson' => $this->encode(array_replace($session->get_metadata(), $metadata)),
            'timemodified' => time(),
        ];
        $this->database->update_record(self::TABLE, $record);
        return $this->require_by_reference($session->get_reference());
    }

    public function transition(CommerceGuestCheckoutSession $session, string $status, array $fields = []): CommerceGuestCheckoutSession {
        $record = (object) array_replace($fields, [
            'id' => $session->get_id(),
            'status' => trim($status),
            'timemodified' => time(),
        ]);
        if (isset($fields['metadatajson']) && is_array($fields['metadatajson'])) {
            $record->metadatajson = $this->encode($fields['metadatajson']);
        }
        $this->database->update_record(self::TABLE, $record);
        return $this->require_by_reference($session->get_reference());
    }

    /** @return CommerceGuestCheckoutSession[] */
    public function find_expired(int $now): array {
        $records = $this->database->get_records_select(
            self::TABLE,
            'expiresat <= :now AND status NOT IN (:active, :purged)',
            ['now' => $now, 'active' => 'active', 'purged' => 'purged'],
            'expiresat ASC'
        );
        return array_map(static fn(\stdClass $record): CommerceGuestCheckoutSession => new CommerceGuestCheckoutSession($record), $records);
    }

    private function encode(array $metadata): string {
        $encoded = json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($encoded === false) {
            throw new \coding_exception('Unable to encode Guest Checkout metadata.');
        }
        return $encoded;
    }
}
