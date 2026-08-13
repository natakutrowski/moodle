<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\purchase\communication;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseCustomer;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseDetails;
use moodle_database;

/**
 * Resolves the current customer identity used for CRM communication actions.
 *
 * The persisted Commerce purchase remains an immutable transactional snapshot.
 * Communication and User360 navigation may use a newer reconciled identity.
 */
final class CommercePurchaseCurrentCustomerResolver {
    public function __construct(private readonly moodle_database $database) {
    }

    public static function create(): self {
        global $DB;
        return new self($DB);
    }

    public function resolve(CommercePurchaseDetails $purchase): CommercePurchaseCustomer {
        $historical = $purchase->summary->customer;

        // A current Moodle account is the strongest canonical identity.
        if ($historical->userid !== null && $historical->userid > 0) {
            $user = $this->database->get_record(
                'user',
                ['id' => $historical->userid, 'deleted' => 0],
                'id,email,firstname,lastname',
                IGNORE_MISSING
            );
            if ($user) {
                return new CommercePurchaseCustomer(
                    (int)$user->id,
                    $this->prefer((string)$user->email, $historical->email),
                    $this->prefer((string)$user->firstname, $historical->firstname),
                    $this->prefer((string)$user->lastname, $historical->lastname)
                );
            }
        }

        // Legacy projection rows can legitimately be corrected after the immutable
        // Commerce purchase was created (e.g. gmai.com -> gmail.com).
        $legacy = $this->legacy_identity($purchase);
        if ($legacy !== null) {
            $userid = isset($legacy->userid) && (int)$legacy->userid > 0
                ? (int)$legacy->userid
                : $historical->userid;

            return new CommercePurchaseCustomer(
                $userid,
                $this->prefer((string)($legacy->email ?? ''), $historical->email),
                $this->prefer((string)($legacy->firstname ?? ''), $historical->firstname),
                $this->prefer((string)($legacy->lastname ?? ''), $historical->lastname)
            );
        }

        return $historical;
    }

    private function legacy_identity(CommercePurchaseDetails $purchase): ?\stdClass {
        $legacyid = (int)($purchase->legacyid ?? 0);
        if ($legacyid <= 0) {
            return null;
        }

        $table = match (strtolower(trim((string)$purchase->legacyfamily))) {
            'digital' => 'subscription_digital_payment_request',
            'subscription' => 'subscription_payment_request',
            default => null,
        };

        if ($table === null || !$this->database->get_manager()->table_exists($table)) {
            return null;
        }

        $columns = $this->database->get_columns($table);
        $fields = array_values(array_intersect(
            ['id', 'userid', 'email', 'firstname', 'lastname'],
            array_keys($columns)
        ));
        if (!in_array('id', $fields, true)) {
            return null;
        }

        return $this->database->get_record(
            $table,
            ['id' => $legacyid],
            implode(',', $fields),
            IGNORE_MISSING
        ) ?: null;
    }

    private function prefer(string $current, string $fallback): string {
        $current = trim($current);
        return $current !== '' ? $current : trim($fallback);
    }
}
