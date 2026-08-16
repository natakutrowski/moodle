<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\service;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\mail\CommerceMailType;
use moodle_database;

/**
 * Resolves Mail Engine records belonging to attribution campaigns.
 *
 * N7.19+ records carry an explicit grantcampaign context. Older campaign
 * mails can still be associated safely by the beneficiary, target product
 * and the member processing timestamp.
 */
final class CommerceGrantCampaignMailService {
    private const TABLE_MAIL = 'local_subs_commerce_mail';
    private const TABLE_CAMPAIGN = 'local_subs_commerce_grant_campaign';
    private const TABLE_MEMBER = 'local_subs_commerce_grant_campaign_member';

    /**
     * Historical campaign processing and queue insertion happen in the same
     * request. Keep a small tolerance for second-level clock differences.
     */
    private const HISTORICAL_TIME_TOLERANCE = 180;

    public function __construct(private readonly moodle_database $db) {
    }

    public static function create(): self {
        global $DB;
        return new self($DB);
    }

    /**
     * @return array{
     *   queued:int,
     *   processing:int,
     *   sent:int,
     *   failed:int,
     *   cancelled:int,
     *   total:int
     * }
     */
    public function summary(int $campaignid): array {
        $result = [
            'queued' => 0,
            'processing' => 0,
            'sent' => 0,
            'failed' => 0,
            'cancelled' => 0,
            'total' => 0,
        ];
        if ($campaignid <= 0) {
            return $result;
        }

        $records = $this->records_for_campaign($campaignid);
        foreach ($records as $record) {
            $status = strtolower((string)$record->status);
            if (array_key_exists($status, $result)) {
                $result[$status]++;
            }
            $result['total']++;
        }

        return $result;
    }

    /**
     * Returns the attribution campaign associated with a Mail Engine row.
     *
     * @param array<string,mixed>|null $decodedcontext
     */
    public function campaign_for_mail(
        \stdClass $mail,
        ?array $decodedcontext = null
    ): ?\stdClass {
        if ((string)($mail->mailtype ?? '') !== CommerceMailType::GRANT_ACCESS) {
            return null;
        }

        $context = $decodedcontext;
        if ($context === null) {
            $context = json_decode(
                (string)($mail->contextjson ?? ''),
                true
            );
        }
        $context = is_array($context) ? $context : [];

        $explicit = is_array($context['grantcampaign'] ?? null)
            ? $context['grantcampaign']
            : [];
        $campaignid = (int)($explicit['campaignid'] ?? 0);
        if ($campaignid > 0) {
            $campaign = $this->db->get_record(
                self::TABLE_CAMPAIGN,
                ['id' => $campaignid],
                'id,campaignkey,name,targetproductid',
                IGNORE_MISSING
            );
            if ($campaign) {
                return $campaign;
            }
        }

        $userid = (int)($mail->userid ?? 0);
        $grantaccess = is_array($context['grantaccess'] ?? null)
            ? $context['grantaccess']
            : [];
        $productid = (int)($grantaccess['rootproductid'] ?? 0);
        $mailtime = (int)($mail->timecreated ?? 0);

        if ($userid <= 0 || $productid <= 0 || $mailtime <= 0) {
            return null;
        }

        $tolerance = self::HISTORICAL_TIME_TOLERANCE;
        $sql = 'SELECT c.id, c.campaignkey, c.name, c.targetproductid
                  FROM {' . self::TABLE_CAMPAIGN . '} c
                  JOIN {' . self::TABLE_MEMBER . '} cm
                    ON cm.campaignid = c.id
                 WHERE c.targetproductid = :productid
                   AND c.sendemail = 1
                   AND cm.userid = :userid
                   AND cm.lastattemptat IS NOT NULL
                   AND cm.lastattemptat <= :mailmax
                   AND COALESCE(
                         cm.completedat,
                         cm.timemodified,
                         cm.lastattemptat
                       ) >= :mailmin
              ORDER BY ABS(cm.lastattemptat - :mailtime) ASC,
                       c.id DESC';

        $campaigns = $this->db->get_records_sql(
            $sql,
            [
                'productid' => $productid,
                'userid' => $userid,
                'mailmax' => $mailtime + $tolerance,
                'mailmin' => $mailtime - $tolerance,
                'mailtime' => $mailtime,
            ],
            0,
            2
        );

        if ($campaigns === []) {
            return null;
        }

        // If two campaigns were processed for the same user/product inside the
        // same narrow time window, do not manufacture an ambiguous CRM link.
        $campaigns = array_values($campaigns);
        if (
            count($campaigns) > 1
            && abs(
                $this->campaign_member_distance(
                    (int)$campaigns[0]->id,
                    $userid,
                    $mailtime
                )
                - $this->campaign_member_distance(
                    (int)$campaigns[1]->id,
                    $userid,
                    $mailtime
                )
            ) <= 1
        ) {
            return null;
        }

        return $campaigns[0];
    }

    /**
     * @return \stdClass[]
     */
    public function records_for_campaign(int $campaignid): array {
        if ($campaignid <= 0) {
            return [];
        }

        $campaign = $this->db->get_record(
            self::TABLE_CAMPAIGN,
            ['id' => $campaignid],
            'id,targetproductid',
            IGNORE_MISSING
        );
        if (!$campaign) {
            return [];
        }

        $records = [];

        // Exact N7.19+ association.
        $needle = '%"campaignid":' . $campaignid . '%';
        $explicit = $this->db->get_records_select(
            self::TABLE_MAIL,
            'mailtype = :mailtype AND contextjson LIKE :campaignneedle',
            [
                'mailtype' => CommerceMailType::GRANT_ACCESS,
                'campaignneedle' => $needle,
            ],
            'id ASC'
        );
        foreach ($explicit as $record) {
            $records[(int)$record->id] = $record;
        }

        // Backward compatibility for mails queued by this campaign before
        // grantcampaign was embedded in contextjson.
        $tolerance = self::HISTORICAL_TIME_TOLERANCE;
        $sql = 'SELECT DISTINCT m.*
                  FROM {' . self::TABLE_MAIL . '} m
                  JOIN {' . self::TABLE_MEMBER . '} cm
                    ON cm.userid = m.userid
                   AND cm.campaignid = :campaignid
                 WHERE m.mailtype = :mailtype
                   AND m.contextjson LIKE :productneedle
                   AND cm.lastattemptat IS NOT NULL
                   AND m.timecreated BETWEEN
                       (cm.lastattemptat - :tolerancebefore)
                       AND (
                           COALESCE(
                               cm.completedat,
                               cm.timemodified,
                               cm.lastattemptat
                           ) + :toleranceafter
                       )
              ORDER BY m.id ASC';

        $historical = $this->db->get_records_sql($sql, [
            'campaignid' => $campaignid,
            'mailtype' => CommerceMailType::GRANT_ACCESS,
            'productneedle' => '%"rootproductid":'
                . (int)$campaign->targetproductid . '%',
            'tolerancebefore' => $tolerance,
            'toleranceafter' => $tolerance,
        ]);
        foreach ($historical as $record) {
            $records[(int)$record->id] = $record;
        }

        ksort($records);
        return array_values($records);
    }

    private function campaign_member_distance(
        int $campaignid,
        int $userid,
        int $mailtime
    ): int {
        $attempt = $this->db->get_field(
            self::TABLE_MEMBER,
            'lastattemptat',
            [
                'campaignid' => $campaignid,
                'userid' => $userid,
            ],
            IGNORE_MISSING
        );
        if (!$attempt) {
            return PHP_INT_MAX;
        }
        return abs((int)$attempt - $mailtime);
    }
}
