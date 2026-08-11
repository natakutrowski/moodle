<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\persistence;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\domain\CommerceProduct;
use local_subscriptions\commerce\catalog\domain\CommerceProductComponent;
use local_subscriptions\commerce\catalog\domain\CommerceProductEntitlementDefinition;
use local_subscriptions\commerce\catalog\domain\CommerceProductPrice;
use local_subscriptions\commerce\catalog\domain\CommerceProductTranslation;
use local_subscriptions\commerce\catalog\presentation\CommerceProductDisplayText;
use local_subscriptions\commerce\domain\value\CommerceMoney;

/** Hydrates Native Commerce catalogue domain objects from SQL records. */
final class CommerceCatalogHydrator {
    public function product(\stdClass $record): CommerceProduct {
        return new CommerceProduct($record->sku, $record->type, $record->status, CommerceProductDisplayText::title((string)$record->name),
            (string)($record->description ?? ''), self::decode_json($record->metadatajson ?? null), (int)$record->id,
            self::nullable_int($record->availablefrom ?? null), self::nullable_int($record->availableuntil ?? null),
            (int)$record->timecreated, (int)$record->timemodified);
    }
    public function price(\stdClass $record, string $sku): CommerceProductPrice {
        return new CommerceProductPrice($sku, CommerceMoney::from_minor((int)$record->amountminor, $record->currency),
            (bool)$record->active, self::nullable_string($record->provider ?? null),
            self::nullable_string($record->providerpriceid ?? null), self::decode_json($record->metadatajson ?? null), (int)$record->id);
    }
    public function translation(\stdClass $record, string $sku): CommerceProductTranslation {
        return new CommerceProductTranslation($sku, $record->language, CommerceProductDisplayText::title((string)$record->name),
            (string)($record->shortdescription ?? ''), (string)($record->description ?? ''),
            self::decode_json($record->metadatajson ?? null), (int)$record->id);
    }
    public function component(\stdClass $record, string $parentsku, string $childsku): CommerceProductComponent {
        return new CommerceProductComponent($parentsku, $childsku, (int)$record->quantity, (int)$record->sortorder,
            self::decode_json($record->metadatajson ?? null), (int)$record->id);
    }
    public function entitlement(\stdClass $record, string $sku): CommerceProductEntitlementDefinition {
        return new CommerceProductEntitlementDefinition($sku, $record->type, $record->resourcekey,
            self::nullable_int($record->durationseconds ?? null), (int)$record->quantity,
            self::decode_json($record->configurationjson ?? null), (int)$record->sortorder, (int)$record->id);
    }
    public static function encode_json(array $value): ?string {
        if ($value === []) { return null; }
        $json = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        return $json;
    }
    private static function decode_json(mixed $value): array {
        if ($value === null || $value === '') { return []; }
        $decoded = json_decode((string)$value, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($decoded)) { throw new \coding_exception('Commerce catalogue JSON payload must decode to an array.'); }
        return $decoded;
    }
    private static function nullable_int(mixed $value): ?int { return $value === null ? null : (int)$value; }
    private static function nullable_string(mixed $value): ?string { return $value === null || trim((string)$value) === '' ? null : (string)$value; }
}
