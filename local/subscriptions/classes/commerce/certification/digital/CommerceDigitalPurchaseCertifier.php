<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\certification\digital;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\fulfillment\native\digital\CommerceNativeDigitalDownloadResolver;
use local_subscriptions\commerce\persistence\CommercePersistenceSchema;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseReadRepository;
use moodle_database;

final class CommerceDigitalPurchaseCertifier {
    public function __construct(private readonly moodle_database $database) {
    }

    public function certify(string $reference): CommerceDigitalPurchaseCertificationReport {
        $checks = [];
        $purchase = $this->database->get_record(CommercePersistenceSchema::TABLE_PURCHASE, ['reference' => trim($reference)], '*', IGNORE_MISSING);
        $this->add($checks, 'purchase', $purchase !== false, $purchase === false ? 'Purchase not found.' : 'Purchase found.');
        if ($purchase === false) {
            return new CommerceDigitalPurchaseCertificationReport($reference, false, $checks);
        }
        $this->add($checks, 'purchase_status', (string)$purchase->status === 'fulfilled', 'Purchase status is ' . (string)$purchase->status . '.');
        $paid = $this->database->record_exists(CommercePersistenceSchema::TABLE_PAYMENT, ['purchaseid' => (int)$purchase->id, 'status' => 'paid']);
        $this->add($checks, 'payment', $paid, $paid ? 'Paid Native payment found.' : 'No paid Native payment found.');

        $grants = array_values($this->database->get_records('local_subs_commerce_grant', ['purchasereference' => $reference, 'type' => 'digital_download'], 'id ASC'));
        $this->add($checks, 'digital_grants', $grants !== [], count($grants) . ' digital_download Grant(s) found.', ['count' => count($grants)]);
        $resolver = new CommerceNativeDigitalDownloadResolver($this->database);
        $details = [];
        $valid = $grants !== [];
        foreach ($grants as $grant) {
            $state = $this->database->get_record('local_subs_commerce_ful_state', ['grantreference' => $grant->grantreference], '*', IGNORE_MISSING);
            $attempt = $this->database->record_exists('local_subs_commerce_ful_attempt', ['grantreference' => $grant->grantreference, 'status' => 'completed']);
            $access = $this->database->get_record('local_subs_commerce_dig_access', ['grantreference' => $grant->grantreference], '*', IGNORE_MISSING);
            $downloadable = false;
            $error = null;
            if ($access !== false) {
                try {
                    $resolver->resolve((string)$access->downloadtoken, time());
                    $downloadable = true;
                } catch (\Throwable $exception) {
                    $error = $exception->getMessage();
                }
            }
            $rowvalid = (string)$grant->status === 'active' && $state !== false && (string)$state->status === 'completed' && $attempt && $access !== false && (string)$access->status === 'active' && $downloadable;
            $valid = $valid && $rowvalid;
            $details[] = [
                'grant_reference' => (string)$grant->grantreference,
                'grant_status' => (string)$grant->status,
                'fulfillment_status' => $state === false ? null : (string)$state->status,
                'completed_attempt' => $attempt,
                'access_status' => $access === false ? null : (string)$access->status,
                'download_token_present' => $access !== false && trim((string)$access->downloadtoken) !== '',
                'download_count' => $access === false ? null : (int)$access->downloadcount,
                'max_downloads' => $access === false || $access->maxdownloads === null ? null : (int)$access->maxdownloads,
                'file_resolved' => $downloadable,
                'error' => $error,
            ];
        }
        $this->add($checks, 'digital_delivery', $valid, $valid ? 'All digital Grants are active and downloadable.' : 'At least one digital Grant is not downloadable.', $details);

        $crm = (new CommercePurchaseReadRepository($this->database))->find_by_reference($reference);
        $crmvalid = $crm !== null && $crm->grants !== [] && $crm->fulfillments !== [] && $crm->fulfillmentattempts !== [];
        $this->add($checks, 'crm_read_model', $crmvalid, $crmvalid ? 'CRM Native Read Model is complete.' : 'CRM Native Read Model is incomplete.');
        $certified = !array_filter($checks, static fn(array $check): bool => $check['status'] !== 'PASS');
        return new CommerceDigitalPurchaseCertificationReport($reference, $certified, $checks);
    }

    private function add(array &$checks, string $key, bool $pass, string $message, array $details = []): void {
        $checks[] = ['key' => $key, 'status' => $pass ? 'PASS' : 'FAIL', 'message' => $message, 'details' => $details];
    }
}
