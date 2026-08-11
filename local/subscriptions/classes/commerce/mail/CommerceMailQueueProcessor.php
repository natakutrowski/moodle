<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail;

defined('MOODLE_INTERNAL') || die();

/**
 * Processes persisted transactional messages safely and independently.
 */
final class CommerceMailQueueProcessor {

    public function __construct(
        private readonly CommerceMailQueueRepository $repository,
        private readonly CommerceMailTemplateRegistry $templates,
        private readonly CommerceMailDispatcher $dispatcher,
        private readonly CommerceMailRetryPolicy $retrypolicy
    ) {
    }

    /**
     * @return array{processed:int,sent:int,retried:int,failed:int,skipped:int}
     */
    public function process_due(
        int $limit = 50,
        ?int $now = null,
        ?array $includedtypes = null,
        array $excludedtypes = [],
        ?bool $auditcopy = null
    ): array {
        $now ??= time();
        return $this->process_records(
            $this->repository->get_due($limit, $now, $includedtypes, $excludedtypes, $auditcopy),
            $now
        );
    }

    /**
     * Immediately processes selected outbox rows. Sent, cancelled, failed or
     * already-processing rows are safely ignored.
     *
     * @param int[] $ids
     * @return array{processed:int,sent:int,retried:int,failed:int,skipped:int}
     */
    public function process_ids(array $ids, ?int $now = null): array {
        $now ??= time();
        $records = [];

        foreach (array_values(array_unique(array_map('intval', $ids))) as $id) {
            if ($id <= 0) {
                continue;
            }
            $record = $this->repository->find_by_id($id);
            if ($record !== null) {
                $records[] = $record;
            }
        }

        return $this->process_records($records, $now);
    }

    /**
     * @param \stdClass[] $records
     * @return array{processed:int,sent:int,retried:int,failed:int,skipped:int}
     */
    private function process_records(array $records, int $now): array {
        $result = ['processed' => 0, 'sent' => 0, 'retried' => 0, 'failed' => 0, 'skipped' => 0];

        foreach ($records as $record) {
            if (!$this->templates->has((string)$record->mailtype)) {
                $result['skipped']++;
                continue;
            }
            if (!$this->repository->mark_processing((int)$record->id, $now)) {
                $result['skipped']++;
                continue;
            }

            $result['processed']++;
            $record = $this->repository->find_by_id((int)$record->id);
            if ($record === null) {
                $result['failed']++;
                continue;
            }

            try {
                $message = $this->dispatcher->dispatch($this->request_from_record($record));
                $this->repository->mark_sent((int)$record->id, $message->get_subject(), $now);
                $result['sent']++;
            } catch (\Throwable $exception) {
                $attemptcount = (int)$record->attemptcount;
                $next = $attemptcount < (int)$record->maxattempts
                    ? $this->retrypolicy->next_runtime($attemptcount, $now)
                    : null;

                if ($next !== null) {
                    $this->repository->mark_retry((int)$record->id, $exception->getMessage(), $next, $now);
                    $result['retried']++;
                } else {
                    $this->repository->mark_failed((int)$record->id, $exception->getMessage(), $now);
                    $result['failed']++;
                }
            }
        }

        return $result;
    }

    private function request_from_record(\stdClass $record): CommerceMailRequest {
        $context = json_decode((string)$record->contextjson, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($context)) {
            throw new \coding_exception('Persisted Commerce mail context must decode to an array.');
        }

        return new CommerceMailRequest(
            (string)$record->mailtype,
            new CommerceMailRecipient(
                (string)$record->recipientemail,
                (string)$record->recipientname,
                $record->userid === null ? null : (int)$record->userid
            ),
            new CommerceMailContext($context),
            (string)$record->language,
            (string)$record->idempotencykey,
            $record->purchaseid === null ? null : (int)$record->purchaseid
        );
    }
}
