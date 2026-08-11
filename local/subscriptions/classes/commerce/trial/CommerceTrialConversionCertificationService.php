<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\trial;

defined('MOODLE_INTERNAL') || die();

/** Read-only J6 certification for the Legacy Trial to Native Commerce bridge. */
final class CommerceTrialConversionCertificationService {
    /** @return array<int, array{status:string,label:string,detail:string}> */
    public function certify(): array {
        global $CFG, $DB;

        $findings = [];
        $requiredclasses = [
            CommerceTrialConversionBridge::class,
            CommerceTrialCartPricingService::class,
            CommerceTrialConversionCompletionService::class,
            \local_subscriptions\commerce\course\storefront\CommerceCourseStorefrontTargetResolver::class,
        ];

        $missing = array_values(array_filter(
            $requiredclasses,
            static fn(string $class): bool => !class_exists($class)
        ));
        $findings[] = $this->finding(
            $missing === [] ? 'ok' : 'error',
            'Trial conversion components',
            $missing === []
                ? count($requiredclasses) . ' required components available.'
                : 'Missing: ' . implode(', ', $missing)
        );

        $trialplanid = (int)get_config(
            'local_subscriptions',
            'trial_plan_id'
        );
        $configurationok = $trialplanid > 0;

        $findings[] = $this->finding(
            $configurationok ? 'ok' : 'warning',
            'Trial conversion configuration',
            $configurationok
                ? 'Trial plan configured; Native targets are resolved dynamically from course entitlements.'
                : 'Configure the Legacy Trial plan.'
        );

        $campuslib = $CFG->dirroot . '/local/campus/lib.php';
        $campussource = is_readable($campuslib)
            ? file_get_contents($campuslib)
            : '';
        $modalpreserved = is_string($campussource) &&
            str_contains($campussource, 'campusTrialModal') &&
            str_contains($campussource, 'CommerceTrialConversionBridge');

        $findings[] = $this->finding(
            $modalpreserved ? 'ok' : 'error',
            'Legacy Trial entry point',
            $modalpreserved
                ? 'Homepage Trial modal is preserved and conversion uses the Native bridge.'
                : 'The homepage Trial modal or Native bridge contract is missing.'
        );

        $carttemplate = $CFG->dirroot .
            '/local/subscriptions/templates/storefront/product_commerce_panel.mustache';
        $cartsource = is_readable($carttemplate)
            ? file_get_contents($carttemplate)
            : '';
        $cartservice = $CFG->dirroot .
            '/local/subscriptions/classes/commerce/cart/service/' .
            'CommerceCartService.php';
        $cartservicesource = is_readable($cartservice)
            ? file_get_contents($cartservice)
            : '';
        $securecart = is_string($cartsource) &&
            !str_contains($cartsource, 'value="trialconversion"') &&
            !str_contains($cartsource, 'name="trialdiscountpercent"') &&
            is_string($cartservicesource) &&
            str_contains(
                $cartservicesource,
                '$this->trialpricing->canonical_metadata'
            );

        $findings[] = $this->finding(
            $securecart ? 'ok' : 'error',
            'Server-side Trial pricing',
            $securecart
                ? 'Trial eligibility and canonical pricing are inferred entirely on the server.'
                : 'The Native cart pricing contract is incomplete.'
        );

        $completer = $CFG->dirroot .
            '/local/subscriptions/classes/commerce/fulfillment/native/checkout/' .
            'CommerceNativePaidPurchaseCompleter.php';
        $completersource = is_readable($completer)
            ? file_get_contents($completer)
            : '';
        $postpurchase = is_string($completersource) &&
            str_contains(
                $completersource,
                'CommerceTrialConversionCompletionService'
            );

        $findings[] = $this->finding(
            $postpurchase ? 'ok' : 'error',
            'Post-purchase Trial consumption',
            $postpurchase
                ? 'Trial consumption runs only after successful Native fulfillment.'
                : 'The post-fulfillment Trial consumption hook is missing.'
        );

        $activetrials = 0;
        if ($trialplanid > 0 && $DB->get_manager()->table_exists('user_subscription')) {
            $activetrials = $DB->count_records('user_subscription', [
                'planid' => $trialplanid,
                'status' => 'active',
            ]);
        }

        $findings[] = $this->finding(
            'ok',
            'Current Trial population',
            'active_trials=' . $activetrials
        );

        return $findings;
    }

    private function finding(
        string $status,
        string $label,
        string $detail
    ): array {
        return [
            'status' => $status,
            'label' => $label,
            'detail' => $detail,
        ];
    }
}
