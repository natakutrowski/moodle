<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\library;

defined('MOODLE_INTERNAL') || die();

use moodle_database;
use stdClass;

final class CommerceMailLibraryRepository {
    private const TABLE = 'local_subs_mail_library';
    private const CONTENT_TABLE = 'local_subs_mail_lib_content';

    public function __construct(private readonly moodle_database $db) {}

    /** @return stdClass[] */
    public function all(string $category = '', string $status = ''): array {
        $conditions = [];
        if (in_array($category, CommerceMailLibrary::categories(), true)) {
            $conditions['category'] = $category;
        }
        if (in_array($status, CommerceMailLibrary::statuses(), true)) {
            $conditions['status'] = $status;
        }
        return array_values($this->db->get_records(self::TABLE, $conditions, 'timemodified DESC, name ASC'));
    }

    public function get(int $id): stdClass {
        return $this->db->get_record(self::TABLE, ['id' => $id], '*', MUST_EXIST);
    }

    public function get_by_runtime_key(string $runtimekey): ?stdClass {
        $runtimekey = trim($runtimekey);
        if ($runtimekey === '') {
            return null;
        }
        $record = $this->db->get_record(
            self::TABLE,
            ['runtimekey' => $runtimekey],
            '*',
            IGNORE_MISSING
        );
        return $record ?: null;
    }

    /** @return array<string,stdClass> */
    public function contents(int $templateid): array {
        $rows = $this->db->get_records(self::CONTENT_TABLE, ['templateid' => $templateid], 'language ASC');
        $result = [];
        foreach ($rows as $row) {
            $result[(string)$row->language] = $row;
        }
        return $result;
    }

    /** @param array<string,mixed> $template @param array<string,array<string,mixed>> $translations */
    public function save(array $template, array $translations, int $userid, ?int $id = null): stdClass {
        $category = (string)($template['category'] ?? CommerceMailLibrary::CATEGORY_MARKETING);
        $status = (string)($template['status'] ?? CommerceMailLibrary::STATUS_DRAFT);
        if (!in_array($category, CommerceMailLibrary::categories(), true)
                || !in_array($status, CommerceMailLibrary::statuses(), true)) {
            throw new \invalid_parameter_exception('Invalid Mail Studio template category/status.');
        }

        $now = time();
        $record = $id ? $this->get($id) : null;
        $payload = (object)[
            'templateuuid' => $record ? (string)$record->templateuuid : CommerceMailLibrary::uuid(),
            'name' => trim(clean_param((string)($template['name'] ?? ''), PARAM_TEXT)),
            'category' => $category,
            'status' => $status,
            'builderversion' => CommerceMailLibrary::BUILDER_VERSION,
            'sourcekind' => CommerceMailLibrary::SOURCE_NATIVE,
            'runtimekey' => array_key_exists('runtimekey', $template)
                ? (trim((string)$template['runtimekey']) ?: null)
                : ($record->runtimekey ?? null),
            'metadatajson' => json_encode($template['metadata'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'timemodified' => $now,
            'usermodified' => $userid,
        ];
        if ($payload->name === '') {
            throw new \invalid_parameter_exception('Mail Studio template name is required.');
        }

        $transaction = $this->db->start_delegated_transaction();
        if ($record) {
            $payload->id = (int)$record->id;
            $this->db->update_record(self::TABLE, $payload);
            $templateid = (int)$record->id;
        } else {
            $payload->timecreated = $now;
            $payload->usercreated = $userid;
            $templateid = (int)$this->db->insert_record(self::TABLE, $payload);
        }

        foreach (CommerceMailLibrary::LANGUAGES as $language) {
            $data = $translations[$language] ?? [];
            $subject = trim(clean_param((string)($data['subject'] ?? ''), PARAM_TEXT));
            $preheader = trim(clean_param((string)($data['preheader'] ?? ''), PARAM_TEXT));
            $bodyhtml = (string)($data['bodyhtml'] ?? '');
            $document = is_array($data['document'] ?? null)
                ? $data['document']
                : [
                    'mode' => 'mail_builder',
                    'builderversion' => CommerceMailLibrary::BUILDER_VERSION,
                    'bodyhtml' => $bodyhtml,
                    'blocks' => [],
                ];
            $document['mode'] = (string)($document['mode'] ?? 'mail_builder');
            $document['builderversion'] = CommerceMailLibrary::BUILDER_VERSION;
            $document['bodyhtml'] = (string)($document['bodyhtml'] ?? $bodyhtml);
            if ($subject === '' && trim(strip_tags((string)$document['bodyhtml'])) === '') {
                $this->db->delete_records(self::CONTENT_TABLE, ['templateid' => $templateid, 'language' => $language]);
                continue;
            }
            $existing = $this->db->get_record(self::CONTENT_TABLE, ['templateid' => $templateid, 'language' => $language]);
            $content = (object)[
                'templateid' => $templateid,
                'language' => $language,
                'subject' => $subject,
                'preheader' => $preheader,
                'contentjson' => json_encode(
                    $document,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ),
                'timemodified' => $now,
                'usermodified' => $userid,
            ];
            if ($existing) {
                $content->id = (int)$existing->id;
                $this->db->update_record(self::CONTENT_TABLE, $content);
            } else {
                $content->timecreated = $now;
                $content->usercreated = $userid;
                $this->db->insert_record(self::CONTENT_TABLE, $content);
            }
        }
        $transaction->allow_commit();
        return $this->get($templateid);
    }

    public function duplicate(int $id, int $userid): stdClass {
        $source = $this->get($id);
        $contents = $this->contents($id);
        $translations = [];
        foreach ($contents as $language => $content) {
            $json = json_decode((string)$content->contentjson, true) ?: [];
            $translations[$language] = [
                'subject' => (string)$content->subject,
                'preheader' => (string)$content->preheader,
                'bodyhtml' => (string)($json['bodyhtml'] ?? ''),
                'document' => $json,
            ];
        }
        return $this->save([
            'name' => (string)$source->name . ' — ' . get_string('commerce_mail_library_copy_suffix', 'local_subscriptions'),
            'category' => (string)$source->category,
            'status' => CommerceMailLibrary::STATUS_DRAFT,
            'metadata' => json_decode((string)$source->metadatajson, true) ?: [],
            'runtimekey' => null,
        ], $translations, $userid);
    }

    /**
     * Permanently delete an archived Mail Studio template.
     *
     * Marketing campaigns keep the source template as a mandatory historical
     * reference, so those templates cannot be deleted. Personal Offer campaigns
     * keep a nullable provenance pointer and already own a frozen content
     * snapshot, so their pointer can safely be cleared.
     */
    public function delete_archived(int $id): void {
        $record = $this->get($id);
        if ((string)$record->status !== CommerceMailLibrary::STATUS_ARCHIVED) {
            throw new \coding_exception(
                'Only archived Mail Studio templates can be permanently deleted.'
            );
        }

        $marketinguses = (int)$this->db->count_records(
            'local_subs_mail_campaign',
            ['templateid' => $id]
        );
        if ($marketinguses > 0) {
            throw new \moodle_exception(
                'commerce_mail_library_delete_in_use',
                'local_subscriptions',
                '',
                $marketinguses
            );
        }

        $contents = $this->contents($id);
        $transaction = $this->db->start_delegated_transaction();

        // Personal Offer campaigns contain a frozen editorial snapshot; only
        // their optional provenance pointer needs to be detached.
        $this->db->set_field(
            'local_subs_commerce_offer_campaign_email_config',
            'librarytemplateid',
            null,
            ['librarytemplateid' => $id]
        );

        $fs = get_file_storage();
        $contextid = \context_system::instance()->id;
        foreach ($contents as $content) {
            $fs->delete_area_files(
                $contextid,
                'local_subscriptions',
                CommerceMailLibraryHeaderImageService::FILEAREA,
                (int)$content->id
            );
        }

        $this->db->delete_records(self::CONTENT_TABLE, ['templateid' => $id]);
        $this->db->delete_records(self::TABLE, ['id' => $id]);

        $transaction->allow_commit();
    }


    public function archive(int $id, int $userid): void {
        $record = $this->get($id);
        $record->status = CommerceMailLibrary::STATUS_ARCHIVED;
        $record->timemodified = time();
        $record->usermodified = $userid;
        $this->db->update_record(self::TABLE, $record);
    }
}
