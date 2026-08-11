<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\storefront\navigation;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\presentation\CommerceProductDiscoveryUrlResolver;
use local_subscriptions\commerce\showroom\CommerceShowroomRegistry;
use local_subscriptions\commerce\showroom\CommerceShowroomUrl;
use local_subscriptions\url\UrlFactory;

/**
 * Resolves the contextual back link of a public Storefront.
 *
 * Storefronts can be entered either directly, from the shop, or from a
 * Showroom offer-details CTA. The return target must follow that customer
 * journey without allowing arbitrary redirect URLs.
 */
final class CommerceStorefrontReturnNavigationResolver {
    /**
     * @return array{
     *     show:bool,
     *     url:string,
     *     label:string,
     *     params:array<int,array{name:string,value:string}>
     * }
     */
    public function resolve(
        string $from,
        string $source,
        string $showroomkey,
        string $showroomoffer,
        string $currency,
        string $language
    ): array {
        $from = strtolower(trim($from));
        $source = strtolower(trim($source));
        $showroomkey = strtolower(trim($showroomkey));
        $showroomoffer = strtolower(trim($showroomoffer));
        $currency = strtoupper(trim($currency));

        if (
            $source === 'showroom'
            && $showroomkey !== ''
            && CommerceProductDiscoveryUrlResolver::is_published_showroom($showroomkey)
        ) {
            $definition = CommerceShowroomRegistry::get($showroomkey);
            if ($definition !== null) {
                $params = [
                    ['name' => 'source', 'value' => 'showroom'],
                    ['name' => 'showroom', 'value' => $showroomkey],
                ];
                if ($showroomoffer !== '') {
                    $params[] = ['name' => 'showroomoffer', 'value' => $showroomoffer];
                }

                return [
                    'show' => true,
                    'url' => CommerceShowroomUrl::make(
                        $definition,
                        ['currency' => $currency],
                        $language
                    )->out(false),
                    'label' => get_string(
                        'commerce_storefront_back_to_showroom',
                        'local_subscriptions'
                    ),
                    'params' => $params,
                ];
            }
        }

        if ($from === 'shop') {
            return [
                'show' => true,
                'url' => UrlFactory::digital_catalog(['currency' => $currency])->out(false),
                'label' => get_string('commerce_storefront_back', 'local_subscriptions'),
                'params' => [
                    ['name' => 'from', 'value' => 'shop'],
                ],
            ];
        }

        return [
            'show' => false,
            'url' => UrlFactory::digital_catalog(['currency' => $currency])->out(false),
            'label' => get_string('commerce_storefront_back', 'local_subscriptions'),
            'params' => [],
        ];
    }
}
