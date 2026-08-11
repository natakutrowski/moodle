<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\personaloffer\audience;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\persistence\CommerceCatalogHydrator;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;
use local_subscriptions\commerce\persistence\CommercePersistenceSchema;
use local_subscriptions\commerce\catalog\presentation\CommerceProductDisplayNameResolver;

final class CommercePersonalOfferNativeProductAudienceProvider implements CommercePersonalOfferAudienceProvider {
    public const TYPE = 'native_product';

    private const SUCCESS = ['paid', 'completed', 'captured', 'succeeded', 'success'];

    public function __construct(
        private readonly \moodle_database $db,
        private readonly CommercePersonalOfferAudienceCandidateResolver $resolver
    ) {
    }

    public function get_type(): string {
        return self::TYPE;
    }

    public function source(int $sourceid, string $language): array {
        $products = new CommerceProductRepository($this->db, new CommerceCatalogHydrator());
        $product = $products->find_by_id($sourceid);
        if ($product === null) {
            throw new \moodle_exception('commerce_personal_offer_source_missing', 'local_subscriptions');
        }

        $displayname = CommerceProductDisplayNameResolver::create($this->db)->resolve(
            [$product->get_sku()],
            $language,
            $product->get_name()
        );

        return [
            'type' => self::TYPE,
            'id' => $sourceid,
            'name' => $displayname,
            'sku' => $product->get_sku(),
            'producttype' => $product->get_type(),
            'active' => $product->is_active(),
        ];
    }

    public function candidates(int $sourceid, array $criteria, string $language): array {
        $source = $this->source($sourceid, $language);
        $sku = strtoupper((string)$source['sku']);
        $out = [];

        $params = ['sku' => $sku];
        $statuses = [];
        foreach (self::SUCCESS as $i => $status) {
            $params['s' . $i] = $status;
            $statuses[] = ':s' . $i;
        }

        $where = [
            'UPPER(i.itemreference) = :sku',
            'EXISTS (
                SELECT 1
                  FROM {' . CommercePersistenceSchema::TABLE_PAYMENT . '} pay
                 WHERE pay.purchaseid = p.id
                   AND pay.status IN (' . implode(',', $statuses) . ')
            )',
        ];
        if (!empty($criteria['from'])) {
            $where[] = 'p.timecreated >= :from';
            $params['from'] = (int)$criteria['from'];
        }
        if (!empty($criteria['to'])) {
            $where[] = 'p.timecreated <= :to';
            $params['to'] = (int)$criteria['to'];
        }

        $sql = 'SELECT DISTINCT p.id, p.reference, p.userid, p.customeremail, p.customerjson
                  FROM {' . CommercePersistenceSchema::TABLE_PURCHASE . '} p
                  JOIN {' . CommercePersistenceSchema::TABLE_ITEM . '} i ON i.purchaseid = p.id
                 WHERE ' . implode(' AND ', $where) . '
              ORDER BY p.id ASC';

        foreach ($this->db->get_records_sql($sql, $params) as $purchase) {
            $customer = json_decode((string)$purchase->customerjson, true);
            $this->resolver->add(
                $out,
                !empty($purchase->userid) ? (int)$purchase->userid : null,
                (string)$purchase->customeremail,
                [
                    'firstname' => is_array($customer) ? (string)($customer['firstname'] ?? '') : '',
                    'lastname' => is_array($customer) ? (string)($customer['lastname'] ?? '') : '',
                ],
                'native_purchase:' . (string)$purchase->reference,
                (int)$purchase->id
            );
        }

        // Native grants make ownership visible even when there is no purchase
        // (manual grants, bulk grants, migrations).
        $grantparams = [
            'sku' => $sku,
            'now' => time(),
            'now2' => time(),
            'rootpattern' => '%"rootsku":"' . $this->db->sql_like_escape($sku) . '"%',
        ];
        $grantsql = "SELECT id, grantreference, productsku, beneficiaryuserid, beneficiaryemail, metadatajson
                       FROM {local_subs_commerce_grant}
                      WHERE status = 'active'
                        AND validfrom <= :now
                        AND (validuntil IS NULL OR validuntil = 0 OR validuntil >= :now2)
                        AND (
                            UPPER(productsku) = :sku
                            OR metadatajson LIKE :rootpattern
                        )
                   ORDER BY id ASC";
        foreach ($this->db->get_records_sql($grantsql, $grantparams) as $grant) {
            $this->resolver->add(
                $out,
                !empty($grant->beneficiaryuserid) ? (int)$grant->beneficiaryuserid : null,
                (string)$grant->beneficiaryemail,
                [],
                'native_grant:' . (string)$grant->grantreference
            );
        }

        return array_values($out);
    }
}
