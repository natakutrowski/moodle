<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\campaign;

defined('MOODLE_INTERNAL') || die();

final class CommerceMarketingCampaignRepository {
    private const CAMPAIGN = 'local_subs_mail_campaign';
    private const CONTENT = 'local_subs_mail_campaign_content';
    private const RECIPIENT = 'local_subs_mail_campaign_recipient';

    public function __construct(private readonly \moodle_database $db) {}

    /** @return \stdClass[] */
    public function all(): array {
        return array_values($this->db->get_records(self::CAMPAIGN, null, 'timecreated DESC, id DESC'));
    }

    public function get(int $id): \stdClass {
        return $this->db->get_record(self::CAMPAIGN, ['id' => $id], '*', MUST_EXIST);
    }

    /** @return array<string,\stdClass> */
    public function contents(int $campaignid): array {
        $records = $this->db->get_records(self::CONTENT, ['campaignid' => $campaignid], 'language ASC');
        $result = [];
        foreach ($records as $record) {
            $result[(string)$record->language] = $record;
        }
        return $result;
    }

    /** @return \stdClass[] */
    public function recipients(int $campaignid): array {
        return array_values($this->db->get_records(
            self::RECIPIENT,
            ['campaignid' => $campaignid],
            'id ASC'
        ));
    }

    /** @return \stdClass[] */
    public function due(int $now, int $limit = 20): array {
        return array_values($this->db->get_records_select(
            self::CAMPAIGN,
            'status = :status AND scheduledat IS NOT NULL AND scheduledat <= :now',
            ['status' => 'scheduled', 'now' => $now],
            'scheduledat ASC, id ASC',
            '*',
            0,
            max(1, $limit)
        ));
    }

    public function recipient_count(int $campaignid): int {
        return (int)$this->db->count_records(self::RECIPIENT, ['campaignid' => $campaignid]);
    }

    /** @return array{total:int,queued:int,sent:int,failed:int} */
    public function statistics(int $campaignid): array {
        $rows = $this->db->get_records_sql(
            'SELECT MIN(r.id) AS rowid, COALESCE(m.status, r.status) AS status, COUNT(1) AS total
               FROM {' . self::RECIPIENT . '} r
          LEFT JOIN {local_subs_commerce_mail} m ON m.id = r.mailid
              WHERE r.campaignid = :campaignid
           GROUP BY COALESCE(m.status, r.status)',
            ['campaignid' => $campaignid]
        );
        $result = ['total' => 0, 'queued' => 0, 'sent' => 0, 'failed' => 0];
        foreach ($rows as $row) {
            $count = (int)$row->total;
            $status = strtolower((string)$row->status);
            $result['total'] += $count;
            if (in_array($status, ['draft', 'queued', 'processing'], true)) {
                $result['queued'] += $count;
            } else if ($status === 'sent') {
                $result['sent'] += $count;
            } else if (in_array($status, ['failed', 'cancelled'], true)) {
                $result['failed'] += $count;
            }
        }
        return $result;
    }

    public function insert_campaign(\stdClass $record): int {
        return (int)$this->db->insert_record(self::CAMPAIGN, $record);
    }

    public function update_campaign(\stdClass $record): void {
        $this->db->update_record(self::CAMPAIGN, $record);
    }

    public function replace_contents(int $campaignid, array $translations): void {
        $this->db->delete_records(self::CONTENT, ['campaignid' => $campaignid]);
        foreach ($translations as $language => $content) {
            $this->db->insert_record(self::CONTENT, (object)[
                'campaignid' => $campaignid,
                'language' => $language,
                'subject' => (string)$content['subject'],
                'preheader' => (string)($content['preheader'] ?? ''),
                'bodyhtml' => (string)$content['bodyhtml'],
            ]);
        }
    }

    public function replace_recipients(int $campaignid, array $recipients, int $now): void {
        $this->db->delete_records(self::RECIPIENT, ['campaignid' => $campaignid]);
        foreach ($recipients as $recipient) {
            $this->db->insert_record(self::RECIPIENT, (object)[
                'campaignid' => $campaignid,
                'email' => (string)$recipient['email'],
                'firstname' => (string)($recipient['firstname'] ?? ''),
                'lastname' => (string)($recipient['lastname'] ?? ''),
                'language' => (string)($recipient['language'] ?? 'fr'),
                'userid' => $recipient['userid'] ?? null,
                'mailid' => null,
                'status' => 'draft',
                'timecreated' => $now,
                'timemodified' => $now,
            ]);
        }
    }

    public function mark_recipient_queued(int $recipientid, int $mailid, int $now): void {
        $this->db->update_record(self::RECIPIENT, (object)[
            'id' => $recipientid,
            'mailid' => $mailid,
            'status' => 'queued',
            'timemodified' => $now,
        ]);
    }

    public function mark_queued(int $campaignid, int $now): void {
        $this->db->update_record(self::CAMPAIGN, (object)[
            'id' => $campaignid,
            'status' => 'queued',
            'queuedat' => $now,
            'timemodified' => $now,
        ]);
    }

    public function mark_cancelled(int $campaignid, int $now): void {
        $this->db->update_record(self::CAMPAIGN, (object)[
            'id' => $campaignid,
            'status' => 'cancelled',
            'timemodified' => $now,
        ]);
    }
}
