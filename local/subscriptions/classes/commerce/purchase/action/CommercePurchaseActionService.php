<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\purchase\action;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\admin\AdminEvents;
use local_subscriptions\admin\AdminLog;
use local_subscriptions\commerce\entitlement\persistence\CommerceEntitlementGrantRecordMapper;
use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrantPlan;
use local_subscriptions\commerce\entitlement\persistence\CommerceEntitlementGrantPersister;
use local_subscriptions\commerce\fulfillment\native\checkout\CommerceNativePurchaseGrantPlanner;
use local_subscriptions\commerce\entitlement\persistence\CommerceEntitlementGrantRepository;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentContext;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentHandlerRegistry;
use local_subscriptions\commerce\fulfillment\native\batch\CommerceNativePurchaseFulfillmentOrchestrator;
use local_subscriptions\commerce\fulfillment\native\course\CommerceCourseAccessFulfillmentHandler;
use local_subscriptions\commerce\fulfillment\native\digital\CommerceDigitalDownloadFulfillmentHandler;
use local_subscriptions\commerce\fulfillment\native\persistence\CommercePersistentNativeFulfillmentExecutor;
use local_subscriptions\commerce\fulfillment\native\persistence\MoodleCommerceNativeFulfillmentPersistenceRepository;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseDetails;
use local_subscriptions\commerce\persistence\CommercePersistenceSchema;

/** Safe Native command façade used by the unified CRM purchase UI. */
final class CommercePurchaseActionService {
    public function __construct(
        private readonly \moodle_database $db,
        private readonly CommercePurchaseActionPolicy $policy = new CommercePurchaseActionPolicy()
    ) {
    }

    public function process_fulfillment(CommercePurchaseDetails $purchase, int $actoruserid): CommercePurchaseActionResult {
        if ($this->is_closed_without_fulfillment($purchase->summary->id)) {
            throw new \moodle_exception('commerce_purchase_action_not_allowed', 'local_subscriptions');
        }
        if (!$this->policy->can_retry_fulfillment($purchase)) {
            throw new \moodle_exception('commerce_purchase_action_not_allowed', 'local_subscriptions');
        }
        $isinitial = $purchase->fulfillments === [];
        $action = $isinitial ? 'start_fulfillment' : 'retry_fulfillment';
        $executionreference = 'crm-' . ($isinitial ? 'start' : 'retry') . '-'
            . substr(hash('sha256', $purchase->summary->uuid . '|' . $actoruserid), 0, 24);
        $mapper = new CommerceEntitlementGrantRecordMapper();
        $repository = new CommerceEntitlementGrantRepository($this->db, $mapper);

        if ($repository->find_by_purchase_reference($purchase->summary->reference) === []) {
            if (!$this->has_confirmed_payment($purchase->summary->id)) {
                throw new \moodle_exception(
                    'commerce_purchase_action_not_allowed',
                    'local_subscriptions'
                );
            }

            $purchaserecord = $this->db->get_record(
                CommercePersistenceSchema::TABLE_PURCHASE,
                ['id' => $purchase->summary->id],
                '*',
                MUST_EXIST
            );
            $itemrecords = array_values($this->db->get_records(
                CommercePersistenceSchema::TABLE_ITEM,
                ['purchaseid' => $purchase->summary->id],
                'position ASC, id ASC'
            ));
            $grantplan = (new CommerceNativePurchaseGrantPlanner($this->db))->plan(
                $purchaserecord,
                $itemrecords
            );
            if ($grantplan->is_empty()) {
                return new CommercePurchaseActionResult(false, false, 'missing_grants', [
                    'count' => 0,
                    'mode' => $isinitial ? 'start' : 'retry',
                ]);
            }

            (new CommerceEntitlementGrantPersister($this->db, $repository))->persist($grantplan);
        }

        $registry = new CommerceNativeFulfillmentHandlerRegistry([
            new CommerceCourseAccessFulfillmentHandler(),
            new CommerceDigitalDownloadFulfillmentHandler(),
        ]);
        $orchestrator = new CommerceNativePurchaseFulfillmentOrchestrator(
            $repository,
            $mapper,
            new CommercePersistentNativeFulfillmentExecutor($registry, new MoodleCommerceNativeFulfillmentPersistenceRepository())
        );
        $result = $orchestrator->execute_purchase(
            $purchase->summary->reference,
            CommerceNativeFulfillmentContext::runtime($executionreference, time(), $actoruserid, 'crm_purchase_action', [
                'purchaseid' => $purchase->summary->id,
                'action' => $action,
            ])
        );
        if ($result->count() === 0) {
            return new CommercePurchaseActionResult(false, false, 'missing_grants', [
                'count' => 0,
                'mode' => $isinitial ? 'start' : 'retry',
            ]);
        }

        $successful = $result->is_successful();
        if ($successful) {
            $this->mark_purchase_fulfilled($purchase->summary->id);
        }

        AdminLog::log(AdminEvents::COMMERCE_PURCHASE_FULFILLMENT_RETRIED, $purchase->summary->customer->userid, 'commerce_purchase', $purchase->summary->id, [
            'reference' => $purchase->summary->reference,
            'executionreference' => $executionreference,
            'successful' => $successful,
            'resultcount' => $result->count(),
            'mode' => $isinitial ? 'start' : 'retry',
        ]);
        return new CommercePurchaseActionResult(
            $successful,
            false,
            $successful ? 'completed' : 'failed',
            ['count' => $result->count(), 'mode' => $isinitial ? 'start' : 'retry']
        );
    }

    private function has_confirmed_payment(int $purchaseid): bool {
        return $this->db->record_exists_select(
            CommercePersistenceSchema::TABLE_PAYMENT,
            'purchaseid = :purchaseid AND status IN (:paid, :completed)',
            [
                'purchaseid' => $purchaseid,
                'paid' => 'paid',
                'completed' => 'completed',
            ]
        );
    }

    private function mark_purchase_fulfilled(int $purchaseid): void {
        $this->db->update_record(CommercePersistenceSchema::TABLE_PURCHASE, (object) [
            'id' => $purchaseid,
            'status' => 'fulfilled',
            'timemodified' => time(),
        ]);
    }

    public function close_without_fulfillment(
        CommercePurchaseDetails $purchase,
        int $actoruserid
    ): CommercePurchaseActionResult {
        if ($this->is_closed_without_fulfillment($purchase->summary->id)) {
            return new CommercePurchaseActionResult(true, true, 'closed_without_fulfillment');
        }

        $record = $this->db->get_record(
            CommercePersistenceSchema::TABLE_PURCHASE,
            ['id' => $purchase->summary->id],
            'id, metadatajson',
            MUST_EXIST
        );
        $metadata = $this->decode_metadata((string)($record->metadatajson ?? ''));
        $metadata['fulfillment_resolution'] = 'closed_without_fulfillment';
        $metadata['fulfillment_resolution_reason'] = 'missing_native_entitlement_grants';
        $metadata['fulfillment_resolved_by'] = $actoruserid;
        $metadata['fulfillment_resolved_at'] = time();

        $this->db->update_record(CommercePersistenceSchema::TABLE_PURCHASE, (object)[
            'id' => $purchase->summary->id,
            'metadatajson' => json_encode(
                $metadata,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
            ),
        ]);

        AdminLog::log(
            AdminEvents::COMMERCE_PURCHASE_FULFILLMENT_CLOSED_WITHOUT_DELIVERY,
            $purchase->summary->customer->userid,
            'commerce_purchase',
            $purchase->summary->id,
            [
                'reference' => $purchase->summary->reference,
                'actoruserid' => $actoruserid,
                'reason' => 'missing_native_entitlement_grants',
            ]
        );

        return new CommercePurchaseActionResult(true, false, 'closed_without_fulfillment');
    }

    /**
     * Return purchase IDs explicitly closed without fulfillment.
     *
     * @param int[] $purchaseids
     * @return int[]
     */
    public function closed_without_fulfillment_ids(array $purchaseids): array {
        $purchaseids = array_values(array_unique(array_filter(
            array_map('intval', $purchaseids),
            static fn(int $purchaseid): bool => $purchaseid > 0
        )));
        if ($purchaseids === []) {
            return [];
        }

        [$insql, $params] = $this->db->get_in_or_equal($purchaseids, SQL_PARAMS_NAMED, 'purchaseid');
        $records = $this->db->get_records_select(
            CommercePersistenceSchema::TABLE_PURCHASE,
            'id ' . $insql,
            $params,
            '',
            'id, metadatajson'
        );

        $closed = [];
        foreach ($records as $record) {
            $metadata = $this->decode_metadata((string)($record->metadatajson ?? ''));
            if (($metadata['fulfillment_resolution'] ?? null) === 'closed_without_fulfillment') {
                $closed[] = (int)$record->id;
            }
        }

        return $closed;
    }

    public function is_closed_without_fulfillment(int $purchaseid): bool {
        $metadatajson = $this->db->get_field(
            CommercePersistenceSchema::TABLE_PURCHASE,
            'metadatajson',
            ['id' => $purchaseid],
            IGNORE_MISSING
        );
        if ($metadatajson === false) {
            return false;
        }

        $metadata = $this->decode_metadata((string)$metadatajson);
        return ($metadata['fulfillment_resolution'] ?? null) === 'closed_without_fulfillment';
    }

    /** @return array<string, mixed> */
    private function decode_metadata(string $metadatajson): array {
        if (trim($metadatajson) === '') {
            return [];
        }

        try {
            $metadata = json_decode($metadatajson, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        return is_array($metadata) ? $metadata : [];
    }

    public function add_note(CommercePurchaseDetails $purchase, int $actoruserid, string $note): CommercePurchaseActionResult {
        $note = trim($note);
        if (!$this->policy->can_add_note($purchase) || $note === '') {
            throw new \moodle_exception('commerce_purchase_note_required', 'local_subscriptions');
        }
        if (\core_text::strlen($note) > 2000) {
            throw new \moodle_exception('commerce_purchase_note_too_long', 'local_subscriptions');
        }
        AdminLog::log(AdminEvents::COMMERCE_PURCHASE_NOTE_ADDED, $purchase->summary->customer->userid, 'commerce_purchase', $purchase->summary->id, [
            'reference' => $purchase->summary->reference,
            'note' => $note,
            'actoruserid' => $actoruserid,
        ]);
        return new CommercePurchaseActionResult(true, false, 'noted');
    }
}
