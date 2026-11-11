<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\campaign;

defined('MOODLE_INTERNAL') || die();

/** Read-only deduplication of genuinely dispatched or already queued Mail Studio models. */
final class CommerceMarketingRepeatSuppression {
    public function __construct(private readonly \moodle_database $db) {}

    /**
     * A "same mail" is the same Mail Studio marketing template, irrespective of
     * campaign name. Drafts, test sends, failed/cancelled dispatches do not count.
     * Queued/processing deliveries count to avoid racing two campaigns.
     * @return array<string,bool> Canonical email => true.
     */
    public function delivered_or_queued_emails(int $templateid, int $exceptcampaignid = 0): array {
        if ($templateid <= 0) {
            return [];
        }
        $sql = "SELECT DISTINCT LOWER(TRIM(r.email)) AS email
                  FROM {local_subs_mail_campaign_recipient} r
                  JOIN {local_subs_mail_campaign} c ON c.id = r.campaignid
             LEFT JOIN {local_subs_commerce_mail} m ON m.id = r.mailid
                 WHERE c.templateid = :templateid
                   AND c.id <> :exceptcampaignid
                   AND (m.status IN ('sent', 'queued', 'processing')
                        OR (m.id IS NULL AND r.status = 'sent'))";
        $emails = $this->db->get_fieldset_sql($sql, [
            'templateid' => $templateid,
            'exceptcampaignid' => $exceptcampaignid,
        ]);
        return array_fill_keys(array_map('strtolower', array_map('trim', $emails)), true);
    }
}
