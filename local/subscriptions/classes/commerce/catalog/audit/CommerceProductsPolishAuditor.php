<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\audit;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\presentation\CommerceLanguagePresentation;
use local_subscriptions\commerce\catalog\presentation\CommerceProductPresentation;
use local_subscriptions\commerce\catalog\service\CommerceCatalogLocaleService;

/** Certifies the final 7.94E Commerce Products presentation polish. */
final class CommerceProductsPolishAuditor {
    public function __construct(private readonly string $plugindir) {
    }

    public function audit(): array {
        $errors = [];
        $checks = [
            'types' => $this->check_types($errors),
            'entitlements' => $this->check_entitlements($errors),
            'navigation' => $this->check_navigation($errors),
            'languages' => $this->check_languages($errors),
            'pricingstrings' => $this->check_pricing_strings($errors),
        ];

        return [
            'checks' => $checks,
            'languages' => count((new CommerceCatalogLocaleService())->get_languages()),
            'errors' => $errors,
            'certified' => $errors === [],
        ];
    }

    private function check_types(array &$errors): bool {
        foreach (['course_access', 'digital_download', 'bundle', 'service'] as $type) {
            if (CommerceProductPresentation::type_label($type) === $type) {
                $errors[] = 'Technical product type exposed: ' . $type;
            }
        }

        $components = $this->read('admin/commerce/products/components.php', $errors);
        if ($components !== '' && str_contains($components, "' [' . \$candidate->get_type() . ']'")) {
            $errors[] = 'components.php still renders technical product types.';
        }

        return !$this->has_prefix($errors, ['Technical product type', 'components.php']);
    }

    private function check_entitlements(array &$errors): bool {
        $preview = $this->read('admin/commerce/products/preview.php', $errors);
        if ($preview !== '' && !str_contains($preview, 'CommerceProductPresentation::entitlement_html')) {
            $errors[] = 'preview.php does not use the readable entitlement renderer.';
        }
        if (!str_contains(CommerceProductPresentation::entitlement_reference('course:13:full'), 'course:13:full')) {
            $errors[] = 'Technical entitlement reference is not preserved.';
        }

        return !$this->has_prefix($errors, ['preview.php', 'Technical entitlement']);
    }

    private function check_navigation(array &$errors): bool {
        $preview = $this->read('admin/commerce/products/preview.php', $errors);
        $count = substr_count($preview, 'CommerceProductEditorNavigationRenderer::render(');
        if ($count !== 1) {
            $errors[] = 'preview.php must render exactly one product workflow navigation; found ' . $count . '.';
        }
        if (str_contains($preview, '$rendersteps(') || str_contains($preview, '$steps = [')) {
            $errors[] = 'preview.php still contains its former local workflow navigation.';
        }

        return !$this->has_prefix($errors, ['preview.php']);
    }

    private function check_languages(array &$errors): bool {
        $languages = (new CommerceCatalogLocaleService())->get_languages();
        if ($languages === []) {
            $errors[] = 'No installed Moodle language was discovered.';
        }
        if (CommerceLanguagePresentation::flag('en') !== '🇬🇧') {
            $errors[] = 'English must use the UK flag.';
        }
        foreach (['edit.php', 'view.php'] as $file) {
            $content = $this->read('admin/commerce/products/' . $file, $errors);
            if ($content !== '' && !str_contains($content, 'CommerceLanguagePresentation::label')) {
                $errors[] = $file . ' does not use the shared language presentation.';
            }
        }

        return !$this->has_prefix($errors, ['No installed', 'English must', 'edit.php', 'view.php']);
    }

    private function check_pricing_strings(array &$errors): bool {
        $pricing = $this->read('admin/commerce/products/pricing.php', $errors);
        if (str_contains($pricing, "get_string('price')")) {
            $errors[] = 'pricing.php still requests the missing Moodle price string.';
        }
        if (!str_contains($pricing, "get_string('commerce_price', 'local_subscriptions')")) {
            $errors[] = 'pricing.php does not use the plugin-owned price string.';
        }
        if (!get_string_manager()->string_exists('commerce_price', 'local_subscriptions')) {
            $errors[] = 'The commerce_price language string is missing.';
        }

        return !$this->has_prefix($errors, ['pricing.php', 'The commerce_price']);
    }

    private function read(string $relativepath, array &$errors): string {
        $path = $this->plugindir . '/' . $relativepath;
        $content = is_file($path) ? file_get_contents($path) : false;
        if (!is_string($content)) {
            $errors[] = 'Missing source file: ' . $relativepath;
            return '';
        }
        return $content;
    }

    private function has_prefix(array $errors, array $prefixes): bool {
        foreach ($errors as $error) {
            foreach ($prefixes as $prefix) {
                if (str_starts_with($error, $prefix)) {
                    return true;
                }
            }
        }
        return false;
    }
}
