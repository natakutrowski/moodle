<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom\cms;

defined('MOODLE_INTERNAL') || die();

/**
 * Portable DEV -> PROD showroom package: JSON definition + Moodle File API media.
 */
final class CommerceShowroomPortablePackageService {
    public const MANIFEST = 'showroom.json';
    public const MAX_ARCHIVE_FILES = 1000;
    public const MAX_ARCHIVE_BYTES = 8 * 1024 * 1024 * 1024; // 8 GiB.
    public const MAX_SINGLE_FILE_BYTES = 2 * 1024 * 1024 * 1024; // 2 GiB.

    /**
     * Temporary disk headroom multiplier.
     *
     * Export needs both the source copies and the final ZIP on temporary disk.
     * 2.25 gives a safety margin for filesystem metadata and the manifest.
     */
    private const EXPORT_DISK_HEADROOM = 2.25;

    /** @var string[] */
    private const ALLOWED_EXTENSIONS = [
        'png', 'jpg', 'jpeg', 'webp', 'mp4', 'webm',
    ];

    /** @var string[] */
    private const ALLOWED_MIMETYPES = [
        'image/png',
        'image/jpeg',
        'image/webp',
        'video/mp4',
        'video/webm',
    ];

    public function __construct(
        private readonly \moodle_database $db,
        private readonly CommerceShowroomCmsRepository $repository,
        private readonly \context_system $context
    ) {
    }

    /**
     * Builds a temporary portable ZIP.
     *
     * Caller owns the returned pathname and must delete it after download.
     *
     * @return array{pathname:string,filename:string,mediacount:int,bytes:int}
     */
    public function export_zip(int $showroomid): array {
        if (!class_exists(\ZipArchive::class)) {
            throw new \moodle_exception('zipnotavailable', 'error');
        }

        $jsonservice = new CommerceShowroomPackageService($this->repository);
        $package = $jsonservice->export($showroomid);
        $showroomkey = clean_filename(
            (string)($package['showroom']['showroomkey'] ?? 'showroom')
        );

        $blockrecords = [];
        foreach ($this->repository->blocks($showroomid) as $block) {
            $blockrecords[(string)$block->blockkey] = $block;
        }

        $manifestmedia = [];
        $totalbytes = 0;
        $mediacount = 0;

        $storage = get_file_storage();

        foreach ($blockrecords as $blockkey => $block) {
            $files = $storage->get_area_files(
                $this->context->id,
                CommerceShowroomBlockMediaManager::COMPONENT,
                CommerceShowroomBlockMediaManager::FILEAREA,
                (int)$block->id,
                'id ASC',
                false
            );

            foreach ($files as $file) {
                $field = trim($file->get_filepath(), '/');
                if ($field === '') {
                    continue;
                }

                $extension = strtolower(
                    pathinfo($file->get_filename(), PATHINFO_EXTENSION)
                );
                if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
                    throw new \invalid_parameter_exception(
                        'Unsupported Showroom media extension in export.'
                    );
                }

                $size = (int)$file->get_filesize();
                if (
                    $size <= 0
                    || $size > self::MAX_SINGLE_FILE_BYTES
                    || $totalbytes + $size > self::MAX_ARCHIVE_BYTES
                ) {
                    throw new \invalid_parameter_exception(
                        'Showroom media package exceeds the portable archive limits.'
                    );
                }

                $safeBlock = clean_param($blockkey, PARAM_ALPHANUMEXT);
                $safeField = clean_param($field, PARAM_ALPHANUMEXT);
                $safeName = clean_filename($file->get_filename());
                $archivepath = sprintf(
                    'media/%s/%s/%s',
                    $safeBlock,
                    $safeField,
                    $safeName
                );

                $sourceurl = \moodle_url::make_pluginfile_url(
                    $this->context->id,
                    CommerceShowroomBlockMediaManager::COMPONENT,
                    CommerceShowroomBlockMediaManager::FILEAREA,
                    (int)$block->id,
                    $file->get_filepath(),
                    $file->get_filename(),
                    false
                )->out(false);

                $manifestmedia[] = [
                    'blockkey' => $blockkey,
                    'field' => $safeField,
                    'filename' => $safeName,
                    'archivepath' => $archivepath,
                    'mimetype' => (string)$file->get_mimetype(),
                    'filesize' => $size,
                    'contenthash' => (string)$file->get_contenthash(),
                    'sourceurl' => $sourceurl,
                    'sourcefileid' => (int)$file->get_id(),
                ];
                $totalbytes += $size;
                $mediacount++;
            }
        }

        if ($mediacount > self::MAX_ARCHIVE_FILES) {
            throw new \invalid_parameter_exception(
                'Showroom package contains too many media files.'
            );
        }

        $package['media'] = [
            'included' => true,
            'count' => $mediacount,
            'bytes' => $totalbytes,
            'files' => $manifestmedia,
        ];

        $temproot = make_request_directory(false, true);

        $requiredbytes = (int)ceil(
            max(1, $totalbytes) * self::EXPORT_DISK_HEADROOM
        );
        $this->require_free_disk_space($temproot, $requiredbytes);

        $zippath = $temproot . DIRECTORY_SEPARATOR
            . $showroomkey . '.showroom.zip';

        $zip = new \ZipArchive();
        $opened = $zip->open(
            $zippath,
            \ZipArchive::CREATE | \ZipArchive::OVERWRITE
        );
        if ($opened !== true) {
            throw new \moodle_exception('cannotcreatezipfile', 'error');
        }

        $tempfiles = [];

        try {
            $manifestjson = json_encode(
                $package,
                JSON_PRETTY_PRINT
                    | JSON_UNESCAPED_UNICODE
                    | JSON_UNESCAPED_SLASHES
                    | JSON_THROW_ON_ERROR
            );

            if (!$zip->addFromString(self::MANIFEST, $manifestjson)) {
                throw new \moodle_exception('cannotcreatezipfile', 'error');
            }

            foreach ($manifestmedia as $index => $entry) {
                $block = $blockrecords[$entry['blockkey']] ?? null;
                if ($block === null) {
                    throw new \invalid_parameter_exception(
                        'Showroom media block disappeared during export.'
                    );
                }

                $stored = $storage->get_file_by_id(
                    (int)$entry['sourcefileid']
                );
                if ($stored === false || $stored === null) {
                    throw new \invalid_parameter_exception(
                        'Showroom media changed during export.'
                    );
                }

                $tmpfile = $temproot
                    . DIRECTORY_SEPARATOR
                    . sprintf(
                        'showroom-export-%04d-%s.bin',
                        $index,
                        sha1($entry['archivepath'])
                    );

                $stored->copy_content_to($tmpfile);
                $tempfiles[] = $tmpfile;

                $copiedsize = filesize($tmpfile);
                if (
                    $copiedsize === false
                    || $copiedsize !== (int)$entry['filesize']
                ) {
                    throw new \invalid_parameter_exception(
                        'Showroom media temporary copy size mismatch.'
                    );
                }

                if (!$zip->addFile($tmpfile, $entry['archivepath'])) {
                    throw new \moodle_exception('cannotcreatezipfile', 'error');
                }

                // PNG/JPEG/WebP/MP4/WebM are already compressed formats.
                // STORE avoids burning CPU and time trying to recompress them.
                if (
                    method_exists($zip, 'setCompressionName')
                    && !$zip->setCompressionName(
                        $entry['archivepath'],
                        \ZipArchive::CM_STORE
                    )
                ) {
                    throw new \moodle_exception('cannotcreatezipfile', 'error');
                }
            }

            if (!$zip->close()) {
                throw new \moodle_exception('cannotcreatezipfile', 'error');
            }
            $zip = null;

            $validation = $this->validate_exported_archive(
                $zippath,
                $mediacount,
                $totalbytes
            );

            return [
                'pathname' => $zippath,
                'filename' => $showroomkey . '.showroom.zip',
                'mediacount' => $mediacount,
                'bytes' => $totalbytes,
                'archivesize' => $validation['archivesize'],
                'largestfile' => $this->largest_media_size($manifestmedia),
                'requiredtempbytes' => $requiredbytes,
            ];
        } catch (\Throwable $exception) {
            if ($zip instanceof \ZipArchive) {
                $zip->close();
            }
            @unlink($zippath);
            throw $exception;
        } finally {
            foreach ($tempfiles as $tmpfile) {
                @unlink($tmpfile);
            }
        }
    }

    /**
     * @return array{
     *     mediacount:int,
     *     bytes:int,
     *     largestfile:int,
     *     requiredfreetempbytes:int,
     *     freetempbytes:int
     * }
     */
    public function preflight_export(int $showroomid): array {
        $largest = 0;
        $total = 0;
        $count = 0;

        foreach ($this->repository->blocks($showroomid) as $block) {
            foreach (
                get_file_storage()->get_area_files(
                    $this->context->id,
                    CommerceShowroomBlockMediaManager::COMPONENT,
                    CommerceShowroomBlockMediaManager::FILEAREA,
                    (int)$block->id,
                    'id ASC',
                    false
                ) as $file
            ) {
                $size = (int)$file->get_filesize();
                $total += $size;
                $largest = max($largest, $size);
                $count++;
            }
        }

        $required = (int)ceil(
            max(1, $total) * self::EXPORT_DISK_HEADROOM
        );
        $tempdir = make_request_directory(false, true);
        $free = disk_free_space($tempdir);

        return [
            'mediacount' => $count,
            'bytes' => $total,
            'largestfile' => $largest,
            'requiredfreetempbytes' => $required,
            'freetempbytes' => $free === false ? 0 : (int)$free,
        ];
    }

    private function require_free_disk_space(
        string $directory,
        int $requiredbytes
    ): void {
        $free = disk_free_space($directory);
        if ($free !== false && $free < $requiredbytes) {
            throw new \moodle_exception(
                'commerce_showroom_export_insufficient_disk',
                'local_subscriptions',
                '',
                (object)[
                    'required' => display_size($requiredbytes),
                    'available' => display_size((int)$free),
                ]
            );
        }
    }

    /**
     * @return array{archivesize:int}
     */
    private function validate_exported_archive(
        string $pathname,
        int $expectedmedia,
        int $expectedmediabytes
    ): array {
        clearstatcache(true, $pathname);

        if (!is_file($pathname) || !is_readable($pathname)) {
            throw new \invalid_parameter_exception(
                'Portable Showroom ZIP was not created.'
            );
        }

        $archivesize = filesize($pathname);
        if ($archivesize === false || $archivesize < 100) {
            throw new \invalid_parameter_exception(
                'Portable Showroom ZIP is unexpectedly small or incomplete.'
            );
        }

        $zip = new \ZipArchive();
        if ($zip->open($pathname, \ZipArchive::RDONLY) !== true) {
            throw new \invalid_parameter_exception(
                'Portable Showroom ZIP failed post-export validation.'
            );
        }

        try {
            $manifestjson = $zip->getFromName(self::MANIFEST);
            if (!is_string($manifestjson) || $manifestjson === '') {
                throw new \invalid_parameter_exception(
                    'Portable Showroom ZIP has no manifest.'
                );
            }

            $manifest = json_decode(
                $manifestjson,
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            if (
                (int)($manifest['media']['count'] ?? -1) !== $expectedmedia
                || (int)($manifest['media']['bytes'] ?? -1)
                    !== $expectedmediabytes
            ) {
                throw new \invalid_parameter_exception(
                    'Portable Showroom ZIP manifest mismatch.'
                );
            }

            $expectedfiles = 1 + $expectedmedia;
            if ($zip->numFiles !== $expectedfiles) {
                throw new \invalid_parameter_exception(
                    'Portable Showroom ZIP file count mismatch.'
                );
            }
        } finally {
            $zip->close();
        }

        return ['archivesize' => (int)$archivesize];
    }

    /**
     * @param array<int,array<string,mixed>> $media
     */
    private function largest_media_size(array $media): int {
        $largest = 0;
        foreach ($media as $entry) {
            $largest = max(
                $largest,
                (int)($entry['filesize'] ?? 0)
            );
        }
        return $largest;
    }

    /**
     * @return array{
     *     showroomid:int,
     *     blockcount:int,
     *     mediacount:int,
     *     remappedcount:int,
     *     unresolvedcount:int
     * }
     */
    public function import_zip(string $pathname, int $userid): array {
        if (!class_exists(\ZipArchive::class)) {
            throw new \moodle_exception('zipnotavailable', 'error');
        }
        if (!is_file($pathname) || !is_readable($pathname)) {
            throw new \invalid_parameter_exception(
                'Invalid portable Showroom archive.'
            );
        }

        $zip = new \ZipArchive();
        if ($zip->open($pathname, \ZipArchive::RDONLY) !== true) {
            throw new \invalid_parameter_exception(
                'Invalid portable Showroom ZIP archive.'
            );
        }

        try {
            $this->validate_archive_entries($zip);

            $manifestjson = $zip->getFromName(self::MANIFEST);
            if (
                !is_string($manifestjson)
                || $manifestjson === ''
                || strlen($manifestjson)
                    > CommerceShowroomPackageService::MAX_IMPORT_BYTES
            ) {
                throw new \invalid_parameter_exception(
                    'Missing or invalid Showroom manifest.'
                );
            }

            $package = json_decode(
                $manifestjson,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
            if (
                !is_array($package)
                || empty($package['media']['included'])
                || !isset($package['media']['files'])
                || !is_array($package['media']['files'])
            ) {
                throw new \invalid_parameter_exception(
                    'The Showroom ZIP does not contain a portable media manifest.'
                );
            }

            $media = $this->validate_media_manifest(
                $zip,
                $package['media']['files']
            );

            if (
                (int)($package['media']['count'] ?? -1) !== count($media)
                || (int)($package['media']['bytes'] ?? -1)
                    !== array_sum(array_column($media, 'filesize'))
            ) {
                throw new \invalid_parameter_exception(
                    'Portable Showroom media manifest totals do not match.'
                );
            }

            // Stage and validate every binary before creating any DB row.
            $temproot = make_request_directory(false, true);
            $staged = [];
            foreach ($media as $index => $entry) {
                $tmpfile = $temproot . DIRECTORY_SEPARATOR
                    . 'showroom-import-stage-' . $index . '.bin';
                $this->copy_zip_entry_to_path(
                    $pathname,
                    (string)$entry['archivepath'],
                    $tmpfile
                );
                $this->validate_extracted_media($tmpfile, $entry);
                $staged[$index] = $tmpfile;
            }

            $transaction = $this->db->start_delegated_transaction();
            $createdblockids = [];

            try {
                $jsonservice = new CommerceShowroomPackageService(
                    $this->repository
                );
                $result = $jsonservice->import_package($package, $userid);
                $createdblockids = array_values($result['blockmap']);

                $mapping = [];
                $temproot = make_request_directory(false, true);
                $storage = get_file_storage();

                foreach ($media as $index => $entry) {
                    $sourceblockkey = (string)$entry['blockkey'];
                    $targetblockid = $result['blockmap'][$sourceblockkey]
                        ?? null;
                    if ($targetblockid === null) {
                        throw new \invalid_parameter_exception(
                            'Portable media references an unknown source block.'
                        );
                    }

                    $tmpfile = $staged[$index];

                    $target = $storage->create_file_from_pathname([
                        'contextid' => $this->context->id,
                        'component' => CommerceShowroomBlockMediaManager::COMPONENT,
                        'filearea' => CommerceShowroomBlockMediaManager::FILEAREA,
                        'itemid' => (int)$targetblockid,
                        'filepath' => '/' . $entry['field'] . '/',
                        'filename' => $entry['filename'],
                    ], $tmpfile);

                    $newurl = \moodle_url::make_pluginfile_url(
                        $this->context->id,
                        CommerceShowroomBlockMediaManager::COMPONENT,
                        CommerceShowroomBlockMediaManager::FILEAREA,
                        (int)$targetblockid,
                        $target->get_filepath(),
                        $target->get_filename(),
                        false
                    )->out(false);

                    $mapping[(string)$entry['sourceurl']] = $newurl;
                }

                $remappedcount = 0;
                $unresolvedcount = 0;
                foreach ($package['blocks'] as $sourceblock) {
                    $sourcekey = (string)($sourceblock['blockkey'] ?? '');
                    $targetid = $result['blockmap'][$sourcekey] ?? null;
                    if ($targetid === null) {
                        continue;
                    }

                    $targetrecord = $this->repository->get_block(
                        (int)$targetid
                    );
                    if ($targetrecord === null) {
                        throw new \invalid_parameter_exception(
                            'Imported Showroom block disappeared.'
                        );
                    }

                    $config = json_decode(
                        (string)$targetrecord->configjson,
                        true,
                        512,
                        JSON_THROW_ON_ERROR
                    );
                    $config = is_array($config) ? $config : [];

                    [$config, $blockremapped] = $this->remap_config(
                        $config,
                        $mapping
                    );
                    $remappedcount += $blockremapped;
                    $unresolvedcount += $this->count_source_media_urls(
                        $config,
                        array_keys($mapping)
                    );

                    $this->repository->save_block(
                        (int)$result['showroomid'],
                        [
                            'id' => (int)$targetrecord->id,
                            'blocktype' => (string)$targetrecord->blocktype,
                            'blockkey' => (string)$targetrecord->blockkey,
                            'sortorder' => (int)$targetrecord->sortorder,
                            'enabled' => (int)$targetrecord->enabled === 1,
                            'configjson' => json_encode(
                                $config,
                                JSON_UNESCAPED_UNICODE
                                    | JSON_UNESCAPED_SLASHES
                                    | JSON_THROW_ON_ERROR
                            ),
                        ],
                        $userid
                    );
                }

                if ($unresolvedcount > 0) {
                    throw new \invalid_parameter_exception(
                        'Portable import left unresolved source media URLs.'
                    );
                }

                $transaction->allow_commit();

                return [
                    'showroomid' => (int)$result['showroomid'],
                    'blockcount' => (int)$result['blockcount'],
                    'mediacount' => count($media),
                    'remappedcount' => $remappedcount,
                    'unresolvedcount' => $unresolvedcount,
                ];
            } catch (\Throwable $exception) {
                foreach ($createdblockids as $blockid) {
                    get_file_storage()->delete_area_files(
                        $this->context->id,
                        CommerceShowroomBlockMediaManager::COMPONENT,
                        CommerceShowroomBlockMediaManager::FILEAREA,
                        (int)$blockid
                    );
                }

                $transaction->rollback($exception);
            }
        } finally {
            if (isset($staged) && is_array($staged)) {
                foreach ($staged as $tmpfile) {
                    @unlink((string)$tmpfile);
                }
            }
            $zip->close();
        }

        throw new \coding_exception('Unreachable portable import state.');
    }

    private function validate_archive_entries(\ZipArchive $zip): void {
        if ($zip->numFiles <= 0 || $zip->numFiles > self::MAX_ARCHIVE_FILES + 1) {
            throw new \invalid_parameter_exception(
                'Portable Showroom archive contains too many files.'
            );
        }

        $total = 0;
        $names = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if (!is_array($stat)) {
                throw new \invalid_parameter_exception(
                    'Invalid ZIP entry.'
                );
            }

            $name = (string)($stat['name'] ?? '');
            if (isset($names[$name])) {
                throw new \invalid_parameter_exception(
                    'Duplicate path in portable Showroom archive.'
                );
            }
            $names[$name] = true;

            if (
                $name === ''
                || str_contains($name, "\0")
                || str_starts_with($name, '/')
                || preg_match('#(^|/)\.\.(/|$)#', $name)
                || str_contains($name, '\\')
            ) {
                throw new \invalid_parameter_exception(
                    'Unsafe path in portable Showroom archive.'
                );
            }

            $size = (int)($stat['size'] ?? 0);
            $compressed = max(1, (int)($stat['comp_size'] ?? $size));
            if (
                $size > 64 * 1024 * 1024
                && ($size / $compressed) > 200
            ) {
                throw new \invalid_parameter_exception(
                    'Suspicious compression ratio in portable Showroom archive.'
                );
            }
            if ($size < 0 || $size > self::MAX_SINGLE_FILE_BYTES) {
                throw new \invalid_parameter_exception(
                    'Portable Showroom archive entry is too large.'
                );
            }

            $total += $size;
            if ($total > self::MAX_ARCHIVE_BYTES) {
                throw new \invalid_parameter_exception(
                    'Portable Showroom archive is too large.'
                );
            }
        }
    }

    /**
     * @param array<int,mixed> $entries
     * @return array<int,array<string,mixed>>
     */
    private function validate_media_manifest(
        \ZipArchive $zip,
        array $entries
    ): array {
        if (count($entries) > self::MAX_ARCHIVE_FILES) {
            throw new \invalid_parameter_exception(
                'Portable Showroom manifest contains too many media files.'
            );
        }

        $validated = [];
        $paths = [];
        $sources = [];

        foreach ($entries as $entry) {
            if (!is_array($entry)) {
                throw new \invalid_parameter_exception(
                    'Invalid portable media manifest entry.'
                );
            }

            $blockkey = clean_param(
                (string)($entry['blockkey'] ?? ''),
                PARAM_ALPHANUMEXT
            );
            $field = clean_param(
                (string)($entry['field'] ?? ''),
                PARAM_ALPHANUMEXT
            );
            $filename = clean_filename(
                (string)($entry['filename'] ?? '')
            );
            $archivepath = (string)($entry['archivepath'] ?? '');
            $sourceurl = trim((string)($entry['sourceurl'] ?? ''));
            $extension = strtolower(
                pathinfo($filename, PATHINFO_EXTENSION)
            );

            if (
                isset($paths[$archivepath])
                || isset($sources[$sourceurl])
            ) {
                throw new \invalid_parameter_exception(
                    'Duplicate portable media manifest entry.'
                );
            }
            $paths[$archivepath] = true;
            $sources[$sourceurl] = true;

            if (
                $blockkey === ''
                || $field === ''
                || $filename === ''
                || $sourceurl === ''
                || !in_array($extension, self::ALLOWED_EXTENSIONS, true)
                || !str_starts_with(
                    $archivepath,
                    'media/' . $blockkey . '/' . $field . '/'
                )
                || $zip->locateName($archivepath) === false
            ) {
                throw new \invalid_parameter_exception(
                    'Invalid portable media manifest entry.'
                );
            }

            $declaredsize = (int)($entry['filesize'] ?? 0);
            if ($declaredsize <= 0) {
                throw new \invalid_parameter_exception(
                    'Portable media manifest has an invalid size.'
                );
            }

            $validated[] = [
                'blockkey' => $blockkey,
                'field' => $field,
                'filename' => $filename,
                'archivepath' => $archivepath,
                'sourceurl' => $sourceurl,
                'filesize' => $declaredsize,
                'contenthash' => (string)($entry['contenthash'] ?? ''),
                'mimetype' => (string)($entry['mimetype'] ?? ''),
            ];
        }

        $expectedpaths = array_fill_keys(array_keys($paths), true);
        $expectedpaths[self::MANIFEST] = true;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $name = is_array($stat)
                ? (string)($stat['name'] ?? '')
                : '';
            if (!isset($expectedpaths[$name])) {
                throw new \invalid_parameter_exception(
                    'Portable Showroom ZIP contains an undeclared file.'
                );
            }
        }

        return $validated;
    }

    /**
     * @param array<string,mixed> $entry
     */
    private function validate_extracted_media(
        string $pathname,
        array $entry
    ): void {
        $size = filesize($pathname);
        if (
            $size === false
            || $size <= 0
            || $size > self::MAX_SINGLE_FILE_BYTES
            || (
                (int)$entry['filesize'] > 0
                && $size !== (int)$entry['filesize']
            )
        ) {
            throw new \invalid_parameter_exception(
                'Portable media size mismatch.'
            );
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimetype = (string)$finfo->file($pathname);
        if (!in_array($mimetype, self::ALLOWED_MIMETYPES, true)) {
            throw new \invalid_parameter_exception(
                'Unsupported portable Showroom media MIME type.'
            );
        }

        if (
            $entry['mimetype'] !== ''
            && $mimetype !== $entry['mimetype']
        ) {
            throw new \invalid_parameter_exception(
                'Portable media MIME type mismatch.'
            );
        }

        if (
            $entry['contenthash'] !== ''
            && sha1_file($pathname) !== $entry['contenthash']
        ) {
            throw new \invalid_parameter_exception(
                'Portable media content hash mismatch.'
            );
        }
    }

    private function copy_zip_entry_to_path(
        string $zippath,
        string $entry,
        string $target
    ): void {
        $source = fopen('zip://' . $zippath . '#' . $entry, 'rb');
        if ($source === false) {
            throw new \invalid_parameter_exception(
                'Unable to read portable media entry.'
            );
        }
        $destination = fopen($target, 'wb');
        if ($destination === false) {
            fclose($source);
            throw new \moodle_exception('cannotwritetempfile', 'error');
        }

        try {
            if (stream_copy_to_stream($source, $destination) === false) {
                throw new \invalid_parameter_exception(
                    'Unable to extract portable media entry.'
                );
            }
        } finally {
            fclose($source);
            fclose($destination);
        }
    }

    /**
     * @param array<string,mixed> $config
     * @param array<string,string> $mapping
     * @return array{0:array<string,mixed>,1:int}
     */
    private function remap_config(array $config, array $mapping): array {
        $count = 0;

        array_walk_recursive(
            $config,
            static function (mixed &$value) use ($mapping, &$count): void {
                if (!is_string($value)) {
                    return;
                }

                foreach ($mapping as $oldurl => $newurl) {
                    if ($value === $oldurl) {
                        $value = $newurl;
                        $count++;
                        return;
                    }
                }
            }
        );

        return [$config, $count];
    }

    /**
     * @param array<string,mixed> $config
     * @param string[] $sourceurls
     */
    private function count_source_media_urls(
        array $config,
        array $sourceurls
    ): int {
        $count = 0;
        array_walk_recursive(
            $config,
            static function (mixed $value) use ($sourceurls, &$count): void {
                if (is_string($value) && in_array($value, $sourceurls, true)) {
                    $count++;
                }
            }
        );
        return $count;
    }
}
