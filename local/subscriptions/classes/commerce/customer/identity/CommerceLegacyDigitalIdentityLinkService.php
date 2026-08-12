<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\identity;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\admin\AdminEvents;
use local_subscriptions\admin\AdminLog;
use moodle_database;

/**
 * Manually attaches an account-less Legacy Digital identity to an existing
 * Moodle user without changing that Moodle user's email or learning history.
 */
final class CommerceLegacyDigitalIdentityLinkService {
    private const TABLE = 'subscription_digital_payment_request';

    public function __construct(
        private readonly moodle_database $database,
        private readonly CommerceCustomerIdentitySimilarityService $similarity
    ) {
    }

    public function preview(
        string $legacyemail,
        int $targetuserid
    ): CommerceLegacyDigitalIdentityLinkPreview {
        global $CFG;

        $legacyemail = \core_text::strtolower(trim($legacyemail));
        if (
            $legacyemail === ''
            || !filter_var($legacyemail, FILTER_VALIDATE_EMAIL)
        ) {
            throw new \invalid_parameter_exception('Invalid Legacy Digital email.');
        }

        $user = $this->database->get_record(
            'user',
            [
                'id' => $targetuserid,
                'deleted' => 0,
                'mnethostid' => (int)$CFG->mnet_localhost_id,
            ],
            'id,username,firstname,lastname,firstnamephonetic,lastnamephonetic,'
                . 'middlename,alternatename,email,phone1,phone2,confirmed,'
                . 'suspended,timecreated,lastaccess',
            MUST_EXIST
        );

        $identity = $this->legacy_identity($legacyemail);
        if ($identity['count'] <= 0) {
            throw new \moodle_exception(
                'commerce_identity_legacy_link_no_purchases',
                'local_subscriptions'
            );
        }

        $external = (object)[
            'id' => 0,
            'email' => $legacyemail,
            'firstname' => $identity['firstname'],
            'lastname' => $identity['lastname'],
            'firstnamephonetic' => '',
            'lastnamephonetic' => '',
            'middlename' => '',
            'alternatename' => '',
            'phone1' => '',
            'phone2' => '',
        ];

        $match = $this->similarity->compare($external, $user);
        $score = $match?->score ?? 0;
        $reasons = $match?->reasons ?? [];

        return new CommerceLegacyDigitalIdentityLinkPreview(
            $legacyemail,
            $identity['firstname'],
            $identity['lastname'],
            (int)$user->id,
            (string)$user->email,
            fullname($user),
            $identity['count'],
            $score,
            $reasons
        );
    }

    public function execute(
        string $legacyemail,
        int $targetuserid,
        int $actoruserid
    ): CommerceLegacyDigitalIdentityLinkPreview {
        $preview = $this->preview($legacyemail, $targetuserid);
        if (!$preview->can_execute()) {
            throw new \moodle_exception(
                'commerce_identity_legacy_link_similarity_too_low',
                'local_subscriptions'
            );
        }

        $transaction = $this->database->start_delegated_transaction();

        $current = $this->preview($legacyemail, $targetuserid);
        if (!$current->can_execute()) {
            throw new \moodle_exception(
                'commerce_identity_legacy_link_similarity_too_low',
                'local_subscriptions'
            );
        }

        $emailcondition = $this->database->sql_equal(
            'email',
            ':legacyemail',
            false
        );

        $this->database->execute(
            'UPDATE {' . self::TABLE . '}
                SET userid = :targetuserid,
                    last_update = :now
              WHERE userid IS NULL
                AND status IN (\'paid\', \'completed\')
                AND ' . $emailcondition,
            [
                'targetuserid' => $targetuserid,
                'now' => time(),
                'legacyemail' => $legacyemail,
            ]
        );

        $transaction->allow_commit();

        AdminLog::log(
            AdminEvents::USER_LEGACY_DIGITAL_LINKED,
            $targetuserid,
            'user',
            $targetuserid,
            [
                'legacyemail' => $legacyemail,
                'targetemail' => $current->targetemail,
                'legacypurchases' => $current->legacypurchases,
                'similarityscore' => $current->similarityscore,
                'reasons' => $current->reasons,
                'source' => 'legacy_digital_identity_link',
            ]
        );

        return $current;
    }

    /**
     * @return array{firstname:string,lastname:string,count:int}
     */
    private function legacy_identity(string $legacyemail): array {
        $emailcondition = $this->database->sql_equal(
            'email',
            ':legacyemail',
            false
        );

        $records = array_values($this->database->get_records_sql(
            'SELECT id,firstname,lastname
               FROM {' . self::TABLE . '}
              WHERE userid IS NULL
                AND status IN (\'paid\', \'completed\')
                AND ' . $emailcondition . '
           ORDER BY COALESCE(payment_date, creation_date) DESC, id DESC',
            ['legacyemail' => $legacyemail]
        ));

        $firstname = '';
        $lastname = '';
        foreach ($records as $record) {
            if ($firstname === '') {
                $firstname = trim((string)$record->firstname);
            }
            if ($lastname === '') {
                $lastname = trim((string)$record->lastname);
            }
            if ($firstname !== '' && $lastname !== '') {
                break;
            }
        }

        return [
            'firstname' => $firstname,
            'lastname' => $lastname,
            'count' => count($records),
        ];
    }
}
