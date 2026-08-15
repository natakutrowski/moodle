<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\personaloffer\campaign;

defined('MOODLE_INTERNAL') || die();

/**
 * Persistence for per-campaign Personal Offer email configuration.
 *
 * The technical CTA destination is stored once per campaign while editorial
 * content is stored per language. Missing records intentionally mean
 * "use the historical Personal Offer email template".
 */
final class CommercePersonalOfferCampaignEmailRepository {
    private const CONFIG = 'local_subs_commerce_offer_campaign_email_config';
    private const CONTENT = 'local_subs_commerce_offer_campaign_email_content';

    public function __construct(private readonly \moodle_database $db) {}

    public static function create(?\moodle_database $db = null): self {
        global $DB;
        return new self($db ?? $DB);
    }

    public function get_config(int $campaignid): ?\stdClass {
        $record = $this->db->get_record(self::CONFIG, ['campaignid' => $campaignid]);
        return $record ?: null;
    }

    /** @return array<string,\stdClass> keyed by language */
    public function get_contents(int $campaignid): array {
        $records = $this->db->get_records(self::CONTENT, ['campaignid' => $campaignid], 'language ASC');
        $result = [];
        foreach ($records as $record) {
            $result[(string)$record->language] = $record;
        }
        return $result;
    }

    public function get_content(int $campaignid, string $language): ?\stdClass {
        $record = $this->db->get_record(self::CONTENT, [
            'campaignid' => $campaignid,
            'language' => $language,
        ]);
        return $record ?: null;
    }

    public function upsert_config(
        int $campaignid,
        string $ctadestination,
        ?int $showroomid,
        int $userid,
        ?int $librarytemplateid = null
    ): void {
        $existing = $this->get_config($campaignid);
        $now = time();

        $record = (object)[
            'campaignid' => $campaignid,
            'ctadestination' => $ctadestination,
            'showroomid' => $showroomid,
            'librarytemplateid' => $librarytemplateid ?? ($existing->librarytemplateid ?? null),
            'timemodified' => $now,
            'usermodified' => $userid,
        ];

        if ($existing) {
            $record->id = (int)$existing->id;
            $this->db->update_record(self::CONFIG, $record);
            return;
        }

        $record->timecreated = $now;
        $record->usercreated = $userid;
        $this->db->insert_record(self::CONFIG, $record);
    }

    public function set_library_template_source(
        int $campaignid,
        ?int $librarytemplateid,
        int $userid
    ): void {
        $existing = $this->get_config($campaignid);
        if ($existing === null) {
            $this->upsert_config(
                $campaignid,
                CommercePersonalOfferCampaignEmailService::DESTINATION_CHECKOUT,
                null,
                $userid,
                $librarytemplateid
            );
            return;
        }

        $record = (object)[
            'id' => (int)$existing->id,
            'librarytemplateid' => $librarytemplateid,
            'timemodified' => time(),
            'usermodified' => $userid,
        ];
        $this->db->update_record(self::CONFIG, $record);
    }

    public function upsert_content(
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
        $existing = $this->get_content($campaignid, $language);
        $now = time();

        $record = (object)[
            'campaignid' => $campaignid,
            'language' => $language,
            'subject' => $subject,
            'body' => $body,
            'bodyformat' => $bodyformat,
            'ctalabel' => $ctalabel,
            'secondaryctalabel' => $secondaryctalabel,
            'secondaryctaurl' => $secondaryctaurl,
            'closing' => $closing,
            'closingformat' => $closingformat,
            'timemodified' => $now,
            'usermodified' => $userid,
        ];

        if ($existing) {
            $record->id = (int)$existing->id;
            $this->db->update_record(self::CONTENT, $record);
            return;
        }

        $record->timecreated = $now;
        $record->usercreated = $userid;
        $this->db->insert_record(self::CONTENT, $record);
    }

    public function delete_content(int $campaignid, string $language): void {
        $this->db->delete_records(self::CONTENT, [
            'campaignid' => $campaignid,
            'language' => $language,
        ]);
    }
}
