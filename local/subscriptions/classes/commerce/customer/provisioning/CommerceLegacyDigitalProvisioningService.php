<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\provisioning;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\admin\AdminEvents;
use local_subscriptions\admin\AdminLog;
use local_subscriptions\commerce\customer\identity\CommerceCustomerIdentitySimilarityService;
use local_subscriptions\commerce\customer\reconciliation\CommerceCustomerIdentityReconciliationService;
use local_subscriptions\commerce\mail\CommerceMailContext;
use local_subscriptions\commerce\mail\CommerceMailIdempotencyKey;
use local_subscriptions\commerce\mail\CommerceMailRecipient;
use local_subscriptions\commerce\mail\CommerceMailRequest;
use local_subscriptions\commerce\mail\CommerceMailRuntime;
use local_subscriptions\commerce\mail\CommerceMailType;
use local_subscriptions\commerce\persistence\CommercePersistenceSchema;
use moodle_database;

/**
 * Dry-run and execution service for Legacy Digital buyers without Moodle accounts.
 */
final class CommerceLegacyDigitalProvisioningService {
    public const TABLE_LEGACY = 'subscription_digital_payment_request';
    public const MAX_SCAN_ROWS = 3000;

    public function __construct(
        private readonly moodle_database $database,
        private readonly CommerceCustomerIdentitySimilarityService $similarity,
        private readonly CommerceCustomerIdentityReconciliationService $reconciliation
    ) {
    }

    /**
     * @param array{q?:string,status?:string} $criteria
     * @return array{total:int,items:CommerceLegacyDigitalProvisioningPlan[],truncated:bool}
     */
    public function search(
        array $criteria,
        int $offset = 0,
        int $limit = 50
    ): array {
        $q = trim((string)($criteria['q'] ?? ''));
        $status = trim((string)($criteria['status'] ?? ''));

        $where = [
            'userid IS NULL',
            "status IN ('paid', 'completed')",
            "email <> ''",
        ];
        $params = [];

        if ($q !== '') {
            $like = '%' . $this->database->sql_like_escape($q) . '%';
            $where[] = '('
                . $this->database->sql_like('email', ':qemail', false, false)
                . ' OR '
                . $this->database->sql_like('firstname', ':qfirstname', false, false)
                . ' OR '
                . $this->database->sql_like('lastname', ':qlastname', false, false)
                . ')';
            $params = [
                'qemail' => $like,
                'qfirstname' => $like,
                'qlastname' => $like,
            ];
        }

        $records = array_values($this->database->get_records_sql(
            'SELECT *
               FROM {' . self::TABLE_LEGACY . '}
              WHERE ' . implode(' AND ', $where) . '
           ORDER BY COALESCE(payment_date, creation_date) DESC, id DESC',
            $params,
            0,
            self::MAX_SCAN_ROWS + 1
        ));

        $truncated = count($records) > self::MAX_SCAN_ROWS;
        if ($truncated) {
            $records = array_slice($records, 0, self::MAX_SCAN_ROWS);
        }

        $emails = [];
        foreach ($records as $record) {
            $email = $this->normalise_email((string)$record->email);
            if ($email !== '') {
                $emails[$email] = $email;
            }
        }

        $plans = [];
        foreach (array_values($emails) as $email) {
            $plan = $this->plan_email($email);
            if (
                $status !== ''
                && $plan->status !== $status
            ) {
                continue;
            }
            $plans[] = $plan;
        }

        usort(
            $plans,
            static fn(
                CommerceLegacyDigitalProvisioningPlan $a,
                CommerceLegacyDigitalProvisioningPlan $b
            ): int => $a->email <=> $b->email
        );

        $total = count($plans);

        return [
            'total' => $total,
            'items' => array_slice(
                $plans,
                max(0, $offset),
                max(1, $limit)
            ),
            'truncated' => $truncated,
        ];
    }

    public function plan_email(
        string $email
    ): CommerceLegacyDigitalProvisioningPlan {
        global $CFG;

        $email = $this->normalise_email($email);

        if (
            $email === ''
            || !filter_var($email, FILTER_VALIDATE_EMAIL)
        ) {
            return new CommerceLegacyDigitalProvisioningPlan(
                $email,
                '',
                '',
                'fr',
                [],
                CommerceLegacyDigitalProvisioningPlan::STATUS_INVALID_EMAIL
            );
        }

        $emailcondition = $this->database->sql_equal(
            'email',
            ':email',
            false
        );

        $records = array_values($this->database->get_records_sql(
            'SELECT *
               FROM {' . self::TABLE_LEGACY . '}
              WHERE userid IS NULL
                AND status IN (\'paid\', \'completed\')
                AND ' . $emailcondition . '
           ORDER BY COALESCE(payment_date, creation_date) DESC, id DESC',
            ['email' => $email]
        ));

        if ($records === []) {
            return new CommerceLegacyDigitalProvisioningPlan(
                $email,
                '',
                '',
                'fr',
                [],
                CommerceLegacyDigitalProvisioningPlan::STATUS_EMPTY
            );
        }

        $identity = $this->best_identity($records);
        $users = $this->find_users_by_email($email);

        if (count($users) === 1) {
            return new CommerceLegacyDigitalProvisioningPlan(
                $email,
                $identity['firstname'],
                $identity['lastname'],
                $identity['language'],
                array_map(
                    static fn(\stdClass $record): int => (int)$record->id,
                    $records
                ),
                CommerceLegacyDigitalProvisioningPlan::STATUS_EXISTING_ACCOUNT,
                array_map('intval', array_keys($users))
            );
        }

        if (count($users) > 1) {
            return new CommerceLegacyDigitalProvisioningPlan(
                $email,
                $identity['firstname'],
                $identity['lastname'],
                $identity['language'],
                array_map(
                    static fn(\stdClass $record): int => (int)$record->id,
                    $records
                ),
                CommerceLegacyDigitalProvisioningPlan::STATUS_AMBIGUOUS_ACCOUNT,
                array_map('intval', array_keys($users))
            );
        }

        $similaraccounts = $this->similarity->suggest_for_external_identity(
            $email,
            $identity['firstname'],
            $identity['lastname'],
            // Account creation is intentionally conservative: an exact
            // normalised first + last name scores 65 and must therefore
            // require manual review instead of silently creating a duplicate.
            60,
            5
        );

        return new CommerceLegacyDigitalProvisioningPlan(
            $email,
            $identity['firstname'],
            $identity['lastname'],
            $identity['language'],
            array_map(
                static fn(\stdClass $record): int => (int)$record->id,
                $records
            ),
            $similaraccounts === []
                ? CommerceLegacyDigitalProvisioningPlan::STATUS_CREATABLE
                : CommerceLegacyDigitalProvisioningPlan::STATUS_SIMILAR_ACCOUNT,
            [],
            $similaraccounts
        );
    }

    public function execute_email(
        string $email,
        int $actoruserid,
        bool $allowSimilar = false
    ): CommerceLegacyDigitalProvisioningResult {
        global $CFG;

        // Re-plan from current DB state immediately before any write.
        $plan = $this->plan_email($email);
        if (!$plan->can_create($allowSimilar)) {
            return new CommerceLegacyDigitalProvisioningResult(
                $plan->email,
                $plan->status
            );
        }

        require_once($CFG->dirroot . '/user/lib.php');
        require_once($CFG->dirroot . '/local/subscriptions/lib.php');

        $transaction = $this->database->start_delegated_transaction();

        // Re-check exact account existence inside the transaction.
        $existing = $this->find_users_by_email($plan->email);
        if ($existing !== []) {
            $transaction->allow_commit();

            return new CommerceLegacyDigitalProvisioningResult(
                $plan->email,
                count($existing) === 1
                    ? CommerceLegacyDigitalProvisioningPlan::STATUS_EXISTING_ACCOUNT
                    : CommerceLegacyDigitalProvisioningPlan::STATUS_AMBIGUOUS_ACCOUNT
            );
        }

        $firstname = trim($plan->firstname);
        $lastname = trim($plan->lastname);
        if ($firstname === '') {
            $firstname = 'CampusFR';
        }
        if ($lastname === '') {
            $lastname = 'Client';
        }

        $user = (object)[
            'auth' => 'manual',
            'confirmed' => 0,
            'suspended' => 1,
            'mnethostid' => (int)$CFG->mnet_localhost_id,
            'username' => local_subscriptions_generate_unique_username(
                $firstname,
                $lastname,
                $plan->email
            ),
            'password' => 'Aa#' . bin2hex(random_bytes(24)),
            'email' => $plan->email,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'lang' => $plan->language,
            'description' =>
                'CampusFR account provisioned from historical Legacy Digital purchases.',
        ];

        $userid = (int)user_create_user(
            $user,
            true,
            false
        );

        set_user_preference(
            'local_subscriptions_account_origin',
            'legacy_digital_provisioning',
            $userid
        );
        set_user_preference(
            'local_subscriptions_account_state',
            'activation_pending',
            $userid
        );
        set_user_preference(
            'local_subscriptions_legacy_provisioned_at',
            time(),
            $userid
        );

        $emailcondition = $this->database->sql_equal(
            'email',
            ':email',
            false
        );
        $legacylinked = (int)$this->database->count_records_sql(
            'SELECT COUNT(1)
               FROM {' . self::TABLE_LEGACY . '}
              WHERE userid IS NULL
                AND status IN (\'paid\', \'completed\')
                AND ' . $emailcondition,
            ['email' => $plan->email]
        );

        if ($legacylinked > 0) {
            $this->database->execute(
                'UPDATE {' . self::TABLE_LEGACY . '}
                    SET userid = :userid,
                        last_update = :now
                  WHERE userid IS NULL
                    AND status IN (\'paid\', \'completed\')
                    AND ' . $emailcondition,
                [
                    'userid' => $userid,
                    'now' => time(),
                    'email' => $plan->email,
                ]
            );
        }

        $transaction->allow_commit();

        $nativereconciled = $this->reconcile_native_purchases(
            $plan->email
        );

        $user = $this->database->get_record(
            'user',
            ['id' => $userid],
            '*',
            MUST_EXIST
        );

        $activation = (
            new CommerceLegacyDigitalAccountActivationService(
                $this->database
            )
        )->issue_activation_url($user);

        $mailrecord = CommerceMailRuntime::queue_service()->queue(
            new CommerceMailRequest(
                CommerceMailType::ACCOUNT_ACTIVATION,
                new CommerceMailRecipient(
                    $plan->email,
                    fullname($user),
                    $userid
                ),
                new CommerceMailContext([
                    'customer' => [
                        'firstname' => (string)$user->firstname,
                        'fullname' => fullname($user),
                    ],
                    'purchase' => [
                        'reference' => '',
                    ],
                    'activationurl' => $activation['url']->out(false),
                    'activationexpirestimestamp' => $activation['expiresat'],
                    'accountemail' => $plan->email,
                    'links' => [],
                ]),
                $plan->language,
                CommerceMailIdempotencyKey::normalise(
                    'legacy-digital-account-activation:'
                    . $userid
                    . ':'
                    . substr(
                        hash(
                            'sha256',
                            $activation['url']->out(false)
                        ),
                        0,
                        32
                    )
                ),
                null
            )
        );

        AdminLog::log(
            AdminEvents::USER_LEGACY_DIGITAL_PROVISIONED,
            $userid,
            'user',
            $userid,
            [
                'email' => $plan->email,
                'legacypurchaseslinked' => $legacylinked,
                'nativepurchasesreconciled' => $nativereconciled,
                'mailqueueid' => (int)$mailrecord->id,
                'allowedsimilarityoverride' => $allowSimilar,
                'source' => 'legacy_digital_bulk_provisioning',
            ]
        );

        return new CommerceLegacyDigitalProvisioningResult(
            $plan->email,
            'created',
            $userid,
            $legacylinked,
            $nativereconciled,
            (int)$mailrecord->id
        );
    }

    /**
     * @param \stdClass[] $records
     * @return array{firstname:string,lastname:string,language:string}
     */
    private function best_identity(array $records): array {
        $firstname = '';
        $lastname = '';
        $language = '';

        foreach ($records as $record) {
            if ($firstname === '') {
                $firstname = trim((string)($record->firstname ?? ''));
            }
            if ($lastname === '') {
                $lastname = trim((string)($record->lastname ?? ''));
            }
            if ($language === '') {
                $candidate = clean_param(
                    (string)($record->buyer_lang ?? ''),
                    PARAM_LANG
                );
                if ($candidate !== '') {
                    $language = $candidate;
                }
            }

            if (
                $firstname !== ''
                && $lastname !== ''
                && $language !== ''
            ) {
                break;
            }
        }

        if ($language === '') {
            $language = clean_param(
                (string)get_config(
                    'local_subscriptions',
                    'defaultuserlang'
                ),
                PARAM_LANG
            );
        }
        $language = $this->resolve_installed_user_language($language);

        return [
            'firstname' => $firstname,
            'lastname' => $lastname,
            'language' => $language,
        ];
    }

    /** @return array<int,\stdClass> */
    private function find_users_by_email(
        string $email
    ): array {
        global $CFG;

        $emailcondition = $this->database->sql_equal(
            'email',
            ':email',
            false
        );

        return $this->database->get_records_sql(
            'SELECT id,email
               FROM {user}
              WHERE ' . $emailcondition . '
                AND deleted = 0
                AND mnethostid = :mnethostid
           ORDER BY id ASC',
            [
                'email' => $email,
                'mnethostid' => (int)$CFG->mnet_localhost_id,
            ],
            0,
            2
        );
    }

    private function reconcile_native_purchases(
        string $email
    ): int {
        $emailcondition = $this->database->sql_equal(
            'customeremail',
            ':customeremail',
            false
        );

        $records = $this->database->get_records_sql(
            'SELECT id
               FROM {' . CommercePersistenceSchema::TABLE_PURCHASE . '}
              WHERE userid IS NULL
                AND ' . $emailcondition . '
           ORDER BY id ASC',
            ['customeremail' => $email]
        );

        $count = 0;
        foreach ($records as $record) {
            $result = $this->reconciliation->reconcile_purchase(
                (int)$record->id,
                true
            );
            if (
                $result->status === 'reconciled'
                || $result->status === 'unchanged'
            ) {
                $count++;
            }
        }

        return $count;
    }

    private function resolve_installed_user_language(
        string $language
    ): string {
        $language = clean_param(
            trim($language),
            PARAM_LANG
        );

        $stringmanager = get_string_manager();
        if (
            $language !== ''
            && $stringmanager->translation_exists(
                $language,
                false
            )
        ) {
            return $language;
        }

        $current = clean_param(
            current_language(),
            PARAM_LANG
        );
        if (
            $current !== ''
            && $stringmanager->translation_exists(
                $current,
                false
            )
        ) {
            return $current;
        }

        return 'en';
    }

    private function normalise_email(
        string $email
    ): string {
        return \core_text::strtolower(trim($email));
    }
}
