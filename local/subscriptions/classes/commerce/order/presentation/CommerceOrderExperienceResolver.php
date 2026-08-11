<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\order\presentation;

defined('MOODLE_INTERNAL') || die();

/** Resolves customer-facing order experience flags without leaking persistence details. */
final class CommerceOrderExperienceResolver {
    /** @return array<string, int|bool> */
    public function resolve(CommerceOrderPresentation $order): array {
        $coursecount = 0;
        $digitalcount = 0;
        $accesscount = 0;
        $types = [];

        foreach ($order->items as $item) {
            $type = strtolower(trim($item->type));
            $types[$type] = true;
            $quantity = max(1, $item->quantity);

            if (in_array($type, ['course_access', 'subscription', 'course'], true)) {
                $coursecount += $quantity;
            } elseif (in_array($type, ['digital_download', 'digital'], true)) {
                $digitalcount += $quantity;
            }

            foreach ($item->accesses as $access) {
                if ($access->available) {
                    $accesscount++;
                }
            }
        }

        $isbundle = strtolower(trim($order->type)) === 'bundle'
            || isset($types['bundle']);
        $ismultiproduct = !$isbundle && count($order->items) > 1;

        return [
            'isbundle' => $isbundle,
            'ismultiproduct' => $ismultiproduct,
            'itemcount' => count($order->items),
            'coursecount' => $coursecount,
            'digitalcount' => $digitalcount,
            'accesscount' => $accesscount,
            'hascourses' => $coursecount > 0,
            'hasdigitals' => $digitalcount > 0,
            'hasaccesses' => $accesscount > 0,
        ];
    }
}
