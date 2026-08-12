<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\reconciliation;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\persistence\CommercePersistenceSchema;
use moodle_database;

/** Advanced read-only search over unresolved Native Commerce identities. */
final class CommerceCustomerIdentitySearchService {
    public function __construct(
        private readonly moodle_database $database,
        private readonly CommerceCustomerIdentityReconciliationService $reconciliation
    ) {}

    /**
     * @param array<string,mixed> $criteria
     * @return array{total:int,items:array<int,array{purchase:\stdClass,preview:CommerceCustomerIdentityReconciliationPreview}>}
     */
    public function search(array $criteria, int $offset, int $limit): array {
        [$sql, $params] = $this->query($criteria);
        $records = $this->database->get_records_sql($sql, $params);
        $filtered = [];
        $status = trim((string)($criteria['status'] ?? ''));
        $candidateuserid = max(0, (int)($criteria['candidateuserid'] ?? 0));

        foreach ($records as $record) {
            $preview = $this->reconciliation->preview_purchase((int)$record->id);
            $result = $preview->result;
            if ($status !== '' && $result->status !== $status) {
                continue;
            }
            if ($candidateuserid > 0) {
                $ids = $result->candidateuserids;
                if ($result->userid !== null) {
                    $ids[] = $result->userid;
                }
                if (!in_array($candidateuserid, array_map('intval', $ids), true)) {
                    continue;
                }
            }
            $filtered[] = ['purchase' => $record, 'preview' => $preview];
        }

        $total = count($filtered);
        return [
            'total' => $total,
            'items' => array_slice($filtered, max(0, $offset), max(0, $limit)),
        ];
    }

    /** @param array<string,mixed> $criteria */
    private function query(array $criteria): array {
        $where = ['p.userid IS NULL', 'p.customeremail IS NOT NULL', "p.customeremail <> ''"];
        $params = [];
        $like = fn(string $value): string => '%' . $this->database->sql_like_escape(trim($value)) . '%';

        $email = trim((string)($criteria['email'] ?? ''));
        if ($email !== '') {
            $where[] = $this->database->sql_like('p.customeremail', ':email', false, false);
            $params['email'] = $like($email);
        }
        $name = trim((string)($criteria['name'] ?? ''));
        if ($name !== '') {
            $where[] = $this->database->sql_like('p.customerjson', ':customername', false, false);
            $params['customername'] = $like($name);
        }
        $reference = trim((string)($criteria['reference'] ?? ''));
        if ($reference !== '') {
            $where[] = $this->database->sql_like('p.reference', ':reference', false, false);
            $params['reference'] = $like($reference);
        }
        $purchaseid = max(0, (int)($criteria['purchaseid'] ?? 0));
        if ($purchaseid > 0) {
            $where[] = 'p.id = :purchaseid';
            $params['purchaseid'] = $purchaseid;
        }
        $sku = trim((string)($criteria['sku'] ?? ''));
        if ($sku !== '') {
            $where[] = 'EXISTS (SELECT 1 FROM {' . CommercePersistenceSchema::TABLE_ITEM . '} pi'
                . ' WHERE pi.purchaseid = p.id AND '
                . $this->database->sql_like('pi.itemreference', ':sku', false, false) . ')';
            $params['sku'] = $like($sku);
        }
        $q = trim((string)($criteria['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(' . $this->database->sql_like('p.customeremail', ':qemail', false, false)
                . ' OR ' . $this->database->sql_like('p.reference', ':qreference', false, false)
                . ' OR ' . $this->database->sql_like('p.customerjson', ':qcustomer', false, false) . ')';
            $params['qemail'] = $like($q);
            $params['qreference'] = $like($q);
            $params['qcustomer'] = $like($q);
        }

        return [
            'SELECT p.* FROM {' . CommercePersistenceSchema::TABLE_PURCHASE . '} p'
                . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY p.id ASC',
            $params,
        ];
    }
}
