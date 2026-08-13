<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\identity;

defined('MOODLE_INTERNAL') || die();

use moodle_database;

/**
 * Read-only graph of known and potential identities around one Moodle account.
 *
 * Evidence is deliberately explainable. Weak signals are surfaced for review only;
 * they never mutate or merge accounts.
 */
final class CommerceCustomerIdentityGraphService {
    public function __construct(private readonly moodle_database $database) {}

    /** @return array{primary:array<string,mixed>,emails:array<int,array<string,mixed>>,potential:array<int,array<string,mixed>>} */
    public function for_user(int $userid): array {
        $user = $this->database->get_record(
            'user', ['id' => $userid, 'deleted' => 0],
            'id,username,firstname,lastname,firstnamephonetic,lastnamephonetic,middlename,alternatename,email,phone1,phone2,confirmed,suspended,timecreated,lastaccess',
            MUST_EXIST
        );

        $emails = [];
        $this->add_email($emails, (string)$user->email, 'moodle_current', $userid, true);

        $purchases = $this->database->get_records('local_subscriptions_commerce_purchase', ['userid' => $userid]);
        foreach ($purchases as $purchase) {
            $this->add_email($emails, (string)$purchase->customeremail, 'commerce_purchase', (int)$purchase->id, false);
        }

        $legacy = $this->database->get_records('subscription_digital_payment_request', ['userid' => $userid]);
        foreach ($legacy as $row) {
            $this->add_email($emails, (string)$row->email, 'legacy_digital', (int)$row->id, false);
        }

        $offers = $this->database->get_records('local_subs_commerce_offer', ['beneficiaryuserid' => $userid]);
        foreach ($offers as $offer) {
            $this->add_email($emails, (string)$offer->beneficiaryemail, 'personal_offer', (int)$offer->id, false);
        }

        $merges = $this->database->get_records('local_subs_identity_merge', ['targetuserid' => $userid]);
        foreach ($merges as $merge) {
            foreach ($this->database->get_records('local_subs_identity_merge_source', ['mergeid' => (int)$merge->id]) as $source) {
                $this->add_email($emails, (string)$source->sourceemail, 'merged_account', (int)$source->sourceuserid, false);
            }
            $result = json_decode((string)$merge->resultjson, true);
            $transfer = is_array($result) && is_array($result['identitytransfer'] ?? null) ? $result['identitytransfer'] : [];
            foreach (['target_before_email', 'source_before_email'] as $key) {
                $this->add_email($emails, (string)($transfer[$key] ?? ''), 'merge_identity_history', (int)$merge->id, false);
            }
        }

        $potential = $this->potential_accounts($user);

        return [
            'primary' => [
                'userid' => $userid,
                'email' => strtolower(trim((string)$user->email)),
                'name' => fullname($user),
                'username' => (string)$user->username,
            ],
            'emails' => array_values($emails),
            'potential' => $potential,
        ];
    }

    /** @return array{primary:array<string,mixed>,emails:array<int,array<string,mixed>>,potential:array<int,array<string,mixed>>} */
    public function for_email(string $email): array {
        $email = strtolower(trim($email));
        if (!validate_email($email)) {
            throw new \invalid_parameter_exception('A valid customer email is required.');
        }
        $moodleuserid = (int)$this->database->get_field('user', 'id', ['email' => $email, 'deleted' => 0], IGNORE_MISSING);
        if ($moodleuserid > 1) {
            return $this->for_user($moodleuserid);
        }

        $emails = [];
        $this->add_email($emails, $email, 'external_current', 0, true);
        $firstname = '';
        $lastname = '';

        $legacy = $this->database->get_records('subscription_digital_payment_request', ['email' => $email], 'id DESC');
        foreach ($legacy as $row) {
            $this->add_email($emails, (string)$row->email, 'legacy_digital', (int)$row->id, false);
            if ($firstname === '') { $firstname = trim((string)($row->firstname ?? '')); }
            if ($lastname === '') { $lastname = trim((string)($row->lastname ?? '')); }
        }
        $purchases = $this->database->get_records('local_subscriptions_commerce_purchase', ['customeremail' => $email], 'id DESC');
        foreach ($purchases as $purchase) {
            $this->add_email($emails, (string)$purchase->customeremail, 'commerce_purchase', (int)$purchase->id, false);
            $customer = json_decode((string)($purchase->customerjson ?? ''), true);
            if (is_array($customer)) {
                if ($firstname === '') { $firstname = trim((string)($customer['firstname'] ?? '')); }
                if ($lastname === '') { $lastname = trim((string)($customer['lastname'] ?? '')); }
            }
        }
        foreach ($this->database->get_records('local_subs_commerce_offer', ['beneficiaryemail' => $email], 'id DESC') as $offer) {
            $this->add_email($emails, (string)$offer->beneficiaryemail, 'personal_offer', (int)$offer->id, false);
        }

        $potential = [];
        if ($firstname !== '' || $lastname !== '') {
            $potential = array_map(
                static fn(CommerceCustomerIdentitySimilarityMatch $match): array => [
                    'userid' => (int)$match->second->id,
                    'email' => (string)$match->second->email,
                    'name' => fullname($match->second),
                    'score' => $match->score,
                    'reasons' => $match->reasons,
                    'suspended' => !empty($match->second->suspended),
                    'confirmed' => !empty($match->second->confirmed),
                ],
                (new CommerceCustomerIdentitySimilarityService($this->database))->suggest_for_external_identity(
                    $email, $firstname, $lastname, 24, 10
                )
            );
        }

        return [
            'primary' => ['userid' => 0, 'email' => $email, 'name' => trim($firstname . ' ' . $lastname), 'username' => ''],
            'emails' => array_values($emails),
            'potential' => $potential,
        ];
    }

    /** @param array<string,array<string,mixed>> $emails */
    private function add_email(array &$emails, string $email, string $source, int $sourceid, bool $current): void {
        $email = strtolower(trim($email));
        if (!validate_email($email)) { return; }
        if (!isset($emails[$email])) {
            $emails[$email] = ['email' => $email, 'current' => $current, 'evidence' => []];
        }
        $emails[$email]['current'] = $emails[$email]['current'] || $current;
        $emails[$email]['evidence'][] = ['source' => $source, 'id' => $sourceid];
    }

    /** @return array<int,array<string,mixed>> */
    private function potential_accounts(\stdClass $user): array {
        $where = ['deleted = 0', 'id > 1', 'id <> :userid'];
        $params = ['userid' => (int)$user->id];
        $signals = [];
        foreach (['firstname', 'lastname'] as $field) {
            $value = trim((string)$user->{$field});
            if ($value === '') { continue; }
            $signals[] = $this->database->sql_equal($field, ':' . $field, false);
            $params[$field] = $value;
        }
        if ($signals === []) { return []; }
        $where[] = '(' . implode(' OR ', $signals) . ')';
        $candidates = $this->database->get_records_sql(
            'SELECT id,username,firstname,lastname,firstnamephonetic,lastnamephonetic,middlename,alternatename,email,phone1,phone2,confirmed,suspended,timecreated,lastaccess
               FROM {user}
              WHERE ' . implode(' AND ', $where) . '
           ORDER BY lastaccess DESC, id DESC',
            $params, 0, 100
        );
        $similarity = new CommerceCustomerIdentitySimilarityService($this->database);
        $out = [];
        foreach ($candidates as $candidate) {
            $match = $similarity->compare($user, $candidate);
            if ($match === null || $match->score < 24) { continue; }
            $out[] = [
                'userid' => (int)$candidate->id,
                'email' => (string)$candidate->email,
                'name' => fullname($candidate),
                'score' => $match->score,
                'reasons' => $match->reasons,
                'suspended' => !empty($candidate->suspended),
                'confirmed' => !empty($candidate->confirmed),
            ];
        }
        usort($out, static fn(array $a, array $b): int => $b['score'] <=> $a['score']);
        return array_slice($out, 0, 10);
    }
}
