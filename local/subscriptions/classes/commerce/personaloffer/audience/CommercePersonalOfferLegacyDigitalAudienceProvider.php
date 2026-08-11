<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\personaloffer\audience;

defined('MOODLE_INTERNAL') || die();

final class CommercePersonalOfferLegacyDigitalAudienceProvider implements CommercePersonalOfferAudienceProvider {
    public const TYPE = 'legacy_digital';

    private const SUCCESS = ['paid', 'completed', 'succeeded', 'success'];

    public function __construct(
        private readonly \moodle_database $db,
        private readonly CommercePersonalOfferAudienceCandidateResolver $resolver
    ) {
    }

    public function get_type(): string {
        return self::TYPE;
    }

    public function source(int $sourceid, string $language): array {
        $product = $this->db->get_record(
            'subscription_digital_product',
            ['id' => $sourceid],
            'id,name,slug,enabled',
            MUST_EXIST
        );
        $translation = $this->db->get_record(
            'subscription_digital_product_lang',
            ['productid' => $sourceid, 'lang' => $language],
            'title',
            IGNORE_MISSING
        );

        return [
            'type' => self::TYPE,
            'id' => (int)$product->id,
            'name' => $translation ? (string)$translation->title : (string)$product->name,
            'slug' => (string)$product->slug,
            'active' => !empty($product->enabled),
        ];
    }

    public function candidates(int $sourceid, array $criteria, string $language): array {
        $params = ['productid' => $sourceid];
        $statuses = [];
        foreach (self::SUCCESS as $i => $status) {
            $params['s' . $i] = $status;
            $statuses[] = ':s' . $i;
        }

        $where = [
            'pr.productid = :productid',
            'pr.status IN (' . implode(',', $statuses) . ')',
        ];
        if (!empty($criteria['from'])) {
            $where[] = 'COALESCE(pr.payment_date, pr.creation_date) >= :from';
            $params['from'] = (int)$criteria['from'];
        }
        if (!empty($criteria['to'])) {
            $where[] = 'COALESCE(pr.payment_date, pr.creation_date) <= :to';
            $params['to'] = (int)$criteria['to'];
        }

        $sql = "SELECT pr.id, pr.userid, pr.email, pr.firstname, pr.lastname, pr.status,
                       pr.payment_date, pr.creation_date
                  FROM {subscription_digital_payment_request} pr
                 WHERE " . implode(' AND ', $where) . "
              ORDER BY pr.id ASC";

        $out = [];
        foreach ($this->db->get_records_sql($sql, $params) as $record) {
            $this->resolver->add(
                $out,
                !empty($record->userid) ? (int)$record->userid : null,
                (string)$record->email,
                [
                    'firstname' => (string)$record->firstname,
                    'lastname' => (string)$record->lastname,
                ],
                'legacy_digital_purchase:#' . (int)$record->id . ':' . (string)$record->status
            );
        }

        return array_values($out);
    }
}
