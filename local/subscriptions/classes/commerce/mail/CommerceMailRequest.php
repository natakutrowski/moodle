<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail;

defined('MOODLE_INTERNAL') || die();

/**
 * Intent submitted to the transactional mail core.
 */
final class CommerceMailRequest {

    private readonly string $type;
    private readonly string $language;
    private readonly string $idempotencykey;

    public function __construct(
        string $type,
        private readonly CommerceMailRecipient $recipient,
        private readonly CommerceMailContext $context,
        string $language,
        string $idempotencykey,
        private readonly ?int $purchaseid = null
    ) {
        $this->type = CommerceMailType::normalise($type);
        $this->language = self::normalise_language($language);
        $this->idempotencykey = CommerceMailIdempotencyKey::normalise($idempotencykey);

        if ($purchaseid !== null && $purchaseid <= 0) {
            throw new \coding_exception(
                'A Commerce transactional mail purchase ID must be positive.'
            );
        }
    }

    public function get_type(): string {
        return $this->type;
    }

    public function get_recipient(): CommerceMailRecipient {
        return $this->recipient;
    }

    public function get_context(): CommerceMailContext {
        return $this->context;
    }

    public function get_language(): string {
        return $this->language;
    }

    public function get_idempotency_key(): string {
        return $this->idempotencykey;
    }

    public function get_purchase_id(): ?int {
        return $this->purchaseid;
    }

    private static function normalise_language(string $language): string {
        $language = strtolower(trim($language));

        if ($language === '' || !preg_match('/^[a-z]{2,3}(?:_[a-z0-9]+)?$/', $language)) {
            throw new \coding_exception(
                'A Commerce transactional mail request requires a valid Moodle language code.'
            );
        }

        return $language;
    }
}
