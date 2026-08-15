<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\personaloffer\campaign;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\mail\builder\CommerceMailBuilder;
use local_subscriptions\commerce\mail\library\CommerceMailLibrary;
use local_subscriptions\commerce\mail\library\CommerceMailLibraryRepository;

/**
 * N5.3 compatibility bridge between Personal Offer campaigns and Mail Studio.
 *
 * Reusable templates are applied as a snapshot into the campaign's existing
 * frozen content storage. This means an issued/scheduled campaign cannot be
 * silently modified by a later edit of the reusable library template.
 */
final class CommercePersonalOfferMailStudioBridge {
    public function __construct(
        private readonly \moodle_database $db,
        private readonly CommerceMailLibraryRepository $library,
        private readonly CommercePersonalOfferCampaignEmailService $emailservice
    ) {}

    public static function create(?\moodle_database $db = null): self {
        global $DB;
        $db ??= $DB;
        return new self(
            $db,
            new CommerceMailLibraryRepository($db),
            CommercePersonalOfferCampaignEmailService::create($db)
        );
    }

    /** @return array<int,string> */
    public function template_options(): array {
        $result = [];
        foreach ($this->library->all(CommerceMailLibrary::CATEGORY_PERSONAL_OFFER) as $template) {
            if ((string)$template->status === CommerceMailLibrary::STATUS_ARCHIVED) {
                continue;
            }
            $label = (string)$template->name;
            if ((string)$template->status === CommerceMailLibrary::STATUS_DRAFT) {
                $label .= ' · ' . get_string(
                    'commerce_mail_library_status_draft',
                    'local_subscriptions'
                );
            }
            $result[(int)$template->id] = $label;
        }
        return $result;
    }

    public function apply_template(int $campaignid, int $templateid, int $userid): void {
        $this->assert_editable_campaign($campaignid);
        $template = $this->library->get($templateid);
        if ((string)$template->category !== CommerceMailLibrary::CATEGORY_PERSONAL_OFFER
                || (string)$template->status === CommerceMailLibrary::STATUS_ARCHIVED) {
            throw new \invalid_parameter_exception(
                'Only non-archived Personal Offer Mail Studio templates can be applied.'
            );
        }

        $contents = $this->library->contents($templateid);
        if ($contents === []) {
            throw new \coding_exception('Selected Mail Studio template has no translations.');
        }

        foreach (CommerceMailLibrary::LANGUAGES as $language) {
            $content = $contents[$language] ?? null;
            if ($content === null) {
                $this->emailservice->delete_content($campaignid, $language);
                continue;
            }

            $document = json_decode((string)$content->contentjson, true) ?: [];
            $bodyhtml = trim((string)($document['bodyhtml'] ?? ''));
            $subject = trim((string)$content->subject);
            if ($subject === '' || CommerceMailBuilder::editorial_empty($bodyhtml)) {
                $this->emailservice->delete_content($campaignid, $language);
                continue;
            }

            $ctalabel = $this->cta_label($bodyhtml, $language);
            $this->emailservice->save_content(
                $campaignid,
                $language,
                $subject,
                $bodyhtml,
                (int)FORMAT_HTML,
                $ctalabel,
                null,
                null,
                null,
                (int)FORMAT_HTML,
                $userid
            );
        }

        $this->emailservice->set_library_template_source(
            $campaignid,
            $templateid,
            $userid
        );
    }

    public function save_campaign_as_template(
        int $campaignid,
        string $name,
        int $userid
    ): \stdClass {
        $campaign = $this->db->get_record(
            'local_subs_commerce_offer_campaign',
            ['id' => $campaignid],
            'id,name,status',
            MUST_EXIST
        );
        $stored = $this->emailservice->get($campaignid);
        $translations = [];

        foreach (CommerceMailLibrary::LANGUAGES as $language) {
            $content = $stored['translations'][$language] ?? null;
            if ($content === null) {
                continue;
            }

            $bodyhtml = (string)$content->body;
            if (trim((string)($content->closing ?? '')) !== '') {
                $bodyhtml = rtrim($bodyhtml) . "\n\n" . (string)$content->closing;
            }
            $translations[$language] = [
                'subject' => (string)$content->subject,
                'preheader' => '',
                'bodyhtml' => $bodyhtml,
            ];
        }

        if ($translations === []) {
            throw new \coding_exception(
                'Campaign has no reusable editorial content to save as a template.'
            );
        }

        $name = trim(clean_param($name, PARAM_TEXT));
        if ($name === '') {
            $name = (string)$campaign->name;
        }

        return $this->library->save([
            'name' => $name,
            'category' => CommerceMailLibrary::CATEGORY_PERSONAL_OFFER,
            'status' => CommerceMailLibrary::STATUS_DRAFT,
            'metadata' => [
                'foundation' => 'N5.3',
                'editor' => 'mail_builder',
                'origin' => 'personal_offer_campaign',
                'origin_campaign_id' => $campaignid,
            ],
        ], $translations, $userid);
    }

    public function source_template_id(int $campaignid): int {
        return $this->emailservice->library_template_source_id($campaignid);
    }

    public function source_template(int $campaignid): ?\stdClass {
        $templateid = $this->source_template_id($campaignid);
        if ($templateid <= 0) {
            return null;
        }
        try {
            return $this->library->get($templateid);
        } catch (\dml_missing_record_exception) {
            return null;
        }
    }

    private function assert_editable_campaign(int $campaignid): void {
        $campaign = $this->db->get_record(
            'local_subs_commerce_offer_campaign',
            ['id' => $campaignid],
            'id,status',
            MUST_EXIST
        );
        if (in_array((string)$campaign->status, ['issued', 'closed'], true)) {
            throw new \coding_exception(
                'Issued or closed campaign email configuration cannot be edited.'
            );
        }
    }

    private function cta_label(string $bodyhtml, string $language): string {
        if (preg_match(
            '/\{\{cta(?:\|[a-z_]+)?\}\}(.*?)\{\{\/cta\}\}/is',
            $bodyhtml,
            $match
        ) === 1) {
            $label = trim(html_entity_decode(
                strip_tags((string)$match[1]),
                ENT_QUOTES | ENT_HTML5,
                'UTF-8'
            ));
            if ($label !== '') {
                return clean_param($label, PARAM_TEXT);
            }
        }

        return match ($language) {
            'en' => 'View my offer',
            'ru' => 'Посмотреть предложение',
            default => 'Voir mon offre',
        };
    }
}
