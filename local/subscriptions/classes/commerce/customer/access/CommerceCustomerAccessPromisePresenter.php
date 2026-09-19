<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\access;

defined('MOODLE_INTERNAL') || die();

/** Converts the semantic access promise into public-safe display data. */
final class CommerceCustomerAccessPromisePresenter {
    /** @param array<string,mixed> $promise
     *  @return array<string,mixed>
     */
    public function present(array $promise): array {
        $kind = (string)($promise['kind'] ?? 'immediate_generic');
        $items = [];

        $title = get_string('commerce_m61_access_title', 'local_subscriptions');
        $lead = get_string(
            'commerce_m61_access_lead_' . $kind,
            'local_subscriptions'
        );

        if (!empty($promise['promotionname'])) {
            $items[] = [
                'icon' => 'fa-user-group',
                'label' => get_string(
                    'commerce_m61_access_promotion',
                    'local_subscriptions',
                    format_string((string)$promise['promotionname'])
                ),
            ];
        }

        $startsat = isset($promise['startsat']) && $promise['startsat'] !== null
            ? (int)$promise['startsat']
            : null;
        if ($startsat !== null && $startsat > 0) {
            $items[] = [
                'icon' => 'fa-calendar-day',
                'label' => get_string(
                    'commerce_m61_access_starts',
                    'local_subscriptions',
                    $this->datetime($startsat)
                ),
            ];
        }

        $firstunlock = isset($promise['firstunlocksat']) && $promise['firstunlocksat'] !== null
            ? (int)$promise['firstunlocksat']
            : null;
        if (!empty($promise['progressive']) && $firstunlock !== null && $firstunlock > 0) {
            $items[] = [
                'icon' => 'fa-lock-open',
                'label' => get_string(
                    'commerce_m61_access_first_unlock',
                    'local_subscriptions',
                    $this->datetime($firstunlock)
                ),
            ];
        }

        $details = $this->details($kind);

        $promotionlabel = '';
        if (!empty($promise['promotionname'])) {
            $promotionlabel = get_string(
                'commerce_m61_access_promotion',
                'local_subscriptions',
                format_string((string)$promise['promotionname'])
            );
        }

        return [
            'hasaccesspromise' => true,
            'accesspromisekind' => $kind,
            'accesspromisetitle' => $title,
            'accesspromiselead' => $lead,
            'accesspromisepromotionlabel' => $promotionlabel,
            'hasaccesspromisepromotion' => $promotionlabel !== '',
            'accesspromiseitems' => $items,
            'hasaccesspromiseitems' => $items !== [],
            'accesspromisedetailstitle' => get_string(
                'commerce_m62_access_details_title',
                'local_subscriptions'
            ),
            'accesspromisedetails' => $details,
            'hasaccesspromisedetails' => $details !== [],
        ];
    }


    /** @return array<int,array{icon:string,label:string}> */
    private function details(string $kind): array {
        $keys = match ($kind) {
            'owner_promotion_join' => [
                ['fa-graduation-cap', 'commerce_m62_access_owner_course'],
                ['fa-user-group', 'commerce_m62_access_owner_offer'],
                ['fa-chart-line', 'commerce_m62_access_owner_progress'],
            ],
            // The customer has already completed the promotion join. The lead now describes
            // the current state, so repeating purchase-oriented details would be misleading.
            'owner_promotion_join_acquired' => [],
            'promotion_progressive' => [
                ['fa-graduation-cap', 'commerce_m62_access_course_included'],
                ['fa-unlock-keyhole', 'commerce_m62_access_progressive'],
            ],
            'promotion_course' => [
                ['fa-graduation-cap', 'commerce_m62_access_course_included'],
                ['fa-user-group', 'commerce_m62_access_promotion_included'],
            ],
            // For simple one-step products the lead already contains the complete access promise.
            // Repeating the same information under "Concrètement" makes the Storefront card noisy.
            'immediate_course' => [],
            'owned_course' => [],
            'immediate_digital' => [],
            'owned_digital' => [],
            'bundle' => [
                ['fa-box-open', 'commerce_m62_access_bundle'],
            ],
            'upgrade' => [
                ['fa-arrow-up-right-dots', 'commerce_m62_access_upgrade'],
                ['fa-chart-line', 'commerce_m62_access_owner_progress'],
            ],
            default => [
                ['fa-circle-check', 'commerce_m62_access_generic'],
            ],
        };

        return array_map(
            static fn(array $entry): array => [
                'icon' => $entry[0],
                'label' => get_string($entry[1], 'local_subscriptions'),
            ],
            $keys
        );
    }

    private function datetime(int $timestamp): string {
        return userdate(
            $timestamp,
            get_string('strftimedatetime', 'langconfig')
        );
    }
}
