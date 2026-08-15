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

    private function required(int $id): \stdClass {
        $record = $this->repository->find_by_id($id);
        if ($record === null) { throw new \moodle_exception('invalidrecord'); }
        return $record;
    }
}
