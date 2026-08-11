<?php

namespace local_subscriptions\commerce\certification;

/**
 * Read-only source integrity checks for the public Commerce storefront.
 *
 * The audit validates the current F6I architecture:
 * - cards render type and merchandising badges;
 * - product templates render the type badge and delegate Commerce content to
 *   the shared Commerce panel;
 * - the Commerce panel renders merchandising badges before prices.
 */
final class CommerceStorefrontUxCertificationAuditor {
    public function __construct(private readonly string $pluginroot) {
    }

    public function audit(): CommerceCertificationReport {
        $report = new CommerceCertificationReport('7.95F7F');

        $requiredfiles = [
            'templates/storefront/product_card.mustache',
            'templates/storefront/product_badges.mustache',
            'templates/storefront/product_commerce_panel.mustache',
            'templates/storefront/product_templates/default.mustache',
            'templates/storefront/product_templates/editorial.mustache',
            'templates/storefront/product_templates/immersive.mustache',
            'styles/storefront.css',
        ];

        $missing = [];
        foreach ($requiredfiles as $relativepath) {
            if (!is_readable($this->pluginroot . '/' . $relativepath)) {
                $missing[] = $relativepath;
            }
        }

        $report->add_inventory('required_files', count($requiredfiles));
        $report->add_inventory('missing_required_files', count($missing));

        foreach ($missing as $relativepath) {
            $report->add_issue(
                'blocking',
                'missing_storefront_file',
                'A required storefront source file is missing.',
                ['file' => $relativepath]
            );
        }

        if ($missing) {
            return $report;
        }

        $card = $this->read('templates/storefront/product_card.mustache');
        $panel = $this->read('templates/storefront/product_commerce_panel.mustache');
        $badges = $this->read('templates/storefront/product_badges.mustache');
        $css = $this->read('styles/storefront.css');

        $this->expect_contains($report, $card, 'commerce-product-type-badge', 'card_type_badge');
        $this->expect_partial(
            $report,
            $card,
            'local_subscriptions/storefront/product_badges',
            'card_shared_badges'
        );
        $this->expect_contains($report, $card, 'commerce-product-card__prices', 'card_prices');
        $this->expect_any_contains(
            $report,
            $card,
            ['quickpurchaseurl', 'cartaction'],
            'card_purchase_action'
        );

        $this->expect_partial_before_marker(
            $report,
            $panel,
            'local_subscriptions/storefront/product_badges',
            'commerce-product-page__prices',
            'panel_commercial_badges_before_prices',
            'The Commerce panel must render merchandising badges before prices.'
        );
        $this->expect_any_contains(
            $report,
            $panel,
            ['quickpurchaseurl', 'cartaction'],
            'panel_purchase_action'
        );

        $this->expect_contains($report, $badges, '{{#hasbadges}}', 'shared_badges_guard');
        $this->expect_contains($report, $badges, '{{#badges}}', 'shared_badges_loop');
        $this->expect_contains($report, $badges, 'commerce-product-badge--gustave', 'gustave_badge_renderer');

        $this->expect_contains($report, $css, '.commerce-product-type-badge', 'type_badge_css');
        $this->expect_contains(
            $report,
            $css,
            '.commerce-product-badge__gustave-medallion',
            'gustave_medallion_css'
        );
        $this->expect_contains($report, $css, '@media (max-width:', 'responsive_rules');

        foreach (['default', 'editorial', 'immersive'] as $template) {
            $source = $this->read('templates/storefront/product_templates/' . $template . '.mustache');

            $this->expect_contains(
                $report,
                $source,
                'commerce-product-type-badge',
                $template . '_type_badge'
            );
            $this->expect_partial(
                $report,
                $source,
                'local_subscriptions/storefront/product_commerce_panel',
                $template . '_commerce_panel'
            );
        }

        return $report;
    }

    private function read(string $relativepath): string {
        $content = file_get_contents($this->pluginroot . '/' . $relativepath);
        return $content === false ? '' : $content;
    }

    private function expect_partial_before_marker(
        CommerceCertificationReport $report,
        string $source,
        string $partial,
        string $marker,
        string $check,
        string $message
    ): void {
        $partialposition = $this->partial_position($source, $partial);
        $markerposition = strpos($source, $marker);
        $passed = $partialposition !== null
            && $markerposition !== false
            && $partialposition < $markerposition;

        $this->record_check($report, $passed, $check, $message);
    }

    private function expect_partial(
        CommerceCertificationReport $report,
        string $source,
        string $partial,
        string $check
    ): void {
        $this->record_check(
            $report,
            $this->partial_position($source, $partial) !== null,
            $check,
            'A required shared storefront partial is missing.'
        );
    }

    private function partial_position(string $source, string $partial): ?int {
        $pattern = '/{{>\s*' . preg_quote($partial, '/') . '\s*}}/';
        $matched = preg_match($pattern, $source, $matches, PREG_OFFSET_CAPTURE);

        if ($matched !== 1) {
            return null;
        }

        return (int)$matches[0][1];
    }

    /** @param string[] $needles */
    private function expect_any_contains(
        CommerceCertificationReport $report,
        string $source,
        array $needles,
        string $check
    ): void {
        $passed = false;
        foreach ($needles as $needle) {
            if (str_contains($source, $needle)) {
                $passed = true;
                break;
            }
        }

        $this->record_check(
            $report,
            $passed,
            $check,
            'An expected storefront capability marker is missing.'
        );
    }

    private function expect_contains(
        CommerceCertificationReport $report,
        string $source,
        string $needle,
        string $check
    ): void {
        $this->record_check(
            $report,
            str_contains($source, $needle),
            $check,
            'An expected storefront capability marker is missing.'
        );
    }

    private function record_check(
        CommerceCertificationReport $report,
        bool $passed,
        string $check,
        string $message
    ): void {
        $report->add_inventory($check, $passed);

        if (!$passed) {
            $report->add_issue(
                'blocking',
                'storefront_source_regression',
                $message,
                ['check' => $check]
            );
        }
    }
}
