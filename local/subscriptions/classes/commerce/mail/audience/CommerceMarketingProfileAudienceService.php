<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\audience;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\mail\campaign\CommerceMarketingRepeatSuppression;

/**
 * Read-only global Moodle audience: course students or Native product buyers,
 * versus strictly trial-only profiles. No implicit marketing consent.
 */
final class CommerceMarketingProfileAudienceService {
    public const CUSTOMERS = 'customers';
    public const TRIAL_ONLY = 'trial_only';
    public const MAX_EXCLUSIONS = 100;

    public function __construct(private readonly \moodle_database $db) {}

    public static function create(?\moodle_database $db = null): self {
        global $DB;
        return new self($db ?? $DB);
    }

    /** @param mixed $ids @return int[] */
    public static function normalise_ids(mixed $ids): array {
        if (!is_array($ids)) {
            throw new \invalid_parameter_exception('Invalid exclusion selection.');
        }
        $result = [];
        foreach ($ids as $id) {
            if (!is_scalar($id) || !ctype_digit((string)$id) || (int)$id <= 0) {
                throw new \invalid_parameter_exception('Invalid exclusion ID.');
            }
            $result[(int)$id] = (int)$id;
            if (count($result) > self::MAX_EXCLUSIONS) {
                throw new \invalid_parameter_exception('Too many selected exclusions.');
            }
        }
        return array_values($result);
    }

    /** @param int[] $courseids @param int[] $productids */
    public function preview(string $segment, array $courseids = [], array $productids = [],
            int $templateid = 0, bool $excludeprevious = true, int $exceptcampaignid = 0): array {
        if (!in_array($segment, [self::CUSTOMERS, self::TRIAL_ONLY], true)) {
            throw new \invalid_parameter_exception('Unknown Moodle audience segment.');
        }
        $courseids = self::normalise_ids($courseids);
        $productids = self::normalise_ids($productids);
        $this->assert_exists('course', $courseids);
        $this->assert_exists('local_subs_commerce_product', $productids);

        // Two independent sources, aggregated by Moodle user ID. A Native
        // purchase must be paid, not refunded, and reference a catalog SKU.
        $roles = $this->db->get_records_sql(
            "SELECT ra.userid AS id,
                    MAX(CASE WHEN r.shortname = 'student' THEN 1 ELSE 0 END) AS studentrole,
                    MAX(CASE WHEN r.shortname = 'trialstudent' THEN 1 ELSE 0 END) AS trialrole
               FROM {role_assignments} ra
               JOIN {role} r ON r.id = ra.roleid
               JOIN {context} ctx ON ctx.id = ra.contextid
              WHERE ctx.contextlevel = :contextlevel
                AND r.shortname IN ('student', 'trialstudent')
           GROUP BY ra.userid",
            ['contextlevel' => CONTEXT_COURSE]
        );
        $buyers = array_fill_keys($this->verified_buyers(), true);
        // An unlinked guest purchase can still be matched by an exact Moodle email,
        // but only if that email identifies a single, active Moodle account below.
        $guestbuyers = array_fill_keys($this->verified_guest_emails(true), true);
        // Trial-only must also exclude paid legacy plans with no Native SKU.
        $allpurchases = array_fill_keys($this->fulfilled_purchase_users(), true);
        $guestpurchases = array_fill_keys($this->verified_guest_emails(false), true);
        $ids = array_unique(array_merge(array_map('intval', array_keys($roles)),
            array_map('intval', array_keys($buyers))));
        foreach (array_chunk(array_keys($guestbuyers), 300) as $guestemails) {
            [$insql, $params] = $this->db->get_in_or_equal($guestemails, SQL_PARAMS_NAMED, 'guestemail');
            $ids = array_merge($ids, array_map('intval', $this->db->get_fieldset_sql(
                "SELECT id FROM {user} WHERE LOWER(TRIM(email)) $insql", $params)));
        }
        $ids = array_values(array_unique($ids));
        sort($ids, SORT_NUMERIC);

        $excludecourseusers = $this->course_holders($courseids);
        $excludeproductusers = $this->product_buyers($productids);
        $excludedproductemails = $productids !== []
            ? array_fill_keys($this->verified_guest_emails(true, $this->product_skus($productids)), true)
            : [];
        $previous = $excludeprevious
            ? (new CommerceMarketingRepeatSuppression($this->db))
                ->delivered_or_queued_emails($templateid, $exceptcampaignid)
            : [];
        // Bulk suppression prevents one database lookup per account while still
        // rejecting duplicates anywhere in Moodle (not only in this segment).
        $duplicateemails = array_fill_keys($this->db->get_fieldset_sql(
            'SELECT LOWER(TRIM(email)) AS canonicalemail FROM {user} '
            . 'WHERE deleted = 0 AND email IS NOT NULL '
            . 'GROUP BY LOWER(TRIM(email)) HAVING COUNT(1) > 1', []), true);
        $optoutemails = array_fill_keys($this->db->get_fieldset_sql(
            'SELECT email FROM {local_subs_mail_optout}', []), true);
        $rows = [];
        $recipients = [];
        $summary = ['candidates' => 0, 'eligible' => 0, 'excluded' => 0];
        $seenemails = [];
        // Moodle's get_records_list generates a bounded IN list, not 10k parameters.
        foreach (array_chunk($ids, 400) as $chunk) {
            $users = $this->db->get_records_list('user', 'id', $chunk, 'id ASC',
                'id,firstname,lastname,email,username,lang,deleted,suspended,confirmed,emailstop');
            foreach ($users as $user) {
                $userid = (int)$user->id;
                $student = !empty($roles[$userid]->studentrole);
                $trial = !empty($roles[$userid]->trialrole);
                $email = strtolower(trim((string)$user->email));
                $buyer = isset($buyers[$userid]) || isset($guestbuyers[$email]);
                if ($segment === self::CUSTOMERS && !$student && !$buyer) {
                    continue;
                }
                if ($segment === self::TRIAL_ONLY &&
                        (!$trial || $student || isset($allpurchases[$userid]) || isset($guestpurchases[$email]))) {
                    continue;
                }
                $reasons = [];
                if ((int)$user->deleted || (int)$user->suspended || !(int)$user->confirmed) {
                    $reasons[] = 'inactive_account';
                }
                if ((int)$user->emailstop) {
                    $reasons[] = 'email_stopped';
                }
                if (\is_siteadmin($userid)) {
                    $reasons[] = 'site_administrator';
                }
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $reasons[] = 'invalid_email';
                } else {
                    // Reject ambiguous identities, including a different non-candidate Moodle user.
                    if (isset($seenemails[$email]) || isset($duplicateemails[$email])) {
                        $reasons[] = 'ambiguous_email';
                    }
                    $seenemails[$email] = true;
                }
                if (isset($excludecourseusers[$userid])) {
                    $reasons[] = 'excluded_existing_course';
                }
                if (isset($excludeproductusers[$userid]) || isset($excludedproductemails[$email])) {
                    $reasons[] = 'excluded_purchased_product';
                }
                if ($email !== '' && isset($previous[$email])) {
                    $reasons[] = 'previously_contacted';
                }
                if ($reasons === [] && isset($optoutemails[$email])) {
                    $reasons[] = 'marketing_optout';
                }
                $sources = [];
                if ($student) { $sources[] = 'student'; }
                if ($trial) { $sources[] = 'trialstudent'; }
                if ($buyer) { $sources[] = 'native_purchase'; }
                $eligible = $reasons === [];
                $summary['candidates']++;
                $summary[$eligible ? 'eligible' : 'excluded']++;
                foreach ($sources as $source) {
                    $summary['source_' . $source] = ($summary['source_' . $source] ?? 0) + 1;
                }
                foreach ($reasons as $reason) {
                    $summary['reason_' . $reason] = ($summary['reason_' . $reason] ?? 0) + 1;
                }
                $language = strtolower(substr((string)$user->lang, 0, 2));
                if (!in_array($language, ['fr', 'ru', 'en'], true)) { $language = 'fr'; }
                $row = [
                    'userid' => $userid, 'email' => $email,
                    'firstname' => (string)$user->firstname, 'lastname' => (string)$user->lastname,
                    'language' => $language, 'sources' => $sources,
                    'eligible' => $eligible, 'reasons' => $reasons,
                ];
                $rows[] = $row;
                if ($eligible) {
                    $recipients[] = [
                        'userid' => $userid, 'email' => $email,
                        'firstname' => (string)$user->firstname, 'lastname' => (string)$user->lastname,
                        'language' => $language,
                    ];
                }
            }
        }
        return [
            'segment' => $segment, 'summary' => $summary, 'rows' => $rows,
            'recipients' => $recipients, 'excludedcourses' => $courseids,
            'excludedproducts' => $productids, 'excludeprevious' => $excludeprevious,
        ];
    }

    /** @param int[] $ids */
    private function assert_exists(string $table, array $ids): void {
        foreach ($ids as $id) {
            if (!$this->db->record_exists($table, ['id' => $id])) {
                throw new \invalid_parameter_exception('Unknown course or Native product in exclusion.');
            }
        }
    }

    /** @return int[] All paid, non-refunded purchasers (including legacy plans). */
    private function fulfilled_purchase_users(): array {
        return array_map('intval', $this->db->get_fieldset_sql(
            "SELECT DISTINCT p.userid FROM {local_subscriptions_commerce_purchase} p
              WHERE p.userid IS NOT NULL AND p.userid > 0 AND p.status IN ('captured','paid','fulfillment_pending','fulfilled','completed')
                AND NOT EXISTS (
                    SELECT 1 FROM {local_subscriptions_commerce_payment} pay
                    JOIN {local_subscriptions_commerce_refund} refund ON refund.paymentid = pay.id
                    WHERE pay.purchaseid = p.id
                      AND refund.status IN ('succeeded','completed','success','refunded')
                )", []));
    }

    /** @return int[] */
    private function verified_buyers(array $skus = []): array {
        $params = [];
        $skucondition = '';
        if ($skus !== []) {
            [$insql, $inparams] = $this->db->get_in_or_equal($skus, SQL_PARAMS_NAMED, 'sku');
            $params += $inparams;
            $skucondition = " AND UPPER(item.itemreference) $insql";
        }
        // Linked purchases use the authoritative Moodle user ID. Guest
        // purchases are matched separately by exact email and checked for ambiguity.
        $sql = "SELECT DISTINCT purchase.userid
                  FROM {local_subscriptions_commerce_purchase} purchase
                  JOIN {local_subscriptions_commerce_purchase_item} item
                    ON item.purchaseid = purchase.id
                  JOIN {local_subs_commerce_product} product
                    ON UPPER(product.sku) = UPPER(item.itemreference)
                 WHERE purchase.userid IS NOT NULL AND purchase.userid > 0
                   AND purchase.status IN ('captured','paid','fulfillment_pending','fulfilled','completed')
                   $skucondition
                   AND NOT EXISTS (
                       SELECT 1 FROM {local_subscriptions_commerce_payment} payment
                       JOIN {local_subscriptions_commerce_refund} refund
                         ON refund.paymentid = payment.id
                      WHERE payment.purchaseid = purchase.id
                        AND refund.status IN ('succeeded','completed','success','refunded')
                   )";
        return array_map('intval', $this->db->get_fieldset_sql($sql, $params));
    }

    /** @param int[] $courseids @return array<int,bool> */
    private function course_holders(array $courseids): array {
        if ($courseids === []) { return []; }
        [$insql, $params] = $this->db->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'excludedcourse');
        $params['contextlevel'] = CONTEXT_COURSE;
        $ids = $this->db->get_fieldset_sql(
            "SELECT DISTINCT ra.userid FROM {role_assignments} ra
               JOIN {role} r ON r.id = ra.roleid
               JOIN {context} ctx ON ctx.id = ra.contextid
              WHERE ctx.contextlevel = :contextlevel AND ctx.instanceid $insql
                AND r.shortname IN ('student','trialstudent','grammarstudent')", $params);
        [$pedinsql, $pedparams] = $this->db->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'pedcourse');
        $ids = array_merge($ids, $this->db->get_fieldset_sql(
            "SELECT DISTINCT userid FROM {local_subs_commerce_ped_access} WHERE courseid $pedinsql", $pedparams));
        return array_fill_keys(array_map('intval', $ids), true);
    }

    /**
     * @param string[] $skus Optional selected SKU list (empty = any Native item).
     * @return string[]
     */
    private function verified_guest_emails(bool $nativeonly, array $skus = []): array {
        $params = [];
        $join = $nativeonly
            ? 'JOIN {local_subscriptions_commerce_purchase_item} item ON item.purchaseid = purchase.id
               JOIN {local_subs_commerce_product} product ON UPPER(product.sku) = UPPER(item.itemreference)'
            : '';
        $filter = '';
        if ($skus !== []) {
            [$insql, $filterparams] = $this->db->get_in_or_equal($skus, SQL_PARAMS_NAMED, 'guests');
            $params += $filterparams;
            $filter = " AND UPPER(item.itemreference) $insql";
        }
        $sql = "SELECT DISTINCT LOWER(TRIM(purchase.customeremail)) AS email
                  FROM {local_subscriptions_commerce_purchase} purchase
                  $join
                 WHERE (purchase.userid IS NULL OR purchase.userid = 0)
                   AND purchase.status IN ('captured','paid','fulfillment_pending','fulfilled','completed')
                   AND purchase.customeremail IS NOT NULL
                   $filter
                   AND NOT EXISTS (
                       SELECT 1 FROM {local_subscriptions_commerce_payment} payment
                       JOIN {local_subscriptions_commerce_refund} refund ON refund.paymentid = payment.id
                      WHERE payment.purchaseid = purchase.id
                        AND refund.status IN ('succeeded','completed','success','refunded')
                   )";
        return array_values(array_filter(array_map('strtolower', $this->db->get_fieldset_sql($sql, $params)),
            static fn(string $email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) !== false));
    }

    /** @param int[] $productids @return array<int,bool> */
    private function product_buyers(array $productids): array {
        if ($productids === []) { return []; }
        return array_fill_keys($this->verified_buyers($this->product_skus($productids)), true);
    }

    /** @param int[] $productids @return string[] */
    private function product_skus(array $productids): array {
        [$insql, $params] = $this->db->get_in_or_equal($productids, SQL_PARAMS_NAMED, 'productid');
        $skus = $this->db->get_fieldset_sql(
            "SELECT sku FROM {local_subs_commerce_product} WHERE id $insql", $params);
        // A bundle purchase also owns its direct components.
        [$childsql, $childparams] = $this->db->get_in_or_equal($productids, SQL_PARAMS_NAMED, 'childid');
        $skus = array_merge($skus, $this->db->get_fieldset_sql(
            "SELECT DISTINCT parent.sku FROM {local_subs_commerce_prod_comp} component
               JOIN {local_subs_commerce_product} parent ON parent.id = component.parentproductid
              WHERE component.childproductid $childsql", $childparams));
        return array_values(array_unique(array_map('strtoupper', $skus)));
    }
}
