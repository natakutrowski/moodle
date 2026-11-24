<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\gift;

defined('MOODLE_INTERNAL') || die();

/** Pure, strict G3C policy; it does not authenticate callers or write grants. */
final class CommerceGiftActivationPolicy {
    private const CLAIMABLE = [
        CommerceGiftLifecycle::ISSUED, CommerceGiftLifecycle::SCHEDULED,
        CommerceGiftLifecycle::NOTIFIED, CommerceGiftLifecycle::DELIVERY_FAILED,
    ];

    public static function assert_claimable(\stdClass $gift, \stdClass $token, int $now): void {
        if (!in_array((string)$gift->status, self::CLAIMABLE, true)
            || (int)$token->giftid !== (int)$gift->id
            || $token->usedat !== null || $token->revokedat !== null
            || (int)$token->issuedat > $now
            || ($token->expiresat !== null && (int)$token->expiresat <= $now)
            || ($gift->expiresat !== null && (int)$gift->expiresat <= $now)
            || ($gift->scheduledat !== null && (int)$gift->scheduledat > $now)
            || $gift->claimedat !== null || $gift->rightsholderuserid !== null
            || $gift->intendedrecipientuserid !== null) {
            throw new \DomainException('Gift invitation cannot be claimed.');
        }
        CommerceGiftLifecycle::assert_transition((string)$gift->status, CommerceGiftLifecycle::CLAIMED);
    }

    public static function assert_issuable(\stdClass $gift, int $now): void {
        if (!in_array((string)$gift->status, self::CLAIMABLE, true)
            || $gift->claimedat !== null || $gift->rightsholderuserid !== null
            || ($gift->expiresat !== null && (int)$gift->expiresat <= $now)
            || ($gift->scheduledat !== null && (int)$gift->scheduledat > $now)) {
            throw new \DomainException('Gift invitation cannot be issued at this time.');
        }
    }

    public static function assert_activation(\stdClass $gift, int $userid): void {
        if (!in_array((string)$gift->status,
            [CommerceGiftLifecycle::CLAIMED, CommerceGiftLifecycle::GRANT_FAILED], true)
            || (int)$gift->intendedrecipientuserid !== $userid || $userid <= 2
            || $gift->claimedat === null || $gift->rightsholderuserid !== null
            || $gift->activatedat !== null) {
            throw new \DomainException('Gift is not reserved for this beneficiary.');
        }
        CommerceGiftLifecycle::assert_transition((string)$gift->status, CommerceGiftLifecycle::GRANT_PENDING);
    }

    /** @return array{type:string,sku:string,resourcekey:string,quantity:int,durationseconds:?int} */
    public static function reader_right(\stdClass $gift): array {
        $rights = json_decode((string)$gift->rightssnapshotjson, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($rights) || count($rights) !== 1 || !isset($rights[0])
            || !is_array($rights[0])) {
            throw new \DomainException('Unsupported gift rights snapshot.');
        }
        $r = $rights[0];
        $required = ['type', 'sku', 'resourcekey', 'quantity', 'durationseconds'];
        $keys = array_keys($r);
        sort($keys);
        sort($required);
        $duration = $r['durationseconds'] ?? null;
        if ($keys !== $required || ($r['type'] ?? null) !== 'reader_access'
            || ($r['sku'] ?? null) !== 'AVENT_2026'
            || ($r['resourcekey'] ?? null) !== 'reader:publication:avent-2026'
            || ($r['quantity'] ?? null) !== 1
            || ($duration !== null && (!is_int($duration) || $duration < 1))
            || (string)$gift->sourceproductsku !== 'AVENT_2026') {
            throw new \DomainException('Gift rights do not match the Reader pilot.');
        }
        return $r;
    }
}
