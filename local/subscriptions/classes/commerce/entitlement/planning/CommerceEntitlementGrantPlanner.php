<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\entitlement\planning;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrant;
use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrantPlan;
use local_subscriptions\commerce\purchase\handler\CommercePreparedPurchaseItem;
use local_subscriptions\commerce\purchase\preparation\CommercePurchasePreparation;

/**
 * Converts catalogue entitlement promises into customer entitlement grants.
 */
final class CommerceEntitlementGrantPlanner {
    public function plan(
        CommercePurchasePreparation $preparation,
        ?int $plannedat = null
    ): CommerceEntitlementGrantPlan {
        $plannedat ??= time();
        $customer = $preparation->get_request()->get_customer();
        $grants = [];
        $position = 0;

        foreach ($preparation->get_items() as $prepareditem) {
            foreach ($this->extract_definitions($prepareditem) as $definition) {
                $position++;
                $durationseconds = $this->normalise_duration($definition['durationseconds'] ?? null);
                $validuntil = $durationseconds === null
                    ? null
                    : $plannedat + $durationseconds;

                $requestitem = $prepareditem->get_request_item();
                $item = $requestitem->get_item();
                $productsku = strtoupper(trim((string)($definition['productsku'] ?? $item->get_reference())));
                $quantity = (int)($definition['quantity'] ?? 1) * $requestitem->get_quantity();

                $grants[] = new CommerceEntitlementGrant(
                    $this->build_reference($preparation->get_reference(), $item->get_reference(), $position),
                    $preparation->get_reference(),
                    $item->get_reference(),
                    $productsku,
                    (string)($definition['type'] ?? ''),
                    (string)($definition['resourcekey'] ?? ''),
                    $quantity,
                    $customer->get_user_id(),
                    $customer->get_email(),
                    $plannedat,
                    $validuntil,
                    $this->normalise_array($definition['configuration'] ?? []),
                    [
                        'handler' => $prepareditem->get_handler_key(),
                        'fulfillmentkey' => $prepareditem->get_fulfillment_key(),
                        'definitionid' => isset($definition['id']) ? (int)$definition['id'] : null,
                        'sortorder' => (int)($definition['sortorder'] ?? 0),
                    ]
                );
            }
        }

        return new CommerceEntitlementGrantPlan(
            $preparation->get_reference(),
            $grants,
            $plannedat
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function extract_definitions(CommercePreparedPurchaseItem $prepareditem): array {
        $definitions = $prepareditem->get_request_item()->get_metadata_value('entitlements', []);

        if (!is_array($definitions)) {
            throw new \coding_exception('Commerce purchase item entitlement metadata must be an array.');
        }

        foreach ($definitions as $definition) {
            if (!is_array($definition)) {
                throw new \coding_exception('Commerce purchase item contains an invalid entitlement definition.');
            }
        }

        return array_values($definitions);
    }

    private function normalise_duration(mixed $duration): ?int {
        if ($duration === null || $duration === '') {
            return null;
        }

        $duration = (int)$duration;

        if ($duration <= 0) {
            throw new \coding_exception('A planned Commerce entitlement duration must be positive.');
        }

        return $duration;
    }

    private function normalise_array(mixed $value): array {
        if (!is_array($value)) {
            throw new \coding_exception('Commerce entitlement configuration must be an array.');
        }

        return $value;
    }

    private function build_reference(
        string $purchasereference,
        string $itemreference,
        int $position
    ): string {
        return 'ent-' . substr(hash(
            'sha256',
            $purchasereference . '|' . $itemreference . '|' . $position
        ), 0, 32);
    }
}
