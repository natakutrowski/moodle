<?php

declare(strict_types=1);
namespace local_subscriptions\commerce\gift;

defined('MOODLE_INTERNAL') || die();

/**
 * DEV-only immutable handoff of a validated pre-checkout gift to a paid Native
 * gift. No tokens, entitlement grants, payment attempts, or email dispatched.
 * The line UUID is included inside the signed G3 envelope and joined on issuance.
 */
final class CommerceGiftPreparedCheckoutService {
    public const TABLE = 'local_subs_commerce_gift_precheckout';

    public function __construct(private readonly \moodle_database $db) {}

    /** @param array<string,string> $values */
    public function prepare(int $buyerid, string $buyeremail, array $values, int $scheduledat,
        int $priceid, int $now): \stdClass {
        CommerceGiftCommercialRuntimeGate::assert_buyer($this->db, $buyerid, $buyeremail);
        if ($buyerid <= 2 || $priceid <= 0 || $now <= 0
            || !filter_var($buyeremail, FILTER_VALIDATE_EMAIL)) {
            throw new \DomainException('Invalid prepared gift source identity.');
        }
        $check = CommerceGiftPrepareInputValidator::validate($values, $now);
        if ($check['errors']) {
            throw new \DomainException('Gift preparation contains invalid fields.');
        }
        $v = $check['values'];
        if ($v['deliverymode'] !== 'email'
            || $check['scheduledat'] !== ($scheduledat === 0 ? null : $scheduledat)) {
            throw new \DomainException('DEV gift rehearsal only supports email delivery.');
        }
        // The stored price comes from Native. Never trust a posted price or PSP.
        $hydrator = new \local_subscriptions\commerce\catalog\persistence\CommerceCatalogHydrator();
        $products = new \local_subscriptions\commerce\catalog\repository\CommerceProductRepository($this->db, $hydrator);
        $prices = new \local_subscriptions\commerce\catalog\repository\CommerceProductPriceRepository(
            $this->db, $hydrator, $products);
        $nativeprice = CommerceGiftPaymentProviderReadiness::resolve_price($prices, $v['currency']);
        if ($nativeprice->get_id() !== $priceid) {
            throw new \DomainException('Prepared gift price differs from Native catalogue.');
        }
        // Preserve G3D's existing confirmed fictitious recipient pathway.
        // The additional *unregistered* mailbox pathway is a separate, short-
        // lived, explicit DEV opt-in. It never creates a Moodle user, permits
        // an existing account, or changes paid checkout/grant policy.
        $recipientid = (int)get_config('local_subscriptions', CommerceGiftDevE2EGate::RECIPIENT_SETTING);
        $recipient = $recipientid > 2
            ? $this->db->get_record('user', ['id' => $recipientid, 'deleted' => 0],
                'id,email,confirmed,suspended', IGNORE_MISSING) : false;
        $legacytestrecipient = $recipient && !empty($recipient->confirmed)
            && empty($recipient->suspended) && !is_siteadmin($recipientid)
            && $recipientid !== $buyerid
            && hash_equals(strtolower(trim((string)$recipient->email)), $v['recipientemail']);
        if (CommerceGiftCommercialRuntimeGate::enabled() || CommerceGiftCommercialDevGate::is_armed()) {
            // Always enforce the real recipient policy, including if the email
            // happens to coincide with G3D's old fixed test recipient.
            CommerceGiftCommercialRuntimeGate::assert_recipient(
                $this->db, $buyerid, $buyeremail, $v['recipientemail']);
        } else if (!$legacytestrecipient) {
            CommerceGiftDevGuestRecipientPolicy::assert_allowed(
                $this->db, $buyerid, $buyeremail, $v['recipientemail'], $now);
        }
        $message = CommerceGiftDeliveryDraftService::normalize_note($v['personalmessage']);
        $locale = CommerceGiftDeliveryDraftService::normalize_locale($v['locale']);
        $expiry = max($now + 60 * 86400, ($scheduledat ?: $now) + 86400);
        $uuid = bin2hex(random_bytes(16));
        $record = (object)[
            'draftuuid' => $uuid,
            'lineuuid' => $uuid,
            'buyeruserid' => $buyerid,
            'buyeremail' => strtolower(trim($buyeremail)),
            'recipientname' => $v['recipientname'],
            'recipientemail' => $v['recipientemail'],
            'deliverymode' => 'email',
            'message' => $message,
            'locale' => $locale,
            'currency' => $v['currency'],
            'priceid' => $priceid,
            'scheduledat' => $scheduledat ?: null,
            'expiresat' => $expiry,
            'status' => 'checkout_started',
            'giftid' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ];
        $record->id = (int)$this->db->insert_record(self::TABLE, $record);
        return $record;
    }

    /**
     * Called only after G3 verifies a settled Native payment and a signed
     * immutable intent. Must run inside the same issuance transaction so the
     * message and the gift cannot become inconsistent on partial failures.
     * Legacy DEV gifts with no pre-checkout row are unchanged.
     *
     * @param array<string,mixed> $intent
     */
    public function attach_paid_gift(\stdClass $gift, \stdClass $purchase, array $intent): void {
        $lineuuid = (string)($intent['lineuuid'] ?? '');
        if (preg_match('/\A[a-f0-9]{32}\z/D', $lineuuid) !== 1) {
            throw new \DomainException('Gift line UUID is invalid.');
        }
        $draft = $this->db->get_record(self::TABLE, ['lineuuid' => $lineuuid], '*', IGNORE_MISSING);
        if (!$draft) { return; } // Existing G3D gifts do not have a prepared draft.
        if ((string)$draft->status !== 'checkout_started' || $draft->giftid !== null
            || (int)$draft->buyeruserid !== (int)$gift->buyeruserid
            || !hash_equals(strtolower((string)$draft->buyeremail), strtolower((string)$purchase->customeremail))
            || !hash_equals(strtolower((string)$draft->recipientemail), (string)$intent['recipientemail'])
            || (string)$draft->deliverymode !== (string)$intent['deliverymode']
            || (int)($draft->scheduledat ?? 0) !== (int)($intent['scheduledat'] ?? 0)
            || (int)$draft->expiresat !== (int)$intent['expiresat']
            || (string)$draft->currency !== strtoupper((string)$purchase->currency)
        ) {
            throw new \DomainException('Prepared checkout does not match paid gift identity.');
        }
        if ((int)$draft->priceid !== (int)$gift->sourcepriceid
            || !hash_equals(strtolower((string)$draft->recipientemail), strtolower((string)$gift->intendedrecipientemail))) {
            throw new \DomainException('Gift prepared price or beneficiary changed after checkout.');
        }
        if ($this->db->record_exists(CommerceGiftDeliveryDraftService::TABLE, ['giftid' => (int)$gift->id])) {
            throw new \DomainException('Paid gift delivery already exists unexpectedly.');
        }
        $now = time();
        // Default = draft. Auto-queue is a completely separate DEV opt-in,
        // requiring an armed G3D, enabled delivery and an active Mail Studio
        // model. No SMTP is performed inside the paid issuance transaction.
        $autoqueue = CommerceGiftPreparedAutoQueuePolicy::should_queue(
            $this->db, $gift, $draft, $now);
        $this->db->insert_record(CommerceGiftDeliveryDraftService::TABLE, (object)[
            'giftid' => (int)$gift->id,
            'message' => (string)$draft->message,
            'locale' => (string)$draft->locale,
            'status' => $autoqueue ? 'queued' : 'draft',
            'queuedat' => $autoqueue ? $now : null,
            'timecreated' => $now,
            'timemodified' => $now,
            'attempts' => 0,
        ]);
        if ($autoqueue) {
            $this->db->insert_record(CommerceGiftSchema::TABLE_EVENT, (object)[
                'giftid' => (int)$gift->id,
                'eventtype' => 'delivery_queued', 'actoruserid' => null,
                'occurredat' => $now,
                'idempotencykey' => hash('sha256',
                    'campusfr:g5b4b3:autoqueue:v1:' . (string)$gift->giftuuid),
                'detailjson' => '{"mode":"email","origin":"prepared_checkout"}',
            ]);
        }
        $this->db->update_record(self::TABLE, (object)[
            'id' => (int)$draft->id, 'status' => 'gift_issued',
            'giftid' => (int)$gift->id, 'timemodified' => $now,
        ]);
    }
}
