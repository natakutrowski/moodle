<?php
// Commerce source of truth for Lovable CMS drafts. Configuration and READ-ONLY review only.
declare(strict_types=1);

namespace local_subscriptions\commerce\showroom\bridge;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\CommerceShowroomCurrencyResolver;
use local_subscriptions\commerce\showroom\CommerceShowroomPresenter;
use local_subscriptions\commerce\showroom\CommerceShowroomProductResolver;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomPreviewDefinitionResolver;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomProductConfig;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomProductLinkOptions;

/** No checkout endpoints or client-provided amounts are ever accepted here. */
final class CommerceShowroomLovableCommerceLink {
    // Stable design-card order; not a new product type or an entitlement model.
    public const CARD_ROLES = [
        'course' => 'Apprendre · cours seul',
        'bundle' => 'Apprendre et soutenir · offre combinée',
        'donation' => 'Soutenir Alf · don seul',
    ];

    public static function selected(\stdClass $record): array {
        $data = json_decode((string)$record->productsjson, true, 64, JSON_THROW_ON_ERROR);
        if (!is_array($data)) {
            throw new \invalid_parameter_exception('Configuration Commerce invalide.');
        }
        $result = [];
        foreach (self::CARD_ROLES as $role => $label) {
            $value = $data[$role] ?? '';
            $result[$role] = is_string($value) || is_numeric($value) ? (string)$value : '';
        }
        return $result;
    }

    /** Reuse the existing SKU validation and product-role mapping from native Commerce CMS. */
    public static function save(\moodle_database $db, int $id, array $submitted, int $userid): void {
        [$record, $meta] = CommerceShowroomLovableDraftService::require_draft($db, $id);
        if (array_diff(array_keys($submitted), array_keys(self::CARD_ROLES)) !== []) {
            throw new \invalid_parameter_exception('Rôle Commerce inconnu.');
        }
        $productoptions = new CommerceShowroomProductLinkOptions($db);
        $products = [];
        foreach (self::CARD_ROLES as $role => $label) {
            if (CommerceShowroomProductConfig::normalise_role($role) !== $role) {
                throw new \invalid_parameter_exception('Rôle non pris en charge par le catalogue Commerce : ' . $role);
            }
            $raw = $submitted[$role] ?? '';
            if (!is_string($raw) || strlen($raw) > 150) {
                throw new \invalid_parameter_exception('SKU invalide pour ' . $role);
            }
            // Blank is an intentionally unconfigured card. A nonblank SKU must
            // correspond to a real product option eligible for this role.
            $products[$role] = $productoptions->normalise_role_sku($role, trim($raw));
        }
        $settings = json_decode((string)$record->settingsjson, true, 64, JSON_THROW_ON_ERROR);
        if (!is_array($settings)) {
            throw new \invalid_parameter_exception('Réglages CMS invalides.');
        }
        // Do not set commerce_connected=true: presentation binding, checkout and
        // legal review have NOT been completed merely by selecting products.
        $meta['commerce_mapping_saved'] = true;
        $meta['commerce_connected'] = false;
        $settings[CommerceShowroomLovableDraftService::SETTINGS_KEY] = $meta;
        $productjson = CommerceShowroomProductConfig::to_json($products);
        $settingsjson = json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        if (strlen($settingsjson) > 55000) {
            throw new \invalid_parameter_exception('Réglages du showroom trop volumineux.');
        }
        $transaction = $db->start_delegated_transaction();
        try {
            $db->update_record('local_subs_showroom', (object)[
                'id' => $id,
                'productsjson' => $productjson,
                'settingsjson' => $settingsjson,
                'timemodified' => time(),
                'usermodified' => $userid,
            ]);
            $transaction->allow_commit();
        } catch (\Throwable $exception) {
            $transaction->rollback($exception);
            throw $exception;
        }
    }

    /**
     * Read prices from the existing Commerce resolver. Never from Lovable JSX.
     * The return payload deliberately excludes cartaction, returnurl, sesskey,
     * priceid or any request capable of initiating a payment.
     * @return array<string,mixed>
     */
    public static function read_only_review(\moodle_database $db, int $id, string $currency): array {
        [$record, $meta] = CommerceShowroomLovableDraftService::require_draft($db, $id);
        $active = CommerceShowroomCurrencyResolver::active_currencies($db);
        $currency = strtoupper($currency);
        if (!in_array($currency, $active, true)) {
            throw new \invalid_parameter_exception('Devise Commerce inactive.');
        }
        $selected = self::selected($record);
        $mapped = array_filter($selected, static fn(string $sku): bool => $sku !== '');
        if (!$mapped) {
            return ['currency' => $currency, 'offers' => [], 'selected' => $selected];
        }
        $definition = (new CommerceShowroomPreviewDefinitionResolver($db))->require($id);
        $offers = CommerceShowroomProductResolver::create($db)->resolve(
            $definition, current_language(), $currency
        );
        $data = (new CommerceShowroomPresenter())->present($definition, $offers, $currency);
        $review = [];
        foreach (CommerceShowroomBridgeOfferContract::expose((array)($data['offers'] ?? [])) as $offer) {
            $role = (string)($offer['role'] ?? '');
            if (!array_key_exists($role, self::CARD_ROLES)) {
                continue;
            }
            $review[$role] = [
                'role' => $role,
                'sku' => (string)($offer['sku'] ?? ''),
                'name' => (string)($offer['name'] ?? ''),
                'available' => !empty($offer['available']),
                'owned' => !empty($offer['owned']),
                'hasprice' => !empty($offer['hasprice']),
                'priceformatted' => (string)($offer['priceformatted'] ?? ''),
                'currency' => (string)($offer['currency'] ?? $currency),
                'isdonation' => !empty($offer['isdonation']),
                // Safe, non-authoritative display calculation only. Never a checkout amount.
                'amountminor' => self::safe_minor($offer['amountminor'] ?? null),
                'donationmaxunits' => self::safe_units($offer['donationmaxunits'] ?? null, 1),
                'donationdefaultunits' => self::safe_units($offer['donationdefaultunits'] ?? null, 1),
                'donationpresets' => self::safe_presets($offer['donationpresets'] ?? []),
            ];
        }
        return ['currency' => $currency, 'offers' => $review, 'selected' => $selected];
    }
    private static function safe_minor(mixed $value): ?int {
        if (!is_int($value) && !(is_string($value) && preg_match('/^[0-9]{1,12}$/D', $value))) {
            return null;
        }
        $number = (int)$value;
        return $number > 0 && $number <= 1000000000 ? $number : null;
    }

    private static function safe_units(mixed $value, int $fallback): int {
        if (!is_int($value) && !(is_string($value) && preg_match('/^[0-9]{1,5}$/D', $value))) {
            return $fallback;
        }
        $number = (int)$value;
        return $number >= 1 && $number <= 10000 ? $number : $fallback;
    }

    private static function safe_presets(mixed $presets): array {
        if (!is_array($presets)) {
            return [];
        }
        $result = [];
        foreach (array_slice($presets, 0, 12) as $preset) {
            $units = is_array($preset) ? ($preset['units'] ?? null) : null;
            $units = self::safe_units($units, 0);
            if ($units > 0) {
                $result[] = ['units' => $units];
            }
        }
        return $result;
    }
}
