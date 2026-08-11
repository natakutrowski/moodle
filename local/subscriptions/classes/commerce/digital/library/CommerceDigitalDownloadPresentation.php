<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\digital\library;

defined('MOODLE_INTERNAL') || die();

/**
 * Immutable presentation of one downloadable file exposed by the digital library.
 */
final class CommerceDigitalDownloadPresentation {
    private const ALLOWED_VARIANTS = ['default', 'desktop', 'mobile'];

    public function __construct(
        public readonly string $label,
        public readonly string $url,
        public readonly string $variant = 'default',
        public readonly bool $available = true,
        public readonly ?string $filename = null,
        public readonly ?string $filetype = null,
        public readonly ?int $filesize = null,
        public readonly ?int $downloadcount = null,
        public readonly ?int $lastdownloadat = null,
        public readonly array $metadata = []
    ) {
        if (trim($label) === '') {
            throw new \coding_exception('A digital download label cannot be empty.');
        }
        if (trim($url) === '') {
            throw new \coding_exception('A digital download URL cannot be empty.');
        }
        if (!in_array($variant, self::ALLOWED_VARIANTS, true)) {
            throw new \coding_exception('Unsupported digital download variant: ' . $variant);
        }
        if ($filesize !== null && $filesize < 0) {
            throw new \coding_exception('A digital download file size cannot be negative.');
        }
        if ($downloadcount !== null && $downloadcount < 0) {
            throw new \coding_exception('A digital download count cannot be negative.');
        }
    }

    public function export(): array {
        $filename = trim((string)$this->filename);
        $filetype = strtoupper(trim((string)$this->filetype));

        $historyavailable = ($this->metadata['historyavailable'] ?? $this->downloadcount !== null) === true;
        $assetkey = trim((string)($this->metadata['assetkey'] ?? $this->variant));

        return [
            'label' => trim($this->label),
            'url' => $this->url,
            'variant' => $this->variant,
            'assetkey' => $assetkey,
            'isdesktop' => $this->variant === 'desktop',
            'ismobile' => $this->variant === 'mobile',
            'isdefault' => $this->variant === 'default',
            'available' => $this->available,
            'filename' => $filename,
            'hasfilename' => $filename !== '',
            'filetype' => $filetype,
            'hasfiletype' => $filetype !== '',
            'filesize' => $this->filesize,
            'filesizeformatted' => $this->filesize !== null ? display_size($this->filesize) : '',
            'hasfilesize' => $this->filesize !== null,
            'downloadcount' => $this->downloadcount,
            'historyavailable' => $historyavailable,
            'historyunavailable' => !$historyavailable,
            'hasdownloadhistory' => $historyavailable && $this->downloadcount !== null,
            'hasbeendownloaded' => $this->downloadcount !== null && $this->downloadcount > 0,
            'lastdownloadat' => $this->lastdownloadat,
            'lastdownloaddate' => $this->lastdownloadat !== null
                ? userdate($this->lastdownloadat, get_string('strftimedatetimeshort', 'langconfig'))
                : '',
            'haslastdownload' => $this->lastdownloadat !== null,
            'metadata' => $this->metadata,
        ];
    }
}
