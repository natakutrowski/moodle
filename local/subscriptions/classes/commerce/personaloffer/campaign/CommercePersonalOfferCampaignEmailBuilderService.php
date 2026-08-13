<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\personaloffer\campaign;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\personaloffer\mail\CommercePersonalOfferCampaignMailVariableResolver;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomStatus;

/** CRM-facing orchestration for campaign email configuration. */
final class CommercePersonalOfferCampaignEmailBuilderService {
    public function __construct(
        private readonly \moodle_database $db,
        private readonly CommercePersonalOfferCampaignEmailService $emailservice
    ) {}

    public static function create(?\moodle_database $db = null): self {
        global $DB;
        $db ??= $DB;
        return new self($db, CommercePersonalOfferCampaignEmailService::create($db));
    }

    /** @return array<string,mixed> */
    public function state(int $campaignid): array {
        $campaign = $this->db->get_record('local_subs_commerce_offer_campaign', ['id' => $campaignid], '*', MUST_EXIST);
        $stored = $this->emailservice->get($campaignid);
        return [
            'campaign' => $campaign,
            'config' => $stored['config'],
            'translations' => $stored['translations'],
            'showrooms' => $this->compatible_showrooms($campaignid),
            'bannerurl' => (new CommercePersonalOfferCampaignMailBannerService())->url($campaignid),
            'variables' => CommercePersonalOfferCampaignMailVariableResolver::AVAILABLE,
            'editable' => !in_array((string)$campaign->status, ['issued', 'closed'], true),
        ];
    }

    /**
     * @param array<string,array{subject:string,body:string,bodyformat:int,ctalabel:string,secondaryctalabel:string,secondaryctaurl:string,closing:string,closingformat:int}> $translations
     */
    public function save(
        int $campaignid,
        string $destination,
        ?int $showroomid,
        array $translations,
        int $userid,
        ?array $bannerupload = null,
        bool $deletebanner = false
    ): void {
        if ($destination === CommercePersonalOfferCampaignEmailService::DESTINATION_SHOWROOM) {
            $this->assert_compatible_showroom($campaignid, (int)$showroomid);
        }

        $transaction = $this->db->start_delegated_transaction();
        try {
            $this->emailservice->save_destination($campaignid, $destination, $showroomid, $userid);

            foreach (CommercePersonalOfferCampaignEmailService::SUPPORTED_LANGUAGES as $language) {
                $data = $translations[$language] ?? ['subject' => '', 'body' => '', 'bodyformat' => (int)FORMAT_HTML, 'ctalabel' => '', 'secondaryctalabel' => '', 'secondaryctaurl' => '', 'closing' => '', 'closingformat' => (int)FORMAT_HTML];
                $subject = trim((string)$data['subject']);
                $body = trim((string)$data['body']);
                $bodyformat = (int)($data['bodyformat'] ?? FORMAT_HTML);
                $ctalabel = trim((string)$data['ctalabel']);
                $secondaryctalabel = trim((string)($data['secondaryctalabel'] ?? ''));
                $secondaryctaurl = trim((string)($data['secondaryctaurl'] ?? ''));
                $closing = trim((string)$data['closing']);
                $closingformat = (int)($data['closingformat'] ?? FORMAT_HTML);
                $bodyempty = $this->editorial_empty($body, $bodyformat);
                $closingempty = $this->editorial_empty($closing, $closingformat);

                if ($subject === '' && $bodyempty && $ctalabel === '' && $secondaryctalabel === ''
                        && $secondaryctaurl === '' && $closingempty) {
                    $this->emailservice->delete_content($campaignid, $language);
                    continue;
                }
                if ($subject === '' || $bodyempty || $ctalabel === '') {
                    throw new \coding_exception('Campaign email subject, body and CTA label are required for language ' . strtoupper($language) . '.');
                }

                $this->emailservice->save_content(
                    $campaignid,
                    $language,
                    $subject,
                    $body,
                    $bodyformat,
                    $ctalabel,
                    $secondaryctalabel !== '' ? $secondaryctalabel : null,
                    $secondaryctaurl !== '' ? $secondaryctaurl : null,
                    !$closingempty ? $closing : null,
                    $closingformat,
                    $userid
                );
            }

            $banners = new CommercePersonalOfferCampaignMailBannerService();
            if ($deletebanner) {
                $banners->delete($campaignid);
            }
            if ($bannerupload !== null && (int)($bannerupload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $banners->save_uploaded_file($campaignid, $bannerupload);
            }

            $transaction->allow_commit();
        } catch (\Throwable $e) {
            $transaction->rollback($e);
        }
    }


    private function editorial_empty(string $value, int $format): bool {
        if (trim($value) === '') {
            return true;
        }
        if ($format !== (int)FORMAT_HTML) {
            return false;
        }
        $text = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\xc2\xa0", ' ', $text);
        return trim($text) === '';
    }

    /** @return array<int,string> */
    public function compatible_showrooms(int $campaignid): array {
        $campaign = $this->db->get_record('local_subs_commerce_offer_campaign', ['id' => $campaignid], 'id,targetproductid', MUST_EXIST);
        $sku = strtoupper(trim((string)$this->db->get_field('local_subs_commerce_product', 'sku', ['id' => (int)$campaign->targetproductid], MUST_EXIST)));
        $showrooms = $this->db->get_records('local_subs_showroom', ['status' => CommerceShowroomStatus::PUBLISHED], 'name ASC');
        $result = [];
        foreach ($showrooms as $showroom) {
            if (!$this->has_enabled_block((int)$showroom->id)) {
                continue;
            }
            $products = json_decode((string)$showroom->productsjson, true);
            if (!is_array($products)) {
                continue;
            }
            $skus = array_map(static fn($value): string => strtoupper(trim((string)$value)), array_values($products));
            if (!in_array($sku, $skus, true)) {
                continue;
            }
            $slug = trim((string)($showroom->slugfr ?? ''));
            $result[(int)$showroom->id] = (string)$showroom->name . ($slug !== '' ? ' · /' . ltrim($slug, '/') : '');
        }
        return $result;
    }

    public function assert_compatible_showroom(int $campaignid, int $showroomid): void {
        if ($showroomid <= 0 || !array_key_exists($showroomid, $this->compatible_showrooms($campaignid))) {
            throw new \coding_exception('Selected showroom is not published and compatible with the campaign target product.');
        }
    }

    private function has_enabled_block(int $showroomid): bool {
        return $this->db->record_exists('local_subs_showroom_block', ['showroomid' => $showroomid, 'enabled' => 1]);
    }
}
