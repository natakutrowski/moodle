<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\campaign;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\mail\audience\CommerceMarketingCampaignAudienceResolver;
use local_subscriptions\commerce\mail\audience\CommerceMarketingProfileAudienceService;

/** Global fail-closed release switch and snapshot revalidation for course marketing. */
final class CommerceMarketingCourseCampaignGate {
    public static function enabled(): bool {
        return (string)get_config('local_subscriptions', 'commerce_mail_course_campaigns_enabled') === '1';
    }

    /**
     * Return true only if EVERY saved recipient is still eligible, and the eligible audience
     * has not changed since the operator last reviewed and saved the draft.
     * This is not proof of legal marketing permission; that needs separate operator review.
     */
    public static function snapshot_is_current(\moodle_database $db,
            CommerceMarketingCampaignRepository $repo, \stdClass $campaign): bool {
        $config = json_decode((string)($campaign->audienceconfig ?? ''), true);
        if (!is_array($config)) { return false; }
        try {
            $exclude = !array_key_exists('excludealreadycontacted', $config)
                || (bool)$config['excludealreadycontacted'];
            if ((string)$campaign->audiencesource === 'profiles'
                    && ($config['audiencepolicy'] ?? '') === 'global_profiles_v1'
                    && isset($config['profilesegment'], $config['excludedcourses'], $config['excludedproducts'])
                    && is_array($config['excludedcourses']) && is_array($config['excludedproducts'])) {
                $report = CommerceMarketingProfileAudienceService::create($db)->preview(
                    (string)$config['profilesegment'], $config['excludedcourses'],
                    $config['excludedproducts'], (int)$campaign->templateid, $exclude, (int)$campaign->id
                );
            } else if ((string)$campaign->audiencesource === 'course'
                    && ($config['audiencepolicy'] ?? '') === 'course_access_v2'
                    && !empty($config['courseid'])
                    && isset($config['supportskus'], $config['excludedids'], $config['filters'])
                    && is_array($config['supportskus']) && is_array($config['excludedids'])
                    && is_array($config['filters'])) {
                $report = CommerceMarketingCampaignAudienceResolver::create($db)->preview_course(
                    (int)$config['courseid'], $config['supportskus'], $config['excludedids'], $config['filters'],
                    (int)$campaign->templateid, $exclude, (int)$campaign->id
                );
            } else {
                return false;
            }
        } catch (\Throwable $exception) {
            return false;
        }
        $current = [];
        foreach ($report['recipients'] as $recipient) {
            $key = (int)$recipient['userid'] . ':' . strtolower((string)$recipient['email']);
            $current[$key] = true;
        }
        $saved = [];
        foreach ($repo->recipients((int)$campaign->id) as $recipient) {
            if (empty($recipient->userid) || !empty($recipient->mailid) ||
                    (string)$recipient->status !== 'draft') {
                return false;
            }
            $key = (int)$recipient->userid . ':' . strtolower((string)$recipient->email);
            $saved[$key] = true;
        }
        return $current !== [] && count($current) === count($saved) &&
            array_diff_key($current, $saved) === [] && array_diff_key($saved, $current) === [];
    }
}
