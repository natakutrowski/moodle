<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\storefront\certification;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\storefront\page\CommerceStorefrontLayoutContract;
use local_subscriptions\commerce\storefront\transfer\CommerceStorefrontPackageService;
use local_subscriptions\commerce\storefront\localisation\CommerceStorefrontLocaleTransferService;

/**
 * Final read-only certification for CampusFR Commerce delta L.
 *
 * This certifies the architectural/runtime invariants introduced or hardened
 * during L1-L7 without modifying catalogue, Storefront or customer data.
 */
final class CommerceStorefrontDeltaLCertificationService {
    /**
     * @return array{
     *     status:string,
     *     errors:int,
     *     checks:array<int,array{ok:bool,scope:string,label:string,detail:string}>
     * }
     */
    public function certify(): array {
        global $CFG;

        $root = $CFG->dirroot . '/local/subscriptions/';
        $checks = [];

        // L1-L3 — public discovery / Storefront routing.
        $discovery = $this->read(
            $root . 'classes/commerce/catalog/presentation/'
                . 'CommerceProductDiscoveryUrlResolver.php'
        );
        $checks[] = $this->check(
            str_contains($discovery, '$showroomkey !== $currentshowroom')
                && str_contains($discovery, 'return self::storefront('),
            'L1-L3',
            'Discovery anti-loop',
            'Discovery falls back to Storefront instead of routing back to the same Showroom.'
        );

        $showroomresolver = $this->read(
            $root . 'classes/commerce/showroom/CommerceShowroomProductResolver.php'
        );
        $checks[] = $this->check(
            str_contains(
                $showroomresolver,
                'CommerceStorefrontUrlResolver::direct_storefront('
            ),
            'L1-L3',
            'Showroom details target',
            'Explicit Showroom detail CTAs resolve to a direct Storefront.'
        );

        // L4 — Builder-first architecture / supported visual modes.
        $layoutcontract = $this->read(
            $root . 'classes/commerce/storefront/page/'
                . 'CommerceStorefrontLayoutContract.php'
        );
        $pageresolver = $this->read(
            $root . 'classes/commerce/storefront/page/'
                . 'CommerceStorefrontPageResolver.php'
        );
        $checks[] = $this->check(
            str_contains($layoutcontract, "public const STANDARD = 'standard'")
                && str_contains($layoutcontract, "public const EDITORIAL = 'editorial'")
                && str_contains($layoutcontract, "public const IMMERSIVE = 'immersive'")
                && str_contains($pageresolver, 'product_header_mode'),
            'L4',
            'Builder page modes',
            'Standard, Editorial and Immersive remain supported with Builder product-header control.'
        );

        $checks[] = $this->check(
            str_contains($layoutcontract, "public const NONE = 'none'")
                && str_contains($pageresolver, 'commerce_position'),
            'L4',
            'Builder Commerce placement',
            'Commerce panel placement, including none, remains part of the Storefront page contract.'
        );

        // L5 — locale transfer, AI translation and portable package.
        $localetransfer = $this->read(
            $root . 'classes/commerce/storefront/localisation/'
                . 'CommerceStorefrontLocaleTransferService.php'
        );
        $aitranslation = $this->read(
            $root . 'classes/commerce/storefront/translation/'
                . 'CommerceStorefrontAiTranslationService.php'
        );
        $fieldmapper = $this->read(
            $root . 'classes/commerce/storefront/translation/'
                . 'CommerceStorefrontTranslationFieldMapper.php'
        );
        $settings = $this->read($root . 'settings.php');

        $checks[] = $this->check(
            CommerceStorefrontLocaleTransferService::LANGUAGES === ['fr', 'en', 'ru']
                && str_contains($localetransfer, "if (\$target === 'fr'")
                && str_contains($localetransfer, "\$showroom['locales'][\$target]"),
            'L5',
            'Locale copy FR / EN / RU',
            'Locale copy is supported for all CampusFR locales and preserves historical FR compatibility.'
        );

        $checks[] = $this->check(
            str_contains($aitranslation, 'storefront_ai_translation_enabled')
                && str_contains($aitranslation, 'preview(')
                && str_contains($aitranslation, 'apply_preview(')
                && str_contains($aitranslation, "'store' => false")
                && str_contains($fieldmapper, 'extract_locale(')
                && str_contains($settings, 'storefront_ai_translation_enabled'),
            'L5',
            'OpenAI translation review workflow',
            'AI translation is server-configured, preview-first and explicitly applied after review.'
        );

        $checks[] = $this->check(
            CommerceStorefrontPackageService::FORMAT === 'campusfr-commerce-product'
                && CommerceStorefrontPackageService::EXTENSION === 'cfrproduct'
                && is_file(
                    $root . 'classes/commerce/storefront/transfer/'
                        . 'CommerceStorefrontPackageService.php'
                ),
            'L5',
            'Portable Storefront package',
            '.cfrproduct export/import support remains available for DEV → PROD iso-configuration.'
        );

        // L6 — product-aware Course and Bundle Storefronts.
        $checks[] = $this->check(
            in_array(CommerceStorefrontLayoutContract::COURSE, CommerceStorefrontLayoutContract::layouts(), true)
                && is_file($root . 'templates/storefront/product_templates/course.mustache')
                && str_contains(
                    $pageresolver,
                    "\$product->get_type() === 'course_access'"
                ),
            'L6',
            'Course light Storefront',
            'Standard course_access products resolve to the dedicated Course layout.'
        );

        $checks[] = $this->check(
            in_array(CommerceStorefrontLayoutContract::BUNDLE, CommerceStorefrontLayoutContract::layouts(), true)
                && is_file($root . 'templates/storefront/product_templates/bundle.mustache')
                && str_contains(
                    $pageresolver,
                    "\$product->get_type() === 'bundle'"
                )
                && str_contains(
                    $pageresolver,
                    '$layout !== CommerceStorefrontLayoutContract::BUNDLE'
                ),
            'L6',
            'Bundle light Storefront',
            'Standard Bundles resolve to the dedicated Bundle layout without duplicated default description sections.'
        );

        $repository = $this->read(
            $root . 'classes/commerce/storefront/repository/'
                . 'CommerceStorefrontRepository.php'
        );
        $componentlocaliser = $this->read(
            $root . 'classes/commerce/storefront/localisation/'
                . 'CommerceStorefrontComponentLocaliser.php'
        );
        $checks[] = $this->check(
            str_contains($repository, 'CommerceStorefrontComponentLocaliser')
                && str_contains($repository, '->localise(')
                && str_contains($componentlocaliser, "'fr'")
                && str_contains($componentlocaliser, "'en'")
                && str_contains($componentlocaliser, "'ru'"),
            'L6',
            'Bundle component localisation',
            'Bundle child product names/descriptions are localised before public Storefront presentation.'
        );

        // L7 — owned/discovery semantics and contextual returns.
        $navigation = (new CommerceStorefrontNavigationCertificationService())->certify();
        foreach ($navigation['checks'] as $navigationcheck) {
            $checks[] = $this->check(
                $navigationcheck['ok'],
                'L7',
                $navigationcheck['label'],
                $navigationcheck['detail']
            );
        }

        // Cross-cutting — customer state, locales, templates and responsive CSS.
        $storefrontpage = $this->read($root . 'storefront_product.php');
        $commercepanel = $this->read(
            $root . 'templates/storefront/product_commerce_panel.mustache'
        );
        $checks[] = $this->check(
            str_contains($storefrontpage, 'subscription_config::guard_public_access()')
                && !str_contains($storefrontpage, 'require_login();')
                && str_contains($storefrontpage, 'isloggedin() && !isguestuser()')
                && str_contains($commercepanel, '{{#owned}}')
                && str_contains($commercepanel, '{{#canpurchase}}'),
            'Cross-cutting',
            'Guest / connected / owned separation',
            'Public Storefront access remains guest-safe while owned and purchase actions stay mutually distinct.'
        );

        $checks[] = $this->check(
            $this->language_keys_exist(
                $root,
                [
                    'commerce_storefront_bundle_includes',
                    'commerce_storefront_access_bundle_contents',
                    'commerce_storefront_back_to_showroom',
                    'commerce_storefront_ai_translation_title',
                    'commerce_storefront_ai_translation_apply',
                ]
            ),
            'Cross-cutting',
            'FR / EN / RU language coverage',
            'All Storefront strings introduced by the final L phases exist in the three supported locales.'
        );

        $css = $this->read($root . 'styles/storefront.css');
        $checks[] = $this->check(
            str_contains($css, '@media (max-width: 991.98px)')
                && str_contains($css, '@media (max-width: 575.98px)')
                && str_contains($css, '.commerce-product-course-hero')
                && str_contains($css, '.commerce-product-bundle-hero'),
            'Cross-cutting',
            'Responsive Course / Bundle Storefronts',
            'Dedicated Course and Bundle layouts retain tablet/mobile responsive rules.'
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
     * @return array{ok:bool,scope:string,label:string,detail:string}
     */
    private function check(
        bool $ok,
        string $scope,
        string $label,
        string $detail
    ): array {
        return [
            'ok' => $ok,
            'scope' => $scope,
            'label' => $label,
            'detail' => $detail,
        ];
    }

    private function read(string $path): string {
        return is_readable($path) ? (string)file_get_contents($path) : '';
    }

    /**
     * @param string[] $keys
     */
    private function language_keys_exist(string $root, array $keys): bool {
        foreach (['fr', 'en', 'ru'] as $language) {
            $source = $this->read(
                $root . 'lang/' . $language . '/local_subscriptions.php'
            );
            foreach ($keys as $key) {
                if (!str_contains($source, "\$string['{$key}']")) {
                    return false;
                }
            }
        }

        return true;
    }
}
