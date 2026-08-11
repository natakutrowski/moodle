<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\storefront\certification;

defined('MOODLE_INTERNAL') || die();

/**
 * Read-only certification of the Storefront / Showroom navigation contract.
 */
final class CommerceStorefrontNavigationCertificationService {
    /**
     * @return array{
     *     status:string,
     *     errors:int,
     *     checks:array<int,array{ok:bool,label:string,detail:string}>
     * }
     */
    public function certify(): array {
        global $CFG;

        $root = $CFG->dirroot . '/local/subscriptions/';
        $checks = [];

        $discovery = $this->read(
            $root . 'classes/commerce/catalog/presentation/'
                . 'CommerceProductDiscoveryUrlResolver.php'
        );
        $checks[] = $this->check(
            str_contains($discovery, '$showroomkey !== $currentshowroom')
                && str_contains($discovery, 'return self::storefront('),
            'Showroom loop guard',
            'Discovery cannot route a product back to the same Showroom and falls back to Storefront.'
        );

        $showroomresolver = $this->read(
            $root . 'classes/commerce/showroom/CommerceShowroomProductResolver.php'
        );
        $checks[] = $this->check(
            str_contains(
                $showroomresolver,
                'CommerceStorefrontUrlResolver::direct_storefront('
            )
                && str_contains($showroomresolver, "'source' => 'showroom'")
                && str_contains($showroomresolver, "'showroom' => \$showroomkey")
                && str_contains($showroomresolver, "'showroomoffer' => \$role"),
            'Showroom details destination',
            'Showroom detail CTAs open a direct Storefront and preserve a safe Showroom origin context.'
        );

        $customerresolver = $this->read(
            $root . 'classes/url/CommerceCustomerPublicUrlResolver.php'
        );
        $purchases = $this->read(
            $root . 'classes/output/my_purchases/CurrentPresentationRenderer.php'
        );
        $library = $this->read(
            $root . 'classes/commerce/digital/library/CommerceDigitalLibraryService.php'
        );
        $checks[] = $this->check(
            str_contains(
                $customerresolver,
                'public static function storefront('
            )
                && str_contains(
                    $purchases,
                    'CommerceCustomerPublicUrlResolver::storefront('
                )
                && str_contains(
                    $library,
                    'CommerceStorefrontUrlResolver::direct_storefront('
                ),
            'Owned product pages',
            'Owned/customer surfaces use direct Storefront URLs instead of restarting discovery.'
        );

        $urlresolver = $this->read(
            $root . 'classes/commerce/storefront/presentation/'
                . 'CommerceStorefrontUrlResolver.php'
        );
        $presenter = $this->read(
            $root . 'classes/commerce/storefront/presentation/'
                . 'CommerceStorefrontPresenter.php'
        );
        $checks[] = $this->check(
            str_contains($urlresolver, "'bundle' =>")
                && str_contains($urlresolver, 'UrlFactory::my_campus()')
                && str_contains(
                    $presenter,
                    "'commerce_storefront_access_bundle_contents'"
                ),
            'Owned Bundle access',
            'An owned Bundle opens the unified Mon Campus destination with a dedicated CTA label.'
        );

        $returnresolver = $this->read(
            $root . 'classes/commerce/storefront/navigation/'
                . 'CommerceStorefrontReturnNavigationResolver.php'
        );
        $productpage = $this->read($root . 'storefront_product.php');
        $sectiontemplate = $this->read(
            $root . 'templates/storefront/product_section.mustache'
        );
        $paneltemplate = $this->read(
            $root . 'templates/storefront/product_commerce_panel.mustache'
        );
        $checks[] = $this->check(
            str_contains(
                $returnresolver,
                'CommerceProductDiscoveryUrlResolver::is_published_showroom($showroomkey)'
            )
                && str_contains($returnresolver, "if (\$from === 'shop')")
                && !str_contains($returnresolver, 'redirect(')
                && str_contains($productpage, "\$data['showbacklink']")
                && str_contains($sectiontemplate, '{{#navigationparams}}')
                && str_contains($paneltemplate, '{{#showbacklink}}'),
            'Contextual return navigation',
            'Storefront returns safely to Shop or a published origin Showroom and preserves origin through currency changes.'
        );

        $recommendationresolver = $this->read(
            $root . 'classes/commerce/storefront/recommendation/'
                . 'CommerceStorefrontRecommendationResolver.php'
        );
        $recommendationservice = $this->read(
            $root . 'classes/commerce/storefront/recommendation/'
                . 'CommerceStorefrontRecommendationService.php'
        );
        $checks[] = $this->check(
            str_contains(
                $recommendationresolver,
                'string $currentsku ='
            )
                && str_contains($recommendationresolver, '$sku !== $currentsku')
                && str_contains($recommendationservice, '!$product->is_owned()'),
            'Recommendation hygiene',
            'The current product and already-owned products are excluded from public Storefront recommendations.'
        );

        $checks[] = $this->check(
            $this->has_language_string(
                $root,
                'commerce_storefront_back_to_showroom'
            )
                && $this->has_language_string(
                    $root,
                    'commerce_storefront_access_bundle_contents'
                ),
            'FR / EN / RU navigation strings',
            'All navigation strings introduced by L7 are present in FR, EN and RU.'
        );

        $errors = count(array_filter(
            $checks,
            static fn(array $check): bool => !$check['ok']
        ));

        return [
            'status' => $errors === 0 ? 'GREEN' : 'FAILED',
            'errors' => $errors,
            'checks' => $checks,
        ];
    }

    /**
     * @return array{ok:bool,label:string,detail:string}
     */
    private function check(bool $ok, string $label, string $detail): array {
        return [
            'ok' => $ok,
            'label' => $label,
            'detail' => $detail,
        ];
    }

    private function read(string $path): string {
        return is_readable($path) ? (string)file_get_contents($path) : '';
    }

    private function has_language_string(string $root, string $key): bool {
        foreach (['fr', 'en', 'ru'] as $language) {
            $source = $this->read(
                $root . 'lang/' . $language . '/local_subscriptions.php'
            );
            if (!str_contains($source, "\$string['{$key}']")) {
                return false;
            }
        }

        return true;
    }
}
