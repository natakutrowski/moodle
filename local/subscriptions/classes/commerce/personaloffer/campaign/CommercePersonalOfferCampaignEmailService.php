<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\personaloffer\campaign;

defined('MOODLE_INTERNAL') || die();

/**
 * Domain service for campaign-specific Personal Offer email configuration.
 *
 * M3A deliberately does not render emails. It owns persistence, safe editorial
 * input and the language fallback contract consumed by the later renderer.
 */
final class CommercePersonalOfferCampaignEmailService {
    public const DESTINATION_CHECKOUT = 'checkout';
    public const DESTINATION_SHOWROOM = 'showroom';
    public const SUPPORTED_LANGUAGES = ['fr', 'en', 'ru'];

    private const CAMPAIGN = 'local_subs_commerce_offer_campaign';
    private const SHOWROOM = 'local_subs_showroom';

    public function __construct(
        private readonly \moodle_database $db,
        private readonly CommercePersonalOfferCampaignEmailRepository $repository
    ) {}

    public static function create(?\moodle_database $db = null): self {
        global $DB;
        $db ??= $DB;
        return new self($db, new CommercePersonalOfferCampaignEmailRepository($db));
    }

    /**
     * Returns the complete persisted configuration without inventing defaults.
     *
     * A null config and empty translations are the compatibility signal used by
     * M3B/M3D to fall back to the historical Personal Offer email template.
     *
     * @return array{config:?\stdClass,translations:array<string,\stdClass>}
     */
    public function get(int $campaignid): array {
        $this->require_campaign($campaignid);
        return [
            'config' => $this->repository->get_config($campaignid),
            'translations' => $this->repository->get_contents($campaignid),
        ];
    }

    public function has_custom_email(int $campaignid): bool {
        $this->require_campaign($campaignid);
        return $this->repository->get_config($campaignid) !== null
            || $this->repository->get_contents($campaignid) !== [];
    }

    /** @return array{destination:string,showroomid:?int} */
    public function resolve_destination(int $campaignid): array {
        $this->require_campaign($campaignid);
        $config = $this->repository->get_config($campaignid);
        if ($config === null) {
            return [
                'destination' => self::DESTINATION_CHECKOUT,
                'showroomid' => null,
            ];
        }

        return [
            'destination' => (string)$config->ctadestination,
            'showroomid' => $config->showroomid === null ? null : (int)$config->showroomid,
        ];
    }

    /**
     * Resolve editorial content using Commerce's FR fallback policy.
     *
     * Returns null when neither the requested language nor FR is configured;
     * callers must then use the historical Personal Offer template.
     */
    public function resolve_content(int $campaignid, string $language): ?\stdClass {
        $this->require_campaign($campaignid);
        $language = $this->normalise_language($language);

        $content = $this->repository->get_content($campaignid, $language);
        if ($content !== null || $language === 'fr') {
            return $content;
        }

        return $this->repository->get_content($campaignid, 'fr');
    }

    public function set_library_template_source(
        int $campaignid,
        ?int $librarytemplateid,
        int $userid
    ): void {
        $this->require_editable_campaign($campaignid);
        if ($librarytemplateid !== null
                && !$this->db->record_exists('local_subs_mail_library', ['id' => $librarytemplateid])) {
            throw new \coding_exception('Unknown Mail Studio template.');
        }
        $this->repository->set_library_template_source(
            $campaignid,
            $librarytemplateid,
            $userid
        );
    }

    public function library_template_source_id(int $campaignid): int {
        $config = $this->repository->get_config($campaignid);
        return $config && !empty($config->librarytemplateid)
            ? (int)$config->librarytemplateid
            : 0;
    }

    public function save_destination(
        int $campaignid,
        string $destination,
        ?int $showroomid,
        int $userid
    ): void {
        $campaign = $this->require_editable_campaign($campaignid);
        $destination = strtolower(trim($destination));

        if (!in_array($destination, [self::DESTINATION_CHECKOUT, self::DESTINATION_SHOWROOM], true)) {
            throw new \coding_exception('Invalid Personal Offer campaign email CTA destination.');
        }

        if ($destination === self::DESTINATION_CHECKOUT) {
            $showroomid = null;
        } else {
            if (($showroomid ?? 0) <= 0) {
                throw new \coding_exception('A showroom is required for a showroom CTA destination.');
            }
            if (!$this->db->record_exists(self::SHOWROOM, ['id' => $showroomid])) {
                throw new \coding_exception('Unknown showroom for Personal Offer campaign email.');
            }
        }

        $this->repository->upsert_config(
            (int)$campaign->id,
            $destination,
            $showroomid,
            $userid
        );
    }

    public function save_content(
        int $campaignid,
        string $language,
        string $subject,
        string $body,
        int $bodyformat,
        string $ctalabel,
        ?string $secondaryctalabel,
        ?string $secondaryctaurl,
        ?string $closing,
        int $closingformat,
        int $userid
    ): void {
        $this->require_editable_campaign($campaignid);
        $language = $this->normalise_language($language);

        if (!in_array($bodyformat, [(int) FORMAT_PLAIN, (int) FORMAT_HTML], true)) {
            throw new \coding_exception('Unsupported Personal Offer campaign email body format.');
        }
        if (!in_array($closingformat, [(int) FORMAT_PLAIN, (int) FORMAT_HTML], true)) {
            throw new \coding_exception('Unsupported Personal Offer campaign email closing format.');
        }

        $subject = $this->clean_single_line($subject, 255, 'subject');
        $ctalabel = $this->clean_single_line($ctalabel, 255, 'CTA label');
        $secondaryctalabel = trim(clean_param((string)$secondaryctalabel, PARAM_TEXT));
        $secondaryctaurl = trim((string)$secondaryctaurl);
        if (($secondaryctalabel === '') !== ($secondaryctaurl === '')) {
            throw new \coding_exception('Secondary CTA label and URL must be configured together.');
        }
        if ($secondaryctalabel !== '') {
            if (\core_text::strlen($secondaryctalabel) > 255) {
                throw new \coding_exception('Personal Offer campaign email secondary CTA label is too long.');
            }
            if (\core_text::strlen($secondaryctaurl) > 2048
                    || !filter_var($secondaryctaurl, FILTER_VALIDATE_URL)
                    || !in_array(strtolower((string)parse_url($secondaryctaurl, PHP_URL_SCHEME)), ['http', 'https'], true)) {
                throw new \coding_exception('Personal Offer campaign email secondary CTA URL is invalid.');
            }
        } else {
            $secondaryctalabel = null;
            $secondaryctaurl = null;
        }
        $body = trim($body);
        $closing = $closing === null ? null : trim($closing);

        if ($body === '') {
            throw new \coding_exception('Personal Offer campaign email body cannot be empty.');
        }

        // Defence in depth: HTML is cleaned before persistence and will still
        // be cleaned/formatted by the renderer in M3B. Plain text is stored as
        // text and escaped by the later FORMAT_PLAIN renderer.
        if ($bodyformat === (int) FORMAT_HTML) {
            $body = clean_text($body, FORMAT_HTML);
        }
        if ($closing !== null && $closing !== '' && $closingformat === (int) FORMAT_HTML) {
            $closing = clean_text($closing, FORMAT_HTML);
        }
        if ($closing === '') {
            $closing = null;
        }

        $this->repository->upsert_content(
            $campaignid,
            $language,
            $subject,
            $body,
            $bodyformat,
            $ctalabel,
            $secondaryctalabel,
            $secondaryctaurl,
            $closing,
            $closingformat,
            $userid
        );
    }

    public function delete_content(int $campaignid, string $language): void {
        $this->require_editable_campaign($campaignid);
        $this->repository->delete_content($campaignid, $this->normalise_language($language));
    }

    private function require_campaign(int $campaignid): \stdClass {
        return $this->db->get_record(self::CAMPAIGN, ['id' => $campaignid], '*', MUST_EXIST);
    }

    private function require_editable_campaign(int $campaignid): \stdClass {
        $campaign = $this->require_campaign($campaignid);
        if (in_array(
            (string)$campaign->status,
            [CommercePersonalOfferCampaignManager::STATUS_ISSUED, CommercePersonalOfferCampaignManager::STATUS_CLOSED],
            true
        )) {
            throw new \coding_exception('Issued or closed campaign email configuration cannot be edited.');
        }
        return $campaign;
    }

    private function normalise_language(string $language): string {
        $language = strtolower(trim($language));
        if (str_contains($language, '_')) {
            $language = explode('_', $language, 2)[0];
        } else if (str_contains($language, '-')) {
            $language = explode('-', $language, 2)[0];
        }

        if (!in_array($language, self::SUPPORTED_LANGUAGES, true)) {
            throw new \coding_exception('Unsupported Personal Offer campaign email language.');
        }
        return $language;
    }

    private function clean_single_line(string $value, int $maxlength, string $fieldname): string {
        $value = trim(clean_param($value, PARAM_TEXT));
        if ($value === '') {
            throw new \coding_exception('Personal Offer campaign email ' . $fieldname . ' cannot be empty.');
        }
        if (\core_text::strlen($value) > $maxlength) {
            throw new \coding_exception('Personal Offer campaign email ' . $fieldname . ' is too long.');
        }
        return $value;
    }
}
