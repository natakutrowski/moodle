<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\storefront\certification;

defined('MOODLE_INTERNAL') || die();

/** Read-only J7.3 structural certification. */
final class CommerceStorefrontSeoResponsiveCertificationService {
    /** @return array<int,array{status:string,label:string,detail:string}> */
    public function certify(): array {
        global $CFG;

        $root = $CFG->dirroot . '/local/subscriptions/';
        $checks = [];

        $productpage = (string)file_get_contents(
            $root . 'storefront_product.php'
        );
        $hooks = (string)file_get_contents(
            $root . 'db/hooks.php'
        );
        $listener = (string)file_get_contents(
            $root . 'classes/hook_listener.php'
        );
        $checks[] = $this->finding(
            str_contains(
                $productpage,
                'CommerceStorefrontSeoHeadRegistry::set'
            )
                && str_contains(
                    $hooks,
                    'before_standard_head_html_generation'
                )
                && str_contains(
                    $listener,
                    'add_storefront_seo_head'
                )
                && !str_contains(
                    $productpage,
                    'additionalhtmlhead'
                ),
            'SEO projection',
            'Canonical, meta description and Open Graph use the official Moodle head hook.'
        );

        $layoutnames = [
            'standard',
            'editorial',
            'immersive',
            'course',
            'digital',
            'bundle',
        ];
        $missinglayouts = [];
        $invalidh1layouts = [];

        foreach ($layoutnames as $layoutname) {
            $layoutpath = $root
                . 'templates/storefront/product_templates/'
                . $layoutname
                . '.mustache';

            if (!is_readable($layoutpath)) {
                $missinglayouts[] = $layoutname;
                continue;
            }

            $h1count = substr_count(
                (string)file_get_contents($layoutpath),
                '<h1'
            );
            if ($h1count !== 1) {
                $invalidh1layouts[] = $layoutname . ':' . $h1count;
            }
        }

        $checks[] = $this->finding(
            $missinglayouts === [] && $invalidh1layouts === [],
            'Unique H1 contract',
            $missinglayouts === [] && $invalidh1layouts === []
                ? 'The six canonical layouts each contain exactly one H1.'
                : 'Missing layouts: '
                    . implode(', ', $missinglayouts)
                    . '; invalid H1 counts: '
                    . implode(', ', $invalidh1layouts)
        );

        $css = (string)file_get_contents(
            $root . 'styles/storefront.css'
        );
        $checks[] = $this->finding(
            str_contains($css, '(max-height: 700px)')
                && str_contains($css, 'prefers-reduced-motion')
                && str_contains($css, ':focus-visible'),
            'Responsive accessibility',
            'Sticky fallback, reduced motion and keyboard focus rules are present.'
        );

        $assets = (string)file_get_contents(
            $root . 'admin/commerce/products/assets.php'
        );
        $checks[] = $this->finding(
            substr_count(
                $assets,
                'CommerceCatalogMediaManager::ROLE_'
            ) >= 4
                && str_contains($assets, '$visualformats'),
            'Master visuals',
            'The product asset editor exposes the controlled responsive visual contract.'
        );

        return $checks;
    }

    /** @param array<int,array{status:string,label:string,detail:string}> $checks */
    public function has_errors(array $checks): bool {
        foreach ($checks as $check) {
            if ($check['status'] === 'error') {
                return true;
            }
        }
        return false;
    }

    /** @return array{status:string,label:string,detail:string} */
    private function finding(
        bool $success,
        string $label,
        string $detail
    ): array {
        return [
            'status' => $success ? 'ok' : 'error',
            'label' => $label,
            'detail' => $detail,
        ];
    }
}
