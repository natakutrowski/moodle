<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\identity;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\admin\AdminEvents;
use local_subscriptions\admin\AdminLog;
use local_subscriptions\commerce\customer\reconciliation\CommerceCustomerIdentityReconciliationService;
use local_subscriptions\commerce\persistence\CommercePersistenceSchema;
use moodle_database;

/**
 * Manually attaches a Legacy Digital identity to an existing Moodle user.
 *
 * Both the historical Legacy row and its Native Commerce projection are
 * reconciled. This is required when student screens run in Native mode.
 */
final class CommerceLegacyDigitalIdentityLinkService {
    private const TABLE = 'subscription_digital_payment_request';

    public function __construct(
        private readonly moodle_database $database,
        private readonly CommerceCustomerIdentitySimilarityService $similarity,
        private readonly CommerceCustomerIdentityReconciliationService $reconciliation
    ) {
    }

    public function preview(
        string $legacyemail,
        int $targetuserid
    ): CommerceLegacyDigitalIdentityLinkPreview {
        global $CFG;

        $legacyemail = \core_text::strtolower(trim($legacyemail));
        if ($legacyemail === '' || !filter_var($legacyemail, FILTER_VALIDATE_EMAIL)) {
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

        // Include rows already linked to the selected target. This makes the
        // operation repairable after the original M4.2G partial link.
        $identity = $this->legacy_identity($legacyemail, $targetuserid);
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

        $nativepurchases = 0;
        $nativepurchaseslinked = 0;
        foreach ($identity['ids'] as $legacyid) {
            $native = $this->database->get_record(
                CommercePersistenceSchema::TABLE_PURCHASE,
                [
                    'legacyfamily' => 'digital',
                    'legacyid' => $legacyid,
                ],
                'id,userid',
                IGNORE_MISSING
            );
            if ($native === false) {
                continue;
            }

            $nativepurchases++;
            if ((int)($native->userid ?? 0) === $targetuserid) {
                $nativepurchaseslinked++;
            }
        }

        return new CommerceLegacyDigitalIdentityLinkPreview(
            $legacyemail,
            $identity['firstname'],
            $identity['lastname'],
            (int)$user->id,
            (string)$user->email,
            fullname($user),
            $identity['count'],
            $nativepurchases,
            $nativepurchaseslinked,
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

        $identity = $this->legacy_identity($legacyemail, $targetuserid);
        $missingnative = 0;

        foreach ($identity['ids'] as $legacyid) {
            $result = $this->reconciliation->reconcile_legacy_digital_to_user(
                $legacyid,
                $targetuserid,
                true
            );

            if ($result->status === 'not_found') {
                $missingnative++;
                continue;
            }

            if (!in_array($result->status, ['reconciled', 'unchanged'], true)) {
                throw new \moodle_exception(
                    'commerce_identity_legacy_link_similarity_too_low',
                    'local_subscriptions'
                );
            }
        }

        if ($missingnative > 0) {
            throw new \coding_exception(
                'Legacy Digital identity link is missing a Native Commerce projection '
                    . 'for ' . $missingnative . ' purchase(s).'
            );
        }

        $current = $this->preview($legacyemail, $targetuserid);

        AdminLog::log(
            AdminEvents::USER_LEGACY_DIGITAL_LINKED,
            $targetuserid,
            'user',
            $targetuserid,
            [
                'legacyemail' => $legacyemail,
                'targetemail' => $current->targetemail,
                'legacypurchases' => $current->legacypurchases,
                'nativepurchases' => $current->nativepurchases,
                'nativepurchaseslinked' => $current->nativepurchaseslinked,
                'similarityscore' => $current->similarityscore,
                'reasons' => $current->reasons,
                'source' => 'legacy_digital_identity_link',
            ]
        );

        return $current;
    }

    /**
     * @return array{firstname:string,lastname:string,count:int,ids:int[]}
     */
    private function legacy_identity(
        string $legacyemail,
        int $targetuserid
    ): array {
        $emailcondition = $this->database->sql_equal(
            'email',
            ':legacyemail',
            false
        );

        $records = array_values($this->database->get_records_sql(
            'SELECT id,firstname,lastname
               FROM {' . self::TABLE . '}
              WHERE (userid IS NULL OR userid = :targetuserid)
                AND status IN (\'paid\', \'completed\')
                AND ' . $emailcondition . '
           ORDER BY COALESCE(payment_date, creation_date) DESC, id DESC',
            [
                'legacyemail' => $legacyemail,
                'targetuserid' => $targetuserid,
            ]
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
            'ids' => array_map(
                static fn(\stdClass $record): int => (int)$record->id,
                $records
            ),
        ];
    }
}
