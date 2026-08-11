<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\storefront\content;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->dirroot . '/repository/lib.php');

/**
 * File API boundary for rich Storefront sections.
 *
 * Each section owns a stable random item ID stored in the Storefront JSON.
 * This keeps FR/EN/RU sections and their files physically isolated without
 * introducing a database table.
 */
final class CommerceStorefrontContentFileService {
    public const COMPONENT = 'local_subscriptions';
    public const FILEAREA = 'storefront_content';
    public const MAX_BYTES = 50 * 1024 * 1024;
    public const AREA_MAX_BYTES = 500 * 1024 * 1024;
    public const MAX_FILES = 50;

    public function __construct(
        private readonly \context_system $context
    ) {
    }

    public static function create(): self {
        return new self(\context_system::instance());
    }

    public function ensure_item_id(int $itemid = 0): int {
        if ($itemid > 0) {
            return $itemid;
        }

        $storage = get_file_storage();
        do {
            $candidate = random_int(100000000, 2000000000);
            $files = $storage->get_area_files(
                $this->context->id,
                self::COMPONENT,
                self::FILEAREA,
                $candidate,
                'id ASC',
                false
            );
        } while ($files !== []);

        return $candidate;
    }

    /**
     * Prepares one section for TinyMCE and returns draft-aware content.
     *
     * @return array{content:string,draftitemid:int,itemid:int}
     */
    public function prepare_editor(
        string $fieldname,
        string $content,
        int $itemid
    ): array {
        $itemid = $this->ensure_item_id($itemid);
        $draftitemid = \file_get_submitted_draft_itemid($fieldname);
        $content = \file_prepare_draft_area(
            $draftitemid,
            $this->context->id,
            self::COMPONENT,
            self::FILEAREA,
            $itemid,
            $this->file_options(),
            $content
        );

        return [
            'content' => (string)$content,
            'draftitemid' => (int)$draftitemid,
            'itemid' => $itemid,
        ];
    }

    public function save_editor(
        int $draftitemid,
        int $itemid,
        string $content
    ): string {
        $itemid = $this->ensure_item_id($itemid);

        // Dynamically inserted sections and untouched template sections may
        // legitimately be submitted without an initialised draft area. In
        // that case there is nothing to merge and the existing permanent
        // file area must remain untouched. Moodle's draft API expects a real
        // draft item ID and may otherwise inspect invalid source metadata.
        if ($draftitemid <= 0) {
            return $content;
        }

        $this->normalise_draft_sources($draftitemid);

        // Dedicated media slots share the same item ID as the TinyMCE area,
        // but they are not part of the editor content itself. Moodle's draft
        // synchronisation may remove files which are not represented in the
        // submitted draft area. Preserve those slots explicitly so a global
        // layout save can never detach an image, video, poster or H5P file.
        $backupitemid = $this->backup_dedicated_slots($itemid);

        try {
            return (string)\file_save_draft_area_files(
                $draftitemid,
                $this->context->id,
                self::COMPONENT,
                self::FILEAREA,
                $itemid,
                $this->file_options(),
                $content
            );
        } finally {
            $this->restore_dedicated_slots($itemid, $backupitemid);
        }
    }


    /**
     * Copies dedicated media slots to a temporary item before draft sync.
     *
     * @return int Temporary backup item ID, or 0 when no slots exist.
     */
    private function backup_dedicated_slots(int $itemid): int {
        $storage = get_file_storage();
        $files = [];

        foreach (['image', 'video', 'poster', 'h5p'] as $slot) {
            $file = $this->get_slot_file($itemid, $slot);
            if ($file instanceof \stored_file) {
                $files[] = $file;
            }
        }

        if ($files === []) {
            return 0;
        }

        $backupitemid = $this->ensure_item_id();
        foreach ($files as $file) {
            $storage->create_file_from_storedfile(
                [
                    'contextid' => $this->context->id,
                    'component' => self::COMPONENT,
                    'filearea' => self::FILEAREA,
                    'itemid' => $backupitemid,
                    'filepath' => $file->get_filepath(),
                    'filename' => $file->get_filename(),
                ],
                $file
            );
        }

        return $backupitemid;
    }

    /** Restores any dedicated slot removed by draft synchronisation. */
    private function restore_dedicated_slots(
        int $itemid,
        int $backupitemid
    ): void {
        if ($backupitemid <= 0) {
            return;
        }

        $storage = get_file_storage();
        $backupfiles = $storage->get_area_files(
            $this->context->id,
            self::COMPONENT,
            self::FILEAREA,
            $backupitemid,
            'id ASC',
            false
        );

        try {
            foreach ($backupfiles as $backupfile) {
                $slot = trim($backupfile->get_filepath(), '/');
                if (!in_array($slot, ['image', 'video', 'poster', 'h5p'], true)) {
                    continue;
                }
                if ($this->get_slot_file($itemid, $slot) instanceof \stored_file) {
                    continue;
                }

                $storage->create_file_from_storedfile(
                    [
                        'contextid' => $this->context->id,
                        'component' => self::COMPONENT,
                        'filearea' => self::FILEAREA,
                        'itemid' => $itemid,
                        'filepath' => $backupfile->get_filepath(),
                        'filename' => $backupfile->get_filename(),
                    ],
                    $backupfile
                );
            }
        } finally {
            $storage->delete_area_files(
                $this->context->id,
                self::COMPONENT,
                self::FILEAREA,
                $backupitemid
            );
        }
    }

    public function rewrite_for_display(
        string $content,
        int $itemid
    ): string {
        if ($itemid <= 0 || trim($content) === '') {
            return $content;
        }

        return \file_rewrite_pluginfile_urls(
            $content,
            'pluginfile.php',
            $this->context->id,
            self::COMPONENT,
            self::FILEAREA,
            $itemid
        );
    }


    public function store_uploaded_slot(
        int $itemid,
        string $slot,
        string $field,
        array $acceptedextensions
    ): ?\stored_file {
        if (
            !isset($_FILES[$field])
            || (int)($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE)
                === UPLOAD_ERR_NO_FILE
        ) {
            return null;
        }
        if (
            (int)($_FILES[$field]['error'] ?? UPLOAD_ERR_OK)
                !== UPLOAD_ERR_OK
            || empty($_FILES[$field]['tmp_name'])
            || !is_uploaded_file($_FILES[$field]['tmp_name'])
        ) {
            throw new \moodle_exception(
                'error_uploading_file',
                'moodle'
            );
        }

        $itemid = $this->ensure_item_id($itemid);
        $slot = $this->clean_slot($slot);
        $filename = clean_param(
            (string)($_FILES[$field]['name'] ?? ''),
            PARAM_FILE
        );
        $extension = strtolower(
            pathinfo($filename, PATHINFO_EXTENSION)
        );
        $acceptedextensions = array_map(
            static fn(string $value): string =>
                ltrim(strtolower($value), '.'),
            $acceptedextensions
        );

        if (
            $filename === ''
            || !in_array($extension, $acceptedextensions, true)
        ) {
            throw new \moodle_exception(
                'commerce_invalid_asset_type',
                'local_subscriptions'
            );
        }
        if (
            (int)($_FILES[$field]['size'] ?? 0)
                > self::MAX_BYTES
        ) {
            throw new \moodle_exception('maxbytes', 'error');
        }

        $this->delete_slot($itemid, $slot);
        return get_file_storage()->create_file_from_pathname(
            [
                'contextid' => $this->context->id,
                'component' => self::COMPONENT,
                'filearea' => self::FILEAREA,
                'itemid' => $itemid,
                'filepath' => '/' . $slot . '/',
                'filename' => $filename,
            ],
            (string)$_FILES[$field]['tmp_name']
        );
    }

    public function get_slot_file(
        int $itemid,
        string $slot
    ): ?\stored_file {
        if ($itemid <= 0) {
            return null;
        }
        $slotpath = '/' . $this->clean_slot($slot) . '/';
        $files = get_file_storage()->get_area_files(
            $this->context->id,
            self::COMPONENT,
            self::FILEAREA,
            $itemid,
            'id DESC',
            false
        );
        foreach ($files as $file) {
            if ($file->get_filepath() === $slotpath) {
                return $file;
            }
        }
        return null;
    }

    public function get_slot_url(
        int $itemid,
        string $slot
    ): ?\moodle_url {
        $file = $this->get_slot_file($itemid, $slot);
        if ($file === null) {
            return null;
        }
        return \moodle_url::make_pluginfile_url(
            $this->context->id,
            self::COMPONENT,
            self::FILEAREA,
            $itemid,
            $file->get_filepath(),
            $file->get_filename(),
            false
        );
    }

    /** @return array<string,mixed>|null */
    public function slot_diagnostic(
        int $itemid,
        string $slot
    ): ?array {
        $file = $this->get_slot_file($itemid, $slot);
        if ($file === null) {
            return null;
        }
        $dimensions = @getimagesizefromstring(
            $file->get_content()
        );
        return [
            'filename' => $file->get_filename(),
            'filesize' => $file->get_filesize(),
            'width' => is_array($dimensions)
                ? (int)($dimensions[0] ?? 0)
                : 0,
            'height' => is_array($dimensions)
                ? (int)($dimensions[1] ?? 0)
                : 0,
            'mimetype' => $file->get_mimetype(),
            'url' => (string)$this->get_slot_url($itemid, $slot),
        ];
    }

    private function delete_slot(
        int $itemid,
        string $slot
    ): void {
        $slotpath = '/' . $this->clean_slot($slot) . '/';
        $files = get_file_storage()->get_area_files(
            $this->context->id,
            self::COMPONENT,
            self::FILEAREA,
            $itemid,
            'id ASC',
            false
        );
        foreach ($files as $file) {
            if ($file->get_filepath() === $slotpath) {
                $file->delete();
            }
        }
    }

    private function clean_slot(string $slot): string {
        $slot = strtolower(trim($slot));
        if (preg_match('/^[a-z0-9_-]{1,40}$/', $slot) !== 1) {
            throw new \coding_exception(
                'Invalid Storefront media slot: ' . $slot
            );
        }
        return $slot;
    }

    /** @return array<string,mixed> */
    public function editor_options(): array {
        return [
            'context' => $this->context,
            'maxfiles' => self::MAX_FILES,
            'maxbytes' => self::MAX_BYTES,
            'trusttext' => false,
            'noclean' => false,
            'subdirs' => true,
            'return_types' =>
                FILE_INTERNAL
                | FILE_EXTERNAL
                | FILE_REFERENCE
                | FILE_CONTROLLED_LINK,
            'enable_filemanagement' => true,
        ];
    }

    /** @return array<string,\stdClass> */
    public function filepicker_options(int $draftitemid): array {
        $returntypes =
            FILE_INTERNAL
            | FILE_EXTERNAL
            | FILE_REFERENCE
            | FILE_CONTROLLED_LINK;

        $options = [
            'image' => $this->initialise_picker(
                $draftitemid,
                ['web_image'],
                $returntypes
            ),
            'media' => $this->initialise_picker(
                $draftitemid,
                ['video', 'audio'],
                $returntypes
            ),
            'link' => $this->initialise_picker(
                $draftitemid,
                '*',
                $returntypes
            ),
            'subtitle' => $this->initialise_picker(
                $draftitemid,
                ['.vtt'],
                $returntypes
            ),
        ];

        if (
            has_capability(
                'moodle/h5p:deploy',
                $this->context
            )
        ) {
            $options['h5p'] = $this->initialise_picker(
                $draftitemid,
                ['.h5p'],
                $returntypes
            );
        }

        return $options;
    }

    /**
     * Creates the repository object expected by TinyMCE.
     *
     * This deliberately mirrors MoodleQuickForm_editor::toHtml().
     *
     * @param string|string[] $acceptedtypes
     */
    private function initialise_picker(
        int $draftitemid,
        string|array $acceptedtypes,
        int $returntypes
    ): \stdClass {
        $arguments = new \stdClass();
        $arguments->accepted_types = $acceptedtypes;
        $arguments->return_types = $returntypes;
        $arguments->context = $this->context;
        $arguments->env = 'filepicker';

        $picker = \initialise_filepicker($arguments);
        $picker->context = $this->context;

        // Moodle core uses uniqid() without entropy. Do not introduce a dot
        // because the client ID is also consumed as a DOM identifier.
        $picker->client_id = uniqid();

        $picker->maxbytes = self::MAX_BYTES;
        $picker->areamaxbytes = self::AREA_MAX_BYTES;
        $picker->env = 'editor';
        $picker->itemid = $draftitemid;

        return $picker;
    }

    /**
     * Repairs legacy or repository draft metadata before Moodle merges files.
     *
     * Draft files are expected to store a serialised object in the source
     * field. Some dynamically initialised pickers may leave a plain source
     * string instead, which makes Moodle's unserialize_object() emit an error
     * during file_save_draft_area_files(). Only the current user's draft area
     * is inspected; permanent Storefront files are never modified here.
     */
    private function normalise_draft_sources(int $draftitemid): void {
        global $USER;

        if ($draftitemid <= 0 || empty($USER->id)) {
            return;
        }

        $usercontext = \context_user::instance((int)$USER->id);
        $files = get_file_storage()->get_area_files(
            $usercontext->id,
            'user',
            'draft',
            $draftitemid,
            'id ASC',
            false
        );

        foreach ($files as $file) {
            $source = $file->get_source();
            if ($source === null || $source === '') {
                continue;
            }

            $decoded = @unserialize(
                $source,
                ['allowed_classes' => [\stdClass::class]]
            );
            if ($decoded instanceof \stdClass) {
                continue;
            }

            $metadata = new \stdClass();
            $metadata->source = $source;
            $file->set_source(serialize($metadata));
        }
    }

    public function context(): \context_system {
        return $this->context;
    }

    public function delete_item_area(int $itemid): void {
        if ($itemid <= 0) {
            return;
        }

        get_file_storage()->delete_area_files(
            $this->context->id,
            self::COMPONENT,
            self::FILEAREA,
            $itemid
        );
    }

    /** @return array<string,mixed> */
    private function file_options(): array {
        return [
            'subdirs' => true,
            'maxfiles' => self::MAX_FILES,
            'maxbytes' => self::MAX_BYTES,
            'areamaxbytes' => self::AREA_MAX_BYTES,
            'accepted_types' => [
                'image',
                'video',
                'audio',
                '.pdf',
                '.h5p',
            ],
            'context' => $this->context,
        ];
    }
}
