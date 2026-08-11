<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\course\library;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\order\reference\CommercePublicOrderReference;

/**
 * Enriches a caller-provided list of real Moodle course enrolments.
 *
 * This service never decides which courses a user can see. The caller remains
 * responsible for obtaining that list from Moodle enrolment APIs.
 */
final class CommerceCourseAccessEnrichmentService {
    private const GRANT_TABLE = 'local_subs_commerce_grant';
    private const PURCHASE_TABLE = 'local_subscriptions_commerce_purchase';

    public function __construct(
        private readonly \moodle_database $db,
        private readonly CommercePublicOrderReference $publicreference = new CommercePublicOrderReference()
    ) {
    }

    /** @param int[] $courseids */
    public function get_for_customer(
        int $userid,
        string $email,
        array $courseids,
        ?int $now = null
    ): CommerceCourseAccessCollection {
        if ($userid <= 0) {
            throw new \coding_exception('Course access enrichment requires a positive user identifier.');
        }
        $email = trim(\core_text::strtolower($email));
        if ($email !== '' && !validate_email($email)) {
            throw new \coding_exception('Course access enrichment received an invalid customer email.');
        }
        $courseids = $this->normalise_course_ids($courseids);
        if ($courseids === []) {
            return new CommerceCourseAccessCollection([]);
        }
        $now ??= time();

        $candidates = array_fill_keys($courseids, []);
        $this->append_native_candidates($candidates, $userid, $email, $courseids, $now);
        $this->append_legacy_candidates($candidates, $userid, $courseids, $now);

        $result = [];
        foreach ($courseids as $courseid) {
            $result[] = $this->select_primary($courseid, $candidates[$courseid] ?? [], $now);
        }
        return new CommerceCourseAccessCollection($result);
    }

    /** @param array<int,array<int,array<string,mixed>>> $candidates @param int[] $courseids */
    private function append_native_candidates(
        array &$candidates,
        int $userid,
        string $email,
        array $courseids,
        int $now
    ): void {
        $params = ['userid' => $userid, 'type' => 'course_access'];
        $identitysql = 'g.beneficiaryuserid = :userid';
        if ($email !== '') {
            $params['email'] = $email;
            $identitysql = '(' . $identitysql . ' OR ' . $this->db->sql_compare_text('g.beneficiaryemail')
                . ' = ' . $this->db->sql_compare_text(':email') . ')';
        }
        $sql = "SELECT g.*, p.timecreated AS purchasetimecreated
                  FROM {" . self::GRANT_TABLE . "} g
             LEFT JOIN {" . self::PURCHASE_TABLE . "} p ON p.reference = g.purchasereference
                 WHERE g.type = :type
                   AND g.status = 'active'
                   AND {$identitysql}
              ORDER BY g.validfrom DESC, g.id DESC";

        foreach ($this->db->get_records_sql($sql, $params) as $grant) {
            $parsed = $this->parse_native_course((string)$grant->resourcekey, (string)$grant->configurationjson);
            $courseid = $parsed['courseid'];
            if (!isset($candidates[$courseid])) {
                continue;
            }
            $configuration = $parsed['configuration'];
            $metadata = $this->decode_json((string)$grant->metadatajson);
            $accesslevel = strtolower(trim((string)($configuration['accesslevel'] ?? $parsed['accesslevel'])));
            $origin = $this->native_origin($accesslevel, $configuration, $metadata);
            $validuntil = $grant->validuntil === null ? null : (int)$grant->validuntil;
            $period = new CommerceCourseAccessPeriod(
                (int)$grant->validfrom,
                $validuntil,
                $validuntil === null
            );
            $purchaseurl = (new \moodle_url('/local/subscriptions/order_details.php', [
                'reference' => (string)$grant->purchasereference,
            ]))->out(false);
            $purchasetime = (int)($grant->purchasetimecreated ?? $grant->timecreated ?? $now);
            $publicreference = $this->publicreference->from_internal((string)$grant->purchasereference, $purchasetime);

            $candidates[$courseid][] = [
                'origin' => $origin,
                'period' => $period,
                'purchaseurl' => $purchaseurl,
                'commercialreference' => $publicreference,
                'productsku' => (string)$grant->productsku,
                'accesslevel' => $accesslevel === '' ? null : $accesslevel,
                'source' => 'native',
                'sourcekey' => (string)$grant->grantreference,
                'createdat' => (int)$grant->timecreated,
            ];
        }
    }

    /** @param array<int,array<int,array<string,mixed>>> $candidates @param int[] $courseids */
    private function append_legacy_candidates(array &$candidates, int $userid, array $courseids, int $now): void {
        $subscriptions = $this->db->get_records('user_subscription', ['userid' => $userid], 'start_date DESC, id DESC');
        if ($subscriptions === []) {
            return;
        }
        $planids = array_values(array_unique(array_map(static fn(\stdClass $s): int => (int)$s->planid, $subscriptions)));
        [$plansql, $planparams] = $this->db->get_in_or_equal($planids, SQL_PARAMS_NAMED, 'plan');
        $plans = $this->db->get_records_select('subscription_plan', "id {$plansql}", $planparams);
        $entitlements = $this->db->get_records_select('subscription_plan_entitlement', "planid {$plansql}", $planparams, 'priority ASC, id ASC');
        $byplan = [];
        foreach ($entitlements as $entitlement) {
            $byplan[(int)$entitlement->planid][(int)$entitlement->courseid] = strtolower(trim((string)$entitlement->accesslevel));
        }
        $scopeids = array_values(array_unique(array_filter(array_map(
            static fn(\stdClass $plan): int => (int)($plan->accessscopeid ?? 0),
            $plans
        ))));
        $scopes = $scopeids === [] ? [] : $this->db->get_records_list('subscription_access_scope', 'id', $scopeids);

        foreach ($subscriptions as $subscription) {
            $planid = (int)$subscription->planid;
            $plan = $plans[$planid] ?? null;
            if ($plan === null) {
                continue;
            }
            $mapped = $byplan[$planid] ?? [];
            if ($mapped === [] && !empty($plan->accessscopeid) && isset($scopes[(int)$plan->accessscopeid])) {
                foreach ($this->parse_course_list((string)$scopes[(int)$plan->accessscopeid]->course_ids) as $courseid) {
                    $mapped[$courseid] = !empty($plan->is_trial) ? 'trial' : 'full';
                }
            }
            foreach ($mapped as $courseid => $accesslevel) {
                if (!isset($candidates[$courseid])) {
                    continue;
                }
                $origin = !empty($plan->is_trial) || $accesslevel === 'trial'
                    ? CommerceCourseAccessOrigin::TRIAL
                    : CommerceCourseAccessOrigin::PURCHASE;
                $validuntil = (int)$subscription->end_date > 0 ? (int)$subscription->end_date : null;
                $period = new CommerceCourseAccessPeriod(
                    (int)$subscription->start_date > 0 ? (int)$subscription->start_date : null,
                    $validuntil,
                    false
                );
                $candidates[$courseid][] = [
                    'origin' => $origin,
                    'period' => $period,
                    'purchaseurl' => (new \moodle_url('/mes-achats'))->out(false),
                    'commercialreference' => null,
                    'productsku' => null,
                    'accesslevel' => $accesslevel === '' ? null : $accesslevel,
                    'source' => 'legacy',
                    'sourcekey' => 'subscription:' . (int)$subscription->id,
                    'createdat' => (int)$subscription->creation_date,
                ];
            }
        }
    }

    /** @param array<int,array<string,mixed>> $candidates */
    private function select_primary(int $courseid, array $candidates, int $now): CommerceCourseAccessPresentation {
        if ($candidates === []) {
            return CommerceCourseAccessPresentation::unknown($courseid);
        }
        usort($candidates, static function(array $left, array $right) use ($now): int {
            $leftcurrent = $left['period']->is_current($now) ? 1 : 0;
            $rightcurrent = $right['period']->is_current($now) ? 1 : 0;
            if ($leftcurrent !== $rightcurrent) {
                return $rightcurrent <=> $leftcurrent;
            }
            $priority = CommerceCourseAccessOrigin::priority($right['origin'])
                <=> CommerceCourseAccessOrigin::priority($left['origin']);
            if ($priority !== 0) {
                return $priority;
            }
            $leftlifetime = $left['period']->lifetime ? 1 : 0;
            $rightlifetime = $right['period']->lifetime ? 1 : 0;
            if ($leftlifetime !== $rightlifetime) {
                return $rightlifetime <=> $leftlifetime;
            }
            return ((int)$right['createdat']) <=> ((int)$left['createdat']);
        });
        $primary = $candidates[0];
        $sources = array_map(static fn(array $candidate): array => [
            'origin' => $candidate['origin'],
            'source' => $candidate['source'],
            'sourcekey' => $candidate['sourcekey'],
            'period' => $candidate['period']->to_array(),
        ], $candidates);

        return new CommerceCourseAccessPresentation(
            $courseid,
            $primary['origin'],
            $primary['period'],
            $primary['purchaseurl'],
            $primary['commercialreference'],
            $primary['productsku'],
            $primary['accesslevel'],
            $primary['source'],
            $sources
        );
    }

    /** @return array{courseid:int,accesslevel:string,configuration:array} */
    private function parse_native_course(string $resourcekey, string $configurationjson): array {
        if (!preg_match('/^course:(\d+)(?::([a-z0-9_-]+))?$/i', trim($resourcekey), $matches)) {
            return ['courseid' => 0, 'accesslevel' => '', 'configuration' => []];
        }
        return [
            'courseid' => (int)$matches[1],
            'accesslevel' => strtolower((string)($matches[2] ?? '')),
            'configuration' => $this->decode_json($configurationjson),
        ];
    }

    private function native_origin(string $accesslevel, array $configuration, array $metadata): string {
        if ($accesslevel === 'trial') {
            return CommerceCourseAccessOrigin::TRIAL;
        }
        $hint = strtolower(trim((string)(
            $configuration['origin'] ?? $configuration['accessorigin'] ?? $metadata['origin'] ?? $metadata['accessorigin'] ?? ''
        )));
        if (in_array($hint, ['gift', 'offered', 'complimentary', 'free_grant'], true)) {
            return CommerceCourseAccessOrigin::GIFT;
        }
        if (in_array($hint, ['admin', 'manual', 'administrative'], true)) {
            return CommerceCourseAccessOrigin::ADMIN;
        }
        return CommerceCourseAccessOrigin::PURCHASE;
    }

    /** @param mixed[] $courseids @return int[] */
    private function normalise_course_ids(array $courseids): array {
        $normalised = [];
        foreach ($courseids as $courseid) {
            $courseid = (int)$courseid;
            if ($courseid > 0 && $courseid !== SITEID) {
                $normalised[$courseid] = $courseid;
            }
        }
        sort($normalised);
        return array_values($normalised);
    }

    /** @return int[] */
    private function parse_course_list(string $value): array {
        return $this->normalise_course_ids(preg_split('/[,;\s]+/', $value, -1, PREG_SPLIT_NO_EMPTY) ?: []);
    }

    private function decode_json(string $json): array {
        if (trim($json) === '') {
            return [];
        }
        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            return is_array($decoded) ? $decoded : [];
        } catch (\JsonException) {
            return [];
        }
    }
}
