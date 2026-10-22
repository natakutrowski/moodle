<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

/** Neutral presentation defaults for the reusable Showroom render engine. */
final class CommerceShowroomDefaultPresentationProfile implements CommerceShowroomPresentationProfile {
    /** @inheritDoc */
    public function decorate_offers(array $offers): array {
        foreach ($offers as &$offer) {
            $offer['ispreferred'] = false;
            $offer['isfeatured'] = false;
            $offer['featuredlabel'] = '';
            $offer['features'] = [];
            $offer['hasfeatures'] = false;
        }
        unset($offer);

        return $offers;
    }

    /** @inheritDoc */
    public function fallback_data(array $offers): array {
        $heroactionurl = '#showroom-offers';
        $herocta = get_string('commerce_showroom_buy_now', 'local_subscriptions');
        $featuredprice = '';

        foreach ($offers as $offer) {
            if (!empty($offer['canbuy'])) {
                $featuredprice = trim((string)($offer['priceformatted'] ?? ''));
                break;
            }
        }

        return [
            'herocta' => $herocta,
            'heroactionurl' => $heroactionurl,
            'heroactionisanchor' => true,
            'herostatus' => 'generic',
            'herosecondarycta' => '',
            'statslayout' => 'bar',
            'herostat1value' => '',
            'herostat1label' => '',
            'herostat2value' => '',
            'herostat2label' => '',
            'herostat3value' => '',
            'herostat3label' => '',
            'herostat4value' => '',
            'herostat4label' => '',
            'showexpeditiontheme' => false,
            'expeditionicon' => 'fa-solid fa-compass',
            'herodevicestage' => '',
            'heromobileicon' => 'fa-solid fa-graduation-cap',
            'heromobilevalue' => '',
            'heromobilelabel' => '',
            'offersbadgeicon' => 'fa-solid fa-bag-shopping',
            'offersheading' => '',
            'offersdescription' => '',
            'comparisonoffers' => [],
            'comparisonrows' => [],
            'comparisonrecommendedlabel' => get_string('commerce_showroom_offer_featured', 'local_subscriptions'),
            'testimonials' => [],
            'hastestimonials' => false,
            'faqs' => [],
            'finalcta' => $herocta,
            'finalactionurl' => $heroactionurl,
            'desktopstickylabel' => '',
            'desktopstickycta' => $herocta,
            'desktopstickyurl' => $heroactionurl,
            'desktopstickyprice' => $featuredprice,
            'hasdesktopstickyprice' => $featuredprice !== '',
        ];
    }
}
