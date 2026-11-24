<?php

declare(strict_types=1);
namespace local_subscriptions\commerce\gift;

defined('MOODLE_INTERNAL') || die();

/**
 * G6B1: independently switched DEV autoqueue for paid prepared Advent gifts.
 * Enabling the commercial rehearsal alone NEVER enables this queue.
 * Existing G3D/EUR path remains compatible and separately gated.
 */
final class CommerceGiftPreparedAutoQueuePolicy {
    public const SWITCH = 'commerce_gifts_dev_autoqueue_prepared';

    public static function enabled(): bool {
        return CommerceGiftCommercialRuntimeGate::enabled()
            ? CommerceGiftDeliveryDispatchService::enabled()
                && (string)get_config('local_subscriptions', CommerceGiftCommercialRuntimeGate::AUTOQUEUE) === '1'
            : CommerceGiftDevE2EGate::is_dev_environment()
                && CommerceGiftCommercialDevGate::can_operate()
                && CommerceGiftDeliveryDispatchService::enabled()
                && (string)get_config('local_subscriptions', self::SWITCH) === '1';
    }

    /** Fail closed to delivery draft: never create a mail or activation token here. */
    public static function should_queue(\moodle_database $db, \stdClass $gift,
        \stdClass $draft, int $now): bool {
        if (!self::enabled() || $now <= 0 || (string)$gift->sourceproductsku !== 'AVENT_2026'
            || (string)$draft->deliverymode !== 'email'
            || !in_array((string)$draft->currency, ['EUR', 'RUB'], true)
            || (int)$draft->priceid <= 0
            || (int)$draft->priceid !== (int)$gift->sourcepriceid
            || (int)$draft->buyeruserid <= 2 || (int)$draft->buyeruserid !== (int)$gift->buyeruserid
            || !(((string)$draft->status === 'checkout_started' && $draft->giftid === null)
                || ((string)$draft->status === 'gift_issued'
                    && (int)($draft->giftid ?? 0) === (int)$gift->id))
            || (string)$gift->status !== CommerceGiftLifecycle::ISSUED
            || $gift->claimedat !== null || $gift->activatedat !== null
            || $gift->rightsholderuserid !== null
            || (int)($draft->scheduledat ?? 0) !== (int)($gift->scheduledat ?? 0)
            || (int)$draft->expiresat <= $now
            || !hash_equals(strtolower(trim((string)$draft->recipientemail)),
                strtolower(trim((string)$gift->intendedrecipientemail)))) {
            return false;
        }
        try {
            $snapshot = json_decode((string)$gift->purchasesnapshotjson, true);
            if (!is_array($snapshot)
                || (string)($snapshot['currency'] ?? '') !== (string)$draft->currency
                || (int)($snapshot['priceid'] ?? 0) !== (int)$draft->priceid) {
                return false;
            }
            // Commerce Native is the authority, not the browser or signed draft alone.
            // Checks settled payment, correct EUR/Stripe or RUB/Alfa provider,
            // anti-refund/duplicate evidence, original sealed intent and no buyer grant.
            (new CommerceGiftPaidSafety($db))->assert_still_paid($gift);
            (new CommerceGiftMailStudioInvitation($db))->assert_ready((string)$draft->locale);
            $email = CommerceGiftDevGuestRecipientPolicy::normalize(
                strtolower(trim((string)$draft->recipientemail)));
            $buyeremail = strtolower(trim((string)$gift->buyeremail));
            if (!hash_equals($buyeremail, strtolower(trim((string)$draft->buyeremail)))) {
                return false;
            }

            if (CommerceGiftCommercialRuntimeGate::enabled() || CommerceGiftCommercialDevGate::is_armed()) {
                CommerceGiftCommercialRuntimeGate::assert_buyer($db, (int)$gift->buyeruserid, $buyeremail);
                CommerceGiftCommercialRuntimeGate::assert_recipient($db,
                    (int)$gift->buyeruserid, $buyeremail, $email);
                return true; // Only preparation and queue; dispatch is still independently gated.
            }

            // Preserve legacy fixed-pair, EUR-only G3D pilot unchanged.
            if (!CommerceGiftDevE2EGate::is_armed() || (string)$draft->currency !== 'EUR'
                || (int)get_config('local_subscriptions', CommerceGiftDevE2EGate::BUYER_SETTING)
                    !== (int)$gift->buyeruserid) {
                return false;
            }
            $recipientid = (int)get_config('local_subscriptions', CommerceGiftDevE2EGate::RECIPIENT_SETTING);
            $recipient = $db->get_record('user', ['id' => $recipientid, 'deleted' => 0],
                'id,email,confirmed,suspended', IGNORE_MISSING);
            if ($recipient && !empty($recipient->confirmed) && empty($recipient->suspended)
                && $recipientid !== (int)$gift->buyeruserid && !is_siteadmin($recipientid)
                && hash_equals(strtolower(trim((string)$recipient->email)), $email)) {
                return true;
            }
            CommerceGiftDevGuestRecipientPolicy::assert_allowed($db,
                (int)$gift->buyeruserid, $buyeremail, $email, $now);
            $due = max($now, (int)($draft->scheduledat ?? 0));
            return (int)get_config('local_subscriptions',
                CommerceGiftDevGuestRecipientPolicy::EXPIRES) > $due;
        } catch (\Throwable $ignored) {
            return false;
        }
    }
}
