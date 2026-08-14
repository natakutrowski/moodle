<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\identity;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\admin_tools\AdminToolStatuses;
use local_subscriptions\crm\admin_tools\repositories\AdminToolRunRepository;
use moodle_database;

/**
 * Changes the current email identity of one Moodle/Commerce customer.
 *
 * Historical commercial snapshots are intentionally preserved. Only records that represent
 * the customer's current entitlement/contact identity are rewritten.
 */
final class CommerceCustomerEmailChangeService {
    public const TOOL_KEY = 'commerce_customer_email_change';

    /** @var array<int,array{table:string,userfield:string,emailfield:string,timefield:?string}> */
    private const CURRENT_IDENTITY_TABLES = [
        ['table' => 'local_subs_commerce_grant', 'userfield' => 'beneficiaryuserid', 'emailfield' => 'beneficiaryemail', 'timefield' => 'timemodified'],
        ['table' => 'local_subs_commerce_dig_access', 'userfield' => 'beneficiaryuserid', 'emailfield' => 'beneficiaryemail', 'timefield' => 'timemodified'],
        ['table' => 'local_subs_commerce_offer', 'userfield' => 'beneficiaryuserid', 'emailfield' => 'beneficiaryemail', 'timefield' => 'timemodified'],
        ['table' => 'local_subs_commerce_offer_campaign_member', 'userfield' => 'userid', 'emailfield' => 'email', 'timefield' => 'timemodified'],
        ['table' => 'local_subs_commerce_grant_campaign_member', 'userfield' => 'userid', 'emailfield' => 'email', 'timefield' => 'timemodified'],
        ['table' => 'local_subs_commerce_guest', 'userfield' => 'userid', 'emailfield' => 'email', 'timefield' => 'timemodified'],
    ];

    /** @var array<int,array{table:string,userfield:string,emailfield:string}> */
    private const HISTORICAL_TABLES = [
        ['table' => 'local_subscriptions_commerce_purchase', 'userfield' => 'userid', 'emailfield' => 'customeremail'],
        ['table' => 'subscription_payment_request', 'userfield' => 'userid', 'emailfield' => 'email'],
        ['table' => 'subscription_digital_payment_request', 'userfield' => 'userid', 'emailfield' => 'email'],
    ];

    public function __construct(
        private readonly moodle_database $database,
        private readonly ?AdminToolRunRepository $auditrepository = null
    ) {}

    /** @return array<string,mixed> */
    public function preview(int $userid, string $newemail): array {
        $user = $this->load_user($userid);
        $newemail = $this->normalise_email($newemail);
        $this->assert_available($userid, $newemail);

        $current = [];
        foreach (self::CURRENT_IDENTITY_TABLES as $spec) {
            $current[$spec['table']] = $this->count_current($spec, $userid);
        }

        $historical = [];
        foreach (self::HISTORICAL_TABLES as $spec) {
            $historical[$spec['table']] = $this->count_historical($spec, $userid);
        }

        return [
            'userid' => $userid,
            'oldemail' => $this->normalise_email((string)$user->email),
            'newemail' => $newemail,
            'current' => $current,
            'historical' => $historical,
            'currenttotal' => array_sum($current),
            'historicaltotal' => array_sum($historical),
        ];
    }

    /** @return array<string,mixed> */
    public function change(int $userid, string $newemail, int $actoruserid): array {
        global $CFG;
        require_once($CFG->dirroot . '/user/lib.php');

        $preview = $this->preview($userid, $newemail);
        if ($preview['oldemail'] === $preview['newemail']) {
            return $preview + ['changed' => false, 'updated' => []];
        }

        $audit = $this->auditrepository ?? new AdminToolRunRepository();
        $requestid = bin2hex(random_bytes(16));
        $started = microtime(true);
        $runid = $audit->create_running(
            self::TOOL_KEY,
            $actoruserid,
            'sensitive',
            $requestid,
            [
                'userid' => $userid,
                'oldemail' => $preview['oldemail'],
                'newemail' => $preview['newemail'],
                'historical_preserved' => array_keys($preview['historical']),
            ]
        );

        try {
            $transaction = $this->database->start_delegated_transaction();
            $user = $this->load_user($userid);
            $user->email = $preview['newemail'];
            user_update_user($user, false, false);

            $updated = [];
            foreach (self::CURRENT_IDENTITY_TABLES as $spec) {
                $updated[$spec['table']] = $this->rewrite_current($spec, $userid, $preview['newemail']);
            }

            $transaction->allow_commit();

            $result = $preview + [
                'changed' => true,
                'updated' => $updated,
                'requestid' => $requestid,
            ];
            $audit->complete(
                $runid,
                AdminToolStatuses::SUCCESS,
                $result,
                (int)round((microtime(true) - $started) * 1000)
            );
            return $result;
        } catch (\Throwable $exception) {
            $audit->fail(
                $runid,
                $exception->getMessage(),
                (int)round((microtime(true) - $started) * 1000)
            );
            throw $exception;
        }
    }

    private function load_user(int $userid): \stdClass {
        if ($userid <= 1) {
            throw new \invalid_parameter_exception('A real Moodle user is required.');
        }
        return $this->database->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);
    }

    private function normalise_email(string $email): string {
        $email = \core_text::strtolower(trim($email));
        if (!validate_email($email)) {
            throw new \invalid_parameter_exception('A valid email address is required.');
        }
        return $email;
    }

    private function assert_available(int $userid, string $email): void {
        $sql = 'SELECT id FROM {user} WHERE deleted = 0 AND id <> :userid AND ' .
            $this->database->sql_equal('email', ':email', false);
        if ($this->database->record_exists_sql($sql, ['userid' => $userid, 'email' => $email])) {
            throw new \moodle_exception('commerce_customer_email_change_duplicate', 'local_subscriptions');
        }
    }

    /** @param array{table:string,userfield:string,emailfield:string,timefield:?string} $spec */
    private function count_current(array $spec, int $userid): int {
        if (!$this->has_fields($spec['table'], [$spec['userfield'], $spec['emailfield']])) {
            return 0;
        }
        return (int)$this->database->count_records($spec['table'], [$spec['userfield'] => $userid]);
    }

    /** @param array{table:string,userfield:string,emailfield:string} $spec */
    private function count_historical(array $spec, int $userid): int {
        if (!$this->has_fields($spec['table'], [$spec['userfield'], $spec['emailfield']])) {
            return 0;
        }
        return (int)$this->database->count_records($spec['table'], [$spec['userfield'] => $userid]);
    }

    /** @param array{table:string,userfield:string,emailfield:string,timefield:?string} $spec */
    private function rewrite_current(array $spec, int $userid, string $email): int {
        if (!$this->has_fields($spec['table'], [$spec['userfield'], $spec['emailfield']])) {
            return 0;
        }
        $count = (int)$this->database->count_records($spec['table'], [$spec['userfield'] => $userid]);
        if ($count === 0) {
            return 0;
        }
        $sets = [$spec['emailfield'] . ' = :email'];
        $params = ['email' => $email, 'userid' => $userid];
        if ($spec['timefield'] !== null && $this->has_fields($spec['table'], [$spec['timefield']])) {
            $sets[] = $spec['timefield'] . ' = :now';
            $params['now'] = time();
        }
        $this->database->execute(
            'UPDATE {' . $spec['table'] . '} SET ' . implode(', ', $sets) .
            ' WHERE ' . $spec['userfield'] . ' = :userid',
            $params
        );
        return $count;
    }

    /** @param string[] $fields */
    private function has_fields(string $table, array $fields): bool {
        $manager = $this->database->get_manager();
        $xmldbtable = new \xmldb_table($table);
        if (!$manager->table_exists($xmldbtable)) {
            return false;
        }
        foreach ($fields as $field) {
            if (!$manager->field_exists($xmldbtable, new \xmldb_field($field))) {
                return false;
            }
        }
        return true;
    }
}
