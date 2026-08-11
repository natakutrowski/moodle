<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail;

defined('MOODLE_INTERNAL') || die();

/**
 * Persistent transactional mail outbox repository.
 */
final class CommerceMailQueueRepository {

    private const TABLE = 'local_subs_commerce_mail';

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
        $where = ['1=1']; $params = [];
        foreach (['status','mailtype','language'] as $field) {
            if (!empty($filters[$field])) { $where[] = "$field = :$field"; $params[$field] = (string)$filters[$field]; }
        }
        if (!empty($filters['purchaseid'])) { $where[] = 'purchaseid = :purchaseid'; $params['purchaseid'] = (int)$filters['purchaseid']; }
        if (!empty($filters['q'])) {
            $where[] = '(recipientemail LIKE :q OR recipientname LIKE :q2 OR idempotencykey LIKE :q3)';
            $like = '%' . $DB->sql_like_escape((string)$filters['q']) . '%';
            $params += ['q'=>$like,'q2'=>$like,'q3'=>$like];
        }
        $sqlwhere = implode(' AND ', $where);
        $total = $DB->count_records_select(self::TABLE, $sqlwhere, $params);
        $records = array_values($DB->get_records_select(self::TABLE, $sqlwhere, $params, 'timecreated DESC, id DESC', '*', max(0,$page)*max(1,$perpage), max(1,$perpage)));
        return ['records'=>$records,'total'=>$total];
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
