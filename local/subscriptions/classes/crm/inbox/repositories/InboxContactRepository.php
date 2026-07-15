<?php

namespace local_subscriptions\crm\inbox\repositories;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\domain\InboxContact;
use local_subscriptions\crm\inbox\domain\InboxContactMatch;

final class InboxContactRepository {

    private const TABLE =
        'local_subscriptions_inbox_contact';

    public function find(int $id): ?InboxContact {
        global $DB;

        $record = $DB->get_record(
            self::TABLE,
            ['id' => $id]
        );

        return $record
            ? $this->map($record)
            : null;
    }

    public function find_by_email(
        string $email
    ): ?InboxContact {
        global $DB;

        $record = $DB->get_record(
            self::TABLE,
            [
                'normalizedemail' =>
                    $this->normalize_email($email),
            ]
        );

        return $record
            ? $this->map($record)
            : null;
    }

    public function get_or_create(
        string $email,
        ?string $displayname = null
    ): InboxContact {
        global $DB;

        $normalizedemail =
            $this->normalize_email($email);

        $existing = $DB->get_record(
            self::TABLE,
            [
                'normalizedemail' =>
                    $normalizedemail,
            ]
        );

        if ($existing) {
            $newname = $this->clean_name(
                $displayname
            );

            if (
                $newname !== null &&
                trim(
                    (string)($existing->displayname ?? '')
                ) === ''
            ) {
                $existing->displayname = $newname;
                $existing->timemodified = time();

                $DB->update_record(
                    self::TABLE,
                    $existing
                );
            }

            return $this->find(
                (int)$existing->id
            );
        }

        $now = time();

        $id = $DB->insert_record(
            self::TABLE,
            (object)[
                'displayname' =>
                    $this->clean_name($displayname),
                'primaryemail' => trim($email),
                'normalizedemail' =>
                    $normalizedemail,
                'matcheduserid' => null,
                'matchstatus' =>
                    InboxContactMatch::STATUS_UNMATCHED,
                'matchsource' =>
                    InboxContactMatch::SOURCE_NONE,
                'matchconfidence' => 0,
                'matchlocked' => 0,
                'lastmatchedat' => null,
                'timecreated' => $now,
                'timemodified' => $now,
            ]
        );

        return $this->find((int)$id);
    }

    /**
     * @return InboxContact[]
     */
    public function get_reconcilable(
        int $limit = 500
    ): array {
        global $DB;

        $statuses = [
            InboxContactMatch::STATUS_UNMATCHED,
            InboxContactMatch::STATUS_AMBIGUOUS,
            InboxContactMatch::STATUS_DETACHED,
        ];

        [$statussql, $params] =
            $DB->get_in_or_equal(
                $statuses,
                SQL_PARAMS_NAMED,
                'matchstatus'
            );

        $sql = "
            SELECT *
              FROM {" . self::TABLE . "}
             WHERE matchlocked = 0
               AND matchstatus {$statussql}
          ORDER BY timemodified ASC, id ASC
        ";

        $records = $DB->get_records_sql(
            $sql,
            $params,
            0,
            max(1, $limit)
        );

        return array_values(
            array_map(
                fn(object $record): InboxContact =>
                    $this->map($record),
                $records
            )
        );
    }

    public function set_automatic_match(
        int $contactid,
        int $userid,
        string $source,
        float $confidence = 1.0
    ): void {
        $this->set_match(
            $contactid,
            $userid,
            InboxContactMatch::STATUS_AUTOMATIC,
            $source,
            $confidence,
            false
        );
    }

    public function set_manual_match(
        int $contactid,
        int $userid
    ): void {
        $this->set_match(
            $contactid,
            $userid,
            InboxContactMatch::STATUS_MANUAL,
            InboxContactMatch::SOURCE_MANUAL,
            1.0,
            true
        );
    }

    public function mark_ambiguous(
        int $contactid,
        string $source
    ): void {
        global $DB;

        $DB->update_record(
            self::TABLE,
            (object)[
                'id' => $contactid,
                'matcheduserid' => null,
                'matchstatus' =>
                    InboxContactMatch::STATUS_AMBIGUOUS,
                'matchsource' => $source,
                'matchconfidence' => 0,
                'lastmatchedat' => time(),
                'timemodified' => time(),
            ]
        );
    }

    public function detach(
        int $contactid,
        bool $lock = true
    ): void {
        global $DB;

        $DB->update_record(
            self::TABLE,
            (object)[
                'id' => $contactid,
                'matcheduserid' => null,
                'matchstatus' =>
                    InboxContactMatch::STATUS_DETACHED,
                'matchsource' =>
                    InboxContactMatch::SOURCE_MANUAL,
                'matchconfidence' => 0,
                'matchlocked' => $lock ? 1 : 0,
                'lastmatchedat' => time(),
                'timemodified' => time(),
            ]
        );
    }

    public function unlock_matching(
        int $contactid
    ): void {
        global $DB;

        $DB->update_record(
            self::TABLE,
            (object)[
                'id' => $contactid,
                'matchlocked' => 0,
                'matchstatus' =>
                    InboxContactMatch::STATUS_UNMATCHED,
                'matchsource' =>
                    InboxContactMatch::SOURCE_NONE,
                'matchconfidence' => 0,
                'timemodified' => time(),
            ]
        );
    }

    private function set_match(
        int $contactid,
        int $userid,
        string $status,
        string $source,
        float $confidence,
        bool $locked
    ): void {
        global $DB;

        $DB->update_record(
            self::TABLE,
            (object)[
                'id' => $contactid,
                'matcheduserid' => $userid,
                'matchstatus' => $status,
                'matchsource' => $source,
                'matchconfidence' => max(
                    0,
                    min(1, $confidence)
                ),
                'matchlocked' => $locked ? 1 : 0,
                'lastmatchedat' => time(),
                'timemodified' => time(),
            ]
        );
    }

    private function map(object $record): InboxContact {
        return new InboxContact(
            (int)$record->id,
            $this->nullable_string(
                $record->displayname ?? null
            ),
            (string)$record->primaryemail,
            (string)$record->normalizedemail,
            isset($record->matcheduserid)
                ? (int)$record->matcheduserid
                : null,
            (string)$record->matchstatus,
            (string)$record->matchsource,
            (float)$record->matchconfidence,
            (bool)$record->matchlocked,
            isset($record->lastmatchedat)
                ? (int)$record->lastmatchedat
                : null
        );
    }

    private function normalize_email(
        string $email
    ): string {
        $email = \core_text::strtolower(
            trim($email)
        );

        if (
            $email === '' ||
            !validate_email($email)
        ) {
            throw new \invalid_parameter_exception(
                'Invalid Inbox contact email.'
            );
        }

        return $email;
    }

    private function clean_name(
        ?string $name
    ): ?string {
        if ($name === null) {
            return null;
        }

        $name = trim(
            clean_param(
                $name,
                PARAM_TEXT
            )
        );

        return $name !== ''
            ? $name
            : null;
    }

    private function nullable_string(
        mixed $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value = trim((string)$value);

        return $value !== ''
            ? $value
            : null;
    }

    public function get_automatic_matches_for_user(
        int $userid
    ): array {
        global $DB;

        $records = $DB->get_records(
            self::TABLE,
            [
                'matcheduserid' => $userid,
                'matchstatus' =>
                    InboxContactMatch::STATUS_AUTOMATIC,
                'matchlocked' => 0,
            ],
            'id ASC'
        );

        return array_values(
            array_map(
                fn(object $record): InboxContact =>
                    $this->map($record),
                $records
            )
        );
    }

    public function clear_automatic_match(
        int $contactid
    ): void {
        global $DB;

        $DB->update_record(
            self::TABLE,
            (object)[
                'id' => $contactid,
                'matcheduserid' => null,
                'matchstatus' =>
                    InboxContactMatch::STATUS_UNMATCHED,
                'matchsource' =>
                    InboxContactMatch::SOURCE_NONE,
                'matchconfidence' => 0,
                'lastmatchedat' => time(),
                'timemodified' => time(),
            ]
        );
    }

}