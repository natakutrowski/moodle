<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\checkout\guest;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\payment\reconciliation\alfa\AlfaPaymentReconciliationService;
use local_subscriptions\commerce\payment\reconciliation\stripe\StripePaymentReconciliationService;
use local_subscriptions\commerce\persistence\CommercePersistenceSchema;
use local_subscriptions\payment\Provider;

final class CommerceUnfinishedGuestCheckoutCrmService {
    public function __construct(
        private readonly \moodle_database $database,
        private readonly CommerceUnfinishedGuestCheckoutRecoveryService $recovery,
        private readonly CommerceGuestCheckoutSessionRepository $sessions
    ) {
    }

    public static function create(): self {
        global $DB;
        return new self(
            $DB,
            CommerceUnfinishedGuestCheckoutRecoveryService::create(),
            new CommerceGuestCheckoutSessionRepository($DB)
        );
    }

    /** @return array<int,array<string,mixed>> */
    public function queue(?string $email = null): array {
        $rows = [];
        foreach ($this->recovery->audit($email) as $candidate) {
            $candidate['classification'] = $this->classify($candidate);
            $candidate['payments'] = $this->decorate_provider_statuses(
                $this->payments_for_user((int)$candidate['userid'])
            );
            $candidate['age'] = $this->source_age((int)$candidate['source_session_id']);
            $candidate['user360url'] = (new \moodle_url(
                '/local/subscriptions/admin/users/view.php',
                ['id' => (int)$candidate['userid']]
            ))->out(false);
            $rows[] = $candidate;
        }

        usort($rows, static function(array $left, array $right): int {
            $weight = [
                'provider_paid_pending' => 500,
                'multiple_pending' => 400,
                'pending_purchase' => 300,
                'stuck_identity' => 250,
                'provisional_no_purchase' => 100,
            ];
            return ($weight[$right['classification']] ?? 0)
                <=> ($weight[$left['classification']] ?? 0);
        });

        return $rows;
    }

    public function candidate_for_user(int $userid): ?array {
        foreach ($this->queue() as $candidate) {
            if ((int)$candidate['userid'] === $userid) {
                return $candidate;
            }
        }
        return null;
    }

    public function repair_user(int $userid): array {
        $candidate = $this->candidate_for_user($userid);
        if ($candidate === null) {
            throw new \moodle_exception('commerce_guest_crm_candidate_not_found', 'local_subscriptions');
        }
        return $this->recovery->repair_stuck_sessions((string)$candidate['email']);
    }

    public function select_resume_purchase(int $userid, string $reference): void {
        $candidate = $this->candidate_for_user($userid);
        if ($candidate === null) {
            throw new \moodle_exception('commerce_guest_crm_candidate_not_found', 'local_subscriptions');
        }

        $purchase = $this->database->get_record(
            CommercePersistenceSchema::TABLE_PURCHASE,
            [
                'reference' => $reference,
                'userid' => $userid,
                'status' => 'payment_pending',
            ],
            '*',
            MUST_EXIST
        );

        $source = $this->recovery->find_source_session($userid);
        if ($source === null) {
            throw new \moodle_exception('commerce_guest_crm_source_not_found', 'local_subscriptions');
        }

        $metadata = array_replace($source->get_metadata(), [
            'resume_purchase_reference' => $reference,
            'm93_resume_selected_at' => time(),
        ]);

        $this->sessions->update_identity(
            $source,
            $userid,
            (string)$source->get_email(),
            (string)$source->get_first_name(),
            (string)$source->get_last_name(),
            $source->get_status(),
            $metadata
        );

        $this->database->set_field(
            'local_subs_commerce_guest',
            'purchasereference',
            $reference,
            ['id' => $source->get_id()]
        );
        $this->database->set_field(
            'local_subs_commerce_guest',
            'paymentreference',
            $reference,
            ['id' => $source->get_id()]
        );
    }

    public function reconcile_payment(int $userid, int $paymentid): array {
        $payment = $this->database->get_record_sql(
            'SELECT pay.*, p.userid, p.reference AS purchasereference
               FROM {' . CommercePersistenceSchema::TABLE_PAYMENT . '} pay
               JOIN {' . CommercePersistenceSchema::TABLE_PURCHASE . '} p ON p.id = pay.purchaseid
              WHERE pay.id = :paymentid AND p.userid = :userid',
            ['paymentid' => $paymentid, 'userid' => $userid],
            MUST_EXIST
        );

        $provider = strtolower((string)$payment->provider);
        if ($provider === Provider::ALFA) {
            $inspection = AlfaPaymentReconciliationService::create($this->database)
                ->reconcile_payment($paymentid);
        } elseif ($provider === Provider::STRIPE) {
            $inspection = StripePaymentReconciliationService::create($this->database)
                ->reconcile_payment($paymentid);
        } else {
            throw new \moodle_exception('commerce_guest_crm_provider_not_supported', 'local_subscriptions');
        }

        return [
            'provider' => $provider,
            'purchase' => (string)$payment->purchasereference,
            'complete' => (bool)$inspection->alreadycomplete,
        ];
    }

    private function classify(array $candidate): string {
        $pending = array_values(array_filter(
            $candidate['purchases'],
            static fn($purchase): bool => (string)$purchase->status === 'payment_pending'
        ));

        if (count($pending) > 1) {
            return 'multiple_pending';
        }
        if (count($pending) === 1) {
            return 'pending_purchase';
        }
        if ((int)$candidate['stuck_sessions'] > 0) {
            return 'stuck_identity';
        }
        return 'provisional_no_purchase';
    }

    /** @return array<int,\stdClass> */
    private function payments_for_user(int $userid): array {
        $sql = 'SELECT pay.id, pay.purchaseid, pay.sequence, pay.provider, pay.status,
                       pay.currency, pay.amountminor, pay.providerreference,
                       pay.providerorderid, pay.timecreated, pay.timemodified,
                       p.reference AS purchasereference, p.status AS purchasestatus
                  FROM {' . CommercePersistenceSchema::TABLE_PAYMENT . '} pay
                  JOIN {' . CommercePersistenceSchema::TABLE_PURCHASE . '} p ON p.id = pay.purchaseid
                 WHERE p.userid = :userid
              ORDER BY pay.timecreated DESC, pay.id DESC';

        return array_values($this->database->get_records_sql($sql, ['userid' => $userid]));
    }


    /**
     * Add a best-effort, read-only provider snapshot to pending payment rows.
     *
     * Provider failures must never make the CRM queue unavailable. No Campus
     * payment/purchase state is changed here; reconciliation remains an
     * explicit administrator action.
     *
     * @param array<int,\stdClass> $payments
     * @return array<int,\stdClass>
     */
    private function decorate_provider_statuses(array $payments): array {
        foreach ($payments as $payment) {
            $payment->providerlivechecked = false;
            $payment->providerlivepaid = false;
            $payment->providerlivestatus = '';
            $payment->providerliveerror = false;

            if (!in_array((string)$payment->status, ['created', 'redirected', 'pending'], true)) {
                continue;
            }

            $provider = strtolower((string)$payment->provider);
            if (!in_array($provider, [Provider::ALFA, Provider::STRIPE], true)) {
                continue;
            }

            try {
                if ($provider === Provider::ALFA) {
                    $inspection = AlfaPaymentReconciliationService::create($this->database)
                        ->inspect_payment((int)$payment->id);
                    $payment->providerlivechecked = true;
                    $payment->providerlivepaid = (bool)$inspection->providerpaid;
                    $payment->providerlivestatus = $inspection->provider->paymentstate !== null
                        ? (string)$inspection->provider->paymentstate
                        : 'orderStatus=' . (string)($inspection->provider->orderstatus ?? '—');
                } else {
                    $inspection = StripePaymentReconciliationService::create($this->database)
                        ->inspect_payment((int)$payment->id);
                    $payment->providerlivechecked = true;
                    $payment->providerlivepaid = (bool)$inspection->providerpaid;
                    $payment->providerlivestatus = (string)$inspection->provider->checkoutstatus
                        . ' / ' . (string)$inspection->provider->paymentstatus;
                }
            } catch (\Throwable $exception) {
                $payment->providerliveerror = true;
                debugging(
                    '[local_subscriptions][unfinished_checkout_provider_probe] payment='
                    . (int)$payment->id . ' provider=' . $provider . ' error='
                    . $exception->getMessage(),
                    DEBUG_DEVELOPER
                );
            }
        }

        return $payments;
    }

    private function source_age(int $sessionid): int {
        $created = $this->database->get_field(
            'local_subs_commerce_guest',
            'timecreated',
            ['id' => $sessionid],
            IGNORE_MISSING
        );
        return $created === false ? 0 : max(0, time() - (int)$created);
    }
}
