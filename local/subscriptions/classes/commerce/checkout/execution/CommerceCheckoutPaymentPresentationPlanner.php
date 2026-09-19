<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\checkout\execution;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\payment\availability\CommercePaymentMethodAvailability;
use local_subscriptions\commerce\payment\policy\CommercePaymentPolicyResolver;
use local_subscriptions\commerce\payment\policy\CommercePaymentPolicyResult;

/**
 * H13.2.2 — one recommendation order shared by all checkout payment surfaces.
 *
 * Availability and executable capability remain authoritative. This planner
 * only orders methods/routes that already exist, then lets the checkout split
 * the ordered result into Express vs standard surfaces.
 */
final class CommerceCheckoutPaymentPresentationPlanner {
    public function __construct(
        private readonly CommercePaymentPolicyResolver $policy =
            new CommercePaymentPolicyResolver()
    ) {
    }

    /**
     * @param CommercePaymentMethodAvailability[] $available
     * @param CommerceCheckoutPaymentRoute[] $routes
     * @return array{
     *   policy:CommercePaymentPolicyResult,
     *   orderedmethods:CommercePaymentMethodAvailability[],
     *   orderedroutes:CommerceCheckoutPaymentRoute[]
     * }
     */
    public function plan(
        string $country,
        string $currency,
        array $available,
        array $routes
    ): array {
        $policy = $this->policy->resolve(
            $country,
            $currency,
            $available
        );
        $orderedmethods =
            $policy->get_ordered_methods();

        $rank = [];
        foreach ($orderedmethods as $index => $availability) {
            $rank[$availability->get_method()] = $index;
        }

        $decorated = [];
        foreach ($routes as $index => $route) {
            if (!$route instanceof CommerceCheckoutPaymentRoute) {
                throw new \coding_exception(
                    'Payment presentation planner received an invalid route.'
                );
            }

            $decorated[] = [
                'route' => $route,
                'rank' => $rank[$route->get_method()] ?? PHP_INT_MAX,
                'index' => $index,
            ];
        }

        usort(
            $decorated,
            static function(array $left, array $right): int {
                $rankcomparison =
                    $left['rank'] <=> $right['rank'];

                if ($rankcomparison !== 0) {
                    return $rankcomparison;
                }

                return $left['index'] <=> $right['index'];
            }
        );

        return [
            'policy' => $policy,
            'orderedmethods' => $orderedmethods,
            'orderedroutes' => array_values(array_map(
                static fn(array $entry): CommerceCheckoutPaymentRoute =>
                    $entry['route'],
                $decorated
            )),
        ];
    }
}
