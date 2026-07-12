<?php

namespace local_subscriptions\digital\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\admin\AdminEvents;
use local_subscriptions\admin\AdminLog;
use local_subscriptions\digital\repositories\DigitalPurchaseAdminActionRepository;

final class DigitalPurchaseAdminActionService {

    private const CANCELLABLE_STATUSES = [
        'pending',
        'failed',
    ];

    private DigitalPurchaseAdminActionRepository $repository;

    public function __construct(
        ?DigitalPurchaseAdminActionRepository $repository = null
    ) {
        $this->repository = $repository ?? new DigitalPurchaseAdminActionRepository();
    }

    public function cancel(int $purchaseid): \stdClass {
        $purchase = $this->repository->get_by_id($purchaseid);

        $currentstatus = strtolower(trim((string)($purchase->status ?? '')));

        if (!in_array($currentstatus, self::CANCELLABLE_STATUSES, true)) {
            throw new \moodle_exception(
                'digital_purchase_cancel_invalid_status',
                'local_subscriptions',
                '',
                strtoupper($currentstatus ?: '-')
            );
        }

        $now = time();

        $this->repository->update_status(
            $purchaseid,
            'cancelled',
            $now
        );

        AdminLog::log(
            AdminEvents::DIGITAL_PURCHASE_CANCELLED,
            !empty($purchase->userid) ? (int)$purchase->userid : null,
            'digital_purchase',
            $purchaseid,
            [
                'previousstatus' => $currentstatus,
                'newstatus' => 'cancelled',
                'email' => (string)($purchase->email ?? ''),
                'provider' => (string)($purchase->payment_provider ?? ''),
                'transactionid' => (string)($purchase->transactionid ?? ''),
            ]
        );

        $purchase->status = 'cancelled';
        $purchase->last_update = $now;

        return $purchase;
    }
}