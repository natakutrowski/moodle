<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\audience;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\mail\campaign\CommerceMarketingOptoutService;

/**
 * Previews a course-based marketing audience and freezes only eligible recipients.
 * This is NOT proof of consent, purchase history, or readiness for dispatch.
 */
final class CommerceMarketingCampaignAudienceResolver {
    public function __construct(
        private readonly CommerceMarketingCourseAudienceService $courses,
        private readonly CommerceMarketingOptoutService $optouts
    ) {
    }

    public static function create(?\moodle_database $db = null): self {
        global $DB;
        $db ??= $DB;
        return new self(new CommerceMarketingCourseAudienceService($db), new CommerceMarketingOptoutService($db));
    }

    /** @return string[] */
    public static function parse_skus(string $raw): array {
        $result = [];
        foreach (preg_split('/[,;\s]+/', trim($raw)) ?: [] as $sku) {
            if ($sku === '') {
                continue;
            }
            $sku = strtoupper($sku);
            if (preg_match('/^[A-Z0-9_-]{1,64}$/D', $sku) !== 1) {
                throw new \invalid_parameter_exception('Invalid support product SKU.');
            }
            $result[$sku] = $sku;
            if (count($result) > 30) {
                throw new \invalid_parameter_exception('Too many support product SKUs.');
            }
        }
        return array_values($result);
    }

    /** @return int[] */
    public static function parse_excluded_ids(string $raw): array {
        if (trim($raw) === '') {
            return [];
        }
        if (preg_match('/^[\d\s,;]+$/D', $raw) !== 1) {
            throw new \invalid_parameter_exception('Excluded user IDs must be integers separated by commas.');
        }
        $result = [];
        foreach (preg_split('/[,;\s]+/', trim($raw)) ?: [] as $id) {
            if ($id === '') {
                continue;
            }
            $id = (int)$id;
            if ($id <= 0) {
                throw new \invalid_parameter_exception('Invalid excluded user ID.');
            }
            $result[$id] = $id;
            if (count($result) > 500) {
                throw new \invalid_parameter_exception('Too many excluded user IDs.');
            }
        }
        return array_values($result);
    }

    /**
     * @param string[] $supportskus
     * @param int[] $excludedids
     * @return array{courseid:int,course:string,summary:array<string,int>,rows:array<int,array<string,mixed>>,recipients:array<int,array<string,mixed>>}
     */
    public function preview_course(int $courseid, array $supportskus, array $excludedids = []): array {
        $report = $this->courses->preview($courseid, $supportskus);
        $excluded = array_fill_keys($excludedids, true);
        $summary = ['candidates' => 0, 'eligible' => 0, 'excluded' => 0];
        $rows = [];
        $recipients = [];

        foreach ($report['rows'] as $row) {
            $reasons = $row['reasons'];
            if (isset($excluded[(int)$row['userid']])) {
                $reasons[] = 'internal_exclusion';
            }
            // Invalid emails have already been excluded by the course segment.
            if ($reasons === [] && $this->optouts->is_suppressed((string)$row['email'])) {
                $reasons[] = 'marketing_optout';
            }
            $row['reasons'] = $reasons;
            $row['eligible'] = $reasons === [];
            $summary['candidates']++;
            $summary[$row['eligible'] ? 'eligible' : 'excluded']++;
            foreach ($reasons as $reason) {
                $key = 'reason_' . $reason;
                $summary[$key] = ($summary[$key] ?? 0) + 1;
            }
            if ($row['eligible']) {
                $language = strtolower((string)$row['language']);
                if (!in_array($language, ['fr', 'en', 'ru'], true)) {
                    $language = 'fr';
                }
                $recipients[] = [
                    'email' => (string)$row['email'],
                    'firstname' => (string)$row['firstname'],
                    'lastname' => (string)$row['lastname'],
                    'language' => $language,
                    'userid' => (int)$row['userid'],
                ];
            }
            $rows[] = $row;
        }
        return [
            'courseid' => $courseid,
            'course' => $report['course'],
            'summary' => $summary,
            'rows' => $rows,
            'recipients' => $recipients,
        ];
    }
}
