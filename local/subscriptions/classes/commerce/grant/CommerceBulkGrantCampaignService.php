<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\grant;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\storefront\ownership\CommerceStorefrontOwnershipResolver;
use local_subscriptions\commerce\mail\service\CommerceGrantAccessMailService;

/**
 * Persists K14 bulk-grant snapshots and executes only their frozen members.
 */
final class CommerceBulkGrantCampaignService {
    public const TABLE_CAMPAIGN = 'local_subs_commerce_grant_campaign';
    public const TABLE_MEMBER = 'local_subs_commerce_grant_campaign_member';

    public const STATUS_READY = 'ready';
    public const STATUS_QUEUED = 'queued';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_COMPLETED_ERRORS = 'completed_errors';

    public const MEMBER_QUEUED = 'queued';
    public const MEMBER_COMPLETED = 'completed';
    public const MEMBER_SKIPPED = 'skipped';
    public const MEMBER_FAILED = 'failed';

    public function __construct(private readonly \moodle_database $db) {
    }

    /**
     * Revalidates one dry-run once, then freezes the selected eligible members.
     *
     * Execution never re-runs the audience query.
     *
     * @param int[] $selecteduserids
     */
    public function create_snapshot(
        string $name,
        string $sourcetype,
        int $sourceid,
        int $targetproductid,
        array $selecteduserids,
        int $actoruserid,
        string $reason = '',
        bool $sendemail = true
    ): int {
        $name = trim($name);
        if ($name === '') {
            throw new \moodle_exception('commerce_bulk_grant_campaign_name_required', 'local_subscriptions');
        }

        $selecteduserids = array_values(array_unique(array_filter(
            array_map('intval', $selecteduserids),
            static fn(int $id): bool => $id > 0
        )));
        if ($selecteduserids === []) {
            throw new \moodle_exception('commerce_bulk_grant_campaign_selection_required', 'local_subscriptions');
        }

        $simulation = (new CommerceBulkGrantDryRunService($this->db))->simulate(
            $sourcetype,
            $sourceid,
            $targetproductid,
            $actoruserid
        );

        $eligible = [];
        foreach ($simulation['rows'] as $row) {
            if (
                $row['decision'] === CommerceBulkGrantDryRunService::DECISION_ELIGIBLE
                && !empty($row['userid'])
            ) {
                $eligible[(int)$row['userid']] = $row;
            }
        }

        foreach ($selecteduserids as $userid) {
            if (!isset($eligible[$userid])) {
                throw new \moodle_exception(
                    'commerce_bulk_grant_campaign_selection_changed',
                    'local_subscriptions',
                    '',
                    $userid
                );
            }
        }

        $now = time();
        $transaction = $this->db->start_delegated_transaction();

        $campaignid = (int)$this->db->insert_record(self::TABLE_CAMPAIGN, (object)[
            'campaignkey' => 'grant-' . substr(hash('sha256', $name . '|' . microtime(true) . '|' . random_int(1, PHP_INT_MAX)), 0, 24),
            'name' => $name,
            'sourcetype' => $sourcetype,
            'sourceid' => $sourceid,
            'sourcejson' => json_encode($simulation['source'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'targetproductid' => $targetproductid,
            'targetjson' => json_encode($simulation['target'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'reason' => trim($reason),
            'sendemail' => $sendemail ? 1 : 0,
            'status' => self::STATUS_READY,
            'selectedcount' => count($selecteduserids),
            'processedcount' => 0,
            'successcount' => 0,
            'skippedcount' => 0,
            'failedcount' => 0,
            'startedat' => null,
            'completedat' => null,
            'timecreated' => $now,
            'timemodified' => $now,
            'usercreated' => $actoruserid,
            'usermodified' => $actoruserid,
        ]);

        foreach ($selecteduserids as $userid) {
            $row = $eligible[$userid];
            $this->db->insert_record(self::TABLE_MEMBER, (object)[
                'campaignid' => $campaignid,
                'memberkey' => 'u:' . $userid,
                'userid' => $userid,
                'firstname' => (string)$row['firstname'],
                'lastname' => (string)$row['lastname'],
                'email' => (string)$row['email'],
                'evidencejson' => json_encode($row['evidence'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'ownershipsource' => (string)$row['ownershipsource'],
                'plannedgrantcount' => (int)$row['grantcount'],
                'status' => self::MEMBER_QUEUED,
                'attempts' => 0,
                'lasterror' => null,
                'lastattemptat' => null,
                'completedat' => null,
                'timecreated' => $now,
                'timemodified' => $now,
            ]);
        }

        $transaction->allow_commit();
        return $campaignid;
    }

    public function launch(int $campaignid, int $actoruserid): void {
        $campaign = $this->get_campaign($campaignid);
        if (!in_array((string)$campaign->status, [self::STATUS_READY, self::STATUS_COMPLETED_ERRORS], true)) {
            throw new \moodle_exception('commerce_bulk_grant_campaign_not_launchable', 'local_subscriptions');
        }

        $now = time();
        $this->db->set_field(self::TABLE_CAMPAIGN, 'status', self::STATUS_QUEUED, ['id' => $campaignid]);
        if (empty($campaign->startedat)) {
            $this->db->set_field(self::TABLE_CAMPAIGN, 'startedat', $now, ['id' => $campaignid]);
        }
        $this->db->set_field(self::TABLE_CAMPAIGN, 'completedat', null, ['id' => $campaignid]);
        $this->touch_campaign($campaignid, $actoruserid, $now);
    }

    public function retry_failures(int $campaignid, int $actoruserid): int {
        $campaign = $this->get_campaign($campaignid);
        if (!in_array((string)$campaign->status, [self::STATUS_COMPLETED_ERRORS, self::STATUS_COMPLETED], true)) {
            throw new \moodle_exception('commerce_bulk_grant_campaign_retry_unavailable', 'local_subscriptions');
        }

        $failed = $this->db->get_records(self::TABLE_MEMBER, [
            'campaignid' => $campaignid,
            'status' => self::MEMBER_FAILED,
        ]);
        if ($failed === []) {
            return 0;
        }

        $now = time();
        foreach ($failed as $member) {
            $member->status = self::MEMBER_QUEUED;
            $member->lasterror = null;
            $member->timemodified = $now;
            $this->db->update_record(self::TABLE_MEMBER, $member);
        }

        $this->db->set_field(self::TABLE_CAMPAIGN, 'status', self::STATUS_QUEUED, ['id' => $campaignid]);
        $this->db->set_field(self::TABLE_CAMPAIGN, 'completedat', null, ['id' => $campaignid]);
        $this->touch_campaign($campaignid, $actoruserid, $now);
        $this->refresh_counters($campaignid);

        return count($failed);
    }

    /**
     * Executes at most $limit frozen members across queued/running campaigns.
     *
     * @return array{processed:int,completed:int,skipped:int,failed:int}
     */
    public function process(int $limit = 25): array {
        $limit = max(1, min(200, $limit));
        $stats = ['processed' => 0, 'completed' => 0, 'skipped' => 0, 'failed' => 0];

        $campaigns = $this->db->get_records_list(
            self::TABLE_CAMPAIGN,
            'status',
            [self::STATUS_QUEUED, self::STATUS_RUNNING],
            'timecreated ASC, id ASC'
        );

        foreach ($campaigns as $campaign) {
            if ($stats['processed'] >= $limit) {
                break;
            }

            if ((string)$campaign->status === self::STATUS_QUEUED) {
                $this->db->set_field(self::TABLE_CAMPAIGN, 'status', self::STATUS_RUNNING, ['id' => $campaign->id]);
            }

            $remaining = $limit - $stats['processed'];
            $members = $this->db->get_records(
                self::TABLE_MEMBER,
                ['campaignid' => $campaign->id, 'status' => self::MEMBER_QUEUED],
                'id ASC',
                '*',
                0,
                $remaining
            );

            foreach ($members as $member) {
                $this->process_member($campaign, $member, $stats);
            }

            $this->refresh_counters((int)$campaign->id);
            $this->finalise_if_done((int)$campaign->id);
        }

        return $stats;
    }

    public function get_campaign(int $campaignid): \stdClass {
        return $this->db->get_record(self::TABLE_CAMPAIGN, ['id' => $campaignid], '*', MUST_EXIST);
    }

    /** @return \stdClass[] */
    public function members(int $campaignid): array {
        return array_values($this->db->get_records(
            self::TABLE_MEMBER,
            ['campaignid' => $campaignid],
            'lastname ASC, firstname ASC, email ASC, id ASC'
        ));
    }

    /** @return \stdClass[] */
    public function campaigns(): array {
        return array_values($this->db->get_records(
            self::TABLE_CAMPAIGN,
            null,
            'timecreated DESC, id DESC'
        ));
    }

    /** @return array{queued:int,completed:int,skipped:int,failed:int,total:int} */
    public function summary(int $campaignid): array {
        $counts = [
            self::MEMBER_QUEUED => 0,
            self::MEMBER_COMPLETED => 0,
            self::MEMBER_SKIPPED => 0,
            self::MEMBER_FAILED => 0,
        ];
        $sql = 'SELECT status, COUNT(1) AS total
                  FROM {' . self::TABLE_MEMBER . '}
                 WHERE campaignid = :campaignid
              GROUP BY status';
        foreach ($this->db->get_records_sql($sql, ['campaignid' => $campaignid]) as $record) {
            $counts[(string)$record->status] = (int)$record->total;
        }

        return [
            'queued' => $counts[self::MEMBER_QUEUED] ?? 0,
            'completed' => $counts[self::MEMBER_COMPLETED] ?? 0,
            'skipped' => $counts[self::MEMBER_SKIPPED] ?? 0,
            'failed' => $counts[self::MEMBER_FAILED] ?? 0,
            'total' => array_sum($counts),
        ];
    }

    private function process_member(\stdClass $campaign, \stdClass $member, array &$stats): void {
        $now = time();
        $member->attempts = (int)$member->attempts + 1;
        $member->lastattemptat = $now;
        $member->timemodified = $now;

        try {
            $target = $this->db->get_record(
                'local_subs_commerce_product',
                ['id' => (int)$campaign->targetproductid],
                'id,sku',
                MUST_EXIST
            );
            $ownership = new CommerceStorefrontOwnershipResolver($this->db);
            if ($ownership->owns((int)$member->userid, (string)$target->sku)) {
                $member->status = self::MEMBER_SKIPPED;
                $member->lasterror = null;
                $member->completedat = $now;
                $this->db->update_record(self::TABLE_MEMBER, $member);
                $stats['skipped']++;
                $stats['processed']++;
                return;
            }

            $result = (new CommerceManualProductGrantService($this->db))->grant(
                (int)$member->userid,
                (int)$campaign->targetproductid,
                !empty($campaign->usermodified)
                    ? (int)$campaign->usermodified
                    : (!empty($campaign->usercreated) ? (int)$campaign->usercreated : 1),
                trim((string)$campaign->reason) !== ''
                    ? (string)$campaign->reason
                    : 'bulk_campaign:' . (string)$campaign->campaignkey
            );

            $allskipped = $result['results'] !== [];
            foreach ($result['results'] as $fulfillment) {
                if (!$fulfillment->is_skipped()) {
                    $allskipped = false;
                    break;
                }
            }

            if (!$allskipped && !empty($campaign->sendemail)) {
                // Queue only: the shared transactional-mail cron/throttling
                // controls provider-safe bulk delivery.
                CommerceGrantAccessMailService::create()->queue(
                    (int)$member->userid,
                    (int)$campaign->targetproductid,
                    $result['plan'],
                    false
                );
            }

            $member->status = $allskipped ? self::MEMBER_SKIPPED : self::MEMBER_COMPLETED;
            $member->lasterror = null;
            $member->completedat = $now;

            $stats[$allskipped ? 'skipped' : 'completed']++;
        } catch (\Throwable $exception) {
            $member->status = self::MEMBER_FAILED;
            $member->lasterror = \core_text::substr($exception->getMessage(), 0, 4000);
            $member->completedat = null;
            $stats['failed']++;
        }

        $this->db->update_record(self::TABLE_MEMBER, $member);
        $stats['processed']++;
    }

    private function refresh_counters(int $campaignid): void {
        $summary = $this->summary($campaignid);
        $campaign = $this->get_campaign($campaignid);
        $campaign->processedcount = $summary['completed'] + $summary['skipped'] + $summary['failed'];
        $campaign->successcount = $summary['completed'];
        $campaign->skippedcount = $summary['skipped'];
        $campaign->failedcount = $summary['failed'];
        $campaign->timemodified = time();
        $this->db->update_record(self::TABLE_CAMPAIGN, $campaign);
    }

    private function finalise_if_done(int $campaignid): void {
        $summary = $this->summary($campaignid);
        if ($summary['queued'] > 0) {
            return;
        }

        $campaign = $this->get_campaign($campaignid);
        $campaign->status = $summary['failed'] > 0
            ? self::STATUS_COMPLETED_ERRORS
            : self::STATUS_COMPLETED;
        $campaign->completedat = time();
        $campaign->timemodified = time();
        $this->db->update_record(self::TABLE_CAMPAIGN, $campaign);
    }

    private function touch_campaign(int $campaignid, int $actoruserid, int $now): void {
        $this->db->set_field(self::TABLE_CAMPAIGN, 'timemodified', $now, ['id' => $campaignid]);
        $this->db->set_field(self::TABLE_CAMPAIGN, 'usermodified', $actoruserid, ['id' => $campaignid]);
    }
}
