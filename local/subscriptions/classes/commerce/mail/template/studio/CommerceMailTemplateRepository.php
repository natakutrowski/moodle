<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\template\studio;

defined('MOODLE_INTERNAL') || die();

use dml_exception;
use moodle_database;
use stdClass;

final class CommerceMailTemplateRepository {
    private const TABLE = 'local_subs_commerce_mail_tpl';

    public function __construct(private readonly moodle_database $db) {
    }

    public function get(string $mailtype, string $language): ?stdClass {
        $record = $this->db->get_record(self::TABLE, [
            'mailtype' => $mailtype,
            'language' => $language,
        ]);
        return $record ?: null;
    }

    /** @return stdClass[] */
    public function get_all(): array {
        return array_values($this->db->get_records(self::TABLE, [], 'mailtype ASC, language ASC'));
    }

    /**
     * @param array<string, mixed> $data
     */
    public function save(array $data, int $userid): stdClass {
        $now = time();
        $record = $this->get((string)$data['mailtype'], (string)$data['language']);
        $payload = (object)[
            'mailtype' => (string)$data['mailtype'],
            'language' => (string)$data['language'],
            'enabled' => !empty($data['enabled']) ? 1 : 0,
            'subject' => trim((string)$data['subject']),
            'preheader' => trim((string)($data['preheader'] ?? '')),
            'heading' => trim((string)($data['heading'] ?? '')),
            'introhtml' => (string)($data['introhtml'] ?? ''),
            'introformat' => FORMAT_HTML,
            'outrohtml' => (string)($data['outrohtml'] ?? ''),
            'outroformat' => FORMAT_HTML,
            'signaturehtml' => (string)($data['signaturehtml'] ?? ''),
            'signatureformat' => FORMAT_HTML,
            'headerimage' => !empty($data['headerimage']) ? 1 : 0,
            'timemodified' => $now,
            'usermodified' => $userid,
        ];

        if ($record === null) {
            $payload->timecreated = $now;
            $payload->id = $this->db->insert_record(self::TABLE, $payload);
        } else {
            $payload->id = (int)$record->id;
            $payload->timecreated = (int)$record->timecreated;
            $this->db->update_record(self::TABLE, $payload);
        }

        return $this->db->get_record(self::TABLE, ['id' => $payload->id], '*', MUST_EXIST);
    }

    public function delete(string $mailtype, string $language): void {
        $this->db->delete_records(self::TABLE, [
            'mailtype' => $mailtype,
            'language' => $language,
        ]);
    }
}
