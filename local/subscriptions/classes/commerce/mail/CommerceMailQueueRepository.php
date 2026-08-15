<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail;

defined('MOODLE_INTERNAL') || die();

/**
 * Persistent transactional mail outbox repository.
 */
final class CommerceMailQueueRepository {

    private const TABLE = 'local_subs_commerce_mail';
    private const AUDIT_EMAIL = 'log@campusfr.fr';

    public function enqueue(CommerceMailRequest $request, int $maxattempts = 5): \stdClass {
        global $DB;

        if ($maxattempts <= 0) {
            throw new \coding_exception('Commerce mail maximum attempts must be positive.');
        }

        $existing = $this->find_by_idempotency_key($request->get_idempotency_key());
        if ($existing !== null) {
            return $existing;
        }

        $recipient = $request->get_recipient();
        $now = time();
        $record = (object)[
            'mailtype' => $request->get_type(),
            'status' => CommerceMailStatus::QUEUED,
            'idempotencykey' => $request->get_idempotency_key(),
            'purchaseid' => $request->get_purchase_id(),
            'userid' => $recipient->get_user_id(),
            'recipientemail' => $recipient->get_email(),
            'recipientname' => $recipient->get_name(),
            'language' => $request->get_language(),
            'subject' => null,
            'contextjson' => json_encode(
                $request->get_context()->all(),
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ),
            'attemptcount' => 0,
            'maxattempts' => $maxattempts,
            'nextruntime' => $now,
            'lasterror' => null,
            'timeprocessing' => null,
            'timesent' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ];

        try {
            $record->id = $DB->insert_record(self::TABLE, $record);
        } catch (\dml_write_exception $exception) {
            // A concurrent request may have inserted the same idempotency key.
            $existing = $this->find_by_idempotency_key($request->get_idempotency_key());
            if ($existing !== null) {
                return $existing;
            }
            throw $exception;
        }

        return $record;
    }

    public function find_by_id(int $id): ?\stdClass {
        global $DB;
        $record = $DB->get_record(self::TABLE, ['id' => $id]);
        return $record ?: null;
    }

    public function find_by_idempotency_key(string $key): ?\stdClass {
        global $DB;
        $key = CommerceMailIdempotencyKey::normalise($key);
        $record = $DB->get_record(self::TABLE, ['idempotencykey' => $key]);
        return $record ?: null;
    }

    /** @return \stdClass[] */
    public function get_due(
        int $limit,
        ?int $now = null,
        ?array $includedtypes = null,
        array $excludedtypes = [],
        ?bool $auditcopy = null
    ): array {
        global $DB;
        if ($limit <= 0) {
            return [];
        }
        $now ??= time();
        $where = ['status = :status', 'nextruntime <= :now'];
        $params = ['status' => CommerceMailStatus::QUEUED, 'now' => $now];
        if ($includedtypes !== null) {
            $includedtypes = array_values(array_unique(array_map([CommerceMailType::class, 'normalise'], $includedtypes)));
            if ($includedtypes === []) { return []; }
            [$insql, $inparams] = $DB->get_in_or_equal($includedtypes, SQL_PARAMS_NAMED, 'imt');
            $where[] = 'mailtype ' . $insql; $params += $inparams;
        }
        if ($excludedtypes !== []) {
            $excludedtypes = array_values(array_unique(array_map([CommerceMailType::class, 'normalise'], $excludedtypes)));
            [$notsql, $notparams] = $DB->get_in_or_equal($excludedtypes, SQL_PARAMS_NAMED, 'xmt', false);
            $where[] = 'mailtype ' . $notsql; $params += $notparams;
        }
        if ($auditcopy === true) {
            $where[] = 'idempotencykey LIKE :auditpattern';
            $params['auditpattern'] = '%:audit';
        } else if ($auditcopy === false) {
            $where[] = 'idempotencykey NOT LIKE :auditpattern';
            $params['auditpattern'] = '%:audit';
        }
        return array_values($DB->get_records_select(
            self::TABLE,
            implode(' AND ', $where),
            $params,
            'nextruntime ASC, id ASC',
            '*',
            0,
            $limit
        ));
    }

    public function count_audit_sent_since(int $since): int {
        global $DB;
        return (int)$DB->count_records_select(
            self::TABLE,
            'status = :status AND timesent IS NOT NULL AND timesent >= :since AND idempotencykey LIKE :auditpattern',
            [
                'status' => CommerceMailStatus::SENT,
                'since' => $since,
                'auditpattern' => '%:audit',
            ]
        );
    }

    public function has_due_non_audit(?int $now = null): bool {
        global $DB;
        $now ??= time();
        return $DB->record_exists_select(
            self::TABLE,
            'status = :status AND nextruntime <= :now AND idempotencykey NOT LIKE :auditpattern',
            [
                'status' => CommerceMailStatus::QUEUED,
                'now' => $now,
                'auditpattern' => '%:audit',
            ]
        );
    }

    public function count_sent_since(string $mailtype, int $since): int {
        global $DB;
        return (int)$DB->count_records_select(
            self::TABLE,
            'mailtype = :mailtype AND status = :status AND timesent IS NOT NULL AND timesent >= :since',
            ['mailtype' => CommerceMailType::normalise($mailtype), 'status' => CommerceMailStatus::SENT, 'since' => $since]
        );
    }

    public function count_non_audit_sent_since(int $since): int {
        global $DB;
        return (int)$DB->count_records_select(
            self::TABLE,
            'status = :status AND timesent IS NOT NULL AND timesent >= :since'
                . ' AND idempotencykey NOT LIKE :auditpattern'
                . ' AND LOWER(recipientemail) <> :auditmail',
            [
                'status' => CommerceMailStatus::SENT,
                'since' => $since,
                'auditpattern' => '%:audit',
                'auditmail' => self::AUDIT_EMAIL,
            ]
        );
    }

    public function count_all_sent_since(int $since): int {
        global $DB;
        return (int)$DB->count_records_select(
            self::TABLE,
            'status = :status AND timesent IS NOT NULL AND timesent >= :since',
            [
                'status' => CommerceMailStatus::SENT,
                'since' => $since,
            ]
        );
    }

    public function count_marketing_sent_since(int $since): int {
        global $DB;
        return (int)$DB->count_records_select(
            self::TABLE,
            'status = :status AND timesent IS NOT NULL AND timesent >= :since'
                . ' AND mailtype = :mailtype',
            [
                'status' => CommerceMailStatus::SENT,
                'since' => $since,
                'mailtype' => CommerceMailType::MARKETING_CAMPAIGN,
            ]
        );
    }

    public function count_transactional_sent_since(int $since): int {
        global $DB;
        return (int)$DB->count_records_select(
            self::TABLE,
            'status = :status AND timesent IS NOT NULL AND timesent >= :since'
                . ' AND mailtype <> :personaloffer'
                . ' AND mailtype <> :marketing'
                . ' AND idempotencykey NOT LIKE :auditpattern'
                . ' AND LOWER(recipientemail) <> :auditmail',
            [
                'status' => CommerceMailStatus::SENT,
                'since' => $since,
                'personaloffer' => CommerceMailType::PERSONAL_OFFER,
                'marketing' => CommerceMailType::MARKETING_CAMPAIGN,
                'auditpattern' => '%:audit',
                'auditmail' => self::AUDIT_EMAIL,
            ]
        );
    }

    /** @return array{sent:int,pending:int} */
    public function personal_offer_operational_counts(array $filters): array {
        global $DB;
        $filters['mailtype'] = CommerceMailType::PERSONAL_OFFER;
        $filters['includeaudit'] = false;
        [$sqlwhere, $params] = $this->search_conditions($filters, false);
        $rows = $DB->get_records_sql(
            'SELECT MIN(id) AS rowid, status, COUNT(1) AS total'
                . ' FROM {' . self::TABLE . '}'
                . ' WHERE ' . $sqlwhere
                . ' GROUP BY status',
            $params
        );

        $result = ['sent' => 0, 'pending' => 0];
        foreach ($rows as $row) {
            $status = strtolower((string)$row->status);
            $count = (int)$row->total;
            if ($status === CommerceMailStatus::SENT) {
                $result['sent'] += $count;
            } else if (in_array(
                $status,
                [CommerceMailStatus::QUEUED, CommerceMailStatus::PROCESSING],
                true
            )) {
                $result['pending'] += $count;
            }
        }
        return $result;
    }

    public function mark_processing(int $id, ?int $now = null): bool {
        global $DB;
        $now ??= time();
        $record = $this->find_by_id($id);
        if ($record === null || $record->status !== CommerceMailStatus::QUEUED || (int)$record->nextruntime > $now) {
            return false;
        }
        $record->status = CommerceMailStatus::PROCESSING;
        $record->attemptcount = (int)$record->attemptcount + 1;
        $record->timeprocessing = $now;
        $record->timemodified = $now;
        $DB->update_record(self::TABLE, $record);
        return true;
    }

    /**
     * Claims a queued row immediately, regardless of its scheduled next runtime.
     * Intended for an explicit CRM operator action, not normal cron processing.
     */
    public function mark_processing_now(int $id, ?int $now = null): bool {
        global $DB;
        $now ??= time();
        $record = $this->find_by_id($id);
        if ($record === null || $record->status !== CommerceMailStatus::QUEUED) {
            return false;
        }
        $record->status = CommerceMailStatus::PROCESSING;
        $record->attemptcount = (int)$record->attemptcount + 1;
        $record->timeprocessing = $now;
        $record->timemodified = $now;
        $DB->update_record(self::TABLE, $record);
        return true;
    }

    public function mark_sent(int $id, string $subject, ?int $now = null): void {
        global $DB;
        $now ??= time();
        $DB->update_record(self::TABLE, (object)[
            'id' => $id,
            'status' => CommerceMailStatus::SENT,
            'subject' => $subject,
            'lasterror' => null,
            'timesent' => $now,
            'timeprocessing' => null,
            'timemodified' => $now,
        ]);
    }

    public function mark_cancelled(int $id, string $reason, ?int $now = null): void {
        global $DB;
        $now ??= time();
        $DB->update_record(self::TABLE, (object)[
            'id' => $id,
            'status' => CommerceMailStatus::CANCELLED,
            'lasterror' => $this->normalise_error($reason),
            'timeprocessing' => null,
            'timemodified' => $now,
        ]);
    }

    public function mark_retry(int $id, string $error, int $nextruntime, ?int $now = null): void {
        global $DB;
        $now ??= time();
        $DB->update_record(self::TABLE, (object)[
            'id' => $id,
            'status' => CommerceMailStatus::QUEUED,
            'lasterror' => $this->normalise_error($error),
            'nextruntime' => $nextruntime,
            'timeprocessing' => null,
            'timemodified' => $now,
        ]);
    }

    public function mark_failed(int $id, string $error, ?int $now = null): void {
        global $DB;
        $now ??= time();
        $DB->update_record(self::TABLE, (object)[
            'id' => $id,
            'status' => CommerceMailStatus::FAILED,
            'lasterror' => $this->normalise_error($error),
            'timeprocessing' => null,
            'timemodified' => $now,
        ]);
    }

    public function reset_failed(int $id, ?int $now = null): bool {
        global $DB;
        $now ??= time();
        $record = $this->find_by_id($id);
        if ($record === null || $record->status !== CommerceMailStatus::FAILED) {
            return false;
        }
        $record->status = CommerceMailStatus::QUEUED;
        $record->attemptcount = 0;
        $record->nextruntime = $now;
        $record->lasterror = null;
        $record->timeprocessing = null;
        $record->timemodified = $now;
        $DB->update_record(self::TABLE, $record);
        return true;
    }

    /** @return \stdClass[] */
    public function get_failed(int $limit, ?int $purchaseid = null, ?string $mailtype = null): array {
        global $DB;
        $conditions = ['status = :status'];
        $params = ['status' => CommerceMailStatus::FAILED];
        if ($purchaseid !== null) {
            $conditions[] = 'purchaseid = :purchaseid';
            $params['purchaseid'] = $purchaseid;
        }
        if ($mailtype !== null && $mailtype !== '') {
            $conditions[] = 'mailtype = :mailtype';
            $params['mailtype'] = CommerceMailType::normalise($mailtype);
        }
        return array_values($DB->get_records_select(
            self::TABLE,
            implode(' AND ', $conditions),
            $params,
            'id ASC',
            '*',
            0,
            max(0, $limit)
        ));
    }

    public function recover_stale_processing(int $olderthan, ?int $now = null): int {
        global $DB;
        $now ??= time();
        return $DB->execute(
            'UPDATE {' . self::TABLE . '}
                SET status = :queued,
                    nextruntime = :now,
                    timeprocessing = NULL,
                    timemodified = :modified
              WHERE status = :processing
                AND timeprocessing IS NOT NULL
                AND timeprocessing < :olderthan',
            [
                'queued' => CommerceMailStatus::QUEUED,
                'now' => $now,
                'modified' => $now,
                'processing' => CommerceMailStatus::PROCESSING,
                'olderthan' => $olderthan,
            ]
        ) ? 1 : 0;
    }


    /** @return array{records:array,total:int} */
    public function search(array $filters, int $page = 0, int $perpage = 25): array {
        global $DB;
        [$sqlwhere, $params] = $this->search_conditions($filters);
        $sort = $this->search_sort((string)($filters['sort'] ?? 'date'), (string)($filters['dir'] ?? 'desc'));
        $total = $DB->count_records_select(self::TABLE, $sqlwhere, $params);
        $records = array_values($DB->get_records_select(
            self::TABLE,
            $sqlwhere,
            $params,
            $sort,
            '*',
            max(0, $page) * max(1, $perpage),
            max(1, $perpage)
        ));
        return ['records' => $records, 'total' => $total];
    }

    /** @return \stdClass[] */
    public function search_all(array $filters): array {
        global $DB;
        [$sqlwhere, $params] = $this->search_conditions($filters);
        $sort = $this->search_sort((string)($filters['sort'] ?? 'date'), (string)($filters['dir'] ?? 'desc'));
        return array_values($DB->get_records_select(self::TABLE, $sqlwhere, $params, $sort));
    }

    /** @return array{total:int,sent:int,pending:int,failed:int,cancelled:int} */
    public function statistics(array $filters): array {
        global $DB;
        [$sqlwhere, $params] = $this->search_conditions($filters, false);
        $rows = $DB->get_records_sql(
            'SELECT MIN(id) AS rowid, status, COUNT(1) AS total'
            . ' FROM {' . self::TABLE . '}'
            . ' WHERE ' . $sqlwhere
            . ' GROUP BY status',
            $params
        );
        $result = ['total' => 0, 'sent' => 0, 'pending' => 0, 'failed' => 0, 'cancelled' => 0];
        foreach ($rows as $row) {
            $count = (int)$row->total;
            $status = strtolower((string)$row->status);
            $result['total'] += $count;
            if ($status === CommerceMailStatus::SENT) {
                $result['sent'] += $count;
            } else if (in_array($status, [CommerceMailStatus::QUEUED, CommerceMailStatus::PROCESSING], true)) {
                $result['pending'] += $count;
            } else if ($status === CommerceMailStatus::FAILED) {
                $result['failed'] += $count;
            } else if ($status === CommerceMailStatus::CANCELLED) {
                $result['cancelled'] += $count;
            }
        }
        return $result;
    }

    /** @return array{0:string,1:array<string,mixed>} */
    private function search_conditions(array $filters, bool $includestatus = true): array {
        global $DB;
        $where = ['1=1'];
        $params = [];

        if (empty($filters['includeaudit'])) {
            $where[] = 'idempotencykey NOT LIKE :journal_auditpattern';
            $where[] = 'LOWER(recipientemail) <> :journal_auditmail';
            $params['journal_auditpattern'] = '%:audit';
            $params['journal_auditmail'] = self::AUDIT_EMAIL;
        }

        foreach (['mailtype', 'language'] as $field) {
            if (!empty($filters[$field])) {
                $where[] = "$field = :$field";
                $params[$field] = (string)$filters[$field];
            }
        }
        if ($includestatus && !empty($filters['status'])) {
            $where[] = 'status = :status';
            $params['status'] = (string)$filters['status'];
        }
        if (!empty($filters['purchaseid'])) {
            $where[] = 'purchaseid = :purchaseid';
            $params['purchaseid'] = (int)$filters['purchaseid'];
        }
        if (!empty($filters['attempts'])) {
            $where[] = 'attemptcount >= :attempts';
            $params['attempts'] = max(0, (int)$filters['attempts']);
        }
        if (!empty($filters['datefrom'])) {
            $where[] = 'timecreated >= :datefrom';
            $params['datefrom'] = (int)$filters['datefrom'];
        }
        if (!empty($filters['dateto'])) {
            $where[] = 'timecreated <= :dateto';
            $params['dateto'] = (int)$filters['dateto'];
        }
        if (!empty($filters['q'])) {
            $query = trim((string)$filters['q']);
            $where[] = '(recipientemail LIKE :q OR recipientname LIKE :q2 OR idempotencykey LIKE :q3'
                . ' OR subject LIKE :q4 OR contextjson LIKE :q5'
                . (ctype_digit($query) ? ' OR purchaseid = :qpurchaseid' : '')
                . ')';
            $like = '%' . $DB->sql_like_escape($query) . '%';
            $params += ['q' => $like, 'q2' => $like, 'q3' => $like, 'q4' => $like, 'q5' => $like];
            if (ctype_digit($query)) {
                $params['qpurchaseid'] = (int)$query;
            }
        }
        return [implode(' AND ', $where), $params];
    }

    private function search_sort(string $sort, string $direction): string {
        $field = match (strtolower($sort)) {
            'recipient' => 'recipientemail',
            'type' => 'mailtype',
            'status' => 'status',
            'language' => 'language',
            'attempts' => 'attemptcount',
            'error' => 'lasterror',
            'id' => 'id',
            default => 'timecreated',
        };
        $direction = strtolower($direction) === 'asc' ? 'ASC' : 'DESC';
        return $field . ' ' . $direction . ', id ' . $direction;
    }

    public function cancel_pending(int $id, ?int $now = null): bool {
        global $DB; $now ??= time(); $record = $this->find_by_id($id);
        if ($record === null || !in_array($record->status, [CommerceMailStatus::QUEUED, CommerceMailStatus::FAILED], true)) { return false; }
        $DB->update_record(self::TABLE, (object)['id'=>$id,'status'=>CommerceMailStatus::CANCELLED,'timeprocessing'=>null,'timemodified'=>$now]);
        return true;
    }

    private function normalise_error(string $error): string {
        $error = trim($error);
        return \core_text::substr($error === '' ? 'Unknown transactional mail error.' : $error, 0, 10000);
    }
}
