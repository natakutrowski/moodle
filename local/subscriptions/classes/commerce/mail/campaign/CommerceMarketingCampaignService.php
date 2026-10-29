<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\campaign;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\mail\builder\CommerceMailBuilder;
use local_subscriptions\commerce\mail\audience\CommerceMarketingCampaignAudienceResolver;
use local_subscriptions\commerce\mail\audience\CommerceMarketingCourseAudienceService;
use local_subscriptions\commerce\mail\campaign\CommerceMarketingOptoutService;
use local_subscriptions\commerce\mail\library\CommerceMailLibrary;
use local_subscriptions\commerce\mail\library\CommerceMailLibraryRepository;

final class CommerceMarketingCampaignService {
    public function __construct(
        private readonly \moodle_database $db,
        private readonly CommerceMarketingCampaignRepository $repository,
        private readonly CommerceMailLibraryRepository $library
    ) {}

    public static function create(?\moodle_database $db = null): self {
        global $DB;
        $db ??= $DB;
        return new self(
            $db,
            new CommerceMarketingCampaignRepository($db),
            new CommerceMailLibraryRepository($db)
        );
    }

    /** @return array<int,string> */
    public function template_options(): array {
        $result = [];
        foreach ($this->library->all(CommerceMailLibrary::CATEGORY_MARKETING) as $template) {
            if ((string)$template->status === CommerceMailLibrary::STATUS_ARCHIVED) {
                continue;
            }
            $result[(int)$template->id] = (string)$template->name;
        }
        return $result;
    }

    public function save(
        array $data,
        int $userid,
        ?int $campaignid = null
    ): int {
        $now = time();
        $templateid = (int)($data['templateid'] ?? 0);
        $template = $this->library->get($templateid);
        if ((string)$template->category !== CommerceMailLibrary::CATEGORY_MARKETING) {
            throw new \invalid_parameter_exception('A marketing campaign requires a Marketing Mail Studio template.');
        }

        $name = trim(clean_param((string)($data['name'] ?? ''), PARAM_TEXT));
        if ($name === '') {
            throw new \invalid_parameter_exception('Campaign name is required.');
        }

        $deliverylanguage = strtolower(trim((string)($data['deliverylanguage'] ?? 'auto')));
        if (!in_array($deliverylanguage, ['auto', 'fr', 'ru', 'en'], true)) {
            throw new \invalid_parameter_exception('Unsupported campaign delivery language.');
        }

        $source = (string)($data['audiencesource'] ?? 'manual');
        if (!in_array($source, ['manual', 'course'], true)) {
            throw new \invalid_parameter_exception('Invalid marketing audience source.');
        }
        // An existing course-sourced draft must not be converted into a manual list
        // to bypass the server-side scheduling lock.
        if ($campaignid !== null) {
            $existing = $this->repository->get($campaignid);
            if ((string)($existing->audiencesource ?? 'manual') !== $source) {
                throw new \invalid_parameter_exception(
                    'Audience source cannot be changed on a saved campaign. Create a new draft.'
                );
            }
        }
        $audienceconfig = null;
        if ($source === 'course') {
            $courseid = (int)($data['audiencecourseid'] ?? 0);
            $supportskus = CommerceMarketingCampaignAudienceResolver::parse_skus(
                (string)($data['audienceskus'] ?? '')
            );
            $excludedids = CommerceMarketingCampaignAudienceResolver::parse_excluded_ids(
                (string)($data['audienceexcludedids'] ?? '')
            );
            $filters = CommerceMarketingCourseAudienceService::normalise_filters(
                (array)($data['audiencefilters'] ?? [])
            );
            $preview = CommerceMarketingCampaignAudienceResolver::create($this->db)
                ->preview_course($courseid, $supportskus, $excludedids, $filters);
            $recipients = $preview['recipients'];
            $audienceconfig = json_encode([
                'courseid' => $courseid,
                'audiencepolicy' => 'course_access_v2',
                'deliverylanguage' => $deliverylanguage,
                'supportskus' => $supportskus,
                'excludedids' => $excludedids,
                'filters' => $filters,
                'checkedat' => time(),
                'eligible' => count($recipients),
            ], JSON_THROW_ON_ERROR);
        } else {
            $recipients = $this->preview_manual((string)($data['audience'] ?? ''))['recipients'];
        }
        if ($recipients === []) {
            throw new \invalid_parameter_exception('At least one valid recipient is required.');
        }

        $translations = [];
        foreach ($this->library->contents($templateid) as $language => $content) {
            $document = json_decode((string)$content->contentjson, true) ?: [];
            $subject = trim((string)$content->subject);
            $bodyhtml = trim((string)($document['bodyhtml'] ?? ''));
            if ($subject === '' || CommerceMailBuilder::editorial_empty($bodyhtml)) {
                continue;
            }
            $translations[$language] = [
                'subject' => $subject,
                'preheader' => (string)$content->preheader,
                'bodyhtml' => $bodyhtml,
            ];
        }
        if ($translations === []) {
            throw new \invalid_parameter_exception('Selected template has no usable translation.');
        }
        // Explicit operator choice for THIS campaign, independent of Moodle UI language.
        // Refuse any override unless the selected template really has that translation.
        if ($deliverylanguage !== 'auto') {
            if (!isset($translations[$deliverylanguage])) {
                throw new \moodle_exception('commerce_campaign_e11_override_unavailable',
                    'local_subscriptions', '', strtoupper($deliverylanguage));
            }
            foreach ($recipients as &$recipient) {
                $recipient['language'] = $deliverylanguage;
            }
            unset($recipient);
        }
        if ($source !== 'course') {
            $audienceconfig = json_encode(['deliverylanguage' => $deliverylanguage], JSON_THROW_ON_ERROR);
        }

        $record = (object)[
            'name' => $name,
            'status' => 'draft',
            'templateid' => $templateid,
            'audiencesource' => $source,
            'audienceconfig' => $audienceconfig,
            'ctaurl' => trim((string)($data['ctaurl'] ?? '')) ?: null,
            'scheduledat' => null,
            'queuedat' => null,
            'completedat' => null,
            'timemodified' => $now,
            'usermodified' => $userid,
        ];

        if ($campaignid === null) {
            $record->timecreated = $now;
            $campaignid = $this->repository->insert_campaign($record);
        } else {
            $existing = $this->repository->get($campaignid);
            if ((string)$existing->status !== 'draft') {
                throw new \coding_exception('Only draft marketing campaigns can be edited.');
            }
            $record->id = $campaignid;
            $this->repository->update_campaign($record);
        }

        $this->repository->replace_contents($campaignid, $translations);
        $this->repository->replace_recipients($campaignid, $recipients, $now);
        return $campaignid;
    }

    public function schedule(int $campaignid, int $scheduledat, int $userid, bool $legalreviewed = false): void {
        $campaign = $this->repository->get($campaignid);
        if ((string)$campaign->status !== 'draft') {
            throw new \coding_exception('Only draft marketing campaigns can be scheduled.');
        }
        $coursecampaign = (string)($campaign->audiencesource ?? 'manual') === 'course';
        if ($coursecampaign) {
            if (!CommerceMarketingCourseCampaignGate::enabled()) {
                throw new \invalid_parameter_exception(get_string(
                    'commerce_campaign_e8_disabled', 'local_subscriptions'));
            }
            if (!$legalreviewed) {
                throw new \invalid_parameter_exception(get_string(
                    'commerce_campaign_e8_legal_required', 'local_subscriptions'));
            }
            if (!CommerceMarketingCourseCampaignGate::snapshot_is_current(
                    $this->db, $this->repository, $campaign)) {
                throw new \invalid_parameter_exception(get_string(
                    'commerce_campaign_e8_stale_audience', 'local_subscriptions'));
            }
        }
        if ($this->repository->recipient_count($campaignid) <= 0) {
            throw new \coding_exception('Marketing campaign has no recipients.');
        }
        $contents = $this->repository->contents($campaignid);
        if ($contents === []) {
            throw new \coding_exception('Marketing campaign has no frozen content.');
        }
        $missing = CommerceMarketingCampaignContentPolicy::missing_languages(
            $contents, $this->repository->recipients($campaignid)
        );
        if ($missing !== []) {
            throw new \moodle_exception('commerce_marketing_campaign_languages_missing',
                'local_subscriptions', '', CommerceMarketingCampaignContentPolicy::format_missing($missing));
        }

        // Record the operator's explicit review, not a machine-issued consent claim.
        // Keep the audit data with the frozen audience (no DB schema migration).
        $record = (object)[
            'id' => $campaignid,
            'status' => 'scheduled',
            'scheduledat' => max(time(), $scheduledat),
            'timemodified' => time(),
            'usermodified' => $userid,
        ];
        if ($coursecampaign) {
            $config = json_decode((string)$campaign->audienceconfig, true);
            $config['marketingreview'] = [
                'by' => $userid,
                'at' => time(),
                'confirmed' => true,
            ];
            $record->audienceconfig = json_encode($config, JSON_THROW_ON_ERROR);
        }
        $this->repository->update_campaign($record);
    }

    public function pause(int $campaignid): void {
        $campaign = $this->repository->get($campaignid);
        if (!in_array((string)$campaign->status, ['scheduled', 'queued'], true)) {
            throw new \coding_exception('Only scheduled or queued campaigns can be paused.');
        }
        $this->repository->mark_paused($campaignid, time());
    }

    public function resume(int $campaignid): void {
        $campaign = $this->repository->get($campaignid);
        if ((string)$campaign->status !== 'paused') {
            throw new \coding_exception('Only paused campaigns can be resumed.');
        }
        $this->repository->mark_resumed($campaignid, !empty($campaign->queuedat),
            (int)($campaign->scheduledat ?? 0), time());
    }

    public function cancel(int $campaignid): void {
        $campaign = $this->repository->get($campaignid);
        if (!in_array((string)$campaign->status, ['draft', 'scheduled', 'queued', 'paused'], true)) {
            throw new \coding_exception('Campaign can no longer be cancelled.');
        }
        // Change the campaign state FIRST, so the worker stops choosing queued records.
        // Already-processing SMTP sends cannot be recalled.
        $now = time();
        $this->repository->mark_cancelled($campaignid, $now);
        $this->repository->cancel_pending($campaignid, $now);
    }

    /** Sends exactly one test to the logged-in operator (never to the campaign audience). */
    public function send_test(int $campaignid, int $userid, string $language): void {
        $campaign = $this->repository->get($campaignid);
        if ((string)$campaign->status !== 'draft') {
            throw new \coding_exception('Test messages are available only for saved draft campaigns.');
        }
        $operator = $this->db->get_record('user', [
            'id' => $userid, 'deleted' => 0, 'suspended' => 0,
        ], '*', MUST_EXIST);
        (new CommerceMarketingCampaignTestMailer($this->db, $this->repository))
            ->send($campaignid, $operator, $language);
    }

    /**
     * Preview manually entered recipients with the same marketing opt-outs used at save time.
     * @return array{summary:array<string,int>,rows:array<int,array<string,mixed>>,recipients:array<int,array<string,mixed>>}
     */
    public function preview_manual(string $raw): array {
        $parsed = $this->parse_recipients($raw);
        $optouts = CommerceMarketingOptoutService::create($this->db);
        $rows = [];
        $recipients = [];
        $summary = ['candidates' => count($parsed), 'eligible' => 0, 'excluded' => 0];
        foreach ($parsed as $recipient) {
            $suppressed = $optouts->is_suppressed((string)$recipient['email']);
            $row = $recipient;
            $row['eligible'] = !$suppressed;
            $row['reasons'] = $suppressed ? ['marketing_optout'] : [];
            $rows[] = $row;
            $summary[$suppressed ? 'excluded' : 'eligible']++;
            if ($suppressed) {
                $summary['reason_marketing_optout'] = ($summary['reason_marketing_optout'] ?? 0) + 1;
            } else {
                $recipients[] = $recipient;
            }
        }
        return ['summary' => $summary, 'rows' => $rows, 'recipients' => $recipients];
    }

    /** @return array<int,array{email:string,firstname:string,lastname:string,language:string,userid:?int}> */
    private function parse_recipients(string $raw): array {
        $result = [];
        foreach (preg_split('/\R+/', trim($raw)) ?: [] as $line) {
            $parts = array_map('trim', preg_split('/[;,]/', $line) ?: []);
            $email = strtolower((string)($parts[0] ?? ''));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            $user = $this->db->get_record('user', ['email' => $email, 'deleted' => 0], '*', IGNORE_MULTIPLE);
            $language = strtolower((string)($parts[3] ?? ($user->lang ?? 'fr')));
            if (!in_array($language, CommerceMailLibrary::LANGUAGES, true)) {
                $language = 'fr';
            }
            $key = $email;
            $result[$key] = [
                'email' => $email,
                'firstname' => (string)($parts[1] ?? ($user->firstname ?? '')),
                'lastname' => (string)($parts[2] ?? ($user->lastname ?? '')),
                'language' => $language,
                'userid' => $user ? (int)$user->id : null,
            ];
        }
        return array_values($result);
    }
}
