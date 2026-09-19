<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\order\document;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\order\creditnote\CommerceIssuedCreditNote;
use moodle_database;

/** Read-only invoice/credit-note history for one Commerce purchase. */
final class CommerceOrderDocumentHistoryRepository {
    public function __construct(private readonly moodle_database $database) {}

    public function invoice_for_purchase(int $purchaseid): ?array {
        $record = $this->database->get_record(
            'local_subs_commerce_invoice',
            ['purchaseid' => $purchaseid],
            '*',
            IGNORE_MISSING
        );
        if ($record === false) {
            return null;
        }

        return [
            'id' => (int)$record->id,
            'number' => (string)$record->invoicenumber,
            'issuedat' => (int)$record->issuedat,
            'entitykey' => (string)$record->entitykey,
            'seller' => $this->decode((string)$record->sellerjson),
            'customer' => $this->decode((string)$record->customerjson),
            'financial' => $this->decode((string)$record->financialjson),
        ];
    }

    /** @return CommerceIssuedCreditNote[] */
    public function credit_notes_for_purchase(int $purchaseid): array {
        $records = $this->database->get_records(
            'local_subs_commerce_credit_note',
            ['purchaseid' => $purchaseid],
            'issuedat ASC, id ASC'
        );

        return array_map(
            fn(\stdClass $record): CommerceIssuedCreditNote => $this->map_credit_note($record),
            array_values($records)
        );
    }

    public function credit_note(int $id): ?CommerceIssuedCreditNote {
        if ($id <= 0) {
            return null;
        }

        $record = $this->database->get_record(
            'local_subs_commerce_credit_note',
            ['id' => $id],
            '*',
            IGNORE_MISSING
        );

        return $record === false ? null : $this->map_credit_note($record);
    }

    private function map_credit_note(\stdClass $record): CommerceIssuedCreditNote {
        return new CommerceIssuedCreditNote(
            (int)$record->id,
            (int)$record->refundid,
            (int)$record->invoiceid,
            (int)$record->purchaseid,
            (string)$record->creditnotenumber,
            (string)$record->entitykey,
            (int)$record->sequence,
            (int)$record->creditnoteyear,
            (int)$record->issuedat,
            $this->decode((string)$record->sellerjson),
            $this->decode((string)$record->customerjson),
            $this->decode((string)$record->financialjson),
            $this->decode((string)$record->metadatajson)
        );
    }

    private function decode(string $value): array {
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }
}
