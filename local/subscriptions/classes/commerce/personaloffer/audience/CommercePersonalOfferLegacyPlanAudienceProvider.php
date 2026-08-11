<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\personaloffer\audience;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\subscription_manager;

final class CommercePersonalOfferLegacyPlanAudienceProvider implements CommercePersonalOfferAudienceProvider {
    public const TYPE = 'legacy_plan';

    public function __construct(
        private readonly \moodle_database $db,
        private readonly CommercePersonalOfferAudienceCandidateResolver $resolver
    ) {
    }

    public function get_type(): string {
        return self::TYPE;
    }

    public function source(int $sourceid, string $language): array {
        $plan = $this->db->get_record(
            'subscription_plan',
            ['id' => $sourceid],
            'id,name,is_active,duration_key',
            MUST_EXIST
        );
        $translated = subscription_manager::get_translated_plan_name((int)$plan->id, $language);

        return [
            'type' => self::TYPE,
            'id' => (int)$plan->id,
            'name' => $translated ?: (string)$plan->name,
            'active' => !empty($plan->is_active),
            'durationkey' => (string)$plan->duration_key,
        ];
    }

    public function candidates(int $sourceid, array $criteria, string $language): array {
        $params = ['planid' => $sourceid];
        $where = ['us.planid = :planid', 'us.status IN (:active, :completed)'];
        $params['active'] = 'active';
        $params['completed'] = 'completed';

        if (!empty($criteria['from'])) {
            $where[] = 'COALESCE(NULLIF(us.creation_date, 0), us.start_date) >= :from';
            $params['from'] = (int)$criteria['from'];
        }
        if (!empty($criteria['to'])) {
            $where[] = 'COALESCE(NULLIF(us.creation_date, 0), us.start_date) <= :to';
            $params['to'] = (int)$criteria['to'];
        }

        $sql = "SELECT us.id AS evidenceid, us.userid, us.status, us.creation_date, us.start_date,
                       u.firstname, u.lastname, u.email
                  FROM {user_subscription} us
             LEFT JOIN {user} u ON u.id = us.userid AND u.deleted = 0
                 WHERE " . implode(' AND ', $where) . "
              ORDER BY us.id ASC";

        $out = [];
        foreach ($this->db->get_records_sql($sql, $params) as $record) {
            $this->resolver->add(
                $out,
                (int)$record->userid,
                (string)($record->email ?? ''),
                [
                    'firstname' => (string)($record->firstname ?? ''),
                    'lastname' => (string)($record->lastname ?? ''),
                ],
                'legacy_plan_subscription:#' . (int)$record->evidenceid . ':' . (string)$record->status
            );
        }

        return array_values($out);
    }
}
