<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\personaloffer\admin;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\order\reference\CommercePublicOrderReference;

/** Shared CRM presentation helpers for Personal Offers and identity tooling. */
final class CommercePersonalOfferCrmPresentation {
    public static function customer_name_from_user(?\stdClass $user): string {
        if ($user === null) { return ''; }
        return trim((string)($user->firstname ?? '') . ' ' . (string)($user->lastname ?? ''));
    }

    public static function customer_name_from_purchase(?\stdClass $purchase): string {
        if ($purchase === null) { return ''; }
        $data = json_decode((string)($purchase->customerjson ?? ''), true);
        if (!is_array($data)) { $data = []; }
        $firstname = trim((string)($data['firstname'] ?? $data['first_name'] ?? ''));
        $lastname = trim((string)($data['lastname'] ?? $data['last_name'] ?? ''));
        return trim($firstname . ' ' . $lastname);
    }

    public static function purchase_reference(?\stdClass $purchase): string {
        if ($purchase === null) { return '—'; }
        $internal = trim((string)($purchase->reference ?? ''));
        if ($internal === '') { return '#' . (int)$purchase->id; }
        $public = (new CommercePublicOrderReference())->from_internal($internal, (int)($purchase->timecreated ?? time()));
        return $public . ' · ' . $internal;
    }

    public static function product_label(\moodle_database $db, int $productid, ?string $language = null): string {
        $product = $db->get_record('local_subs_commerce_product', ['id' => $productid], 'id,sku,name', IGNORE_MISSING);
        if (!$product) { return '#' . $productid; }
        $language = strtolower(substr(trim($language ?: current_language()), 0, 2));
        $translation = $db->get_record('local_subs_commerce_prod_tr', ['productid' => $productid, 'language' => $language], 'id,name', IGNORE_MISSING);
        $name = trim((string)($translation->name ?? $product->name));
        return ($name !== '' ? $name : (string)$product->name) . ' [' . (string)$product->sku . ']';
    }

    public static function beneficiary_label(string $email, ?\stdClass $user = null, ?\stdClass $purchase = null): string {
        $name = self::customer_name_from_user($user);
        if ($name === '') { $name = self::customer_name_from_purchase($purchase); }
        return $name !== '' ? $name . ' · ' . $email : $email;
    }
}
