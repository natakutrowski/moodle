<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\campaign;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\mail\builder\CommerceMailBuilder;
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

        $recipients = $this->parse_recipients((string)($data['audience'] ?? ''));
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

        $record = (object)[
            'name' => $name,
            'status' => 'draft',
            'templateid' => $templateid,
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

    public function schedule(int $campaignid, int $scheduledat, int $userid): void {
        $campaign = $this->repository->get($campaignid);
        if ((string)$campaign->status !== 'draft') {
            throw new \coding_exception('Only draft marketing campaigns can be scheduled.');
        }
        if ($this->repository->recipient_count($campaignid) <= 0) {
            throw new \coding_exception('Marketing campaign has no recipients.');
        }
        if ($this->repository->contents($campaignid) === []) {
            throw new \coding_exception('Marketing campaign has no frozen content.');
        }

        $this->repository->update_campaign((object)[
            'id' => $campaignid,
            'status' => 'scheduled',
            'scheduledat' => max(time(), $scheduledat),
            'timemodified' => time(),
            'usermodified' => $userid,
        ]);
    }

    public function cancel(int $campaignid): void {
        $campaign = $this->repository->get($campaignid);
        if (!in_array((string)$campaign->status, ['draft', 'scheduled'], true)) {
            throw new \coding_exception('Campaign can no longer be cancelled.');
        }
        $this->repository->mark_cancelled($campaignid, time());
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
