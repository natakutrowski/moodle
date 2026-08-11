<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\course\certification;

defined('MOODLE_INTERNAL') || die();

/**
 * Read-only certification of the My Courses presentation and Native upgrade chain.
 *
 * The service intentionally performs no purchase, grant, cart, or enrolment write.
 */
final class CommerceMyCoursesCertificationService {
    public function __construct(private readonly \moodle_database $db) {
    }

    public function certify(): CommerceMyCoursesCertificationReport {
        global $CFG;

        $report = new CommerceMyCoursesCertificationReport();
        $this->check_required_files($report, $CFG->dirroot);
        $this->check_native_my_courses_contract($report, $CFG->dirroot);
        $this->check_recommendation_contract($report, $CFG->dirroot);
        $this->check_upgrade_ownership_contract($report, $CFG->dirroot);
        $this->check_native_cart_contract($report, $CFG->dirroot);
        $this->check_post_upgrade_contract($report, $CFG->dirroot);
        $this->check_database_contract($report);
        $this->check_plugin_versions($report, $CFG->dirroot);

        return $report;
    }

    private function check_required_files(CommerceMyCoursesCertificationReport $report, string $dirroot): void {
        $required = [
            '/local/campus/classes/mycourses/MyCoursesService.php',
            '/local/campus/classes/output/mycourses/MyCoursesPage.php',
            '/local/campus/templates/mycourses/page.mustache',
            '/local/campus/templates/mycourses/components/course_card.mustache',
            '/local/campus/templates/mycourses/components/recommendations.mustache',
            '/local/subscriptions/classes/commerce/course/library/CommerceCourseAccessEnrichmentService.php',
            '/local/subscriptions/classes/commerce/course/recommendation/CommerceCourseRecommendationService.php',
            '/local/subscriptions/classes/commerce/storefront/upgrade/CommerceStorefrontUpgradeResolver.php',
            '/local/subscriptions/classes/commerce/upgrade/CommercePlanOwnershipResolver.php',
            '/local/subscriptions/classes/commerce/cart/service/CommerceCartUpgradePricingService.php',
        ];

        $missing = [];
        foreach ($required as $relative) {
            if (!is_readable($dirroot . $relative)) {
                $missing[] = $relative;
            }
        }

        if ($missing === []) {
            $report->add(new CommerceMyCoursesCertificationFinding(
                CommerceMyCoursesCertificationFinding::OK,
                'Required My Courses components',
                count($required) . ' required components available.'
            ));
            return;
        }

        $report->add(new CommerceMyCoursesCertificationFinding(
            CommerceMyCoursesCertificationFinding::ERROR,
            'Required My Courses components',
            'Missing: ' . implode(', ', $missing)
        ));
    }

    private function check_native_my_courses_contract(
        CommerceMyCoursesCertificationReport $report,
        string $dirroot
    ): void {
        $paths = [
            $dirroot . '/local/campus/mycourses.php',
            $dirroot . '/local/campus/classes/output/mycourses/MyCoursesPage.php',
            $dirroot . '/local/campus/templates/mycourses/page.mustache',
        ];
        $source = $this->read_sources($paths);

        $problems = [];
        foreach (['block_edly_course_filter', 'access_info_map'] as $needle) {
            if (str_contains($source, $needle)) {
                $problems[] = $needle;
            }
        }
        foreach (['subscribe.php', 'checkout.php?planid='] as $needle) {
            if (str_contains($source, $needle)) {
                $problems[] = $needle;
            }
        }

        if ($problems === []) {
            $report->add(new CommerceMyCoursesCertificationFinding(
                CommerceMyCoursesCertificationFinding::OK,
                'Native My Courses rendering',
                'No Edly renderer dependency or Legacy purchase CTA remains in the page contract.'
            ));
        } else {
            $report->add(new CommerceMyCoursesCertificationFinding(
                CommerceMyCoursesCertificationFinding::ERROR,
                'Native My Courses rendering',
                'Forbidden contracts found: ' . implode(', ', array_unique($problems))
            ));
        }
    }

    private function check_recommendation_contract(
        CommerceMyCoursesCertificationReport $report,
        string $dirroot
    ): void {
        $service = $this->read_file(
            $dirroot . '/local/subscriptions/classes/commerce/course/recommendation/'
            . 'CommerceCourseRecommendationService.php'
        );
        $ranker = $this->read_file(
            $dirroot . '/local/subscriptions/classes/commerce/course/recommendation/'
            . 'CommerceCourseRecommendationRanker.php'
        );
        $template = $this->read_file(
            $dirroot . '/local/campus/templates/mycourses/components/recommendations.mustache'
        );

        $requiredneedles = [
            'if ($product->is_owned())',
            'CommerceCourseRecommendationRanker',
            'gustave_choice',
            'upgrade',
        ];
        $missing = [];
        $combined = $service . "\n" . $ranker;
        foreach ($requiredneedles as $needle) {
            if (!str_contains($combined, $needle)) {
                $missing[] = $needle;
            }
        }

        $decorativebadges = str_contains($template, '{{#badges}}') || str_contains($template, '{{typelabel}}');
        if ($missing === [] && !$decorativebadges) {
            $report->add(new CommerceMyCoursesCertificationFinding(
                CommerceMyCoursesCertificationFinding::OK,
                'Course recommendations',
                'Owned targets are excluded and recommendation ranking remains internal.'
            ));
            return;
        }

        $detail = $missing === [] ? '' : 'Missing contracts: ' . implode(', ', $missing) . '. ';
        if ($decorativebadges) {
            $detail .= 'Decorative Storefront badges are rendered in recommendations.';
        }
        $report->add(new CommerceMyCoursesCertificationFinding(
            CommerceMyCoursesCertificationFinding::ERROR,
            'Course recommendations',
            trim($detail)
        ));
    }

    private function check_upgrade_ownership_contract(
        CommerceMyCoursesCertificationReport $report,
        string $dirroot
    ): void {
        $source = $this->read_file(
            $dirroot . '/local/subscriptions/classes/commerce/upgrade/CommercePlanOwnershipResolver.php'
        );
        $needles = [
            'native_grant_for_source_plan',
            "'sourceplanid'",
            "'source_plan_id'",
            "'legacyplanid'",
            "'source' => 'native_grant'",
        ];
        $missing = array_values(array_filter(
            $needles,
            static fn(string $needle): bool => !str_contains($source, $needle)
        ));

        $report->add(new CommerceMyCoursesCertificationFinding(
            $missing === []
                ? CommerceMyCoursesCertificationFinding::OK
                : CommerceMyCoursesCertificationFinding::ERROR,
            'Native plan ownership',
            $missing === []
                ? 'Legacy plans can be recognised from Native grants through sourceplanid.'
                : 'Missing ownership contracts: ' . implode(', ', $missing)
        ));
    }

    private function check_native_cart_contract(
        CommerceMyCoursesCertificationReport $report,
        string $dirroot
    ): void {
        $paths = [
            $dirroot . '/local/subscriptions/cart_action.php',
            $dirroot . '/local/subscriptions/classes/commerce/cart/service/CommerceCartUpgradePricingService.php',
            $dirroot . '/local/subscriptions/classes/commerce/checkout/unified/CommerceCheckoutPurchaseBuilder.php',
            $dirroot . '/local/subscriptions/classes/commerce/fulfillment/native/checkout/'
                . 'CommerceNativePurchaseGrantPlanner.php',
            $dirroot . '/local/subscriptions/templates/storefront/product_card.mustache',
            $dirroot . '/local/subscriptions/templates/storefront/product_commerce_panel.mustache',
        ];
        $source = $this->read_sources($paths);
        $needles = [
            'operation',
            'upgrade',
            'sourceplanid',
            'targetplanid',
            'upgradeamountminor',
            'CommerceCartUpgradePricingService',
        ];
        $missing = array_values(array_filter(
            $needles,
            static fn(string $needle): bool => !str_contains($source, $needle)
        ));
        $legacyupgradeurl = str_contains($source, 'checkout.php?planid=');

        if ($missing === [] && !$legacyupgradeurl) {
            $report->add(new CommerceMyCoursesCertificationFinding(
                CommerceMyCoursesCertificationFinding::OK,
                'Native upgrade checkout',
                'Upgrade price and metadata are preserved from Storefront to fulfillment.'
            ));
            return;
        }

        $detail = $missing === [] ? '' : 'Missing contracts: ' . implode(', ', $missing) . '. ';
        if ($legacyupgradeurl) {
            $detail .= 'A Legacy upgrade checkout URL is still present.';
        }
        $report->add(new CommerceMyCoursesCertificationFinding(
            CommerceMyCoursesCertificationFinding::ERROR,
            'Native upgrade checkout',
            trim($detail)
        ));
    }

    private function check_post_upgrade_contract(
        CommerceMyCoursesCertificationReport $report,
        string $dirroot
    ): void {
        $repository = $this->read_file(
            $dirroot . '/local/subscriptions/classes/commerce/storefront/repository/CommerceStorefrontRepository.php'
        );
        $presenter = $this->read_file(
            $dirroot . '/local/subscriptions/classes/commerce/storefront/presentation/CommerceStorefrontPresenter.php'
        );
        $recommendations = $this->read_file(
            $dirroot . '/local/subscriptions/classes/commerce/course/recommendation/'
            . 'CommerceCourseRecommendationService.php'
        );
        $purchases = $this->read_file(
            $dirroot . '/local/subscriptions/classes/output/my_purchases/CurrentPresentationRenderer.php'
        );

        $checks = [
            'repository_owned_precedence' => str_contains($repository, 'if (!$owned'),
            'presenter_owned_precedence' => str_contains($presenter, '$product->is_owned() ? null'),
            'recommendation_owned_exclusion' => str_contains($recommendations, 'if ($product->is_owned())'),
            'immutable_purchase_item_label' => str_contains($purchases, 'nativeitemlabel'),
        ];
        $failed = array_keys(array_filter($checks, static fn(bool $passed): bool => !$passed));

        $report->add(new CommerceMyCoursesCertificationFinding(
            $failed === []
                ? CommerceMyCoursesCertificationFinding::OK
                : CommerceMyCoursesCertificationFinding::ERROR,
            'Post-upgrade state',
            $failed === []
                ? 'Owned state wins over upgrade CTAs and purchase labels remain purchase-specific.'
                : 'Missing protections: ' . implode(', ', $failed)
        ));
    }

    private function check_database_contract(CommerceMyCoursesCertificationReport $report): void {
        $tables = [
            'local_subs_commerce_grant',
            'local_subs_commerce_product',
            'local_subscriptions_commerce_purchase',
            'subscription_plan_upgrade',
        ];
        $manager = $this->db->get_manager();
        $missing = [];
        foreach ($tables as $table) {
            if (!$manager->table_exists(new \xmldb_table($table))) {
                $missing[] = $table;
            }
        }

        if ($missing !== []) {
            $report->add(new CommerceMyCoursesCertificationFinding(
                CommerceMyCoursesCertificationFinding::ERROR,
                'Commerce database contract',
                'Missing tables: ' . implode(', ', $missing)
            ));
            return;
        }

        $activeupgrades = $this->db->count_records('subscription_plan_upgrade', ['isactive' => 1]);
        $report->add(new CommerceMyCoursesCertificationFinding(
            $activeupgrades > 0
                ? CommerceMyCoursesCertificationFinding::OK
                : CommerceMyCoursesCertificationFinding::WARNING,
            'Commerce database contract',
            $activeupgrades > 0
                ? $activeupgrades . ' active upgrade rule(s) available.'
                : 'All required tables exist, but no active upgrade rule is configured.'
        ));
    }

    private function check_plugin_versions(
        CommerceMyCoursesCertificationReport $report,
        string $dirroot
    ): void {
        $subscriptionversion = $this->extract_plugin_version($dirroot . '/local/subscriptions/version.php');
        $campusversion = $this->extract_plugin_version($dirroot . '/local/campus/version.php');
        $minimum = 2026080100;

        if ($subscriptionversion >= $minimum && $campusversion >= $minimum) {
            $report->add(new CommerceMyCoursesCertificationFinding(
                CommerceMyCoursesCertificationFinding::OK,
                'Plugin release versions',
                'local_subscriptions=' . $subscriptionversion . ', local_campus=' . $campusversion . '.'
            ));
            return;
        }

        $report->add(new CommerceMyCoursesCertificationFinding(
            CommerceMyCoursesCertificationFinding::ERROR,
            'Plugin release versions',
            'Expected both plugin versions to be at least ' . $minimum
                . '; got local_subscriptions=' . $subscriptionversion
                . ', local_campus=' . $campusversion . '.'
        ));
    }

    /** @param string[] $paths */
    private function read_sources(array $paths): string {
        return implode("\n", array_map(fn(string $path): string => $this->read_file($path), $paths));
    }

    private function read_file(string $path): string {
        if (!is_readable($path)) {
            return '';
        }
        $content = file_get_contents($path);
        return is_string($content) ? $content : '';
    }

    private function extract_plugin_version(string $path): int {
        $source = $this->read_file($path);
        if (preg_match('/\\$plugin->version\\s*=\\s*(\\d+)/', $source, $matches) !== 1) {
            return 0;
        }
        return (int)$matches[1];
    }
}
