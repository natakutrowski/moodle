<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\admin;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\mail\CommerceMailContext;
use local_subscriptions\commerce\mail\CommerceMailDispatcher;
use local_subscriptions\commerce\mail\CommerceMailQueueRepository;
use local_subscriptions\commerce\mail\CommerceMailRecipient;
use local_subscriptions\commerce\mail\CommerceMailRequest;
use local_subscriptions\commerce\mail\CommerceMailRuntime;
use local_subscriptions\commerce\mail\MoodleCommerceMailTransport;

final class CommerceMailAdminService {
    public function __construct(private readonly CommerceMailQueueRepository $repository = new CommerceMailQueueRepository()) {}

    /** @return array{records:array,total:int} */
    public function search(array $filters, int $page, int $perpage): array {
        return $this->repository->search($filters, $page, $perpage);
    }

    /** @return \stdClass[] */
    public function search_all(array $filters): array {
        return $this->repository->search_all($filters);
    }

    /** @return array{total:int,sent:int,pending:int,failed:int,cancelled:int} */
    public function statistics(array $filters): array {
        return $this->repository->statistics($filters);
    }

    /**
     * @return array{
     *   offerssent:int,
     *   offerspending:int,
     *   sentlasthour:int,
     *   lastoffercheck:int
     * }
     */
    public function operational_statistics(array $filters, ?int $now = null): array {
        $now ??= time();
        $offercounts = $this->repository->personal_offer_operational_counts($filters);
        return [
            'offerssent' => $offercounts['sent'],
            'offerspending' => $offercounts['pending'],
            'sentlasthour' => $this->repository->count_non_audit_sent_since($now - HOURSECS),
            'lastoffercheck' => (int)(get_config(
                'local_subscriptions',
                'personal_offer_mail_last_check_at'
            ) ?: 0),
        ];
    }

    public function find(int $id): ?\stdClass { return $this->repository->find_by_id($id); }

    public function preview(int $id): array {
        $record = $this->required($id);
        $context = json_decode((string)$record->contextjson, true, 512, JSON_THROW_ON_ERROR);
        $request = new CommerceMailRequest(
            (string)$record->mailtype,
            new CommerceMailRecipient((string)$record->recipientemail, (string)$record->recipientname, $record->userid === null ? null : (int)$record->userid),
            new CommerceMailContext($context),
            (string)$record->language,
            (string)$record->idempotencykey,
            $record->purchaseid === null ? null : (int)$record->purchaseid
        );
        $registry = CommerceMailRuntime::template_registry();
        $message = (new CommerceMailDispatcher($registry, new MoodleCommerceMailTransport()))->preview($request);
        return ['subject' => $message->get_subject(), 'html' => $message->get_html(), 'text' => $message->get_text()];
    }


    /** Immediately processes an existing queued outbox row through the certified queue processor. */
    public function send_now(int $id): array {
        $record = $this->required($id);
        if ((string)$record->status !== 'queued') {
            throw new \moodle_exception('commerce_mail_send_now_not_allowed', 'local_subscriptions');
        }

        // A direct CRM send happens inside an HTTP request. Some lower-level
        // Moodle/SMTP debugging paths can emit output before throwing. Capture
        // that output so the queue processor can persist the real exception and
        // the controller can still redirect cleanly afterwards.
        $level = ob_get_level();
        ob_start();
        try {
            return CommerceMailRuntime::processor()->process_ids([$id]);
        } finally {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
        }
    }

    public function retry(int $id): bool { return $this->repository->reset_failed($id); }
    public function cancel(int $id): bool { return $this->repository->cancel_pending($id); }

    public function resend(int $id, int $adminuserid): \stdClass {
        $record = $this->required($id);
        if ((string)$record->status !== 'sent') {
            throw new \moodle_exception('commerce_mail_resend_not_allowed', 'local_subscriptions');
        }

        $contextvalues = json_decode((string)$record->contextjson, true, 512, JSON_THROW_ON_ERROR);
        $context = (new CommerceMailContext($contextvalues))->with('resend', [
            'frommailid' => (int)$record->id,
            'byuserid' => $adminuserid > 0 ? $adminuserid : null,
            'timecreated' => time(),
        ]);

        // Double-clicks within the same second collapse to one resend, while a later
        // explicit resend remains a distinct auditable outbox intention.
        $key = sprintf(
            'mail:%d:resend:%d:%d',
            (int)$record->id,
            max(0, $adminuserid),
            time()
        );

        return CommerceMailRuntime::queue_service()->queue(new CommerceMailRequest(
            (string)$record->mailtype,
            new CommerceMailRecipient(
                (string)$record->recipientemail,
                (string)$record->recipientname,
                $record->userid === null ? null : (int)$record->userid
            ),
            $context,
            (string)$record->language,
            $key,
            $record->purchaseid === null ? null : (int)$record->purchaseid
        ));
    }

    /**
     * Forward a previously sent transactional mail to an explicitly entered address.
     * The original outbox record is immutable. The new row keeps its user/purchase
     * association, and the actual destination is stored in recipientemail.
     * Account activation, OTP, campaigns and audit copies are never forwardable.
     */
    public function forward(int $id, int $adminuserid, string $destination): \stdClass {
        $record = $this->required($id);
        if (!self::can_forward($record)) {
            throw new \moodle_exception('commerce_mail_forward_not_allowed', 'local_subscriptions');
        }

        $destination = trim($destination);
        if (strlen($destination) > 254 || !filter_var($destination, FILTER_VALIDATE_EMAIL)
                || preg_match('/[\r\n]/', $destination)) {
            throw new \moodle_exception('commerce_mail_forward_invalid_email', 'local_subscriptions');
        }

        $now = time();
        $contextvalues = json_decode((string)$record->contextjson, true, 512, JSON_THROW_ON_ERROR);
        $context = (new CommerceMailContext($contextvalues))
            ->with('resend', [
                'frommailid' => (int)$record->id,
                'byuserid' => $adminuserid > 0 ? $adminuserid : null,
                'timecreated' => $now,
            ])
            ->with('forward', [
                'frommailid' => (int)$record->id,
                'originalemail' => (string)$record->recipientemail,
                'destinationemail' => $destination,
                'byuserid' => $adminuserid > 0 ? $adminuserid : null,
                'timecreated' => $now,
            ]);

        // Same-second duplicate clicks collapse; distinct destinations are distinct intents.
        $key = sprintf(
            'mail:%d:forward:%d:%d:%s',
            (int)$record->id,
            max(0, $adminuserid),
            $now,
            substr(hash('sha256', strtolower($destination)), 0, 16)
        );

        return CommerceMailRuntime::queue_service()->queue(new CommerceMailRequest(
            (string)$record->mailtype,
            new CommerceMailRecipient($destination, (string)$record->recipientname, (int)$record->userid),
            $context,
            (string)$record->language,
            $key,
            $record->purchaseid === null ? null : (int)$record->purchaseid
        ));
    }

    /** Security-sensitive identity and campaign messages cannot be redirected. */
    public static function can_forward(\stdClass $record): bool {
        if ((string)($record->status ?? '') !== 'sent' || (int)($record->userid ?? 0) <= 0) {
            return false;
        }
        if (str_ends_with((string)($record->idempotencykey ?? ''), ':audit')) {
            return false;
        }
        return in_array((string)($record->mailtype ?? ''), [
            \local_subscriptions\commerce\mail\CommerceMailType::PURCHASE_ACCESS,
            \local_subscriptions\commerce\mail\CommerceMailType::GRANT_ACCESS,
            \local_subscriptions\commerce\mail\CommerceMailType::PURCHASE_RECEIPT,
            \local_subscriptions\commerce\mail\CommerceMailType::PAYMENT_FAILED,
            \local_subscriptions\commerce\mail\CommerceMailType::PAYMENT_PENDING,
            \local_subscriptions\commerce\mail\CommerceMailType::PAYMENT_CANCELLED,
        ], true);
    }

    private function required(int $id): \stdClass {
        $record = $this->repository->find_by_id($id);
        if ($record === null) { throw new \moodle_exception('invalidrecord'); }
        return $record;
    }
}
