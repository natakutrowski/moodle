<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\gift;

defined('MOODLE_INTERNAL') || die();

/**
 * Read-only view of the isolated G2B gift registry. Does not grant access.
 * No public HTTP endpoint or CRM route is added by this milestone.
 */
final class CommerceGiftReadRepository {
    public function __construct(private readonly \moodle_database $db) {
    }

    public function find_by_issue_key(string $issuekey): ?\stdClass {
        if (!preg_match('/\A[a-f0-9]{64}\z/D', $issuekey)) {
            throw new \InvalidArgumentException('Invalid gift issuance key.');
        }
        $row = $this->db->get_record(
            CommerceGiftSchema::TABLE_GIFT, ['issuekey' => $issuekey], '*', IGNORE_MISSING
        );
        return $row ?: null;
    }

    public function find_by_uuid(string $uuid): ?\stdClass {
        if (!preg_match('/\A[a-f0-9]{32}\z/D', $uuid)) {
            throw new \InvalidArgumentException('Invalid gift reference.');
        }
        $row = $this->db->get_record(
            CommerceGiftSchema::TABLE_GIFT, ['giftuuid' => $uuid], '*', IGNORE_MISSING
        );
        return $row ?: null;
    }

    /** No personal data, token hashes, purchase details or message bodies. */
    public function count_by_status(): array {
        $rows = $this->db->get_records_sql(
            'SELECT status, COUNT(id) AS total FROM {' . CommerceGiftSchema::TABLE_GIFT .
            '} GROUP BY status ORDER BY status'
        );
        $result = [];
        foreach ($rows as $row) {
            $result[(string)$row->status] = (int)$row->total;
        }
        return $result;
    }
}
