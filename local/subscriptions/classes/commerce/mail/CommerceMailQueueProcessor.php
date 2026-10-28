<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail;

use local_subscriptions\commerce\mail\campaign\CommerceMarketingCourseCampaignGate;

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
     * @return array{processed:int,sent:int,retried:int,failed:int,cancelled:int,skipped:int}
     */
    public function process_due(
        int $limit = 50,
        ?int $now = null,
        ?array $includedtypes = null,
        array $excludedtypes = [],
        ?bool $auditcopy = null,
        bool $activemarketingonly = false
    ): array {
        $now ??= time();
        if ($activemarketingonly) {
            if ($includedtypes !== [CommerceMailType::MARKETING_CAMPAIGN]
                    || $excludedtypes !== [] || $auditcopy !== false) {
                throw new \coding_exception('Active marketing selection requires the dedicated marketing worker.');
            }
            $records = $this->repository->get_due_active_marketing($limit, $now);
        } else {
            $records = $this->repository->get_due($limit, $now, $includedtypes, $excludedtypes, $auditcopy);
        }
        return $this->process_records($records, $now);
    }

    /**
     * Immediately processes selected outbox rows. Sent, cancelled, failed or
     * already-processing rows are safely ignored.
     *
     * @param int[] $ids
     * @return array{processed:int,sent:int,retried:int,failed:int,cancelled:int,skipped:int}
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

        return $this->process_records($records, $now, true);
    }

    /**
     * @param \stdClass[] $records
     * @return array{processed:int,sent:int,retried:int,failed:int,cancelled:int,skipped:int}
     */
    private function process_records(array $records, int $now, bool $force = false): array {
        $result = ['processed' => 0, 'sent' => 0, 'retried' => 0, 'failed' => 0, 'cancelled' => 0, 'skipped' => 0];

        foreach ($records as $record) {
            // A pause or cancellation may occur AFTER the worker selects a batch.
            // Check again before claiming and immediately before SMTP dispatch.
            if ((string)$record->mailtype === CommerceMailType::MARKETING_CAMPAIGN) {
                if (!CommerceMarketingCourseCampaignGate::enabled() &&
                        $this->repository->marketing_campaign_source((int)$record->id) === 'course') {
                    $result['skipped']++;
                    continue;
                }
                $campaignstatus = $this->repository->marketing_campaign_status((int)$record->id);
                if ($campaignstatus !== 'queued') {
                    if ($campaignstatus === 'cancelled') {
                        $this->repository->mark_cancelled((int)$record->id,
                            'campaign_cancelled', $now);
                        $result['cancelled']++;
                    } else {
                        $result['skipped']++;
                    }
                    continue;
                }
            }
            if (!$this->templates->has((string)$record->mailtype)) {
                $result['skipped']++;
                continue;
            }
            $claimed = $force
                ? $this->repository->mark_processing_now((int)$record->id, $now)
                : $this->repository->mark_processing((int)$record->id, $now);
            if (!$claimed) {
                $result['skipped']++;
                continue;
            }

            $result['processed']++;
            if ((string)$record->mailtype === CommerceMailType::MARKETING_CAMPAIGN) {
                if (!CommerceMarketingCourseCampaignGate::enabled() &&
                        $this->repository->marketing_campaign_source((int)$record->id) === 'course') {
                    $this->repository->mark_retry((int)$record->id,
                        'course_campaign_release_switch_disabled', $now + 60, $now);
                    $result['skipped']++;
                    continue;
                }
                $campaignstatus = $this->repository->marketing_campaign_status((int)$record->id);
                if ($campaignstatus !== 'queued') {
                    if ($campaignstatus === 'cancelled') {
                        $this->repository->mark_cancelled((int)$record->id,
                            'campaign_cancelled', $now);
                        $result['cancelled']++;
                    } else {
                        // Preserve the message for a later resume, without sending it.
                        $this->repository->mark_retry((int)$record->id,
                            'campaign_paused_or_not_active', $now + 60, $now);
                        $result['skipped']++;
                    }
                    continue;
                }
            }
            $record = $this->repository->find_by_id((int)$record->id);
            if ($record === null) {
                $result['failed']++;
                continue;
            }

            try {
                $message = $this->dispatcher->dispatch($this->request_from_record($record));
                $this->repository->mark_sent((int)$record->id, $message->get_subject(), $now);
                $result['sent']++;
            } catch (CommerceMailTerminalCancellationException $exception) {
                $this->repository->mark_cancelled(
                    (int)$record->id,
                    $exception->get_reason() . ': ' . $exception->getMessage(),
                    $now
                );
                $result['cancelled']++;
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
