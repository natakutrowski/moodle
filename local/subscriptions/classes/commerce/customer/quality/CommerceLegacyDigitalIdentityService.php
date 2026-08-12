<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\quality;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\admin\AdminEvents;
use local_subscriptions\admin\AdminLog;
use moodle_database;

/** Read/search/update operations for Legacy Digital customer coordinates. */
final class CommerceLegacyDigitalIdentityService {
    private const TABLE = 'subscription_digital_payment_request';
    private const SUCCESS_STATUSES = ['paid', 'completed', 'succeeded', 'success'];

    public function __construct(
        private readonly moodle_database $db,
        private readonly CommerceEmailQualityService $quality
    ) {}

    /**
     * @return array{total:int,items:array<int,array{record:\stdClass,diagnostic:CommerceEmailQualityDiagnostic,purchasecount:int}>}
     */
    public function search(string $q = '', string $qualitystatus = '', int $offset = 0, int $limit = 100): array {
        $params = [];
        $statusparams = [];
        foreach (self::SUCCESS_STATUSES as $i => $status) {
            $params['status' . $i] = $status;
            $statusparams[] = ':status' . $i;
        }
        $where = ['status IN (' . implode(',', $statusparams) . ')', "email <> ''"];
        if (trim($q) !== '') {
            $like = '%' . $this->db->sql_like_escape(trim($q)) . '%';
            $where[] = '(' . $this->db->sql_like('email', ':qemail', false, false)
                . ' OR ' . $this->db->sql_like('firstname', ':qfirstname', false, false)
                . ' OR ' . $this->db->sql_like('lastname', ':qlastname', false, false) . ')';
            $params['qemail'] = $like;
            $params['qfirstname'] = $like;
            $params['qlastname'] = $like;
        }

        // Scan a bounded set because quality status is computed in PHP.
        $records = array_values($this->db->get_records_sql(
            'SELECT * FROM {' . self::TABLE . '} WHERE ' . implode(' AND ', $where)
                . ' ORDER BY COALESCE(payment_date, creation_date) DESC, id DESC',
            $params,
            0,
            5000
        ));

        // Present one identity per normalised email, with the latest purchase as representative.
        $grouped = [];
        foreach ($records as $record) {
            $key = strtolower(trim((string)$record->email));
            if (!isset($grouped[$key])) {
                $grouped[$key] = ['record' => $record, 'purchasecount' => 0];
            }
            $grouped[$key]['purchasecount']++;
        }

        $items = [];
        foreach ($grouped as $group) {
            $record = $group['record'];
            $diagnostic = $this->quality->diagnose((string)$record->email);
            if ($qualitystatus !== '' && $diagnostic->status !== $qualitystatus) {
                continue;
            }
            $items[] = [
                'record' => $record,
                'diagnostic' => $diagnostic,
                'purchasecount' => (int)$group['purchasecount'],
            ];
        }

        return [
            'total' => count($items),
            'items' => array_slice($items, max(0, $offset), max(1, $limit)),
        ];
    }

    public function get(int $id): \stdClass {
        return $this->db->get_record(self::TABLE, ['id' => $id], '*', MUST_EXIST);
    }

    /**
     * Updates the selected Legacy purchase and, optionally, every Legacy Digital row carrying the exact same old email.
     * @return array{updated:int,oldemail:string,newemail:string}
     */
    public function update(int $id, string $email, string $firstname, string $lastname, bool $update_same_email = true): array {
        $record = $this->get($id);
        $email = strtolower(trim($email));
        $firstname = trim($firstname);
        $lastname = trim($lastname);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \invalid_parameter_exception('Invalid email address.');
        }
        $oldemail = strtolower(trim((string)$record->email));
        $tx = $this->db->start_delegated_transaction();
        $targets = [$record];
        if ($update_same_email && $oldemail !== '') {
            $targets = array_values($this->db->get_records_select(
                self::TABLE,
                $this->db->sql_equal('email', ':oldemail', false, false),
                ['oldemail' => $oldemail]
            ));
        }

        $now = time();
        foreach ($targets as $target) {
            $this->db->update_record(self::TABLE, (object)[
                'id' => (int)$target->id,
                'email' => $email,
                'firstname' => $firstname,
                'lastname' => $lastname,
                'last_update' => $now,
            ]);
        }

        AdminLog::log(
            AdminEvents::USER_LEGACY_DIGITAL_IDENTITY_UPDATED,
            !empty($record->userid) ? (int)$record->userid : null,
            'legacy_digital_purchase',
            (int)$record->id,
            [
                'oldemail' => $oldemail,
                'newemail' => $email,
                'oldfirstname' => (string)$record->firstname,
                'newfirstname' => $firstname,
                'oldlastname' => (string)$record->lastname,
                'newlastname' => $lastname,
                'updatedrecords' => count($targets),
                'update_same_email' => $update_same_email,
            ]
        );

        $tx->allow_commit();
        return ['updated' => count($targets), 'oldemail' => $oldemail, 'newemail' => $email];
    }
}
