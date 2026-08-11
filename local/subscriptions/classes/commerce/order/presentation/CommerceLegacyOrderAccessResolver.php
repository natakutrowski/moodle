<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\order\presentation;

defined('MOODLE_INTERNAL') || die();

use moodle_database;
use moodle_url;
use local_subscriptions\url\UrlFactory;

/** Resolves secure customer actions for purchases originating from Legacy Commerce. */
final class CommerceLegacyOrderAccessResolver {
    public function __construct(private readonly moodle_database $database) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function resolve(?string $family, ?int $legacyid): array {
        $family = strtolower(trim((string)$family));
        if ($legacyid === null || $legacyid <= 0) {
            return [];
        }

        return match ($family) {
            'subscription' => $this->resolve_subscription($legacyid),
            'digital' => $this->resolve_digital($legacyid),
            default => [],
        };
    }

    private function resolve_subscription(int $subscriptionid): array {
        $subscription = $this->database->get_record(
            'user_subscription',
            ['id' => $subscriptionid],
            'id,planid,status,end_date',
            IGNORE_MISSING
        );
        if ($subscription === false) {
            return [];
        }
        $plan = $this->database->get_record(
            'subscription_plan',
            ['id' => (int)$subscription->planid],
            'id,accessscopeid,is_trial',
            IGNORE_MISSING
        );
        if ($plan === false) {
            return [];
        }
        $scope = $this->database->get_record(
            'subscription_access_scope',
            ['id' => (int)$plan->accessscopeid],
            'course_ids',
            IGNORE_MISSING
        );
        if ($scope === false) {
            return [];
        }
        $courseids = array_values(array_filter(array_map(
            'intval',
            preg_split('/\s*,\s*/', trim((string)$scope->course_ids)) ?: []
        )));
        if ($courseids === []) {
            return [];
        }
        $courses = $this->database->get_records_list('course', 'id', $courseids, 'fullname ASC, id ASC', 'id,fullname');
        $status = strtolower(trim((string)$subscription->status));
        $configuredtrialplanid = (int)(get_config('local_subscriptions', 'trial_plan_id') ?? 0);
        $istrial = !empty($plan->is_trial)
            || ($configuredtrialplanid > 0 && $configuredtrialplanid === (int)$plan->id);
        $trialnotexpired = empty($subscription->end_date) || (int)$subscription->end_date >= time();
        $accessavailable = $status === 'active' || ($istrial && $trialnotexpired);

        $actions = [];
        foreach ($courses as $course) {
            $actions[] = [
                'type' => 'course_access',
                'available' => $accessavailable,
                'url' => UrlFactory::course((int)$course->id)->out(false),
                'resourcelabel' => format_string((string)$course->fullname),
                'hasdesktop' => false,
                'hasmobile' => false,
                'legacy' => true,
            ];
        }
        return $actions;
    }

    private function resolve_digital(int $requestid): array {
        $request = $this->database->get_record(
            'subscription_digital_payment_request',
            ['id' => $requestid],
            'id,productid,status,download_token,download_token_expires',
            IGNORE_MISSING
        );
        if ($request === false) {
            return [];
        }
        $product = $this->database->get_record(
            'subscription_digital_product',
            ['id' => (int)$request->productid],
            'id,name,filename,mobile_filename',
            IGNORE_MISSING
        );
        if ($product === false || trim((string)$request->download_token) === '') {
            return [];
        }

        $available = in_array(strtolower((string)$request->status), ['paid', 'completed'], true)
            && (empty($request->download_token_expires) || (int)$request->download_token_expires >= time());
        $baseparams = ['token' => (string)$request->download_token];

        return [[
            'type' => 'digital_download',
            'available' => $available,
            'url' => null,
            'desktopurl' => (new moodle_url('/local/subscriptions/download_pdf.php', $baseparams + ['version' => 'main']))->out(false),
            'mobileurl' => empty($product->mobile_filename)
                ? null
                : (new moodle_url('/local/subscriptions/download_pdf.php', $baseparams + ['version' => 'mobile']))->out(false),
            'resourcelabel' => format_string((string)$product->name),
            'hasdesktop' => trim((string)$product->filename) !== '',
            'hasmobile' => trim((string)$product->mobile_filename) !== '',
            'legacy' => true,
        ]];
    }
}
