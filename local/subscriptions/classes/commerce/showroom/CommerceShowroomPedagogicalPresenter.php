<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\education\calendar\CommercePedagogicalCalendarRepository;
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionOfferRepository;

/**
 * Public-safe pedagogical context shared by Showroom blocks.
 *
 * Pricing/ownership stay in CommerceStorefrontPresenter. This presenter only
 * exposes the currently selected pedagogical cohort behind one stable product
 * SKU so editorial blocks can display dates/group size without hard-coding
 * promotion data in the CMS.
 */
final class CommerceShowroomPedagogicalPresenter {
    public function __construct(
        private readonly CommercePedagogicalPromotionOfferRepository $offers,
        private readonly CommercePedagogicalGroupRepository $groups,
        private readonly CommercePedagogicalCalendarRepository $calendar
    ) {
    }

    public static function create(?\moodle_database $db = null): self {
        return new self(
            CommercePedagogicalPromotionOfferRepository::create($db),
            CommercePedagogicalGroupRepository::create($db),
            CommercePedagogicalCalendarRepository::create($db)
        );
    }

    /** @return array<string,mixed> */
    public function for_product(string $sku, int $now): array {
        $link = $this->offers->sale_link_for_product($sku, $now);
        if ($link === null) {
            return self::empty_context();
        }

        $promotion = $link['promotion'];
        $promotionid = (int)$promotion->get_id();
        if ($promotionid <= 0) {
            return self::empty_context();
        }

        $groupconfig = $this->groups->get_configuration($promotionid);
        $calendaritems = $this->calendar->for_promotion($promotionid);
        $firstunlock = null;
        foreach ($calendaritems as $item) {
            $candidate = $item->get_unlocks_at();
            if ($firstunlock === null || $candidate < $firstunlock) {
                $firstunlock = $candidate;
            }
        }

        $startsat = $promotion->get_starts_at();
        $endsat = $promotion->get_ends_at();
        $salesopensat = $promotion->get_sales_opens_at();
        $salesclosesat = $promotion->get_sales_closes_at();
        $groupsenabled = $groupconfig->is_enabled();

        return [
            'haspedagogicalpromotion' => true,
            'pedagogicalpromotionid' => $promotionid,
            'pedagogicalpromotionkey' => $promotion->get_promotion_key(),
            'pedagogicalpromotionname' => format_string($promotion->get_name()),
            'pedagogicalpromotioncourseid' => $promotion->get_course_id(),
            'pedagogicalpromotionstatus' => $promotion->get_status(),
            'pedagogicalpromotionpublished' => $promotion->is_published(),
            'pedagogicalpromotionsalesopen' => $promotion->sales_are_open($now),
            'pedagogicalpromotionsalesopensat' => $salesopensat,
            'haspedagogicalpromotionsalesopen' => $salesopensat !== null,
            'pedagogicalpromotionsalesopenlabel' => $this->datetime($salesopensat),
            'pedagogicalpromotionsalesclosesat' => $salesclosesat,
            'haspedagogicalpromotionsalesclose' => $salesclosesat !== null,
            'pedagogicalpromotionsalescloselabel' => $this->datetime($salesclosesat),
            'pedagogicalpromotionstartsat' => $startsat,
            'haspedagogicalpromotionstart' => $startsat !== null,
            'pedagogicalpromotionstartlabel' => $this->datetime($startsat),
            'pedagogicalpromotionendsat' => $endsat,
            'haspedagogicalpromotionend' => $endsat !== null,
            'pedagogicalpromotionendlabel' => $this->datetime($endsat),
            'pedagogicalpromotioncapacity' => $promotion->get_capacity_total(),
            'haspedagogicalpromotioncapacity' => $promotion->get_capacity_total() !== null,
            'pedagogicalgroupsenabled' => $groupsenabled,
            'pedagogicalgroupsize' => $groupsenabled ? $groupconfig->get_group_size() : null,
            'haspedagogicalgroupsize' => $groupsenabled,
            'pedagogicalprogressive' => $calendaritems !== [],
            'pedagogicalfirstunlocksat' => $firstunlock,
            'haspedagogicalfirstunlock' => $firstunlock !== null,
            'pedagogicalfirstunlocklabel' => $this->datetime($firstunlock),
        ];
    }

    /**
     * Promotes the first course/promotion offer context to page-level data.
     *
     * @param array<int,array<string,mixed>> $offers
     * @return array<string,mixed>
     */
    public static function page_context(array $offers): array {
        foreach ($offers as $offer) {
            if (empty($offer['haspedagogicalpromotion'])) {
                continue;
            }
            if (
                (string)($offer['role'] ?? '') === 'course'
                || in_array(
                    (string)($offer['cataloguetype'] ?? ''),
                    ['course_access', 'subscription'],
                    true
                )
            ) {
                return self::extract_context($offer);
            }
        }

        foreach ($offers as $offer) {
            if (!empty($offer['haspedagogicalpromotion'])) {
                return self::extract_context($offer);
            }
        }

        return self::empty_context();
    }

    /** @param array<string,mixed> $offer @return array<string,mixed> */
    private static function extract_context(array $offer): array {
        $context = [];
        foreach (array_keys(self::empty_context()) as $key) {
            if (array_key_exists($key, $offer)) {
                $context[$key] = $offer[$key];
            }
        }
        return array_replace(self::empty_context(), $context);
    }

    /** @return array<string,mixed> */
    private static function empty_context(): array {
        return [
            'haspedagogicalpromotion' => false,
            'pedagogicalpromotionid' => null,
            'pedagogicalpromotionkey' => '',
            'pedagogicalpromotionname' => '',
            'pedagogicalpromotioncourseid' => null,
            'pedagogicalpromotionstatus' => '',
            'pedagogicalpromotionpublished' => false,
            'pedagogicalpromotionsalesopen' => false,
            'pedagogicalpromotionsalesopensat' => null,
            'haspedagogicalpromotionsalesopen' => false,
            'pedagogicalpromotionsalesopenlabel' => '',
            'pedagogicalpromotionsalesclosesat' => null,
            'haspedagogicalpromotionsalesclose' => false,
            'pedagogicalpromotionsalescloselabel' => '',
            'pedagogicalpromotionstartsat' => null,
            'haspedagogicalpromotionstart' => false,
            'pedagogicalpromotionstartlabel' => '',
            'pedagogicalpromotionendsat' => null,
            'haspedagogicalpromotionend' => false,
            'pedagogicalpromotionendlabel' => '',
            'pedagogicalpromotioncapacity' => null,
            'haspedagogicalpromotioncapacity' => false,
            'pedagogicalgroupsenabled' => false,
            'pedagogicalgroupsize' => null,
            'haspedagogicalgroupsize' => false,
            'pedagogicalprogressive' => false,
            'pedagogicalfirstunlocksat' => null,
            'haspedagogicalfirstunlock' => false,
            'pedagogicalfirstunlocklabel' => '',
            // Capacity/readiness fields are provided by
            // CommercePedagogicalCapacityPresenter on the offer itself and
            // promoted to page scope here for future editorial blocks.
            'haspedagogicalcapacity' => false,
            'pedagogicalavailable' => true,
            'pedagogicalbuyavailable' => true,
            'pedagogicaldirectresume' => false,
            'pedagogicalsoldout' => false,
            'pedagogicalremaining' => null,
            'pedagogicalcapacitylabel' => '',
            'pedagogicalcapacityclass' => '',
            'pedagogicalsalesclosed' => false,
            'haspedagogicalsalesclose' => false,
            'pedagogicalsalesclosesat' => null,
            'pedagogicalsalescloselabel' => '',
            'pedagogicalsalescloseurgent' => false,
            'pedagogicaldisabledctlabel' => '',
        ];
    }

    private function datetime(?int $timestamp): string {
        if ($timestamp === null || $timestamp <= 0) {
            return '';
        }
        return userdate(
            $timestamp,
            get_string('strftimedatetime', 'langconfig')
        );
    }
}
